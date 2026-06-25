<?php

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../models/Order.php';

class OrderDAO {
    private PDO $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function create(int $userId, float $total, ?string $paymentMethod = null, ?string $shippingMethod = null, float $shippingCost = 0.0): int {
        $stmt = $this->db->prepare(
            'INSERT INTO orders (user_id, total, payment_method, shipping_method, shipping_cost)
             VALUES (:user_id, :total, :payment_method, :shipping_method, :shipping_cost)'
        );
        $stmt->execute([
            ':user_id'         => $userId,
            ':total'           => $total,
            ':payment_method'  => $paymentMethod,
            ':shipping_method' => $shippingMethod,
            ':shipping_cost'   => $shippingCost,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function addItem(int $orderId, int $productId, int $quantity, float $unitPrice): void {
        $stmt = $this->db->prepare(
            'INSERT INTO order_items (order_id, product_id, quantity, unit_price)
             VALUES (:order_id, :product_id, :quantity, :unit_price)'
        );
        $stmt->execute([
            ':order_id'   => $orderId,
            ':product_id' => $productId,
            ':quantity'   => $quantity,
            ':unit_price' => $unitPrice,
        ]);
    }

    public function decrementStock(int $productId, int $quantity): void {
        $stmt = $this->db->prepare(
            'UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty'
        );
        $stmt->execute([':qty' => $quantity, ':id' => $productId]);
    }

    public function restoreStock(int $orderId): void {
        $stmt = $this->db->prepare(
            'UPDATE products p
             JOIN order_items oi ON oi.product_id = p.id
             SET p.stock = p.stock + oi.quantity
             WHERE oi.order_id = :order_id'
        );
        $stmt->execute([':order_id' => $orderId]);
    }

    public function findAllWithClient(int $page = 1, int $perPage = 10): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            'SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             ORDER BY o.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int {
        return (int)$this->db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    public function search(string $query, int $page = 1, int $perPage = 10): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            'SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.id = :id OR u.name LIKE :name
             ORDER BY o.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':id', is_numeric($query) ? (int)$query : 0, PDO::PARAM_INT);
        $stmt->bindValue(':name', '%' . $query . '%');
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSearch(string $query): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id
             WHERE o.id = :id OR u.name LIKE :name'
        );
        $stmt->bindValue(':id', is_numeric($query) ? (int)$query : 0, PDO::PARAM_INT);
        $stmt->bindValue(':name', '%' . $query . '%');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function findItemsByOrderId(int $orderId, int $page = 1, int $perPage = 5): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            'SELECT oi.*, p.name AS product_name, p.description AS product_description, p.image_path
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countItemsByOrderId(int $orderId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = :order_id');
        $stmt->execute([':order_id' => $orderId]);
        return (int)$stmt->fetchColumn();
    }

    public function findAllItemsByOrderId(int $orderId): array {
        $stmt = $this->db->prepare(
            'SELECT oi.*, p.name AS product_name, p.image_path
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id ASC'
        );
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status): void {
        if ($status === 'enviado') {
            $stmt = $this->db->prepare(
                'UPDATE orders SET status = :status, sent_at = COALESCE(sent_at, NOW()) WHERE id = :id'
            );
        } elseif ($status === 'cancelado') {
            $stmt = $this->db->prepare(
                'UPDATE orders SET status = :status, cancelled_at = COALESCE(cancelled_at, NOW()) WHERE id = :id'
            );
        } else {
            $stmt = $this->db->prepare('UPDATE orders SET status = :status WHERE id = :id');
        }
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function findByUserId(int $userId, int $page = 1, int $perPage = 10): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            'SELECT o.*, u.name AS user_name
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.user_id = :user_id
             ORDER BY o.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByUserId(int $userId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }
}
