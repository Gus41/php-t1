<?php
require_once __DIR__ . '/../services/SessionsService.php';
$session = new SessionManager();
$user = $session->currentUser();

$inputStyle = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:11px 13px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
$labelStyle = "font-size:10.5px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.38);display:block;margin-bottom:7px";
$sectionStyle = "font-size:10px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:rgba(240,236,228,0.25);margin:1.75rem 0 1rem;padding-bottom:8px;border-bottom:1px solid rgba(240,236,228,0.06)";
$cardStyle = "border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03)";

$cartCount = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$showHeaderSearch = in_array($currentPage, ['index.php', 'cart.php'], true);
$headerSearchAction = $currentPage === 'cart.php' ? 'cart.php' : 'index.php';
$headerSearchValue = htmlspecialchars(trim($_GET['search'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'E-System' ?></title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/theme.css">
  <script src="assets/img-carousel.js" defer></script>
</head>
<body class="min-h-screen flex flex-col">

<div class="ml-topbar"></div>

<header class="ml-header">
  <div class="ml-header-inner">
    <a href="index.php" class="ml-logo">
      <div class="ml-logo-icon"><span></span></div>
      <span class="ml-logo-text">E-System</span>
    </a>

    <?php if ($showHeaderSearch && $user): ?>
      <form method="get" action="<?= $headerSearchAction ?>" class="ml-search hide-mobile">
        <input type="text" name="search" value="<?= $headerSearchValue ?>"
          placeholder="Buscar produtos por nome ou SKU...">
        <button type="submit">Buscar</button>
      </form>
    <?php endif ?>

    <nav class="ml-nav">
      <?php if ($user): ?>
        <span class="ml-user hide-mobile"><?= htmlspecialchars($user['name']) ?></span>
        <?php
        $links = [['index.php', 'Home'], ['address.php', 'Meu Endereço']];
        if ($session->hasRole(['cliente', 'admin', 'superuser'])):
          $links[] = ['cart.php', $cartCount > 0 ? "Carrinho ({$cartCount})" : 'Carrinho'];
        endif;
        if ($session->hasRole(['cliente'])):
          $links[] = ['orders.php', 'Meus Pedidos'];
        endif;
        if ($session->hasRole(['superuser', 'admin'])):
          $links[] = ['products.php', 'Produtos'];
          $links[] = ['suppliers.php', 'Fornecedores'];
          $links[] = ['orders.php', 'Pedidos'];
        endif;
        if ($session->hasRole(['superuser'])):
          $links[] = ['create_admin.php', 'Usuários'];
          $links[] = ['addresses.php', 'Endereços'];
        endif;
        $links[] = ['logout.php', 'Sair'];
        foreach ($links as [$href, $label]):
        ?>
          <a href="<?= $href ?>"><?= $label ?></a>
        <?php endforeach ?>
      <?php else: ?>
        <a href="login.php">Entrar</a>
        <a href="register.php" class="ml-btn ml-btn-primary" style="padding:8px 14px;font-size:11px">Cadastrar</a>
      <?php endif ?>
    </nav>
  </div>
</header>
