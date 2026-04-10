<?php

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../models/User.php';

class UserDAO {
    private PDO $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
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
                'INSERT INTO users (name, phone, email, password, address, role) VALUES (:name, :phone, :email, :password, :address, :role)'
            );
            $stmt->execute([
                ':name' => $user->getName(),
                ':phone' => $user->getPhone(),
                ':email' => $user->getEmail(),
                ':password' => $user->getPassword(),
                ':address' => $user->getAddress(),
                ':role' => $user->getRole(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE users SET name = :name, phone = :phone, email = :email, password = :password, address = :address, role = :role WHERE id = :id'
            );
            $stmt->execute([
                ':name' => $user->getName(),
                ':phone' => $user->getPhone(),
                ':email' => $user->getEmail(),
                ':password' => $user->getPassword(),
                ':address' => $user->getAddress(),
                ':role' => $user->getRole(),
                ':id' => $user->getId(),
            ]);
        }
    }
}
