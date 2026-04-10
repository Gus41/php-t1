<?php

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../models/Supplier.php';

class SupplierDAO {
    private PDO $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function findAllWithAddress(): array {
        $stmt = $this->db->query(
            'SELECT s.*, a.street, a.complement, a.city, a.state, a.neighborhood, a.zip_code
             FROM suppliers s
             LEFT JOIN addresses a ON a.id = s.address_id
             ORDER BY s.id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?Supplier {
        $stmt = $this->db->prepare('SELECT * FROM suppliers WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Supplier::fromArray($data) : null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool {
        $sql = 'SELECT id FROM suppliers WHERE email = :email';
        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
        }

        $stmt = $this->db->prepare($sql);
        $params = [':email' => $email];
        if ($excludeId !== null) {
            $params[':excludeId'] = $excludeId;
        }
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function save(Supplier $supplier): void {
        if ($supplier->getId() === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO suppliers (name, phone, email, address_id) VALUES (:name, :phone, :email, :address_id)'
            );
            $stmt->execute([
                ':name' => $supplier->getName(),
                ':phone' => $supplier->getPhone(),
                ':email' => $supplier->getEmail(),
                ':address_id' => $supplier->getAddressId(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE suppliers SET name = :name, phone = :phone, email = :email, address_id = :address_id WHERE id = :id'
            );
            $stmt->execute([
                ':name' => $supplier->getName(),
                ':phone' => $supplier->getPhone(),
                ':email' => $supplier->getEmail(),
                ':address_id' => $supplier->getAddressId(),
                ':id' => $supplier->getId(),
            ]);
        }
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM suppliers WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
