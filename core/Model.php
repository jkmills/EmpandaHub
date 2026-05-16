<?php
declare(strict_types=1);

abstract class Model
{
    protected static string $table = '';
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id, int $orgId): ?array
    {
        $sql  = 'SELECT * FROM ' . static::$table . ' WHERE id = ? AND org_id = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $orgId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(int $orgId, string $order = 'id ASC', int $limit = 500, int $offset = 0): array
    {
        $sql  = 'SELECT * FROM ' . static::$table . ' WHERE org_id = ? ORDER BY ' . $order . ' LIMIT ? OFFSET ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orgId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function insert(array $data): int
    {
        $cols        = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql         = 'INSERT INTO ' . static::$table . ' (' . $cols . ') VALUES (' . $placeholders . ')';
        $stmt        = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $orgId, array $data): bool
    {
        $sets = implode(', ', array_map(fn($col) => $col . ' = ?', array_keys($data)));
        $sql  = 'UPDATE ' . static::$table . ' SET ' . $sets . ', updated_at = NOW() WHERE id = ? AND org_id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([...array_values($data), $id, $orgId]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $orgId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ' . static::$table . ' WHERE id = ? AND org_id = ?');
        $stmt->execute([$id, $orgId]);
        return $stmt->rowCount() > 0;
    }

    public function count(int $orgId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ' . static::$table . ' WHERE org_id = ?');
        $stmt->execute([$orgId]);
        return (int)$stmt->fetchColumn();
    }

    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
