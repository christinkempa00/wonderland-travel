<?php
/**
 * ================================================================
 * ATTACHMENT CONTROLLER - COMPLETE VERSION
 * ================================================================
 * 
 * Controller khusus untuk role "attachment" (Staff Lampiran)
 * Semua link kembali ke /attachment-order/{id} bukan /orders/{id}
 * Tidak ada informasi harga yang ditampilkan
 */

class AttachmentController {
    
    /**
     * Dashboard untuk Staff Lampiran
     */
    public function dashboard(): void {
        $companyId = Session::companyId();
        
        if (!$companyId) {
            Session::flash('error', 'Silakan login terlebih dahulu.');
            redirect('/login');
            return;
        }
        
        $filter = $_GET['filter'] ?? 'all';
        $search = trim($_GET['search'] ?? '');
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 20;
        
        switch ($filter) {
            case 'hotel':
                $itemTypes = ['hotel'];
                $filterLabel = 'Hotel';
                break;
            case 'flight':
                $itemTypes = ['flight'];
                $filterLabel = 'Pesawat';
                break;
            case 'vehicle':
                $itemTypes = ['bus', 'towing'];
                $filterLabel = 'Kendaraan';
                break;
            case 'rental':
                $itemTypes = ['rental'];
                $filterLabel = 'Rental';
                break;
            default:
                $itemTypes = ['hotel', 'flight', 'bus', 'towing', 'rental'];
                $filterLabel = 'Semua';
        }
        
        $itemTypesStr = "'" . implode("','", $itemTypes) . "'";
        
        $where = "o.company_id = ? AND o.status NOT IN ('cancelled', 'draft')";
        $params = [$companyId];
        
        if (!empty($search)) {
            $where .= " AND (o.order_number LIKE ? OR o.event_name LIKE ? OR c.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $total = (int) db()->fetchColumn("
            SELECT COUNT(DISTINCT o.id) FROM orders o 
            LEFT JOIN clients c ON o.client_id = c.id
            INNER JOIN order_items oi ON o.id = oi.order_id AND oi.item_type IN ({$itemTypesStr})
            WHERE {$where}
        ", $params);
        
        $offset = ($page - 1) * $perPage;
        
        $orders = db()->fetchAll("
            SELECT o.id, o.order_number, o.event_name, o.event_date, o.event_end_date, o.status,
                   c.name as client_name, GROUP_CONCAT(DISTINCT oi.item_type) as item_types
            FROM orders o
            LEFT JOIN clients c ON o.client_id = c.id
            INNER JOIN order_items oi ON o.id = oi.order_id AND oi.item_type IN ({$itemTypesStr})
            WHERE {$where}
            GROUP BY o.id
            ORDER BY o.event_date DESC, o.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);
        
        foreach ($orders as &$order) {
            $order['attachment_status'] = $this->getAttachmentStatus($order['id']);
        }
        
        $data = [
            'pageTitle' => 'Dashboard Lampiran',
            'orders' => $orders,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage) ?: 1,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total)
            ],
            'filter' => $filter,
            'filterLabel' => $filterLabel,
            'search' => $search,
            'stats' => $this->getAttachmentStats($companyId)
        ];
        
        render('attachments/dashboard', $data);
    }
    
    /**
     * View order detail (tanpa harga)
     */
    public function viewOrder(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        $items = db()->fetchAll("
            SELECT id, item_type, description, quantity, num_days, hotel_name, room_type, 
                   check_in_date, check_out_date, vehicle_plate, attachment_logo
            FROM order_items WHERE order_id = ? ORDER BY id
        ", [$id]);
        
        $groupedItems = ['hotel' => [], 'flight' => [], 'vehicle' => [], 'rental' => []];
        foreach ($items as $item) {
            switch ($item['item_type']) {
                case 'hotel':
                    $groupedItems['hotel'][] = $item;
                    break;
                case 'flight':
                    $groupedItems['flight'][] = $item;
                    break;
                case 'bus':
                case 'towing':
                    $groupedItems['vehicle'][] = $item;
                    break;
                case 'rental':
                    $groupedItems['rental'][] = $item;
                    break;
            }
        }
        
        // Get count data for status display
        $hotelGuestCount = [];
        $flightPassengerCount = [];
        $vehicleDocCount = [];
        $rentalDetailCount = [];
        
        // Hotel guests count per item
        foreach ($groupedItems['hotel'] as $hotel) {
            try {
                $count = db()->fetchColumn(
                    "SELECT COUNT(*) FROM hotel_guests WHERE order_id = ? AND order_item_id = ?",
                    [$id, $hotel['id']]
                );
                $hotelGuestCount[$hotel['id']] = (int)$count;
            } catch (Exception $e) {
                $hotelGuestCount[$hotel['id']] = 0;
            }
        }
        
        // Flight passengers count per item
        foreach ($groupedItems['flight'] as $flight) {
            try {
                $count = db()->fetchColumn(
                    "SELECT COUNT(*) FROM flight_details WHERE order_id = ? AND order_item_id = ?",
                    [$id, $flight['id']]
                );
                $flightPassengerCount[$flight['id']] = (int)$count;
            } catch (Exception $e) {
                $flightPassengerCount[$flight['id']] = 0;
            }
        }
        
        // Vehicle documents count per item
        foreach ($groupedItems['vehicle'] as $vehicle) {
            try {
                $count = db()->fetchColumn(
                    "SELECT COUNT(*) FROM vehicle_documents WHERE order_id = ? AND order_item_id = ?",
                    [$id, $vehicle['id']]
                );
                $vehicleDocCount[$vehicle['id']] = (int)$count;
            } catch (Exception $e) {
                $vehicleDocCount[$vehicle['id']] = 0;
            }
        }
        
        // Rental details count per item
        foreach ($groupedItems['rental'] as $rental) {
            try {
                $count = db()->fetchColumn(
                    "SELECT COUNT(*) FROM rental_details WHERE order_id = ? AND order_item_id = ?",
                    [$id, $rental['id']]
                );
                $rentalDetailCount[$rental['id']] = (int)$count;
            } catch (Exception $e) {
                $rentalDetailCount[$rental['id']] = 0;
            }
        }
        
        $data = [
            'pageTitle' => 'Detail Pesanan - ' . $order['order_number'],
            'order' => $order,
            'items' => $items,
            'groupedItems' => $groupedItems,
            'attachmentStatus' => $this->getAttachmentStatus($id),
            'hotelGuestCount' => $hotelGuestCount,
            'flightPassengerCount' => $flightPassengerCount,
            'vehicleDocCount' => $vehicleDocCount,
            'rentalDetailCount' => $rentalDetailCount
        ];
        
        render('attachments/order-detail', $data);
    }
    
    // ========================================
    // HOTEL GUESTS
    // ========================================
    
    public function hotelGuests(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        $hotelItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'hotel' ORDER BY id", [$id]
        );
        
        if (empty($hotelItems)) {
            Session::flash('error', 'Pesanan ini tidak memiliki item hotel.');
            redirect('/attachment-order/' . $id);
            return;
        }
        
        $guestsByHotel = [];
        $existingGuests = [];
        foreach ($hotelItems as $hotel) {
            $guests = db()->fetchAll(
                "SELECT * FROM hotel_guests WHERE order_id = ? AND order_item_id = ? ORDER BY id",
                [$id, $hotel['id']]
            );
            $guestsByHotel[$hotel['id']] = $guests;
            $existingGuests = array_merge($existingGuests, $guests);
        }
        
        $data = [
            'pageTitle' => 'Data Tamu Hotel',
            'order' => $order,
            'hotelItems' => $hotelItems,
            'guestsByHotel' => $guestsByHotel,
            'existingGuests' => $existingGuests
        ];
        
        render('attachments/hotel-guests', $data);
    }
    
   public function saveHotelGuests(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        // Get hotel items WITH description (untuk hotel_name)
        $hotelItems = db()->fetchAll(
            "SELECT id, description FROM order_items WHERE order_id = ? AND item_type = 'hotel'", [$id]
        );
        
        // Form uses: rooms[hotelId][index][field]
        $allRooms = $_POST['rooms'] ?? [];
        
        foreach ($hotelItems as $hotel) {
            $hotelId = $hotel['id'];
            $hotelName = $hotel['description'] ?? '';
            $rooms = $allRooms[$hotelId] ?? [];
            
            // ============================================
            // FIXED: Delete hanya untuk hotel ini, bukan semua
            // ============================================
            db()->delete('hotel_guests', 'order_id = ? AND order_item_id = ?', [$id, $hotelId]);
            
            foreach ($rooms as $room) {
                $roomNumber = trim($room['room_number'] ?? '');
                $roomType = trim($room['room_type'] ?? '');
                $checkIn = $room['check_in_date'] ?? null;
                $checkOut = $room['check_out_date'] ?? null;
                
                foreach (['guest_1', 'guest_2'] as $guestKey) {
                    $guestName = trim($room[$guestKey] ?? '');
                    if (!empty($guestName)) {
                        db()->insert('hotel_guests', [
                            'order_id' => $id,
                            'order_item_id' => $hotelId,
                            'hotel_name' => $hotelName,  // ← FIXED: Simpan nama hotel!
                            'guest_name' => $guestName,
                            'room_number' => $roomNumber,
                            'room_type' => $roomType,
                            'check_in_date' => $checkIn ?: null,
                            'check_out_date' => $checkOut ?: null,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }
        
        Session::flash('success', 'Data tamu hotel berhasil disimpan.');
        redirect('/attachment-order/' . $id . '/hotel-guests');
    }
    
    public function hotelAttachment(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        $hotelItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'hotel' ORDER BY id", [$id]
        );
        
        $guestsByHotel = [];
        foreach ($hotelItems as $hotel) {
            $guestsByHotel[$hotel['id']] = db()->fetchAll(
                "SELECT * FROM hotel_guests WHERE order_id = ? AND order_item_id = ? ORDER BY room_number, id",
                [$id, $hotel['id']]
            );
        }
        
        $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [Session::companyId()]);
        
        // Get client data
        $client = null;
        if (!empty($order['client_id'])) {
            $client = db()->fetchOne("SELECT * FROM clients WHERE id = ?", [$order['client_id']]);
        }
        
        // Render directly without layout (for print page)
        $pageTitle = 'Lampiran Hotel';
        $attachmentMode = true;
        
        include BASE_PATH . '/views/documents/templates/hotel-attachment.php';
        exit;
    }
    
    public function uploadHotelLogo(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order tidak ditemukan']);
            return;
        }
        
        $itemId = (int)($_POST['hotel_item_id'] ?? $_POST['item_id'] ?? 0);
        
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['success' => false, 'message' => 'Gagal upload file']);
            return;
        }
        
        $file = $_FILES['logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            jsonResponse(['success' => false, 'message' => 'Format file tidak didukung']);
            return;
        }
        
        $uploadDir = UPLOADS_PATH . '/logos';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $filename = 'hotel_' . $id . '_' . $itemId . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            db()->update('order_items', ['attachment_logo' => $filename], 'id = ?', [$itemId]);
            jsonResponse([
                'success' => true, 
                'message' => 'Logo berhasil diupload', 
                'data' => ['url' => url('/uploads/logos/' . $filename)]
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Gagal menyimpan file']);
        }
    }
    
    public function deleteHotelLogo(int $id): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $itemId = (int)($input['hotel_item_id'] ?? $input['item_id'] ?? $_POST['hotel_item_id'] ?? $_POST['item_id'] ?? 0);
        
        $item = db()->fetchOne("SELECT attachment_logo FROM order_items WHERE id = ?", [$itemId]);
        
        if ($item && $item['attachment_logo']) {
            $filepath = UPLOADS_PATH . '/logos/' . $item['attachment_logo'];
            if (file_exists($filepath)) unlink($filepath);
            db()->update('order_items', ['attachment_logo' => null], 'id = ?', [$itemId]);
        }
        
        jsonResponse(['success' => true, 'message' => 'Logo berhasil dihapus']);
    }
    
    // ========================================
    // FLIGHT GUESTS
    // ========================================
    
    public function flightGuests(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        $flightItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'flight' ORDER BY id", [$id]
        );
        
        if (empty($flightItems)) {
            Session::flash('error', 'Pesanan ini tidak memiliki item pesawat.');
            redirect('/attachment-order/' . $id);
            return;
        }
        
        $passengersByFlight = [];
        foreach ($flightItems as $flight) {
            try {
                $passengersByFlight[$flight['id']] = db()->fetchAll(
                    "SELECT * FROM flight_details WHERE order_id = ? AND order_item_id = ? ORDER BY id",
                    [$id, $flight['id']]
                );
            } catch (Exception $e) {
                $passengersByFlight[$flight['id']] = [];
            }
        }
        
        $data = [
            'pageTitle' => 'Data Penumpang Pesawat',
            'order' => $order,
            'flightItems' => $flightItems,
            'passengersByFlight' => $passengersByFlight,
            'attachmentMode' => true,
            'backUrl' => '/attachment-order/' . $id,
            'formAction' => '/attachment-order/' . $id . '/flight-guests'
        ];
        
        render('attachments/flight-guests', $data);
    }
    
     public function saveFlightGuests(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        // Get flight items WITH description
        $flightItems = db()->fetchAll(
            "SELECT id, description FROM order_items WHERE order_id = ? AND item_type = 'flight'", [$id]
        );
        
        foreach ($flightItems as $flight) {
            $flightId = $flight['id'];
            $flightName = $flight['description'] ?? '';
            $passengers = $_POST['flight'][$flightId]['passengers'] ?? [];
            
            // ============================================
            // FIXED: Delete hanya untuk flight ini
            // ============================================
            try { db()->delete('flight_details', 'order_id = ? AND order_item_id = ?', [$id, $flightId]); } catch (Exception $e) {}
            
            foreach ($passengers as $passenger) {
                $name = trim($passenger['passenger_name'] ?? '');
                if (empty($name)) continue;
                
                try {
                    db()->insert('flight_details', [
                        'order_id' => $id,
                        'order_item_id' => $flightId,
                        'flight_name' => $flightName,  // ← FIXED: Simpan nama flight!
                        'passenger_name' => $name,
                        'id_number' => trim($passenger['id_number'] ?? ''),
                        'seat_number' => trim($passenger['seat_number'] ?? ''),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {
                    // Fallback jika kolom flight_name tidak ada
                    try {
                        db()->insert('flight_details', [
                            'order_id' => $id,
                            'order_item_id' => $flightId,
                            'passenger_name' => $name,
                            'id_number' => trim($passenger['id_number'] ?? ''),
                            'seat_number' => trim($passenger['seat_number'] ?? ''),
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    } catch (Exception $e2) {}
                }
            }
        }
        
        Session::flash('success', 'Data penumpang berhasil disimpan.');
        redirect('/attachment-order/' . $id . '/flight-guests');
    }
    
    public function flightAttachment(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        $flightItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'flight' ORDER BY id", [$id]
        );
        
        // Template uses $detailsByFlight
        $detailsByFlight = [];
        foreach ($flightItems as $flight) {
            try {
                $detailsByFlight[$flight['id']] = db()->fetchAll(
                    "SELECT * FROM flight_details WHERE order_id = ? AND order_item_id = ? ORDER BY id",
                    [$id, $flight['id']]
                );
            } catch (Exception $e) {
                $detailsByFlight[$flight['id']] = [];
            }
        }
        
        $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [Session::companyId()]);
        
        // Get client data
        $client = null;
        if (!empty($order['client_id'])) {
            $client = db()->fetchOne("SELECT * FROM clients WHERE id = ?", [$order['client_id']]);
        }
        
        // Convert order array to object for template compatibility
        $order = (object) $order;
        
        // Render directly without layout
        include BASE_PATH . '/views/documents/templates/flight-attachment.php';
        exit;
    }
    
    public function uploadFlightLogo(int $id): void {
        $this->uploadLogo($id, 'flight', 'flights');
    }
    
    public function deleteFlightLogo(int $id): void {
        $this->deleteLogo();
    }
    
    // ========================================
    // VEHICLE DOCUMENTS
    // ========================================
    
    public function vehicleDocuments(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        // Ambil semua item kendaraan (bus, towing, DAN rental)
        $vehicleItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type IN ('bus', 'towing', 'rental') ORDER BY id", [$id]
        );
        
        if (empty($vehicleItems)) {
            Session::flash('error', 'Pesanan ini tidak memiliki item kendaraan/rental.');
            redirect('/attachment-order/' . $id);
            return;
        }
        
        // Get documents grouped by item
        $documentsByItem = [];
        $documents = [];
        foreach ($vehicleItems as $vehicle) {
            try {
                $docs = db()->fetchAll(
                    "SELECT * FROM vehicle_documents WHERE order_id = ? AND order_item_id = ? ORDER BY id",
                    [$id, $vehicle['id']]
                );
                $documentsByItem[$vehicle['id']] = $docs;
                $documents = array_merge($documents, $docs);
            } catch (Exception $e) {
                $documentsByItem[$vehicle['id']] = [];
            }
        }
        
        $data = [
            'pageTitle' => 'Data Kendaraan',
            'order' => $order,
            'vehicleItems' => $vehicleItems,
            'documentsByItem' => $documentsByItem,
            'documents' => $documents
        ];
        
        render('attachments/vehicle-documents', $data);
    }
    
    public function saveVehicleDocuments(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        $vehicleItems = db()->fetchAll(
            "SELECT id, description FROM order_items WHERE order_id = ? AND item_type IN ('bus', 'towing', 'rental')", [$id]
        );
        
        // Form uses: vehicles[itemId][index][field]
        $allVehicles = $_POST['vehicles'] ?? [];
        
        foreach ($vehicleItems as $vehicle) {
            $vehicleId = $vehicle['id'];
            $vehicleDocs = $allVehicles[$vehicleId] ?? [];
            
            // ============================================
            // FIXED: Delete hanya untuk vehicle ini
            // ============================================
            try { db()->delete('vehicle_documents', 'order_id = ? AND order_item_id = ?', [$id, $vehicleId]); } catch (Exception $e) {}
            
            foreach ($vehicleDocs as $doc) {
                $vehicleType = trim($doc['vehicle_type'] ?? '');
                if (empty($vehicleType)) continue;
                
                try {
                    db()->insert('vehicle_documents', [
                        'order_id' => $id,
                        'order_item_id' => $vehicleId,
                        'vehicle_type' => $vehicleType,
                        'plate_number' => trim($doc['plate_number'] ?? ''),
                        'driver_name' => trim($doc['driver_name'] ?? ''),
                        'photo_driver' => trim($doc['photo_driver'] ?? ''),
                        'photo_sim' => trim($doc['photo_sim'] ?? ''),
                        'photo_stnk' => trim($doc['photo_stnk'] ?? ''),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {
                    try {
                        db()->insert('vehicle_documents', [
                            'order_id' => $id,
                            'order_item_id' => $vehicleId,
                            'vehicle_type' => $vehicleType,
                            'driver_name' => trim($doc['driver_name'] ?? ''),
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    } catch (Exception $e2) {}
                }
            }
        }
        
        Session::flash('success', 'Data kendaraan berhasil disimpan.');
        redirect('/attachment-order/' . $id . '/vehicle-documents');
    }
    
    public function uploadVehiclePhoto(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order tidak ditemukan']);
            return;
        }
        
        $itemId = (int)($_POST['item_id'] ?? 0);
        $photoType = $_POST['photo_type'] ?? 'driver';
        
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['success' => false, 'message' => 'Gagal upload file']);
            return;
        }
        
        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            jsonResponse(['success' => false, 'message' => 'Format file tidak didukung']);
            return;
        }
        
        $uploadDir = UPLOADS_PATH . '/attachments/vehicles';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $filename = 'vehicle_' . $id . '_' . $itemId . '_' . $photoType . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $column = $photoType . '_photo';
            
            $existingDoc = db()->fetchOne(
                "SELECT id FROM vehicle_documents WHERE order_id = ? AND order_item_id = ?",
                [$id, $itemId]
            );
            
            if ($existingDoc) {
                db()->update('vehicle_documents', [$column => 'attachments/vehicles/' . $filename], 'id = ?', [$existingDoc['id']]);
            } else {
                db()->insert('vehicle_documents', [
                    'order_id' => $id,
                    'order_item_id' => $itemId,
                    $column => 'attachments/vehicles/' . $filename,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            jsonResponse(['success' => true, 'message' => 'Foto berhasil diupload', 'photo_url' => uploadUrl('attachments/vehicles/' . $filename)]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Gagal menyimpan file']);
        }
    }
    
    public function deleteVehiclePhoto(int $id): void {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $photoType = $_POST['photo_type'] ?? 'driver';
        $column = $photoType . '_photo';
        
        try {
            $doc = db()->fetchOne(
                "SELECT * FROM vehicle_documents WHERE order_id = ? AND order_item_id = ?",
                [$id, $itemId]
            );
            
            if ($doc && !empty($doc[$column])) {
                $filepath = UPLOADS_PATH . '/' . $doc[$column];
                if (file_exists($filepath)) unlink($filepath);
                db()->update('vehicle_documents', [$column => null], 'id = ?', [$doc['id']]);
            }
        } catch (Exception $e) {}
        
        jsonResponse(['success' => true, 'message' => 'Foto berhasil dihapus']);
    }
    
    public function vehicleDocumentsPrint(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        // Get all vehicle documents for this order
        $documents = db()->fetchAll(
            "SELECT * FROM vehicle_documents WHERE order_id = ? ORDER BY id", [$id]
        );
        
        if (empty($documents)) {
            Session::flash('error', 'Belum ada data kendaraan untuk dicetak.');
            redirect('/attachment-order/' . $id . '/vehicle-documents');
            return;
        }
        
        $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [Session::companyId()]);
        
        // Get client data
        $client = null;
        if (!empty($order['client_id'])) {
            $client = db()->fetchOne("SELECT * FROM clients WHERE id = ?", [$order['client_id']]);
        }
        
        // Convert order array to object for template compatibility
        $order = (object) $order;
        
        // Render directly without layout
        include BASE_PATH . '/views/documents/templates/vehicle-documents-print.php';
        exit;
    }
    
    // ========================================
    // RENTAL VEHICLES
    // ========================================
    
    public function rentalVehicles(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        // Ambil semua item rental, bus, dan towing (semuanya bisa menggunakan lampiran data harian)
        $rentalItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type IN ('rental', 'bus', 'towing') ORDER BY id", [$id]
        );
        
        if (empty($rentalItems)) {
            Session::flash('error', 'Pesanan ini tidak memiliki item rental/kendaraan.');
            redirect('/attachment-order/' . $id);
            return;
        }
        
        $detailsByRental = [];
        $existingDetails = [];
        foreach ($rentalItems as $rental) {
            try {
                $details = db()->fetchAll(
                    "SELECT * FROM rental_details WHERE order_id = ? AND order_item_id = ? ORDER BY id",
                    [$id, $rental['id']]
                );
                $detailsByRental[$rental['id']] = $details;
                $existingDetails = array_merge($existingDetails, $details);
            } catch (Exception $e) {
                $detailsByRental[$rental['id']] = [];
            }
        }
        
        $data = [
            'pageTitle' => 'Data Harga & Jadwal Kendaraan',
            'order' => $order,
            'rentalItems' => $rentalItems,
            'detailsByRental' => $detailsByRental,
            'existingDetails' => $existingDetails
        ];
        
        render('attachments/rental-vehicles', $data);
    }
    
    public function saveRentalVehicles(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        $rentalItems = db()->fetchAll(
            "SELECT id, description FROM order_items WHERE order_id = ? AND item_type IN ('rental', 'bus', 'towing')", [$id]
        );
        
        // Form uses: vehicles[rentalId][index][field]
        $allVehicles = $_POST['vehicles'] ?? [];
        
        foreach ($rentalItems as $rental) {
            $rentalId = $rental['id'];
            $vehicles = $allVehicles[$rentalId] ?? [];
            
            // ============================================
            // FIXED: Delete hanya untuk rental ini
            // ============================================
            try { db()->delete('rental_details', 'order_id = ? AND order_item_id = ?', [$id, $rentalId]); } catch (Exception $e) {}
            
            foreach ($vehicles as $vehicle) {
                $vehicleType = trim($vehicle['vehicle_type'] ?? '');
                if (empty($vehicleType)) continue;
                
                $startDate = $vehicle['start_date'] ?? null;
                $endDate = $vehicle['end_date'] ?? null;
                $samePrice = isset($vehicle['same_price']) ? 1 : 0;
                $pricePerDay = (int) str_replace(['.', ','], '', $vehicle['price_per_day'] ?? '0');
                $dailyPrices = $vehicle['daily_prices'] ?? [];
                
                try {
                    db()->insert('rental_details', [
                        'order_id' => $id,
                        'order_item_id' => $rentalId,
                        'vehicle_type' => $vehicleType,
                        'start_date' => $startDate ?: null,
                        'end_date' => $endDate ?: null,
                        'same_price' => $samePrice,
                        'price_per_day' => $pricePerDay,
                        'daily_prices' => json_encode($dailyPrices),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {
                    try {
                        db()->insert('rental_details', [
                            'order_id' => $id,
                            'order_item_id' => $rentalId,
                            'vehicle_type' => $vehicleType,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    } catch (Exception $e2) {}
                }
            }
        }
        
        Session::flash('success', 'Data rental berhasil disimpan.');
        redirect('/attachment-order/' . $id . '/rental-vehicles');
    }
    
    public function rentalAttachment(int $id): void {
        $order = $this->getOrder($id);
        if (!$order) return;
        
        $rentalItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type IN ('rental', 'bus', 'towing') ORDER BY id", [$id]
        );
        
        $detailsByRental = [];
        foreach ($rentalItems as $rental) {
            try {
                $detailsByRental[$rental['id']] = db()->fetchAll(
                    "SELECT * FROM rental_details WHERE order_id = ? AND order_item_id = ? ORDER BY id",
                    [$id, $rental['id']]
                );
            } catch (Exception $e) {
                $detailsByRental[$rental['id']] = [];
            }
        }
        
        $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [Session::companyId()]);
        
        // Get client data
        $client = null;
        if (!empty($order['client_id'])) {
            $client = db()->fetchOne("SELECT * FROM clients WHERE id = ?", [$order['client_id']]);
        }
        
        // Convert order array to object for template compatibility
        $order = (object) $order;
        
        // Render directly without layout
        include BASE_PATH . '/views/documents/templates/rental-attachment.php';
        exit;
    }
    
    public function uploadRentalLogo(int $id): void {
        $this->uploadLogo($id, 'rental', 'rentals');
    }
    
    public function deleteRentalLogo(int $id): void {
        $this->deleteLogo();
    }
    
    // ========================================
    // HELPER METHODS
    // ========================================
    
    private function getOrder(int $id): ?array {
        $companyId = Session::companyId();
        
        if (!$companyId) {
            Session::flash('error', 'Silakan login terlebih dahulu.');
            redirect('/login');
            return null;
        }
        
        $order = db()->fetchOne("
            SELECT o.id, o.order_number, o.event_name, o.event_date, o.event_end_date, 
                   o.status, o.notes, c.name as client_name 
            FROM orders o 
            LEFT JOIN clients c ON o.client_id = c.id 
            WHERE o.id = ? AND o.company_id = ?
        ", [$id, $companyId]);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/attachment-dashboard');
            return null;
        }
        
        return $order;
    }
    
    private function getAttachmentStatus(int $orderId): array {
        $status = [
            'hotel' => ['required' => false, 'filled' => false],
            'flight' => ['required' => false, 'filled' => false],
            'vehicle' => ['required' => false, 'filled' => false],
            'rental' => ['required' => false, 'filled' => false]
        ];
        
        $hotelCount = (int) db()->fetchColumn("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND item_type = 'hotel'", [$orderId]);
        if ($hotelCount > 0) {
            $status['hotel']['required'] = true;
            $status['hotel']['filled'] = (int) db()->fetchColumn("SELECT COUNT(*) FROM hotel_guests WHERE order_id = ?", [$orderId]) > 0;
        }
        
        $flightCount = (int) db()->fetchColumn("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND item_type = 'flight'", [$orderId]);
        if ($flightCount > 0) {
            $status['flight']['required'] = true;
            try {
                $status['flight']['filled'] = (int) db()->fetchColumn("SELECT COUNT(*) FROM flight_details WHERE order_id = ?", [$orderId]) > 0;
            } catch (Exception $e) {}
        }
        
        $vehicleCount = (int) db()->fetchColumn("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND item_type IN ('bus', 'towing')", [$orderId]);
        if ($vehicleCount > 0) {
            $status['vehicle']['required'] = true;
            try {
                $status['vehicle']['filled'] = (int) db()->fetchColumn("SELECT COUNT(*) FROM vehicle_documents WHERE order_id = ?", [$orderId]) > 0;
            } catch (Exception $e) {}
        }
        
        $rentalCount = (int) db()->fetchColumn("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND item_type = 'rental'", [$orderId]);
        if ($rentalCount > 0) {
            $status['rental']['required'] = true;
            try {
                $status['rental']['filled'] = (int) db()->fetchColumn("SELECT COUNT(*) FROM rental_details WHERE order_id = ?", [$orderId]) > 0;
            } catch (Exception $e) {}
        }
        
        return $status;
    }
    
    private function getAttachmentStats(int $companyId): array {
        $stats = ['total_orders' => 0, 'pending_hotel' => 0, 'pending_flight' => 0, 'pending_vehicle' => 0, 'pending_rental' => 0];
        
        try {
            $stats['total_orders'] = (int) db()->fetchColumn("
                SELECT COUNT(DISTINCT o.id) FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.company_id = ? AND o.status NOT IN ('cancelled', 'draft')
                AND oi.item_type IN ('hotel', 'flight', 'bus', 'towing', 'rental')
            ", [$companyId]);
            
            $stats['pending_hotel'] = (int) db()->fetchColumn("
                SELECT COUNT(DISTINCT o.id) FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id AND oi.item_type = 'hotel'
                LEFT JOIN hotel_guests hg ON o.id = hg.order_id
                WHERE o.company_id = ? AND o.status NOT IN ('cancelled', 'draft') AND hg.id IS NULL
            ", [$companyId]);
            
            $stats['pending_flight'] = (int) db()->fetchColumn("
                SELECT COUNT(DISTINCT o.id) FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id AND oi.item_type = 'flight'
                LEFT JOIN flight_details fd ON o.id = fd.order_id
                WHERE o.company_id = ? AND o.status NOT IN ('cancelled', 'draft') AND fd.id IS NULL
            ", [$companyId]);
            
            $stats['pending_vehicle'] = (int) db()->fetchColumn("
                SELECT COUNT(DISTINCT o.id) FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id AND oi.item_type IN ('bus', 'towing')
                LEFT JOIN vehicle_documents vd ON o.id = vd.order_id
                WHERE o.company_id = ? AND o.status NOT IN ('cancelled', 'draft') AND vd.id IS NULL
            ", [$companyId]);
            
            $stats['pending_rental'] = (int) db()->fetchColumn("
                SELECT COUNT(DISTINCT o.id) FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id AND oi.item_type = 'rental'
                LEFT JOIN rental_details rd ON o.id = rd.order_id
                WHERE o.company_id = ? AND o.status NOT IN ('cancelled', 'draft') AND rd.id IS NULL
            ", [$companyId]);
        } catch (Exception $e) {}
        
        return $stats;
    }
    
    private function uploadLogo(int $orderId, string $type, string $folder): void {
        $order = $this->getOrder($orderId);
        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order tidak ditemukan']);
            return;
        }
        
        $itemId = (int)($_POST['item_id'] ?? 0);
        
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['success' => false, 'message' => 'Gagal upload file']);
            return;
        }
        
        $file = $_FILES['logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            jsonResponse(['success' => false, 'message' => 'Format file tidak didukung']);
            return;
        }
        
        $uploadDir = UPLOADS_PATH . '/attachments/' . $folder;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $filename = $type . '_' . $orderId . '_' . $itemId . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            db()->update('order_items', ['attachment_logo' => 'attachments/' . $folder . '/' . $filename], 'id = ?', [$itemId]);
            jsonResponse(['success' => true, 'message' => 'Logo berhasil diupload', 'logo_url' => uploadUrl('attachments/' . $folder . '/' . $filename)]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Gagal menyimpan file']);
        }
    }
    
    private function deleteLogo(): void {
        $itemId = (int)($_POST['item_id'] ?? 0);
        
        $item = db()->fetchOne("SELECT attachment_logo FROM order_items WHERE id = ?", [$itemId]);
        
        if ($item && $item['attachment_logo']) {
            $filepath = UPLOADS_PATH . '/' . $item['attachment_logo'];
            if (file_exists($filepath)) unlink($filepath);
            db()->update('order_items', ['attachment_logo' => null], 'id = ?', [$itemId]);
        }
        
        jsonResponse(['success' => true, 'message' => 'Logo berhasil dihapus']);
    }
}