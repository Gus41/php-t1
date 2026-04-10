<?php
require_once __DIR__ . '/../services/SessionsService.php';
$session = new SessionManager();
$user = $session->currentUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'E-System' ?></title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="min-h-screen flex flex-col bg-slate-100 text-slate-900">
<header class="bg-white border-b border-slate-200 p-4">
  <div class="container mx-auto flex justify-between items-center">
    <a href="index.php" class="font-bold text-xl">E-System</a>
    <nav class="space-x-4">
      <?php if ($user): ?>
        <span class="text-slate-700">Olá, <?= htmlspecialchars($user['name']) ?></span>
        <a href="index.php" class="text-blue-600">Home</a>
        <?php if ($session->hasRole(['superuser'])): ?>
            <a href="create_admin.php" class="text-blue-600">Cadastrar Admin</a>
        <?php endif ?>
        <a href="logout.php" class="text-red-600">Sair</a>
      <?php else: ?>
        <a href="login.php" class="text-blue-600">Entrar</a>
        <a href="register.php" class="text-blue-600">Cadastrar</a>
      <?php endif ?>
    </nav>
  </div>
</header>
