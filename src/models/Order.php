<?php

class Order {
    private ?int $id;
    private int $userId;
    private string $status;
    private float $total;
    private ?string $createdAt;
    private ?string $sentAt;
    private ?string $cancelledAt;
    private ?string $userName;
    private ?string $userEmail;

    public function __construct(
        ?int $id,
        int $userId,
        string $status,
        float $total,
        ?string $createdAt = null,
        ?string $sentAt = null,
        ?string $cancelledAt = null,
        ?string $userName = null,
        ?string $userEmail = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->status = $status;
        $this->total = $total;
        $this->createdAt = $createdAt;
        $this->sentAt = $sentAt;
        $this->cancelledAt = $cancelledAt;
        $this->userName = $userName;
        $this->userEmail = $userEmail;
    }

    public static function fromArray(array $data): Order {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            (int)($data['user_id'] ?? 0),
            $data['status'] ?? 'pendente',
            (float)($data['total'] ?? 0),
            $data['created_at'] ?? null,
            $data['sent_at'] ?? null,
            $data['cancelled_at'] ?? null,
            $data['user_name'] ?? null,
            $data['user_email'] ?? null,
        );
    }

    public function getId(): ?int       { return $this->id; }
    public function getUserId(): int    { return $this->userId; }
    public function getStatus(): string { return $this->status; }
    public function getTotal(): float   { return $this->total; }
    public function getCreatedAt(): ?string  { return $this->createdAt; }
    public function getSentAt(): ?string     { return $this->sentAt; }
    public function getCancelledAt(): ?string { return $this->cancelledAt; }
    public function getUserName(): ?string   { return $this->userName; }
    public function getUserEmail(): ?string  { return $this->userEmail; }
}
