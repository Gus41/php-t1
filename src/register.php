<?php
require_once 'connection/db.php';
require_once 'services/SessionsService.php';
require_once 'dao/UserDAO.php';
require_once 'services/UserService.php';

$session = new SessionManager();

$message = '';
$roleOptions = [
    'cliente' => 'Cliente',
    'admin' => 'Admin',
];

$canAssignAdmin = $session->hasRole(['superuser']);

$userDAO = new UserDAO();
$userService = new UserService($userDAO);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestedRole = $_POST['role'] ?? 'cliente';

    if ($requestedRole === 'admin' && !$canAssignAdmin) {
        $message = 'Somente um SuperUser pode cadastrar um Admin.';
    } elseif ($requestedRole === 'superuser') {
        $message = 'O cadastro de SuperUser não é permitido pelo formulário público.';
    } else {
        $result = $userService->register($_POST, $requestedRole);
        $message = $result['message'];
    }
}

include 'partials/header.php';
?>
<main class="flex-grow justify-center items-center flex">
    <div class="container mx-auto p-6 bg-white shadow-sm rounded-md max-w-xl">
    <h1 class="text-2xl font-bold mb-4">Cadastro de Usuário</h1>
    <?php if ($message): ?>
        <div class="mb-4 p-3 rounded-md bg-slate-100 text-slate-800"><?= htmlspecialchars($message) ?></div>
    <?php endif ?>
    <form method="post" class="space-y-4">
        <label class="block">
            <span class="font-semibold">Nome</span>
            <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" class="w-full border rounded px-3 py-2" required>
        </label>
        <label class="block">
            <span class="font-semibold">Telefone</span>
            <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" class="w-full border rounded px-3 py-2" required>
        </label>
        <label class="block">
            <span class="font-semibold">E-mail</span>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full border rounded px-3 py-2" required>
        </label>
        <label class="block">
            <span class="font-semibold">Senha</span>
            <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
        </label>
        <label class="block">
            <span class="font-semibold">Endereço</span>
            <textarea name="address" class="w-full border rounded px-3 py-2" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
        </label>
        <label class="block">
            <span class="font-semibold">Tipo de usuário</span>
            <select name="role" class="w-full border rounded px-3 py-2">
                <option value="cliente">Cliente</option>
                <?php if ($canAssignAdmin): ?>
                    <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                <?php endif ?>
            </select>
        </label>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Cadastrar</button>
    </form>
    <p class="mt-4 text-sm text-slate-600">Se você não for SuperUser, o tipo será registrado como cliente. Apenas SuperUser pode cadastrar Admins.</p>
</div>
</main>
<?php include 'partials/footer.php';
