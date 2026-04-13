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
    }
}

include 'partials/header.php';
?>
<main class="flex-grow flex justify-center px-6 py-12">
  <div style="width:100%;max-width:520px">

    <h1 style="font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;letter-spacing:-0.01em;margin:0 0 4px">Criar conta.</h1>
    <p style="font-size:13px;font-weight:300;color:rgba(240,236,228,0.35);margin:0 0 2.5rem">Preencha os dados abaixo para se cadastrar.</p>

    <?php if ($message): ?>
      <div style="background:rgba(226,75,74,0.08);border:1px solid rgba(226,75,74,0.2);border-radius:8px;padding:10px 14px;font-size:13px;color:#f09595;margin-bottom:1.5rem">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif ?>

    <form method="post" style="display:flex;flex-direction:column;gap:0">

      <!-- Dados pessoais -->
      <p style="font-size:10px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:rgba(240,236,228,0.25);margin:1.75rem 0 1rem;padding-bottom:8px;border-bottom:1px solid rgba(240,236,228,0.06)">Dados pessoais</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <?php
        $inputStyle = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:11px 13px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
        $labelStyle = "font-size:10.5px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.38);display:block;margin-bottom:7px";
        $fields = [
          ['text','name','Nome','Seu nome'],
          ['text','phone','Telefone','(00) 00000-0000'],
          ['email','email','E-mail','seu@email.com'],
          ['password','password','Senha','••••••••'],
        ];
        foreach ($fields as [$type, $name, $label, $placeholder]): ?>
          <label style="display:flex;flex-direction:column">
            <span style="<?= $labelStyle ?>"><?= $label ?></span>
            <input type="<?= $type ?>" name="<?= $name ?>"
              value="<?= $type !== 'password' ? htmlspecialchars($_POST[$name] ?? '') : '' ?>"
              placeholder="<?= $placeholder ?>"
              style="<?= $inputStyle ?>" <?= in_array($name,['complement']) ? '' : 'required' ?>>
          </label>
        <?php endforeach ?>
      </div>

      <!-- Endereço -->
      <p style="font-size:10px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:rgba(240,236,228,0.25);margin:1.75rem 0 1rem;padding-bottom:8px;border-bottom:1px solid rgba(240,236,228,0.06)">Endereço</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <label style="display:flex;flex-direction:column;grid-column:span 2">
          <span style="<?= $labelStyle ?>">Rua</span>
          <input type="text" name="street" value="<?= htmlspecialchars($_POST['street'] ?? '') ?>" placeholder="Nome da rua" style="<?= $inputStyle ?>" required>
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
            <span style="<?= $labelStyle ?>"><?= $label ?></span>
            <input type="text" name="<?= $name ?>" value="<?= htmlspecialchars($_POST[$name] ?? '') ?>"
              placeholder="<?= $ph ?>" style="<?= $inputStyle ?>" <?= $req ? 'required' : '' ?>>
          </label>
        <?php endforeach ?>
      </div>

      <!-- Acesso -->
      <p style="font-size:10px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:rgba(240,236,228,0.25);margin:1.75rem 0 1rem;padding-bottom:8px;border-bottom:1px solid rgba(240,236,228,0.06)">Acesso</p>
      <label style="display:flex;flex-direction:column">
        <span style="<?= $labelStyle ?>">Tipo de usuário</span>
        <select name="role" style="<?= $inputStyle ?>">
          <option value="cliente">Cliente</option>
          <?php if ($canAssignAdmin): ?>
            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          <?php endif ?>
        </select>
      </label>

      <button type="submit" style="width:100%;padding:13px;margin-top:1.75rem;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer">
        Cadastrar
      </button>
    </form>

    <p style="margin-top:1.25rem;font-size:12px;color:rgba(240,236,228,0.2);line-height:1.6">
      Se você não for SuperUser, o tipo será registrado como cliente. Apenas SuperUser pode cadastrar Admins.
    </p>
  </div>
</main>
 <?php include 'partials/footer.php';
