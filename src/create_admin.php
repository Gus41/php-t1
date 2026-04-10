<?php
require_once 'services/sessions.php';
require_once 'models/User.php';
require_once 'dao/UserDAO.php';
require_once 'services/UserService.php';

$session = new SessionManager();
$userData = $session->currentUser();
if (!$userData || !$session->hasRole(['superuser'])) {
    header('Location: index.php');
    exit;
}

$creator = User::fromArray($userData);
$userDAO = new UserDAO();
$userService = new UserService($userDAO);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $userService->createAdmin($creator, $_POST);
    $message = $result['message'];
}

include 'partials/header.php';
?>

<main class="flex-grow justify-center items-center flex">
    <div class="container mx-auto p-6 bg-white shadow-sm rounded-md max-w-xl">
        <h1 class="text-2xl font-bold mb-4">Cadastrar novo Admin</h1>
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
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Criar Admin</button>
        </form>
    </div>
</main>
<?php include 'partials/footer.php';
