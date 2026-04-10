<?php
require_once 'connection/db.php';
require_once 'dao/UserDAO.php';
require_once 'services/SessionsService.php';

$session = new SessionManager();
$message = '';
$userDAO = new UserDAO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = 'Preencha e-mail e senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'E-mail inválido.';
    } else {
        try {
            $user = $userDAO->findByEmail($email);
            if (!$user) {
                $message = 'E-mail ou senha incorretos.';
            } else {
                $storedPassword = $user->getPassword();
                if ($storedPassword === $password || password_verify($password, $storedPassword)) {
                    $session->loginUser($user->toArray());
                    header('Location: index.php');
                    exit;
                }
                $message = 'E-mail ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao entrar: ' . $e->getMessage();
        }
    }
}

include 'partials/header.php';
?>
<main class="flex-grow justify-center items-center flex">
    <div class="container mx-auto p-6 bg-white shadow-sm rounded-md max-w-md">
    <h1 class="text-2xl font-bold mb-4">Login</h1>
    <?php if ($message): ?>
        <div class="mb-4 p-3 rounded-md bg-slate-100 text-slate-800"><?= htmlspecialchars($message) ?></div>
    <?php endif ?>
    <form method="post" class="space-y-4">
        <label class="block">
            <span class="font-semibold">E-mail</span>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full border rounded px-3 py-2" required>
        </label>
        <label class="block">
            <span class="font-semibold">Senha</span>
            <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
        </label>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Entrar</button>
    </form>
    <p class="mt-4 text-sm text-slate-600">Não tem conta? <a href="register.php" class="text-blue-600">Cadastre-se</a></p>
    <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-slate-700">
        <p class="font-semibold">SuperUser seeded</p>
        <p class="text-sm">E-mail: <span class="font-medium">superuser@example.com</span></p>
        <p class="text-sm">Senha: <span class="font-medium">superpass123</span></p>
        <p class="text-xs text-slate-500 mt-2">Este SuperUser já é criado no init.sql.</p>
    </div>
</div>
</main>
<?php include 'partials/footer.php';
