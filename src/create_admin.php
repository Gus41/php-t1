<?php
require_once 'services/SessionsService.php';
require_once 'models/User.php';
require_once 'dao/UserDAO.php';
require_once 'dao/AddressDAO.php';
require_once 'services/UserService.php';

$session = new SessionManager();
$userData = $session->currentUser();
if (!$userData || !$session->hasRole(['superuser'])) {
    header('Location: index.php');
    exit;
}

$creator    = User::fromArray($userData);
$userDAO    = new UserDAO();
$addressDAO = new AddressDAO();
$userService = new UserService($userDAO, $addressDAO);
$message    = '';

$perPage     = 10;
$page        = max(1, (int)($_GET['page'] ?? 1));
$searchQuery = trim($_GET['search'] ?? '');

if (!empty($searchQuery)) {
    $totalUsers = $userDAO->countSearch($searchQuery);
} else {
    $totalUsers = $userDAO->countAll();
}
$totalPages = (int)ceil($totalUsers / $perPage);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $userService->createAdmin($creator, $_POST);
    $message = $result['message'];
}

include 'partials/header.php';
?>
<main class="flex-grow flex justify-center px-6 py-12">
  <div style="width:100%;max-width:520px">

    <h1 style="font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;letter-spacing:-0.01em;margin:0 0 4px">Cadastrar Admin.</h1>
    <p style="font-size:13px;font-weight:300;color:rgba(240,236,228,0.35);margin:0 0 2.5rem">Preencha os dados para criar um novo administrador.</p>

    <?php if ($message): ?>
      <div style="background:rgba(226,75,74,0.08);border:1px solid rgba(226,75,74,0.2);border-radius:8px;padding:10px 14px;font-size:13px;color:#f09595;margin-bottom:1.5rem">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif ?>

    <?php
      $inputStyle = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:11px 13px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
      $labelStyle = "font-size:10.5px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.38);display:block;margin-bottom:7px";
      $sectionStyle = "font-size:10px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:rgba(240,236,228,0.25);margin:1.75rem 0 1rem;padding-bottom:8px;border-bottom:1px solid rgba(240,236,228,0.06)";
    ?>

    <form method="post" style="display:flex;flex-direction:column">

      <p style="<?= $sectionStyle ?>">Dados pessoais</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Nome</span>
          <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Nome completo" style="<?= $inputStyle ?>" required>
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Telefone</span>
          <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="(00) 00000-0000" style="<?= $inputStyle ?>" required>
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">E-mail</span>
          <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@email.com" style="<?= $inputStyle ?>" required>
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Senha</span>
          <input type="password" name="password" placeholder="••••••••" style="<?= $inputStyle ?>" required>
        </label>

      </div>

      <p style="<?= $sectionStyle ?>">Endereço</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

        <label style="display:flex;flex-direction:column;grid-column:span 2">
          <span style="<?= $labelStyle ?>">Rua</span>
          <input type="text" name="street" value="<?= htmlspecialchars($_POST['street'] ?? '') ?>" placeholder="Nome da rua" style="<?= $inputStyle ?>" required>
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Complemento</span>
          <input type="text" name="complement" value="<?= htmlspecialchars($_POST['complement'] ?? '') ?>" placeholder="Apto, bloco..." style="<?= $inputStyle ?>">
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Bairro</span>
          <input type="text" name="neighborhood" value="<?= htmlspecialchars($_POST['neighborhood'] ?? '') ?>" placeholder="Seu bairro" style="<?= $inputStyle ?>" required>
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Cidade</span>
          <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" placeholder="Sua cidade" style="<?= $inputStyle ?>" required>
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Estado</span>
          <input type="text" name="state" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>" placeholder="UF" style="<?= $inputStyle ?>" required>
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">CEP</span>
          <input type="text" name="zip_code" value="<?= htmlspecialchars($_POST['zip_code'] ?? '') ?>" placeholder="00000-000" style="<?= $inputStyle ?>" required>
        </label>

      </div>

      <button type="submit" style="width:100%;padding:13px;margin-top:1.75rem;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer">
        Criar Admin
      </button>

    </form>
  </div>
</main>

<?php
$users = !empty($searchQuery)
    ? $userDAO->searchByNameOrId($searchQuery, $page, $perPage)
    : $userDAO->findAllPaginated($page, $perPage);
$roleBadge = ['cliente' => ['rgba(240,236,228,0.07)', 'rgba(240,236,228,0.5)', 'Cliente'], 'admin' => ['rgba(96,165,250,0.12)', '#93c5fd', 'Admin'], 'superuser' => ['rgba(167,139,250,0.12)', '#c4b5fd', 'Superuser']];
?>
<section class="flex justify-center px-6 pb-12">
  <div style="width:100%;max-width:860px">
    <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03)">
      <h2 style="font-family:'DM Serif Display',serif;font-size:24px;font-weight:400;margin:0 0 4px">Usuários.</h2>

      <form method="get" action="create_admin.php" style="display:flex;gap:8px;margin-bottom:14px">
        <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>"
          placeholder="Buscar por código ou nome..."
          style="flex:1;padding:9px 14px;border-radius:8px;border:1px solid rgba(240,236,228,0.14);background:rgba(255,255,255,0.04);color:#f0ece4;font-size:13px;font-family:'DM Sans',sans-serif;outline:none">
        <button type="submit" style="padding:9px 16px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer">Buscar</button>
        <?php if (!empty($searchQuery)): ?>
          <a href="create_admin.php" style="padding:9px 14px;border:1px solid rgba(240,236,228,0.12);border-radius:8px;font-size:12px;color:#f0ece4;text-decoration:none;display:flex;align-items:center">✕</a>
        <?php endif ?>
      </form>

      <p style="font-size:13px;color:rgba(240,236,228,0.35);margin:0 0 20px">
        <?= $totalUsers ?> usuário<?= $totalUsers !== 1 ? 's' : '' ?><?= !empty($searchQuery) ? ' encontrado' . ($totalUsers !== 1 ? 's' : '') . ' para "' . htmlspecialchars($searchQuery) . '"' : ' cadastrado' . ($totalUsers !== 1 ? 's' : '') ?>.
      </p>

      <div style="overflow-x:auto;border:1px solid rgba(240,236,228,0.08);border-radius:10px">
        <table style="width:100%;border-collapse:collapse;font-size:13px;color:#f0ece4">
          <thead>
            <tr style="border-bottom:1px solid rgba(240,236,228,0.08)">
              <?php foreach (['#','Nome','E-mail','Telefone','Papel','Criado em'] as $col): ?>
                <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:rgba(240,236,228,0.28);white-space:nowrap"><?= $col ?></th>
              <?php endforeach ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u):
              $rowBg = $i % 2 === 0 ? 'transparent' : 'rgba(240,236,228,0.02)';
              $badge = $roleBadge[$u['role']] ?? $roleBadge['cliente'];
              $createdAt = !empty($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '—';
            ?>
              <tr style="border-bottom:1px solid rgba(240,236,228,0.05);background:<?= $rowBg ?>"
                  onmouseover="this.style.background='rgba(240,236,228,0.05)'"
                  onmouseout="this.style.background='<?= $rowBg ?>'">
                <td style="padding:12px 16px;color:rgba(240,236,228,0.3);font-size:12px">#<?= $u['id'] ?></td>
                <td style="padding:12px 16px;font-weight:500"><?= htmlspecialchars($u['name']) ?></td>
                <td style="padding:12px 16px;color:rgba(240,236,228,0.55)"><?= htmlspecialchars($u['email']) ?></td>
                <td style="padding:12px 16px;color:rgba(240,236,228,0.55);white-space:nowrap"><?= htmlspecialchars($u['phone']) ?></td>
                <td style="padding:12px 16px">
                  <span style="background:<?= $badge[0] ?>;color:<?= $badge[1] ?>;border:1px solid <?= $badge[1] ?>;border-radius:6px;padding:3px 10px;font-size:11px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase">
                    <?= $badge[2] ?>
                  </span>
                </td>
                <td style="padding:12px 16px;color:rgba(240,236,228,0.4);white-space:nowrap"><?= $createdAt ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
        <div style="display:flex;gap:6px;justify-content:center;margin-top:18px;flex-wrap:wrap">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="create_admin.php?page=<?= $i ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '' ?>"
               style="padding:7px 12px;border-radius:6px;font-size:12px;text-decoration:none;<?= $i === $page ? 'background:#f0ece4;color:#0e0e0e;font-weight:600' : 'border:1px solid rgba(240,236,228,0.1);color:rgba(240,236,228,0.5)' ?>">
              <?= $i ?>
            </a>
          <?php endfor ?>
        </div>
      <?php endif ?>
    </div>
  </div>
</section>
<?php include 'partials/footer.php'; ?>
