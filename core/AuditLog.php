<?php
declare(strict_types=1);

class AuditLog
{
    public static function record(string $action, string $targetType = '', int $targetId = 0, string $details = ''): void
    {
        try {
            $user  = Auth::user();
            $orgId = $user['org_id'] ?? 0;
            $userId = $user['id'] ?? null;

            if (!$orgId) return;

            $db   = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO audit_log (org_id, user_id, action, target_type, target_id, details, ip)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $orgId,
                $userId,
                $action,
                $targetType ?: null,
                $targetId   ?: null,
                $details    ?: null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Throwable) {
            // Audit failures must never break the main flow
        }
    }
}
