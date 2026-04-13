<?php

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../models/User.php';

class UserDAO {
    private PDO $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->db->prepare(
            'SELECT u.*, CONCAT_WS(", ", a.street, a.complement, a.neighborhood, a.city, a.state, a.zip_code) AS address
             FROM users u
             LEFT JOIN addresses a ON a.id = u.address_id
             WHERE u.email = :email'
        );
        $stmt->execute([':email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? User::fromArray($data) : null;
    }

    public function findById(int $id): ?User {
        $stmt = $this->db->prepare(
            'SELECT u.*, CONCAT_WS(", ", a.street, a.complement, a.neighborhood, a.city, a.state, a.zip_code) AS address
             FROM users u
             LEFT JOIN addresses a ON a.id = u.address_id
             WHERE u.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? User::fromArray($data) : null;
    }

    public function emailExists(string $email): bool {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function save(User $user): void {
        if ($user->getId() === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO users (name, phone, email, password, address_id, role) VALUES (:name, :phone, :email, :password, :address_id, :role)'
            );
            $stmt->execute([
                ':name' => $user->getName(),
                ':phone' => $user->getPhone(),
                ':email' => $user->getEmail(),
                ':password' => $user->getPassword(),
                ':address_id' => $user->getAddressId(),
                ':role' => $user->getRole(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE users SET name = :name, phone = :phone, email = :email, password = :password, address_id = :address_id, role = :role WHERE id = :id'
            );
            $stmt->execute([
                ':name' => $user->getName(),
                ':phone' => $user->getPhone(),
                ':email' => $user->getEmail(),
                ':password' => $user->getPassword(),
                ':address_id' => $user->getAddressId(),
                ':role' => $user->getRole(),
                ':id' => $user->getId(),
            ]);
        }
    }

    public function updateAddressId(int $userId, ?int $addressId): void {
        $stmt = $this->db->prepare('UPDATE users SET address_id = :address_id WHERE id = :id');
        $stmt->execute([
            ':address_id' => $addressId,
            ':id' => $userId,
        ]);
    }
}
