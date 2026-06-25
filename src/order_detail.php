<?php
require_once 'services/SessionsService.php';
require_once 'dao/OrderDAO.php';

$session  = new SessionManager();
$user     = $session->currentUser();
$orderDAO = new OrderDAO();

$orderId = (int)($_GET['order_id'] ?? 0);

if (!$orderId) {
    header('Location: index.php');
    exit;
}

$order = $orderDAO->findById($orderId);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Controle de acesso: admin/superuser OU o próprio cliente
$isAdmin = $user && in_array($user['role'] ?? '', ['admin', 'superuser'], true);
$isOwner = $user && (int)($user['id'] ?? 0) === (int)$order['user_id'];

if (!$isAdmin && !$isOwner) {
    if (!$user) {
        $_SESSION['redirect_after_auth'] = 'order_detail.php?order_id=' . $orderId;
        header('Location: login.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// ── AJAX: retorna itens paginados em JSON ─────────────────────────────────────
$action = $_GET['action'] ?? '';
if ($action === 'items') {
    header('Content-Type: application/json');
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 5;
    $items   = $orderDAO->findItemsByOrderId($orderId, $page, $perPage);
    $total   = $orderDAO->countItemsByOrderId($orderId);
    echo json_encode([
        'items'       => $items,
        'total'       => $total,
        'page'        => $page,
        'total_pages' => (int)ceil($total / $perPage),
    ]);
    exit;
}

// Carrossel: todos os itens com imagem
$allItems = $orderDAO->findAllItemsByOrderId($orderId);

$statusLabel = [
    'pendente'  => ['rgba(251,191,36,0.12)',  '#fcd34d', 'Pendente'],
    'enviado'   => ['rgba(31,198,156,0.12)',  '#5eead4', 'Enviado'],
    'cancelado' => ['rgba(226,75,74,0.08)',   '#fda4af', 'Cancelado'],
];
$badge = $statusLabel[$order['status']] ?? $statusLabel['pendente'];

$isNew  = isset($_GET['new']);
if ($isNew) {
    $_SESSION['flash'] = [
        'type'    => 'success',
        'message' => '🎉 Pedido #' . $orderId . ' realizado com sucesso! Acompanhe o status abaixo.',
    ];
}
$title  = 'Pedido #' . $orderId . ' — E-System';
include 'partials/header.php';
?>
<main class="flex-grow px-6 py-10" style="max-width:960px;margin:0 auto;width:100%">

  <?php if ($isNew): ?>
    <div style="background:rgba(31,198,156,0.12);border:1px solid rgba(31,198,156,0.3);border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:14px">
      <div style="width:36px;height:36px;border-radius:50%;background:rgba(31,198,156,0.2);border:1px solid rgba(31,198,156,0.4);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">✓</div>
      <div>
        <p style="font-size:14px;font-weight:600;color:#d5f7ef;margin:0 0 2px">Pedido realizado com sucesso!</p>
        <p style="font-size:12px;color:rgba(213,247,239,0.65);margin:0">Seu pedido #<?= $orderId ?> foi registrado. Acompanhe o status abaixo.</p>
      </div>
    </div>
  <?php endif ?>

  <!-- ── CABEÇALHO (MESTRE) ──────────────────────────────────────────────── -->
  <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03);margin-bottom:24px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:20px">
      <div>
        <p style="font-size:11px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:rgba(240,236,228,0.3);margin:0 0 6px">Pedido</p>
        <h1 style="font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;margin:0 0 4px">#<?= $orderId ?>.</h1>
        <span style="background:<?= $badge[0] ?>;color:<?= $badge[1] ?>;border:1px solid <?= $badge[1] ?>;border-radius:6px;padding:4px 12px;font-size:12px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase">
          <?= $badge[2] ?>
        </span>
      </div>
      <?php if ($isAdmin): ?>
        <a href="orders.php" style="font-size:12px;color:rgba(240,236,228,0.4);text-decoration:none;border:1px solid rgba(240,236,228,0.1);padding:8px 14px;border-radius:8px">← Todos os pedidos</a>
      <?php else: ?>
        <a href="index.php" style="font-size:12px;color:rgba(240,236,228,0.4);text-decoration:none;border:1px solid rgba(240,236,228,0.1);padding:8px 14px;border-radius:8px">← Início</a>
      <?php endif ?>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px">
      <?php
      $shippingCost = (float)($order['shipping_cost'] ?? 0);
      $subtotal     = (float)$order['total'] - $shippingCost;
      $fields = [
        'Cliente'      => htmlspecialchars($order['user_name'] ?? '—'),
        'E-mail'       => htmlspecialchars($order['user_email'] ?? '—'),
        'Data'         => !empty($order['created_at'])  ? date('d/m/Y H:i', strtotime($order['created_at']))  : '—',
        'Pagamento'    => !empty($order['payment_method']) ? htmlspecialchars($order['payment_method']) : '—',
        'Entrega'      => !empty($order['shipping_method']) ? htmlspecialchars($order['shipping_method']) : '—',
        'Frete'        => $shippingCost > 0 ? 'R$ ' . number_format($shippingCost, 2, ',', '.') : 'Grátis',
        'Subtotal'     => 'R$ ' . number_format($subtotal, 2, ',', '.'),
        'Total'        => 'R$ ' . number_format((float)$order['total'], 2, ',', '.'),
        'Enviado em'   => !empty($order['sent_at'])     ? date('d/m/Y H:i', strtotime($order['sent_at']))     : '—',
        'Cancelado em' => !empty($order['cancelled_at'])? date('d/m/Y H:i', strtotime($order['cancelled_at'])): '—',
      ];
      foreach ($fields as $label => $val): ?>
        <div>
          <p style="font-size:10px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.3);margin:0 0 4px"><?= $label ?></p>
          <p style="font-size:14px;font-weight:500;margin:0"><?= $val ?></p>
        </div>
      <?php endforeach ?>
    </div>
  </div>

  <!-- ── CARROSSEL DE FOTOS ─────────────────────────────────────────────── -->
  <?php $itemsWithImg = array_values(array_filter($allItems, fn($it) => !empty($it['image_path']))); ?>
  <?php if (!empty($itemsWithImg)): ?>
    <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:24px;background:rgba(255,255,255,0.03);margin-bottom:24px">
      <h2 style="font-family:'DM Serif Display',serif;font-size:20px;font-weight:400;margin:0 0 16px">Fotos dos itens.</h2>
      <div style="position:relative">
        <div id="carousel" style="display:flex;gap:12px;overflow:hidden;scroll-behavior:smooth;border-radius:10px">
          <?php foreach ($itemsWithImg as $it): ?>
            <div style="flex:0 0 auto;text-align:center">
              <img src="<?= htmlspecialchars($it['image_path']) ?>"
                alt="<?= htmlspecialchars($it['product_name']) ?>"
                style="height:160px;width:160px;object-fit:cover;border-radius:10px;border:1px solid rgba(240,236,228,0.08)">
              <p style="font-size:11px;color:rgba(240,236,228,0.4);margin:6px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px">
                <?= htmlspecialchars($it['product_name']) ?>
              </p>
            </div>
          <?php endforeach ?>
        </div>
        <?php if (count($itemsWithImg) > 3): ?>
          <button onclick="document.getElementById('carousel').scrollBy({left:-180,behavior:'smooth'})"
            style="position:absolute;left:-14px;top:50%;transform:translateY(-50%);background:rgba(14,14,14,0.9);border:1px solid rgba(240,236,228,0.1);color:#f0ece4;border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:14px">‹</button>
          <button onclick="document.getElementById('carousel').scrollBy({left:180,behavior:'smooth'})"
            style="position:absolute;right:-14px;top:50%;transform:translateY(-50%);background:rgba(14,14,14,0.9);border:1px solid rgba(240,236,228,0.1);color:#f0ece4;border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:14px">›</button>
        <?php endif ?>
      </div>
    </div>
  <?php endif ?>

  <!-- ── ITENS DO PEDIDO (DETALHE via AJAX) ────────────────────────────── -->
  <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03)">
    <h2 style="font-family:'DM Serif Display',serif;font-size:20px;font-weight:400;margin:0 0 16px">Itens do pedido.</h2>

    <div id="items-container">
      <div style="text-align:center;padding:32px;color:rgba(240,236,228,0.3);font-size:13px">Carregando itens...</div>
    </div>
    <div id="items-pagination" style="display:flex;gap:6px;justify-content:center;margin-top:16px;flex-wrap:wrap"></div>
  </div>

</main>

<script>
(function() {
  var orderId = <?= $orderId ?>;
  var currentPage = 1;

  function fmtBrl(v) {
    return 'R$ ' + parseFloat(v).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2});
  }

  function loadItems(page) {
    currentPage = page;
    var url = 'order_detail.php?action=items&order_id=' + orderId + '&page=' + page;
    fetch(url)
      .then(r => r.json())
      .then(data => {
        var container = document.getElementById('items-container');
        if (!data.items || data.items.length === 0) {
          container.innerHTML = '<div style="text-align:center;padding:32px;color:rgba(240,236,228,0.3);font-size:13px">Nenhum item encontrado.</div>';
          return;
        }

        var html = '<div style="overflow-x:auto;border:1px solid rgba(240,236,228,0.08);border-radius:10px">'
          + '<table style="width:100%;border-collapse:collapse;font-size:13px;color:#f0ece4">'
          + '<thead><tr style="border-bottom:1px solid rgba(240,236,228,0.08)">';
        ['Foto','Produto','Descrição','Qtd.','Valor unit.','Subtotal'].forEach(function(c) {
          html += '<th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:rgba(240,236,228,0.28);white-space:nowrap">' + c + '</th>';
        });
        html += '</tr></thead><tbody>';

        data.items.forEach(function(item, i) {
          var rowBg = i % 2 === 0 ? 'transparent' : 'rgba(240,236,228,0.02)';
          var subtotal = parseFloat(item.unit_price) * parseInt(item.quantity);
          var img = item.image_path
            ? '<img src="' + item.image_path + '" style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid rgba(240,236,228,0.08)">'
            : '<div style="width:40px;height:40px;border-radius:6px;background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.08);display:flex;align-items:center;justify-content:center;font-size:18px">📦</div>';
          html += '<tr style="border-bottom:1px solid rgba(240,236,228,0.05);background:' + rowBg + '">'
            + '<td style="padding:12px 16px">' + img + '</td>'
            + '<td style="padding:12px 16px;font-weight:500;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escHtml(item.product_name) + '</td>'
            + '<td style="padding:12px 16px;color:rgba(240,236,228,0.45);max-width:200px;font-size:12px;line-height:1.5">' + escHtml((item.product_description||'').substring(0,80) + ((item.product_description||'').length>80?'…':'')) + '</td>'
            + '<td style="padding:12px 16px;text-align:center">' + item.quantity + '</td>'
            + '<td style="padding:12px 16px;white-space:nowrap">' + fmtBrl(item.unit_price) + '</td>'
            + '<td style="padding:12px 16px;font-weight:600;white-space:nowrap">' + fmtBrl(subtotal) + '</td>'
            + '</tr>';
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;

        // Paginação
        var pag = document.getElementById('items-pagination');
        pag.innerHTML = '';
        if (data.total_pages > 1) {
          for (var p = 1; p <= data.total_pages; p++) {
            (function(pg) {
              var a = document.createElement('a');
              a.href = '#';
              a.textContent = pg;
              a.style.cssText = 'padding:7px 12px;border-radius:6px;font-size:12px;text-decoration:none;'
                + (pg === currentPage ? 'background:#f0ece4;color:#0e0e0e;font-weight:600' : 'border:1px solid rgba(240,236,228,0.1);color:rgba(240,236,228,0.5)');
              a.addEventListener('click', function(e) { e.preventDefault(); loadItems(pg); });
              pag.appendChild(a);
            })(p);
          }
        }
      })
      .catch(function() {
        document.getElementById('items-container').innerHTML = '<div style="text-align:center;padding:32px;color:#fda4af;font-size:13px">Erro ao carregar itens.</div>';
      });
  }

  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  loadItems(1);
})();
</script>

<?php include 'partials/footer.php'; ?>
