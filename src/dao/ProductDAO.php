<?php

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../models/Product.php';

class ProductDAO {
    private PDO $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    private function baseSelect(): string {
        return 'SELECT p.*, s.name AS supplier_name
                FROM products p
                LEFT JOIN suppliers s ON s.id = p.supplier_id';
    }

    public function findAllWithSupplier(): array {
        $stmt = $this->db->query($this->baseSelect() . ' ORDER BY p.id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllWithSupplierPaginated(int $page, int $perPage): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare($this->baseSelect() . ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int {
        return (int)$this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    public function countSearch(string $query): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM products WHERE name LIKE :query OR sku LIKE :query'
        );
        $stmt->execute([':query' => '%' . $query . '%']);
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?Product {
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE p.id = :id');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Product::fromArray($data) : null;
    }

    public function searchByName(string $name): array {
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE p.name LIKE :name ORDER BY p.id DESC');
        $stmt->execute([':name' => '%' . $name . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchByNameOrSku(string $query): array {
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE p.name LIKE :query OR p.sku LIKE :query ORDER BY p.id DESC');
        $stmt->execute([':query' => '%' . $query . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchByNameOrSkuPaginated(string $query, int $page, int $perPage): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE p.name LIKE :query OR p.sku LIKE :query ORDER BY p.id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':query', '%' . $query . '%');
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySku(string $sku): ?Product {
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE p.sku = :sku');
        $stmt->execute([':sku' => $sku]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Product::fromArray($data) : null;
    }

    public function save(Product $product): int {
        if ($product->getId() === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO products (supplier_id, name, description, category, price, stock, sku, status, image_path)
                 VALUES (:supplier_id, :name, :description, :category, :price, :stock, :sku, :status, :image_path)'
            );
            $stmt->execute([
                ':supplier_id' => $product->getSupplierId(),
                ':name'        => $product->getName(),
                ':description' => $product->getDescription(),
                ':category'    => $product->getCategory(),
                ':price'       => $product->getPrice(),
                ':stock'       => $product->getStock(),
                ':sku'         => $product->getSku(),
                ':status'      => $product->getStatus(),
                ':image_path'  => $product->getImagePath(),
            ]);
            return (int)$this->db->lastInsertId();
        } else {
            $stmt = $this->db->prepare(
                'UPDATE products
                 SET supplier_id = :supplier_id, name = :name, description = :description,
                     category = :category, price = :price, stock = :stock, sku = :sku,
                     status = :status, image_path = :image_path
                 WHERE id = :id'
            );
            $stmt->execute([
                ':supplier_id' => $product->getSupplierId(),
                ':name'        => $product->getName(),
                ':description' => $product->getDescription(),
                ':category'    => $product->getCategory(),
                ':price'       => $product->getPrice(),
                ':stock'       => $product->getStock(),
                ':sku'         => $product->getSku(),
                ':status'      => $product->getStatus(),
                ':image_path'  => $product->getImagePath(),
                ':id'          => $product->getId(),
            ]);
            return $product->getId();
        }
    }

    public function saveImages(int $productId, array $imagePaths): void {
        $this->db->prepare('DELETE FROM product_images WHERE product_id = :pid')
            ->execute([':pid' => $productId]);
        $ins = $this->db->prepare(
            'INSERT INTO product_images (product_id, image_path, sort_order) VALUES (:pid, :path, :ord)'
        );
        foreach ($imagePaths as $i => $path) {
            $ins->execute([':pid' => $productId, ':path' => $path, ':ord' => $i]);
        }
    }

    public function findImagesByProductId(int $productId): array {
        $stmt = $this->db->prepare(
            'SELECT image_path FROM product_images WHERE product_id = :pid ORDER BY sort_order ASC'
        );
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
