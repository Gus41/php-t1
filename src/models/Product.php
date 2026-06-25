<?php

class Product {
    private ?int $id;
    private ?int $supplierId;
    private string $name;
    private string $description;
    private ?string $category;
    private float $price;
    private int $stock;
    private string $sku;
    private string $status;
    private ?string $supplierName;
    private ?string $createdAt;
    private ?string $imagePath;

    public function __construct(?int $id, ?int $supplierId, string $name, string $description, ?string $category, float $price, int $stock, string $sku, string $status = 'ativo', ?string $supplierName = null, ?string $createdAt = null, ?string $imagePath = null) {
        $this->id = $id;
        $this->supplierId = $supplierId;
        $this->name = $name;
        $this->description = $description;
        $this->category = $category;
        $this->price = $price;
        $this->stock = $stock;
        $this->sku = $sku;
        $this->status = $status;
        $this->supplierName = $supplierName;
        $this->createdAt = $createdAt;
        $this->imagePath = $imagePath;
    }

    public static function fromArray(array $data): Product {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            isset($data['supplier_id']) ? (int)$data['supplier_id'] : null,
            $data['name'] ?? '',
            $data['description'] ?? '',
            $data['category'] ?? null,
            isset($data['price']) ? (float)$data['price'] : 0.0,
            isset($data['stock']) ? (int)$data['stock'] : 0,
            $data['sku'] ?? '',
            $data['status'] ?? 'ativo',
            $data['supplier_name'] ?? null,
            $data['created_at'] ?? null,
            $data['image_path'] ?? null,
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplierId,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'price' => $this->price,
            'stock' => $this->stock,
            'sku' => $this->sku,
            'status' => $this->status,
            'supplier_name' => $this->supplierName,
            'created_at' => $this->createdAt,
            'image_path' => $this->imagePath,
        ];
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function getSupplierId(): ?int {
        return $this->supplierId;
    }

    public function setSupplierId(?int $supplierId): void {
        $this->supplierId = $supplierId;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getCategory(): ?string {
        return $this->category;
    }

    public function getPrice(): float {
        return $this->price;
    }

    public function getStock(): int {
        return $this->stock;
    }

    public function getSku(): string {
        return $this->sku;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function setStatus(string $status): void {
        $this->status = $status;
    }

    public function getSupplierName(): ?string {
        return $this->supplierName;
    }

    public function getCreatedAt(): ?string {
        return $this->createdAt;
    }

    public function getImagePath(): ?string {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): void {
        $this->imagePath = $imagePath;
    }
}
