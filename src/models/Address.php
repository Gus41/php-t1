<?php

class Address {
    private ?int $id;
    private string $street;
    private ?string $complement;
    private string $city;
    private string $state;
    private string $neighborhood;
    private string $zipCode;

    public function __construct(?int $id, string $street, ?string $complement, string $city, string $state, string $neighborhood, string $zipCode) {
        $this->id = $id;
        $this->street = $street;
        $this->complement = $complement;
        $this->city = $city;
        $this->state = $state;
        $this->neighborhood = $neighborhood;
        $this->zipCode = $zipCode;
    }

    public static function fromArray(array $data): Address {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            $data['street'] ?? '',
            $data['complement'] ?? null,
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['neighborhood'] ?? '',
            $data['zip_code'] ?? ''
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'street' => $this->street,
            'complement' => $this->complement,
            'city' => $this->city,
            'state' => $this->state,
            'neighborhood' => $this->neighborhood,
            'zip_code' => $this->zipCode,
        ];
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function getStreet(): string {
        return $this->street;
    }

    public function getComplement(): ?string {
        return $this->complement;
    }

    public function getCity(): string {
        return $this->city;
    }

    public function getState(): string {
        return $this->state;
    }

    public function getNeighborhood(): string {
        return $this->neighborhood;
    }

    public function getZipCode(): string {
        return $this->zipCode;
    }

    public function getFullAddress(): string {
        $parts = [
            $this->street,
            $this->complement,
            $this->neighborhood,
            $this->city,
            $this->state,
            $this->zipCode,
        ];

        return implode(', ', array_filter(array_map(fn($p) => trim($p ?? ''), $parts)));
    }
}
