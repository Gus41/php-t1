<?php
require_once 'connection/db.php';
require_once 'services/SessionsService.php';
require_once 'dao/UserDAO.php';
require_once 'dao/AddressDAO.php';
require_once 'services/UserService.php';

$session = new SessionManager();

$message = '';
$roleOptions = [
    'cliente' => 'Cliente',
    'admin' => 'Admin',
];

$canAssignAdmin = $session->hasRole(['superuser']);

$userDAO = new UserDAO();
$addressDAO = new AddressDAO();
$userService = new UserService($userDAO, $addressDAO);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestedRole = $_POST['role'] ?? 'cliente';

    if ($requestedRole === 'admin' && !$canAssignAdmin) {
        $message = 'Somente um SuperUser pode cadastrar um Admin.';
    } elseif ($requestedRole === 'superuser') {
        $message = 'O cadastro de SuperUser não é permitido pelo formulário público.';
    } else {
        $result = $userService->register($_POST, $requestedRole);
        $message = $result['message'];
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after_auth'] ?? 'login.php';
            unset($_SESSION['redirect_after_auth']);
            header('Location: ' . $redirect . '?registered=1');
            exit;
        }
    }
}

$title = 'Criar conta — E-System';
include 'partials/header.php';
?>
<main class="flex-grow ml-page">
  <div class="ml-card" style="max-width:520px;margin:0 auto">

    <h1 class="ml-page-title">Criar conta.</h1>
    <p class="ml-page-sub">Preencha os dados abaixo para se cadastrar.</p>

    <?php if ($message): ?>
      <div class="ml-alert-error"><?= htmlspecialchars($message) ?></div>
    <?php endif ?>

    <form method="post" style="display:flex;flex-direction:column;gap:0">

      <p class="ml-section-title">Dados pessoais</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <?php
        $fields = [
          ['text','name','Nome','Seu nome'],
          ['text','phone','Telefone','(00) 00000-0000'],
          ['email','email','E-mail','seu@email.com'],
          ['password','password','Senha','••••••••'],
        ];
        foreach ($fields as [$type, $name, $label, $placeholder]): ?>
          <label style="display:flex;flex-direction:column">
            <span class="ml-label"><?= $label ?></span>
            <input type="<?= $type ?>" name="<?= $name ?>" class="ml-input"
              value="<?= $type !== 'password' ? htmlspecialchars($_POST[$name] ?? '') : '' ?>"
              placeholder="<?= $placeholder ?>" <?= in_array($name,['complement']) ? '' : 'required' ?>>
          </label>
        <?php endforeach ?>
      </div>

      <!-- Endereço -->
      <p class="ml-section-title">Endereço</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <label style="display:flex;flex-direction:column;grid-column:span 2">
          <span class="ml-label">Rua</span>
          <input type="text" name="street" class="ml-input" value="<?= htmlspecialchars($_POST['street'] ?? '') ?>" placeholder="Nome da rua" required>
        </label>
        <?php
        $addrFields = [
          ['complement','Complemento','Apto, bloco...', false],
          ['neighborhood','Bairro','Seu bairro', true],
          ['city','Cidade','Sua cidade', true],
          ['state','Estado','UF', true],
          ['zip_code','CEP','00000-000', true],
        ];
        foreach ($addrFields as [$name, $label, $ph, $req]): ?>
          <label style="display:flex;flex-direction:column">
            <span class="ml-label"><?= $label ?></span>
            <input type="text" name="<?= $name ?>" class="ml-input" value="<?= htmlspecialchars($_POST[$name] ?? '') ?>"
              placeholder="<?= $ph ?>" <?= $req ? 'required' : '' ?>>
          </label>
        <?php endforeach ?>
      </div>

      <!-- Acesso -->
      <p class="ml-section-title">Acesso</p>
      <label style="display:flex;flex-direction:column">
        <span class="ml-label">Tipo de usuário</span>
        <select name="role" class="ml-input">
          <option value="cliente">Cliente</option>
          <?php if ($canAssignAdmin): ?>
            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          <?php endif ?>
        </select>
      </label>

      <button type="submit" class="ml-btn ml-btn-primary" style="width:100%;margin-top:1.5rem">
        Cadastrar
      </button>
    </form>

    <p style="margin-top:1.25rem;font-size:12px;color:rgba(240,236,228,0.2);line-height:1.6">
      Se você não for SuperUser, o tipo será registrado como cliente. Apenas SuperUser pode cadastrar Admins.
    </p>
  </div>
</main>
 <?php include 'partials/footer.php';
