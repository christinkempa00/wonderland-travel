<?php
/**
 * Notification Controller
 */

class NotificationController {

    public function index(): void {
        $userId = Session::userId();

        $notifications = db()->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30",
            [$userId]
        );

        jsonSuccess($notifications);
    }

    public function markRead(int $id): void {
        db()->update(
            'notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'id = ? AND user_id = ?',
            [$id, Session::userId()]
        );

        jsonSuccess(['marked' => true]);
    }

    public function markAllRead(): void {
        db()->update(
            'notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'user_id = ? AND is_read = 0',
            [Session::userId()]
        );

        jsonSuccess(['marked' => true]);
    }
}
