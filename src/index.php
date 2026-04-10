<?php
require_once 'services/SessionsService.php';
$session = new SessionManager();
$user = $session->currentUser();
include 'partials/header.php';
?>
<main class="flex-grow">
    <div class="container mx-auto p-6 bg-white shadow-sm rounded-md max-w-2xl">
        <h1 class="text-2xl font-bold mb-4">Dashboard</h1>
    <?php if ($user): ?>
        <div class="space-y-3">
            <div class="p-4 bg-slate-50 rounded-md border">
                <p><strong>Nome:</strong> <?= htmlspecialchars($user['name']) ?></p>
                <p><strong>E-mail:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Telefone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
                <p><strong>Endereço:</strong> <?= nl2br(htmlspecialchars($user['address'])) ?></p>
                <p><strong>Tipo de usuário:</strong> <?= htmlspecialchars(ucfirst($user['role'])) ?></p>
            </div>
            <?php if ($user['role'] === 'superuser'): ?>
                <div class="p-4 bg-emerald-50 rounded-md border border-emerald-200">
                    <strong>SuperUser</strong>: você pode cadastrar Admins usando o formulário de cadastro.
                    <div class="mt-2">
                        <a href="create_admin.php" class="text-blue-600">Clique aqui para criar um Admin</a>
                    </div>
                </div>
            <?php elseif ($user['role'] === 'admin'): ?>
                <div class="p-4 bg-yellow-50 rounded-md border border-yellow-200">
                    <strong>Admin</strong>: você tem permissões administrativas no sistema.
                </div>
            <?php else: ?>
                <div class="p-4 bg-slate-100 rounded-md border border-slate-200">
                    <strong>Cliente</strong>: perfil padrão de uso.
                </div>
            <?php endif ?>
        </div>
    <?php else: ?>
        <p class="mb-4">Você não está logado. Faça login ou registre-se.</p>
        <div class="space-x-3">
            <a class="bg-blue-600 text-white px-4 py-2 rounded" href="login.php">Login</a>
            <a class="bg-slate-600 text-white px-4 py-2 rounded" href="register.php">Cadastrar</a>
        </div>
    <?php endif ?>
    </div>
</main>
<?php include 'partials/footer.php';
