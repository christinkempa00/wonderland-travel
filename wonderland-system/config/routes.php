<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Routes Configuration
 * ================================================================
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Route Definitions
 * Format: 'pattern' => ['controller', 'method', 'middleware']
 */

$routes = [
    
    // ============================================
    // PUBLIC ROUTES (No Auth Required)
    // ============================================
    
    'GET /' => ['AuthController', 'loginPage'],
    'GET /login' => ['AuthController', 'loginPage'],
    'POST /login' => ['AuthController', 'login'],
    'GET /logout' => ['AuthController', 'logout'],
    'GET /forgot-password' => ['AuthController', 'forgotPasswordPage'],
    'POST /forgot-password' => ['AuthController', 'forgotPassword'],
    'GET /reset-password/{token}' => ['AuthController', 'resetPasswordPage'],
    'POST /reset-password' => ['AuthController', 'resetPassword'],
    
    // Installation
    'GET /install' => ['InstallController', 'index'],
    'GET /install/step/{step:\d+}' => ['InstallController', 'step'],
    'POST /install/step/{step:\d+}' => ['InstallController', 'processStep'],
    
    'GET /analysis/vendor-recap' => ['AnalysisController', 'vendorRecap', 'auth'],
    
    // ============================================
    // DASHBOARD
    // ============================================
    
    'GET /dashboard' => ['DashboardController', 'index', 'auth'],
    'GET /api/dashboard/stats' => ['DashboardController', 'apiStats', 'auth'],
    'GET /api/dashboard/chart/{type}' => ['DashboardController', 'getChartData', 'auth'],
    'POST /switch-company' => ['DashboardController', 'switchCompany', 'auth'],

    // ============================================
    // CLIENT MANAGEMENT
    // ============================================
    
    'GET /clients' => ['ClientController', 'index', 'auth'],
    'GET /clients/create' => ['ClientController', 'create', 'auth'],
    'POST /clients' => ['ClientController', 'store', 'auth'],
    'GET /clients/{id:\d+}' => ['ClientController', 'show', 'auth'],
    'GET /clients/{id:\d+}/edit' => ['ClientController', 'edit', 'auth'],
    'PUT /clients/{id:\d+}' => ['ClientController', 'update', 'auth'],
    'DELETE /clients/{id:\d+}' => ['ClientController', 'destroy', 'auth'],
    'POST /clients/{id:\d+}/communications' => ['ClientController', 'addCommunication', 'auth'],
    'GET /api/clients/search' => ['ClientController', 'search', 'auth'],
    
    // ============================================
    // ORDER MANAGEMENT
    // ============================================
    
    'GET /orders' => ['OrderController', 'index', 'auth'],
    'GET /orders/create' => ['OrderController', 'create', 'auth'],
    'POST /orders' => ['OrderController', 'store', 'auth'],
    'GET /orders/{id:\d+}' => ['OrderController', 'show', 'auth'],
    'GET /orders/{id:\d+}/edit' => ['OrderController', 'edit', 'auth'],
    'PUT /orders/{id:\d+}' => ['OrderController', 'update', 'auth'],
    'DELETE /orders/{id:\d+}' => ['OrderController', 'destroy', 'auth'],
    'POST /orders/{id:\d+}/status' => ['OrderController', 'updateStatus', 'auth'],
    
    // Payment Status Update (Quick)
    'PATCH /orders/{id:\d+}/payment-status' => ['OrderController', 'updatePaymentStatus', 'auth'],
    'POST /orders/{id:\d+}/payment-status' => ['OrderController', 'updatePaymentStatus', 'auth'],
    
    // ============================================
    // ORDER PAYMENT MANAGEMENT (With Accounting)
    // ============================================
    
    'GET /orders/{id:\d+}/payment' => ['OrderController', 'payment', 'auth'],
    'POST /orders/{id:\d+}/payment' => ['OrderController', 'storePayment', 'auth'],
    'DELETE /orders/{id:\d+}/payment/{paymentId:\d+}/void' => ['OrderController', 'voidPayment', 'auth'],
    'POST /orders/{id:\d+}/payment/{paymentId:\d+}/void' => ['OrderController', 'voidPayment', 'auth'],

    // ============================================
    // INVOICE MANAGEMENT
    // ============================================

    'GET /invoices' => ['InvoiceController', 'index', 'auth'],
    'GET /invoices/create' => ['InvoiceController', 'create', 'auth'],
    'POST /invoices' => ['InvoiceController', 'store', 'auth'],
    'GET /invoices/{id:\d+}' => ['InvoiceController', 'show', 'auth'],
    'POST /invoices/{id:\d+}/payment' => ['InvoiceController', 'storePayment', 'auth'],

    // ============================================
    // HOTEL GUESTS MANAGEMENT
    // ============================================
    
    'GET /orders/{id:\d+}/hotel-guests' => ['OrderController', 'hotelGuests', 'auth'],
    'POST /orders/{id:\d+}/hotel-guests' => ['OrderController', 'saveHotelGuests', 'auth'],
    'GET /orders/{id:\d+}/hotel-attachment' => ['OrderController', 'hotelAttachment', 'auth'],
    'POST /orders/{id:\d+}/upload-hotel-logo' => ['OrderController', 'uploadHotelLogo', 'auth'],
    'POST /orders/{id:\d+}/delete-hotel-logo' => ['OrderController', 'deleteHotelLogo', 'auth'],
    
    // ============================================
    // FLIGHT PASSENGERS/DETAILS MANAGEMENT
    // ============================================
    
    'GET /orders/{id:\d+}/flight-guests' => ['OrderController', 'flightGuests', 'auth'],
    'POST /orders/{id:\d+}/flight-guests' => ['OrderController', 'saveFlightGuests', 'auth'],
    'GET /orders/{id:\d+}/flight-attachment' => ['OrderController', 'flightAttachment', 'auth'],
    'POST /orders/{id:\d+}/upload-flight-logo' => ['OrderController', 'uploadFlightLogo', 'auth'],
    'POST /orders/{id:\d+}/delete-flight-logo' => ['OrderController', 'deleteFlightLogo', 'auth'],
    
    // ============================================
    // RENTAL/VEHICLE MANAGEMENT
    // ============================================
    
    'GET /orders/{id:\d+}/rental-vehicles' => ['OrderController', 'rentalVehicles', 'auth'],
    'POST /orders/{id:\d+}/rental-vehicles' => ['OrderController', 'saveRentalVehicles', 'auth'],
    'GET /orders/{id:\d+}/rental-attachment' => ['OrderController', 'rentalAttachment', 'auth'],
    'POST /orders/{id:\d+}/upload-rental-logo' => ['OrderController', 'uploadRentalLogo', 'auth'],
    'POST /orders/{id:\d+}/delete-rental-logo' => ['OrderController', 'deleteRentalLogo', 'auth'],
    
    // ============================================
    // VEHICLE DOCUMENTS MANAGEMENT
    // ============================================
    
    'GET /orders/{id:\d+}/vehicle-documents' => ['OrderController', 'vehicleDocuments', 'auth'],
    'POST /orders/{id:\d+}/vehicle-documents' => ['OrderController', 'saveVehicleDocuments', 'auth'],
    'POST /orders/{id:\d+}/upload-vehicle-photo' => ['OrderController', 'uploadVehiclePhoto', 'auth'],
    'POST /orders/{id:\d+}/delete-vehicle-photo' => ['OrderController', 'deleteVehiclePhoto', 'auth'],
    'GET /orders/{id:\d+}/vehicle-documents-print' => ['OrderController', 'vehicleDocumentsPrint', 'auth'],
    
    // ============================================
    // ORDER DOCUMENTS
    // ============================================
    
    'GET /orders/{id:\d+}/quotation' => ['OrderController', 'quotation', 'auth'],
    'GET /orders/{id:\d+}/invoice' => ['OrderController', 'invoice', 'auth'],
    'GET /orders/{id:\d+}/receipt' => ['OrderController', 'receipt', 'auth'],
    
    // ============================================
    // ORDER FINANCIAL ANALYSIS
    // ============================================
    
    
// ============================================
    // REPORT CHECKLIST (Database Storage)
    // ============================================
    
    'GET /analysis/load-checklist' => ['AnalysisController', 'loadChecklist', 'auth'],
    'POST /analysis/save-checklist' => ['AnalysisController', 'saveChecklist', 'auth'],
    'POST /analysis/save-catatan' => ['AnalysisController', 'saveCatatan', 'auth'],
    'POST /analysis/reset-checklist' => ['AnalysisController', 'resetChecklist', 'auth'],
    
    'GET /orders/{id:\d+}/analysis' => ['OrderController', 'analysis', 'auth'],
    'GET /api/orders/{id:\d+}/analysis-data' => ['OrderController', 'getAnalysisData', 'auth'],
    
    // ============================================
    // DOCUMENTS
    // ============================================
    
    'GET /documents' => ['DocumentController', 'index', 'auth'],
    'GET /documents/search' => ['DocumentController', 'search', 'auth'],
    
    // ============================================
    // EXPENSE TRACKING & ANALYSIS
    // ============================================
    
    'GET /analysis' => ['AnalysisController', 'index', 'auth'],
    'GET /analysis/report' => ['AnalysisController', 'report', 'auth'],
    'GET /analysis/export' => ['AnalysisController', 'export', 'auth'],
    'GET /analysis/export-report' => ['AnalysisController', 'exportReport', 'auth'],
    'GET /analysis/chart-data' => ['AnalysisController', 'getChartData', 'auth'],
    'GET /analysis/bank-cash-options' => ['AnalysisController', 'getBankCashOptions', 'auth'],
    'GET /analysis/get-draft' => ['AnalysisController', 'getDraft', 'auth'],
    'GET /analysis/load-simulasi' => ['AnalysisController', 'loadSimulasi', 'auth'],
    'GET /analysis/save-simulasi' => ['AnalysisController', 'saveSimulasi', 'auth'],
    
    'POST /analysis/update-sbm' => ['AnalysisController', 'updateSbm', 'auth'],
    'POST /analysis/update-markup' => ['AnalysisController', 'updateMarkup', 'auth'],
    'POST /analysis/update-filing-status' => ['AnalysisController', 'updateFilingStatus', 'auth'],
    'POST /analysis/update-payment-status' => ['AnalysisController', 'updatePaymentStatus', 'auth'],
    'POST /analysis/update-expense-status' => ['AnalysisController', 'updateExpenseStatus', 'auth'],
    'POST /analysis/bulk-update-markup' => ['AnalysisController', 'bulkUpdateMarkup', 'auth'],
    'POST /analysis/bulk-update-expense' => ['AnalysisController', 'bulkUpdateExpense', 'auth'],
    'POST /analysis/save-draft' => ['AnalysisController', 'saveDraft', 'auth'],
    'POST /analysis/delete-draft' => ['AnalysisController', 'deleteDraft', 'auth'],
    'POST /analysis/approve-draft' => ['AnalysisController', 'approveDraft', 'auth'],
    'POST /analysis/create-draft-table' => ['AnalysisController', 'createDraftTable', 'auth'],
    'POST /analysis/save-simulasi' => ['AnalysisController', 'saveSimulasi', 'auth'],
    'POST /analysis/create-simulasi-table' => ['AnalysisController', 'createSimulasiTable', 'auth'],
    
    // ============================================
    // LOAN MANAGEMENT (Pinjaman)
    // ============================================
    
    'GET /loans' => ['LoanController', 'index', 'auth'],
    'GET /loans/receive' => ['LoanController', 'receive', 'auth'],
    'POST /loans/receive' => ['LoanController', 'storeReceive', 'auth'],
    'GET /loans/repay' => ['LoanController', 'repay', 'auth'],
    'POST /loans/repay' => ['LoanController', 'storeRepay', 'auth'],
    'POST /loans/{id:\d+}/void' => ['LoanController', 'void', 'auth'],
    
    // ============================================
    // PROFIT SHARING
    // ============================================
    
    'GET /profit' => ['ProfitController', 'index', 'auth'],
    'GET /profit/create' => ['ProfitController', 'create', 'auth'],
    'POST /profit' => ['ProfitController', 'store', 'auth'],
    'GET /profit/{id:\d+}' => ['ProfitController', 'show', 'auth'],
    'GET /profit/{id:\d+}/edit' => ['ProfitController', 'edit', 'auth'],
    'PUT /profit/{id:\d+}' => ['ProfitController', 'update', 'auth'],
    'DELETE /profit/{id:\d+}' => ['ProfitController', 'destroy', 'auth'],
    'POST /profit/{id:\d+}/mark-paid' => ['ProfitController', 'markPaid', 'auth'],
    'POST /profit/{id:\d+}/notify' => ['ProfitController', 'notify', 'auth'],
    
    // ============================================
    // ACCOUNTING - DASHBOARD
    // ============================================
    
    'GET /accounting/dashboard' => ['AccountingController', 'dashboard', 'auth'],
    
    // ============================================
    // ACCOUNTING - ACCOUNTS
    // ============================================
    
    'GET /accounting/accounts' => ['AccountingController', 'accounts', 'auth'],
    'GET /accounting/accounts/create' => ['AccountingController', 'createAccount', 'auth'],
    'POST /accounting/accounts' => ['AccountingController', 'storeAccount', 'auth'],
    'GET /accounting/accounts/{id:\d+}/edit' => ['AccountingController', 'editAccount', 'auth'],
    'PUT /accounting/accounts/{id:\d+}' => ['AccountingController', 'updateAccount', 'auth'],
    'DELETE /accounting/accounts/{id:\d+}' => ['AccountingController', 'destroyAccount', 'auth'],
    
    // ============================================
    // ACCOUNTING - JOURNALS
    // ============================================
    
    'GET /accounting/journals' => ['AccountingController', 'journals', 'auth'],
    'GET /accounting/journals/create' => ['AccountingController', 'createJournal', 'auth'],
    'POST /accounting/journals' => ['AccountingController', 'storeJournal', 'auth'],
    'GET /accounting/journals/{id:\d+}' => ['AccountingController', 'showJournal', 'auth'],
    'GET /accounting/journals/{id:\d+}/edit' => ['AccountingController', 'editJournal', 'auth'],
    'PUT /accounting/journals/{id:\d+}' => ['AccountingController', 'updateJournal', 'auth'],
    'POST /accounting/journals/{id:\d+}/post' => ['AccountingController', 'postJournal', 'auth'],
    'POST /accounting/journals/{id:\d+}/void' => ['AccountingController', 'voidJournal', 'auth'],
    
    // ============================================
    // ACCOUNTING - BANK & CASH
    // ============================================
    
    'GET /accounting/bank-cash' => ['AccountingController', 'bankCash', 'auth'],
    'GET /accounting/bank-cash/create' => ['AccountingController', 'createBankCash', 'auth'],
    'POST /accounting/bank-cash' => ['AccountingController', 'storeBankCash', 'auth'],
    'GET /accounting/bank-cash/{id:\d+}' => ['AccountingController', 'showBankCash', 'auth'],
    'GET /accounting/bank-cash/{id:\d+}/edit' => ['AccountingController', 'editBankCash', 'auth'],
    'PUT /accounting/bank-cash/{id:\d+}' => ['AccountingController', 'updateBankCash', 'auth'],
    'DELETE /accounting/bank-cash/{id:\d+}' => ['AccountingController', 'destroyBankCash', 'auth'],
    
    // ============================================
    // ACCOUNTING - SETTINGS
    // ============================================
    
    'GET /accounting/settings' => ['AccountingController', 'settings', 'auth'],
    'POST /accounting/settings' => ['AccountingController', 'updateSettings', 'auth'],
    
    // ============================================
    // ACCOUNTING - LEDGER
    // ============================================
    
    'GET /accounting/ledger' => ['AccountingController', 'ledger', 'auth'],
    
    // ============================================
    // ACCOUNTING - REPORTS
    // ============================================
    
    'GET /accounting/reports' => ['AccountingController', 'reports', 'auth'],
    'GET /accounting/reports/trial-balance' => ['AccountingController', 'trialBalance', 'auth'],
    'GET /accounting/reports/income-statement' => ['AccountingController', 'incomeStatement', 'auth'],
    'GET /accounting/reports/balance-sheet' => ['AccountingController', 'balanceSheet', 'auth'],
    'GET /accounting/reports/receivables' => ['AccountingController', 'receivables', 'auth'],
    'GET /accounting/reports/cash-flow' => ['AccountingController', 'cashFlow', 'auth'],
    
    // ============================================
    // TRANSACTIONS (Pengeluaran/Pemasukan Operasional)
    // ============================================
    
    'GET /transactions' => ['TransactionController', 'index', 'auth'],
    'GET /transactions/create' => ['TransactionController', 'create', 'auth'],
    'POST /transactions' => ['TransactionController', 'store', 'auth'],
    'GET /transactions/{id:\d+}' => ['TransactionController', 'show', 'auth'],
    'POST /transactions/{id:\d+}/void' => ['TransactionController', 'void', 'auth'],
    
    // ============================================
    // SETTINGS
    // ============================================
    
    'GET /settings' => ['SettingsController', 'index', 'auth'],
    'GET /settings/company' => ['SettingsController', 'company', 'auth'],
    'POST /settings/company' => ['SettingsController', 'updateCompany', 'auth'],
    'GET /settings/documents' => ['SettingsController', 'documents', 'auth'],
    'POST /settings/documents' => ['SettingsController', 'updateDocuments', 'auth'],
    'GET /settings/integrations' => ['SettingsController', 'integrations', 'auth'],
    'POST /settings/integrations' => ['SettingsController', 'updateIntegrations', 'auth'],
    'POST /settings/test-dripsender' => ['SettingsController', 'testDripsender', 'auth'],
    
    // ============================================
    // USER MANAGEMENT
    // ============================================
    
    'GET /users' => ['UserController', 'index', 'auth'],
    'GET /users/create' => ['UserController', 'create', 'auth'],
    'POST /users' => ['UserController', 'store', 'auth'],
    'GET /users/{id:\d+}/edit' => ['UserController', 'edit', 'auth'],
    'PUT /users/{id:\d+}' => ['UserController', 'update', 'auth'],
    'DELETE /users/{id:\d+}' => ['UserController', 'destroy', 'auth'],
    
    // Profile
    'GET /profile' => ['UserController', 'profile', 'auth'],
    'POST /profile' => ['UserController', 'updateProfile', 'auth'],
    'POST /profile/password' => ['UserController', 'changePassword', 'auth'],
    
    // ============================================
    // ACTIVITY LOG
    // ============================================
    
    'GET /activity-logs' => ['ActivityLogController', 'index', 'auth'],
    'GET /activity-logs/export' => ['ActivityLogController', 'export', 'auth'],
    
    // ============================================
    // NOTIFICATIONS API
    // ============================================
    
    'GET /api/notifications' => ['NotificationController', 'index', 'auth'],
    'POST /api/notifications/{id:\d+}/read' => ['NotificationController', 'markRead', 'auth'],
    'POST /api/notifications/read-all' => ['NotificationController', 'markAllRead', 'auth'],
    
    // ============================================
    // ATTACHMENT STAFF (Staff Lampiran)
    // ============================================
    
    'GET /attachment-dashboard' => ['AttachmentController', 'dashboard', 'auth'],
    'GET /attachment-order/{id:\d+}' => ['AttachmentController', 'viewOrder', 'auth'],
    
    // Attachment - Hotel
    'GET /attachment-order/{id:\d+}/hotel-guests' => ['AttachmentController', 'hotelGuests', 'auth'],
    'POST /attachment-order/{id:\d+}/hotel-guests' => ['AttachmentController', 'saveHotelGuests', 'auth'],
    'GET /attachment-order/{id:\d+}/hotel-attachment' => ['AttachmentController', 'hotelAttachment', 'auth'],
    'POST /attachment-order/{id:\d+}/upload-hotel-logo' => ['AttachmentController', 'uploadHotelLogo', 'auth'],
    'POST /attachment-order/{id:\d+}/delete-hotel-logo' => ['AttachmentController', 'deleteHotelLogo', 'auth'],
    
    // Attachment - Flight
    'GET /attachment-order/{id:\d+}/flight-guests' => ['AttachmentController', 'flightGuests', 'auth'],
    'POST /attachment-order/{id:\d+}/flight-guests' => ['AttachmentController', 'saveFlightGuests', 'auth'],
    'GET /attachment-order/{id:\d+}/flight-attachment' => ['AttachmentController', 'flightAttachment', 'auth'],
    'POST /attachment-order/{id:\d+}/upload-flight-logo' => ['AttachmentController', 'uploadFlightLogo', 'auth'],
    'POST /attachment-order/{id:\d+}/delete-flight-logo' => ['AttachmentController', 'deleteFlightLogo', 'auth'],
    
    // Attachment - Vehicle
    'GET /attachment-order/{id:\d+}/vehicle-documents' => ['AttachmentController', 'vehicleDocuments', 'auth'],
    'POST /attachment-order/{id:\d+}/vehicle-documents' => ['AttachmentController', 'saveVehicleDocuments', 'auth'],
    'POST /attachment-order/{id:\d+}/upload-vehicle-photo' => ['AttachmentController', 'uploadVehiclePhoto', 'auth'],
    'POST /attachment-order/{id:\d+}/delete-vehicle-photo' => ['AttachmentController', 'deleteVehiclePhoto', 'auth'],
    'GET /attachment-order/{id:\d+}/vehicle-documents-print' => ['AttachmentController', 'vehicleDocumentsPrint', 'auth'],
    
    // Attachment - Rental
    'GET /attachment-order/{id:\d+}/rental-vehicles' => ['AttachmentController', 'rentalVehicles', 'auth'],
    'POST /attachment-order/{id:\d+}/rental-vehicles' => ['AttachmentController', 'saveRentalVehicles', 'auth'],
    'GET /attachment-order/{id:\d+}/rental-attachment' => ['AttachmentController', 'rentalAttachment', 'auth'],
    'POST /attachment-order/{id:\d+}/upload-rental-logo' => ['AttachmentController', 'uploadRentalLogo', 'auth'],
    'POST /attachment-order/{id:\d+}/delete-rental-logo' => ['AttachmentController', 'deleteRentalLogo', 'auth'],
    
];

return $routes;