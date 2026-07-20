<?php

if (!function_exists('ensure_admin_schema')) {
    function ensure_admin_schema(mysqli $conn): void
    {
        $conn->query("CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) DEFAULT NULL,
            entity_id VARCHAR(50) DEFAULT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_admin_id (admin_id),
            INDEX idx_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("CREATE TABLE IF NOT EXISTS otps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            otp VARCHAR(6) NOT NULL,
            type ENUM('register','reset') NOT NULL,
            expires_at DATETIME NOT NULL,
            verified TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_type (email, type),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("CREATE TABLE IF NOT EXISTS saved_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_saved_post (user_id, post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_post_id (post_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            body TEXT DEFAULT NULL,
            link VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        ensure_column_exists($conn, 'messages', 'is_read', 'TINYINT(1) NOT NULL DEFAULT 0');

        ensure_column_exists($conn, 'contact_messages', 'is_read', 'TINYINT(1) NOT NULL DEFAULT 0');
        ensure_column_exists($conn, 'contact_messages', 'replied_at', 'DATETIME NULL DEFAULT NULL');
        ensure_column_exists($conn, 'contact_messages', 'reply_message', 'TEXT NULL');
        ensure_column_exists($conn, 'contact_messages', 'reply_admin_id', 'INT NULL DEFAULT NULL');

        ensure_column_exists($conn, 'users', 'email_verified_at', 'DATETIME NULL DEFAULT NULL');
    }

    function ensure_column_exists(mysqli $conn, string $table, string $column, string $definition): void
    {
        $dbResult = $conn->query('SELECT DATABASE() AS db_name');
        $dbRow = $dbResult ? $dbResult->fetch_assoc() : null;
        $dbName = $dbRow['db_name'] ?? '';

        if ($dbName === '') {
            return;
        }

        $stmt = $conn->prepare(
            'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
        );
        $stmt->bind_param('sss', $dbName, $table, $column);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;

        if (!$exists) {
            $conn->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    function admin_log_activity(mysqli $conn, string $action, string $description = '', ?string $entityType = null, $entityId = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $adminId = (int)($_SESSION['user_id'] ?? 0);
        if ($adminId <= 0) {
            return false;
        }

        $entityIdValue = $entityId === null ? null : (string)$entityId;
        $stmt = $conn->prepare('INSERT INTO activity_log (admin_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param(
            'issss',
            $adminId,
            $action,
            $entityType,
            $entityIdValue,
            $description
        );

        return $stmt->execute();
    }

    function push_notification(mysqli $conn, int $userId, string $type, string $title, string $body = '', string $link = ''): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $stmt = $conn->prepare('INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $userId, $type, $title, $body, $link);
        return $stmt->execute();
    }

    function push_notification_to_users(mysqli $conn, string $type, string $title, string $body = '', string $link = ''): void
    {
        $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'user'");
        $stmt->execute();
        $users = $stmt->get_result();

        while ($row = $users->fetch_assoc()) {
            push_notification($conn, (int) $row['id'], $type, $title, $body, $link);
        }
    }
}
