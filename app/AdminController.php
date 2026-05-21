<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

class AdminController
{
    public function __construct(private PDO $db)
    {
    }

    public function getTypes(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT form_type FROM submissions ORDER BY form_type");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getPaginated(string $filter, int $page, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $where  = '';
        $params = [];

        if ($filter !== '') {
            $where    = "WHERE form_type = ?";
            $params[] = $filter;
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM submissions $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare("
            SELECT * FROM submissions
            $where
            ORDER BY id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);

        return [
            'rows'        => $stmt->fetchAll(),
            'total'       => $total,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }
}

$controller = new AdminController($pdo);
