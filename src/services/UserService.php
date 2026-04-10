<?php

require_once __DIR__ . '/../dao/UserDAO.php';
require_once __DIR__ . '/../models/User.php';

class UserService {
    private UserDAO $dao;

    public function __construct(UserDAO $dao) {
        $this->dao = $dao;
    }

    public function register(array $data, string $requestedRole = 'cliente'): array {
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $address = trim($data['address'] ?? '');

        if ($name === '' || $phone === '' || $email === '' || $password === '' || $address === '') {
            return ['success' => false, 'message' => 'Preencha todos os campos.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'E-mail inválido.'];
        }

        if ($this->dao->emailExists($email)) {
            return ['success' => false, 'message' => 'Já existe um usuário cadastrado com esse e-mail.'];
        }

        $role = $requestedRole;
        if (!in_array($role, ['cliente', 'admin'], true)) {
            $role = 'cliente';
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = new User(null, $name, $phone, $email, $hash, $address, $role);
        $this->dao->save($user);

        return ['success' => true, 'message' => 'Conta criada com sucesso! Agora faça login.'];
    }

    public function createAdmin(User $creator, array $data): array {
        if (!$creator->isSuperUser()) {
            return ['success' => false, 'message' => 'Acesso negado: somente SuperUser pode cadastrar Admins.'];
        }

        $data['role'] = 'admin';
        return $this->register($data, 'admin');
    }
}
