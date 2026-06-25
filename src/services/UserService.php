<?php

require_once __DIR__ . '/../dao/UserDAO.php';
require_once __DIR__ . '/../dao/AddressDAO.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Address.php';

class UserService {
    private UserDAO $dao;
    private AddressDAO $addressDao;

    public function __construct(UserDAO $dao, AddressDAO $addressDao) {
        $this->dao = $dao;
        $this->addressDao = $addressDao;
    }

    public function register(array $data, string $requestedRole = 'cliente'): array {
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $street = trim($data['street'] ?? '');
        $complement = trim($data['complement'] ?? '');
        $neighborhood = trim($data['neighborhood'] ?? '');
        $city = trim($data['city'] ?? '');
        $state = trim($data['state'] ?? '');
        $zipCode = trim($data['zip_code'] ?? '');

        if ($name === '' || $phone === '' || $email === '' || $password === '' || $street === '' || $neighborhood === '' || $city === '' || $state === '' || $zipCode === '') {
            return ['success' => false, 'message' => 'Preencha todos os campos obrigatórios.'];
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

        $address = new Address(
            null,
            $street,
            $complement !== '' ? $complement : null,
            $city,
            $state,
            $neighborhood,
            $zipCode
        );

        $addressId = $this->addressDao->save($address);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = new User(null, $name, $phone, $email, $hash, $addressId, $address->getFullAddress(), $role);
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

    public function updateUser(User $actor, int $userId, array $data): array {
        if (!$actor->isSuperUser()) {
            return ['success' => false, 'message' => 'Acesso negado: somente SuperUser pode editar usuários.'];
        }

        $target = $this->dao->findById($userId);
        if (!$target) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        $name         = trim($data['name'] ?? '');
        $phone        = trim($data['phone'] ?? '');
        $email        = trim($data['email'] ?? '');
        $street       = trim($data['street'] ?? '');
        $complement   = trim($data['complement'] ?? '');
        $neighborhood = trim($data['neighborhood'] ?? '');
        $city         = trim($data['city'] ?? '');
        $state        = trim($data['state'] ?? '');
        $zipCode      = trim($data['zip_code'] ?? '');
        $password     = $data['password'] ?? '';

        if ($name === '' || $phone === '' || $email === '' || $street === '' || $neighborhood === '' || $city === '' || $state === '' || $zipCode === '') {
            return ['success' => false, 'message' => 'Preencha todos os campos obrigatórios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'E-mail inválido.'];
        }

        // Verifica e-mail duplicado (ignorando o próprio usuário)
        $existing = $this->dao->findByEmail($email);
        if ($existing && $existing->getId() !== $userId) {
            return ['success' => false, 'message' => 'Já existe outro usuário com esse e-mail.'];
        }

        // Atualiza ou cria endereço
        $addrId = $target->getAddressId();
        $addr   = new Address($addrId ?: null, $street, $complement !== '' ? $complement : null, $city, $state, $neighborhood, $zipCode);
        $addrId = $this->addressDao->save($addr);

        $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : $target->getPassword();

        $updated = new User($userId, $name, $phone, $email, $hash, $addrId, null, $target->getRole());
        $this->dao->save($updated);

        return ['success' => true, 'message' => 'Usuário atualizado com sucesso.'];
    }

    public function deleteUser(User $actor, int $userId): array {
        if (!$actor->isSuperUser()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }
        if ($actor->getId() === $userId) {
            return ['success' => false, 'message' => 'Você não pode excluir seu próprio usuário.'];
        }
        $target = $this->dao->findById($userId);
        if (!$target) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }
        $this->dao->delete($userId);
        return ['success' => true, 'message' => 'Usuário excluído com sucesso.'];
    }
}
