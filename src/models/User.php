<?php

class User {
    private ?int $id;
    private string $name;
    private string $phone;
    private string $email;
    private string $password;
    private ?int $addressId;
    private string $address;
    private string $role;
    private ?string $createdAt;

    public function __construct(?int $id, string $name, string $phone, string $email, string $password, ?int $addressId, string $address, string $role = 'cliente', ?string $createdAt = null) {
        $this->id = $id;
        $this->name = $name;
        $this->phone = $phone;
        $this->email = $email;
        $this->password = $password;
        $this->addressId = $addressId;
        $this->address = $address;
        $this->role = $role;
        $this->createdAt = $createdAt;
    }

    public static function fromArray(array $data): User {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            $data['name'] ?? '',
            $data['phone'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? '',
            isset($data['address_id']) ? (int)$data['address_id'] : null,
            $data['address'] ?? '',
            $data['role'] ?? 'cliente',
            $data['created_at'] ?? null
        );
    }

    public function toArray(bool $includePassword = false): array {
        $result = [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address_id' => $this->addressId,
            'address' => $this->address,
            'role' => $this->role,
            'created_at' => $this->createdAt,
        ];

        if ($includePassword) {
            $result['password'] = $this->password;
        }

        return $result;
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

    public function getPassword(): string {
        return $this->password;
    }

    public function getAddressId(): ?int {
        return $this->addressId;
    }

    public function setAddressId(?int $addressId): void {
        $this->addressId = $addressId;
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function getRole(): string {
        return $this->role;
    }

    public function isSuperUser(): bool {
        return $this->role === 'superuser';
    }

    public function isAdmin(): bool {
        return $this->role === 'admin';
    }

    public function isCliente(): bool {
        return $this->role === 'cliente';
    }
}
