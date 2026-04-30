<?php

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../models/Product.php';

class ProductDAO {
    private PDO $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function findAllWithSupplier(): array {
        $stmt = $this->db->query(
            'SELECT p.*, s.name AS supplier_name
             FROM products p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             ORDER BY p.id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?Product {
        $stmt = $this->db->prepare(
            'SELECT p.*, s.name AS supplier_name
             FROM products p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Product::fromArray($data) : null;
    }

    public function searchByName(string $name): array {
        $stmt = $this->db->prepare(
            'SELECT p.*, s.name AS supplier_name
             FROM products p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.name LIKE :name
             ORDER BY p.id DESC'
        );
        $stmt->execute([':name' => '%' . $name . '%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchByNameOrSku(string $query): array {
        $stmt = $this->db->prepare(
            'SELECT p.*, s.name AS supplier_name
             FROM products p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.name LIKE :query OR p.sku LIKE :query
             ORDER BY p.id DESC'
        );
        $stmt->execute([':query' => '%' . $query . '%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySku(string $sku): ?Product {
        $stmt = $this->db->prepare(
            'SELECT p.*, s.name AS supplier_name
             FROM products p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.sku = :sku'
        );
        $stmt->execute([':sku' => $sku]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? Product::fromArray($data) : null;
    }

    public function save(Product $product): void {
        if ($product->getId() === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO products (supplier_id, name, description, category, price, stock, sku, status)
                 VALUES (:supplier_id, :name, :description, :category, :price, :stock, :sku, :status)'
            );
            $stmt->execute([
                ':supplier_id' => $product->getSupplierId(),
                ':name' => $product->getName(),
                ':description' => $product->getDescription(),
                ':category' => $product->getCategory(),
                ':price' => $product->getPrice(),
                ':stock' => $product->getStock(),
                ':sku' => $product->getSku(),
                ':status' => $product->getStatus(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE products
                 SET supplier_id = :supplier_id,
                     name = :name,
                     description = :description,
                     category = :category,
                     price = :price,
                     stock = :stock,
                     sku = :sku,
                     status = :status
                 WHERE id = :id'
            );
            $stmt->execute([
                ':supplier_id' => $product->getSupplierId(),
                ':name' => $product->getName(),
                ':description' => $product->getDescription(),
                ':category' => $product->getCategory(),
                ':price' => $product->getPrice(),
                ':stock' => $product->getStock(),
                ':sku' => $product->getSku(),
                ':status' => $product->getStatus(),
                ':id' => $product->getId(),
            ]);
        }
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
