<?php
require_once __DIR__ . '/../services/SessionsService.php';
$session = new SessionManager();
$user = $session->currentUser();
?>
<!-- partials/header.php -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'E-System' ?></title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <style>
    :root {
      --cream: #f0ece4;
      --bg: #0e0e0e;
      --border: rgba(240,236,228,0.1);
      --muted: rgba(240,236,228,0.38);
    }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--cream); }
  </style>
</head>
<body class="min-h-screen flex flex-col">

<header style="border-bottom:1px solid rgba(240,236,228,0.08); background:rgba(14,14,14,0.97); height:60px;"
        class="sticky top-0 z-50 flex items-center justify-between px-8">

  <a href="index.php" class="flex items-center gap-3 no-underline" style="color:var(--cream)">
    <div style="width:32px;height:32px;border:1px solid rgba(240,236,228,0.2);border-radius:8px;background:rgba(240,236,228,0.04);display:flex;align-items:center;justify-content:center;">
      <div style="width:10px;height:10px;background:var(--cream);border-radius:2px;opacity:0.7;"></div>
    </div>
    <span style="font-size:13px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;opacity:0.9">E-System</span>
  </a>

  <nav class="flex items-center gap-2">
    <?php if ($user): ?>
      <span style="font-size:12px;color:rgba(240,236,228,0.4);padding:6px 12px;border:1px solid rgba(240,236,228,0.08);border-radius:6px;letter-spacing:0.04em">
        <?= htmlspecialchars($user['name']) ?>
      </span>
      <?php
      $links = [['index.php','Home'],['address.php','Meu Endereço']];
      if ($session->hasRole(['superuser', 'admin'])):
        $links[] = ['products.php','Produtos'];
        $links[] = ['suppliers.php','Fornecedores'];
      endif;
      if ($session->hasRole(['superuser'])):
        $links[] = ['create_admin.php','Cadastrar Admin'];
        $links[] = ['addresses.php','Todos Endereços'];
      endif;
      $links[] = ['logout.php','Sair'];
      foreach ($links as [$href, $label]):
      ?>
        <a href="<?= $href ?>" style="font-size:12px;font-weight:400;letter-spacing:0.06em;color:rgba(240,236,228,0.5);text-decoration:none;padding:6px 12px;border-radius:6px;border:1px solid transparent;transition:all 0.2s;"
           onmouseover="this.style.color='#f0ece4';this.style.borderColor='rgba(240,236,228,0.12)';this.style.background='rgba(240,236,228,0.04)'"
           onmouseout="this.style.color='rgba(240,236,228,0.5)';this.style.borderColor='transparent';this.style.background='transparent'">
          <?= $label ?>
        </a>
      <?php endforeach ?>
    <?php else: ?>
      <a href="login.php"    style="...mesmo estilo...">Entrar</a>
      <a href="register.php" style="...mesmo estilo...">Cadastrar</a>
    <?php endif ?>
  </nav>
</header>