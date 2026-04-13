<?php

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../models/Address.php';

class AddressDAO {
    private PDO $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function findById(int $id): ?Address {
        $stmt = $this->db->prepare('SELECT * FROM addresses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Address::fromArray($data) : null;
    }

    public function findByUserId(int $userId): ?array {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.address_id FROM users u LEFT JOIN addresses a ON a.id = u.address_id WHERE u.id = :userId'
        );
        $stmt->execute([':userId' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data || $data['id'] === null) {
            return ['address' => null, 'address_id' => null];
        }

        return [
            'address' => Address::fromArray($data),
            'address_id' => isset($data['address_id']) ? (int)$data['address_id'] : null,
        ];
    }

    public function findAllWithOwners(): array {
        $stmt = $this->db->query(
            'SELECT a.*, u.id AS user_id, u.name AS user_name, u.email AS user_email, u.role AS user_role, s.id AS supplier_id, s.name AS supplier_name, s.email AS supplier_email
             FROM addresses a
             LEFT JOIN users u ON u.address_id = a.id
             LEFT JOIN suppliers s ON s.address_id = a.id'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(Address $address): int {
        if ($address->getId() === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO addresses (street, complement, city, state, neighborhood, zip_code) VALUES (:street, :complement, :city, :state, :neighborhood, :zip_code)'
            );
            $stmt->execute([
                ':street' => $address->getStreet(),
                ':complement' => $address->getComplement(),
                ':city' => $address->getCity(),
                ':state' => $address->getState(),
                ':neighborhood' => $address->getNeighborhood(),
                ':zip_code' => $address->getZipCode(),
            ]);
            return (int)$this->db->lastInsertId();
        }

        $stmt = $this->db->prepare(
            'UPDATE addresses SET street = :street, complement = :complement, city = :city, state = :state, neighborhood = :neighborhood, zip_code = :zip_code WHERE id = :id'
        );
        $stmt->execute([
            ':street' => $address->getStreet(),
            ':complement' => $address->getComplement(),
            ':city' => $address->getCity(),
            ':state' => $address->getState(),
            ':neighborhood' => $address->getNeighborhood(),
            ':zip_code' => $address->getZipCode(),
            ':id' => $address->getId(),
        ]);

        return $address->getId();
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM addresses WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
