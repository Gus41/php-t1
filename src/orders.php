<?php
require_once 'services/SessionsService.php';
require_once 'dao/OrderDAO.php';

$session = new SessionManager();
$user    = $session->currentUser();

if (!$user) {
    header('Location: login.php');
    exit;
}

$isAdmin  = $session->hasRole(['superuser', 'admin']);
$isClient = $session->hasRole(['cliente']);

if (!$isAdmin && !$isClient) {
    header('Location: index.php');
    exit;
}

$orderDAO    = new OrderDAO();
$message     = '';
$messageType = 'success';

// Alterar status do pedido (somente admin)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId   = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];

    if (!in_array($newStatus, ['pendente', 'enviado', 'cancelado'], true)) {
        $message     = 'Status inválido.';
        $messageType = 'error';
    } else {
        $currentOrder = $orderDAO->findById($orderId);
        if ($newStatus === 'cancelado' && $currentOrder && $currentOrder['status'] !== 'cancelado') {
            $orderDAO->restoreStock($orderId);
        }
        $orderDAO->updateStatus($orderId, $newStatus);
        header('Location: orders.php?message=' . urlencode('Status atualizado com sucesso.'));
        exit;
    }
}

if (isset($_GET['message'])) {
    $message     = trim($_GET['message']);
    $messageType = 'success';
}

$perPage     = 10;
$page        = max(1, (int)($_GET['page'] ?? 1));
$searchQuery = trim($_GET['search'] ?? '');

if ($isClient) {
    // Cliente vê apenas seus próprios pedidos
    $orders     = $orderDAO->findByUserId((int)$user['id'], $page, $perPage);
    $totalItems = $orderDAO->countByUserId((int)$user['id']);
} elseif (!empty($searchQuery)) {
    $orders     = $orderDAO->search($searchQuery, $page, $perPage);
    $totalItems = $orderDAO->countSearch($searchQuery);
} else {
    $orders     = $orderDAO->findAllWithClient($page, $perPage);
    $totalItems = $orderDAO->countAll();
}
$totalPages = (int)ceil($totalItems / $perPage);

$statusLabel = [
    'pendente'  => ['rgba(251,191,36,0.12)',  '#fcd34d', 'Pendente'],
    'enviado'   => ['rgba(31,198,156,0.12)',  '#5eead4', 'Enviado'],
    'cancelado' => ['rgba(226,75,74,0.08)',   '#fda4af', 'Cancelado'],
];

$title = $isClient ? 'Meus Pedidos — E-System' : 'Pedidos — E-System';
include 'partials/header.php';

$inputStyle = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:10px 13px;font-size:13px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
?>
<main class="flex-grow flex justify-center px-6 py-12">
  <div style="width:100%;max-width:1100px">

    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap">
      <div>
        <h1 style="font-family:'DM Serif Display',serif;font-size:32px;font-weight:400;margin:0 0 4px"><?= $isClient ? 'Meus Pedidos.' : 'Pedidos.' ?></h1>
        <p style="font-size:13px;color:rgba(240,236,228,0.35);margin:0"><?= $totalItems ?> pedido<?= $totalItems !== 1 ? 's' : '' ?> encontrado<?= $totalItems !== 1 ? 's' : '' ?>.</p>
      </div>
      <?php if ($isAdmin): ?>
      <form method="get" style="display:flex;gap:8px;flex:1;max-width:420px">
        <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>"
          placeholder="Buscar por nº do pedido ou nome do cliente" style="<?= $inputStyle ?>;flex:1">
        <button type="submit" style="padding:10px 18px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;cursor:pointer;white-space:nowrap">Buscar</button>
        <?php if (!empty($searchQuery)): ?>
          <a href="orders.php" style="padding:10px 14px;border:1px solid rgba(240,236,228,0.1);border-radius:8px;font-size:12px;color:rgba(240,236,228,0.5);text-decoration:none">✕</a>
        <?php endif ?>
      </form>
      <?php endif ?>
    </div>

    <?php if ($message): ?>
      <div style="background:<?= $messageType === 'error' ? 'rgba(226,75,74,0.08)' : 'rgba(31,198,156,0.12)' ?>;border:1px solid <?= $messageType === 'error' ? 'rgba(226,75,74,0.2)' : 'rgba(31,198,156,0.22)' ?>;border-radius:8px;padding:12px 16px;font-size:13px;color:<?= $messageType === 'error' ? '#f09595' : '#d5f7ef' ?>;margin-bottom:20px">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif ?>

    <?php if (empty($orders)): ?>
      <div style="border:1px solid rgba(240,236,228,0.08);border-radius:14px;padding:56px;text-align:center;font-size:13px;color:rgba(240,236,228,0.3)">
        Nenhum pedido encontrado.
      </div>
    <?php else: ?>
      <div style="border:1px solid rgba(240,236,228,0.08);border-radius:14px;overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:13px;color:#f0ece4">
          <thead>
            <tr style="border-bottom:1px solid rgba(240,236,228,0.08)">
              <?php
                $cols = $isClient
                  ? ['Nº', 'Data', 'Total', 'Status', 'Ações']
                  : ['Nº', 'Cliente', 'Data', 'Total', 'Status', 'Enviado em', 'Cancelado em', 'Ações'];
                foreach ($cols as $col):
              ?>
                <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:rgba(240,236,228,0.28);white-space:nowrap"><?= $col ?></th>
              <?php endforeach ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $i => $order):
              $rowBg  = $i % 2 === 0 ? 'rgba(255,255,255,0)' : 'rgba(240,236,228,0.02)';
              $badge  = $statusLabel[$order['status']] ?? $statusLabel['pendente'];
              $date   = !empty($order['created_at'])   ? date('d/m/Y H:i', strtotime($order['created_at']))   : '—';
              $sentAt = !empty($order['sent_at'])       ? date('d/m/Y H:i', strtotime($order['sent_at']))       : '—';
              $canAt  = !empty($order['cancelled_at'])  ? date('d/m/Y H:i', strtotime($order['cancelled_at']))  : '—';
            ?>
              <tr style="border-bottom:1px solid rgba(240,236,228,0.05);background:<?= $rowBg ?>"
                  onmouseover="this.style.background='rgba(240,236,228,0.04)'"
                  onmouseout="this.style.background='<?= $rowBg ?>'">
                <td style="padding:14px 16px;color:rgba(240,236,228,0.4);font-weight:500">#<?= $order['id'] ?></td>
                <?php if ($isAdmin): ?>
                <td style="padding:14px 16px;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                  <a href="order_detail.php?order_id=<?= $order['id'] ?>" style="color:#f0ece4;text-decoration:none;font-weight:500"><?= htmlspecialchars($order['user_name']) ?></a>
                </td>
                <?php endif ?>
                <td style="padding:14px 16px;color:rgba(240,236,228,0.5);white-space:nowrap"><?= $date ?></td>
                <td style="padding:14px 16px;font-weight:600;white-space:nowrap">R$ <?= number_format((float)$order['total'], 2, ',', '.') ?></td>
                <td style="padding:14px 16px">
                  <span style="background:<?= $badge[0] ?>;color:<?= $badge[1] ?>;border:1px solid <?= $badge[1] ?>;border-radius:6px;padding:3px 10px;font-size:11px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;white-space:nowrap"><?= $badge[2] ?></span>
                </td>
                <?php if ($isAdmin): ?>
                <td style="padding:14px 16px;color:rgba(240,236,228,0.4);font-size:12px;white-space:nowrap"><?= $sentAt ?></td>
                <td style="padding:14px 16px;color:rgba(240,236,228,0.4);font-size:12px;white-space:nowrap"><?= $canAt ?></td>
                <?php endif ?>
                <td style="padding:14px 16px;white-space:nowrap">
                  <a href="order_detail.php?order_id=<?= $order['id'] ?>" style="font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#7dd3fc;text-decoration:none;margin-right:10px">Ver</a>
                  <?php if ($isAdmin): ?>
                  <form method="post" style="display:inline-flex;gap:6px;align-items:center;margin:0">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <select name="status" style="background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:6px;padding:4px 8px;font-size:11px;color:#f0ece4;outline:none">
                      <?php foreach (['pendente' => 'Pendente', 'enviado' => 'Enviado', 'cancelado' => 'Cancelado'] as $val => $lab): ?>
                        <option value="<?= $val ?>" <?= $order['status'] === $val ? 'selected' : '' ?>><?= $lab ?></option>
                      <?php endforeach ?>
                    </select>
                    <button type="submit" style="padding:4px 10px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer">OK</button>
                  </form>
                  <?php endif ?>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
        <div style="display:flex;gap:6px;justify-content:center;margin-top:20px;flex-wrap:wrap">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="orders.php?page=<?= $i ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>"
               style="padding:7px 12px;border-radius:6px;font-size:12px;text-decoration:none;<?= $i === $page ? 'background:#f0ece4;color:#0e0e0e;font-weight:600' : 'border:1px solid rgba(240,236,228,0.1);color:rgba(240,236,228,0.5)' ?>">
              <?= $i ?>
            </a>
          <?php endfor ?>
        </div>
      <?php endif ?>
    <?php endif ?>
  </div>
</main>
<?php include 'partials/footer.php'; ?>
