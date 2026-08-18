<?php
/**
 * ================================================================
 * WONDERLAND TRAVEL - Client Communication Model
 * Riwayat komunikasi dengan klien (telepon/WhatsApp/email/tatap muka)
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class ClientCommunication extends Model {

    protected static string $table = 'client_communications';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'communication_date';
    protected static string $orderDir = 'DESC';
    protected static bool $timestamps = true;

    protected static array $fillable = [
        'company_id',
        'client_id',
        'type',
        'notes',
        'communication_date',
        'created_by'
    ];

    /**
     * Get communication history for a client
     */
    public static function getForClient(int $clientId, int $limit = 20): array {
        return self::db()->fetchAll(
            "SELECT cc.*, u.name as created_by_name
             FROM client_communications cc
             LEFT JOIN users u ON cc.created_by = u.id
             WHERE cc.client_id = ?
             ORDER BY cc.communication_date DESC, cc.id DESC
             LIMIT {$limit}",
            [$clientId]
        );
    }
}
