<?php

class Supplier {
    private ?int $id;
    private string $name;
    private string $phone;
    private string $email;
    private ?int $addressId;
    private ?string $createdAt;

    public function __construct(?int $id, string $name, string $phone, string $email, ?int $addressId = null, ?string $createdAt = null) {
        $this->id = $id;
        $this->name = $name;
        $this->phone = $phone;
        $this->email = $email;
        $this->addressId = $addressId;
        $this->createdAt = $createdAt;
    }

    public static function fromArray(array $data): Supplier {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            $data['name'] ?? '',
            $data['phone'] ?? '',
            $data['email'] ?? '',
            isset($data['address_id']) ? (int)$data['address_id'] : null,
            $data['created_at'] ?? null
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address_id' => $this->addressId,
            'created_at' => $this->createdAt,
        ];
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getPhone(): string {
        return $this->phone;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getAddressId(): ?int {
        return $this->addressId;
    }

    public function setAddressId(?int $addressId): void {
        $this->addressId = $addressId;
    }
}
