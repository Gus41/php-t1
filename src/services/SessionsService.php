<?php

class SessionManager {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function loginUser(array $user): void {
        unset($user['password']);
        $_SESSION['user'] = $user;
    }

    public function logoutUser(): void {
        session_unset();
        session_destroy();
    }

    public function currentUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public function hasRole(array $roles): bool {
        $user = $this->currentUser();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    public function isLoggedIn(): bool {
        return $this->currentUser() !== null;
    }
}

// Funções globais para compatibilidade
function startSession(): void {
    new SessionManager();
}

function loginUser(array $user): void {
    $session = new SessionManager();
    $session->loginUser($user);
}

function logoutUser(): void {
    $session = new SessionManager();
    $session->logoutUser();
}

function currentUser(): ?array {
    $session = new SessionManager();
    return $session->currentUser();
}

function hasRole(array $roles): bool {
    $session = new SessionManager();
    return $session->hasRole($roles);
}

