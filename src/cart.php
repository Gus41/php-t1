<?php
require_once 'services/SessionsService.php';
require_once 'dao/ProductDAO.php';
require_once 'dao/OrderDAO.php';
require_once 'dao/UserDAO.php';

$session    = new SessionManager();
$productDAO = new ProductDAO();
$orderDAO   = new OrderDAO();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$user        = $session->currentUser();
$message     = '';
$messageType = 'success';
$showAuth    = false;
$action      = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Redirecionar novo cliente para o registro mantendo retorno ao cart ────────
if ($action === 'goto_register' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['redirect_after_auth'] = 'cart.php';
    header('Location: register.php');
    exit;
}

// ── AJAX: adicionar item ao carrinho (retorna JSON) ───────────────────────────
if ($action === 'ajax_add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $productId = (int)($_POST['product_id'] ?? 0);
    $product   = $productDAO->findById($productId);

    if (!$product || $product->getStatus() !== 'ativo' || $product->getStock() <= 0) {
        echo json_encode(['error' => 'Produto indisponível ou fora de estoque.']);
        exit;
    }
    $currentQty = $_SESSION['cart'][$productId]['quantity'] ?? 0;
    $newQty     = $currentQty + 1;
    if ($newQty > $product->getStock()) {
        echo json_encode(['error' => 'Você já tem o máximo disponível no carrinho (' . $product->getStock() . ' un.).']);
        exit;
    }
    $_SESSION['cart'][$productId] = [
        'product_id' => $productId,
        'name'       => $product->getName(),
        'price'      => $product->getPrice(),
        'image_path' => $product->getImagePath(),
        'quantity'   => $newQty,
    ];
    $cartTotal = 0.0;
    foreach ($_SESSION['cart'] as $ci) { $cartTotal += $ci['price'] * $ci['quantity']; }
    $fmtTotal = 'R$ ' . number_format($cartTotal, 2, ',', '.');
    echo json_encode([
        'success'    => true,
        'message'    => '"' . $product->getName() . '" adicionado ao carrinho.',
        'item_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
        'in_cart'    => $newQty,
        'cart_total' => $fmtTotal,
        'item'       => [
            'product_id' => $productId,
            'name'       => $product->getName(),
            'price'      => $product->getPrice(),
            'image_path' => $product->getImagePath(),
            'quantity'   => $newQty,
            'subtotal'   => 'R$ ' . number_format($product->getPrice() * $newQty, 2, ',', '.'),
        ],
    ]);
    exit;
}

// ── AJAX: atualiza item e retorna total ──────────────────────────────────────
if ($action === 'ajax_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty       = (int)($_POST['quantity'] ?? 0);
    $product   = $productDAO->findById($productId);

    if (!$product) {
        echo json_encode(['error' => 'Produto não encontrado']);
        exit;
    }

    if ($qty > $product->getStock()) {
        echo json_encode([
            'error'   => 'Estoque insuficiente. Máximo disponível: ' . $product->getStock(),
            'max_qty' => $product->getStock(),
            'total'   => cartTotal(),
        ]);
        exit;
    }

    if ($qty <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = [
            'product_id' => $productId,
            'name'       => $product->getName(),
            'price'      => $product->getPrice(),
            'image_path' => $product->getImagePath(),
            'quantity'   => $qty,
        ];
    }

    echo json_encode([
        'total'          => cartTotal(),
        'item_subtotal'  => $qty > 0 ? fmtBrl($qty * $product->getPrice()) : fmtBrl(0),
        'item_count'     => array_sum(array_column($_SESSION['cart'], 'quantity')),
    ]);
    exit;
}

// ── Adicionar ao carrinho ────────────────────────────────────────────────────
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $product   = $productDAO->findById($productId);
    $redirect  = $_POST['redirect'] ?? 'cart.php';
    // Sanitiza redirect para evitar open redirect
    $allowed   = ['cart.php', 'index.php'];
    if (!in_array($redirect, $allowed, true)) $redirect = 'cart.php';

    if ($product && $product->getStock() > 0 && $product->getStatus() === 'ativo') {
        $currentQty = $_SESSION['cart'][$productId]['quantity'] ?? 0;
        $newQty     = $currentQty + 1;

        if ($newQty > $product->getStock()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Quantidade máxima em estoque atingida (' . $product->getStock() . ' un.).'];
        } else {
            $_SESSION['cart'][$productId] = [
                'product_id' => $productId,
                'name'       => $product->getName(),
                'price'      => $product->getPrice(),
                'image_path' => $product->getImagePath(),
                'quantity'   => $newQty,
            ];
            $_SESSION['flash'] = ['type' => 'success', 'message' => '"' . $product->getName() . '" adicionado ao carrinho!'];
        }
    }
    header('Location: ' . $redirect);
    exit;
}

// ── Remover do carrinho ──────────────────────────────────────────────────────
if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $removedName = $_SESSION['cart'][$pid]['name'] ?? '';
    unset($_SESSION['cart'][$pid]);
    if ($removedName) {
        $_SESSION['flash'] = ['type' => 'info', 'message' => '"' . $removedName . '" removido do carrinho.'];
    }
    header('Location: cart.php');
    exit;
}

// ── Login inline ─────────────────────────────────────────────────────────────
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userDAO = new UserDAO();
    $email   = trim($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $userObj = $userDAO->findByEmail($email);

    if ($userObj && (password_verify($pass, $userObj->getPassword()) || $pass === $userObj->getPassword())) {
        $session->loginUser($userObj->toArray());
        $user = $session->currentUser();
    } else {
        $message  = 'E-mail ou senha incorretos.';
        $messageType = 'error';
        $showAuth = true;
    }
}

// ── Finalizar pedido ─────────────────────────────────────────────────────────
if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $session->currentUser();
    if (!$user) {
        $_SESSION['redirect_after_auth'] = 'cart.php';
        $showAuth = true;
    } elseif (empty($_SESSION['cart'])) {
        $message     = 'Seu carrinho está vazio.';
        $messageType = 'error';
    } else {
        $errors        = [];
        $validatedCart = [];
        $total         = 0.0;

        foreach ($_SESSION['cart'] as $item) {
            $product = $productDAO->findById($item['product_id']);
            if (!$product || $product->getStatus() !== 'ativo') {
                $errors[] = '"' . htmlspecialchars($item['name']) . '" não está mais disponível.';
                unset($_SESSION['cart'][$item['product_id']]);
                continue;
            }
            if ($product->getStock() < $item['quantity']) {
                if ($product->getStock() === 0) {
                    $errors[] = '"' . htmlspecialchars($product->getName()) . '" saiu do estoque.';
                    unset($_SESSION['cart'][$item['product_id']]);
                } else {
                    $errors[] = '"' . htmlspecialchars($product->getName()) . '": máximo disponível agora é ' . $product->getStock() . ' un.';
                    $_SESSION['cart'][$item['product_id']]['quantity'] = $product->getStock();
                }
                continue;
            }
            $validatedCart[] = [
                'product_id' => $product->getId(),
                'quantity'   => $item['quantity'],
                'unit_price' => $product->getPrice(),
            ];
            $total += $product->getPrice() * $item['quantity'];
        }

        if (!empty($errors)) {
            $message     = implode(' ', $errors) . ' Por favor, revise o carrinho.';
            $messageType = 'error';
        } else {
            $orderId = $orderDAO->create($user['id'], $total);
            foreach ($validatedCart as $item) {
                $orderDAO->addItem($orderId, $item['product_id'], $item['quantity'], $item['unit_price']);
                $orderDAO->decrementStock($item['product_id'], $item['quantity']);
            }
            $_SESSION['cart'] = [];
            header('Location: order_detail.php?order_id=' . $orderId . '&new=1');
            exit;
        }
    }
}

// ── Carrega catálogo ─────────────────────────────────────────────────────────
$searchQuery = trim($_GET['search'] ?? '');
$allProducts = $searchQuery !== '' ? $productDAO->searchByNameOrSku($searchQuery) : $productDAO->findAllWithSupplier();
$catalog     = array_values(array_filter($allProducts, fn($p) => ($p['status'] ?? '') === 'ativo'));

$user = $session->currentUser();

function cartTotal(): string {
    $t = 0.0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $t += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    }
    return fmtBrl($t);
}
function fmtBrl(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}

$title = 'Carrinho — E-System';
include 'partials/header.php';

$inputStyle = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:10px 13px;font-size:13px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
$labelStyle = "font-size:10px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.38);display:block;margin-bottom:6px";
?>
<main class="flex-grow px-6 py-10" style="max-width:1300px;margin:0 auto;width:100%">

  <?php if ($message): ?>
    <div style="background:<?= $messageType === 'error' ? 'rgba(226,75,74,0.08)' : 'rgba(31,198,156,0.12)' ?>;border:1px solid <?= $messageType === 'error' ? 'rgba(226,75,74,0.2)' : 'rgba(31,198,156,0.22)' ?>;border-radius:8px;padding:12px 16px;font-size:13px;color:<?= $messageType === 'error' ? '#f09595' : '#d5f7ef' ?>;margin-bottom:20px">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif ?>

  <div class="rg-cart">

    <!-- ── CATÁLOGO ────────────────────────────────────────────────────────── -->
    <section>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px">
        <h1 style="font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;margin:0">Loja.</h1>
        <form method="get" style="display:flex;gap:8px;flex:1;max-width:360px">
          <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>"
            placeholder="Buscar produtos..." style="<?= $inputStyle ?>;flex:1">
          <button type="submit" style="padding:10px 18px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;cursor:pointer;white-space:nowrap">Buscar</button>
          <?php if ($searchQuery !== ''): ?>
            <a href="cart.php" style="padding:10px 14px;border:1px solid rgba(240,236,228,0.1);border-radius:8px;font-size:12px;color:rgba(240,236,228,0.5);text-decoration:none">✕</a>
          <?php endif ?>
        </form>
      </div>

      <?php if (empty($catalog)): ?>
        <div style="border:1px solid rgba(240,236,228,0.08);border-radius:14px;padding:48px;text-align:center;font-size:13px;color:rgba(240,236,228,0.3)">
          Nenhum produto disponível<?= $searchQuery ? ' para "' . htmlspecialchars($searchQuery) . '"' : '' ?>.
        </div>
      <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px">
          <?php foreach ($catalog as $p):
            $pStock  = (int)($p['stock'] ?? 0);
            $inCart  = $_SESSION['cart'][$p['id']]['quantity'] ?? 0;
            $unavail = $pStock === 0;
          ?>
            <div style="border:1px solid rgba(240,236,228,0.08);border-radius:14px;padding:16px;background:rgba(255,255,255,0.02);<?= $unavail ? 'opacity:0.55' : '' ?>">
              <a href="product_detail.php?id=<?= (int)$p['id'] ?>" style="display:block;text-decoration:none">
                <?php if (!empty($p['image_path'])): ?>
                  <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                    style="width:100%;height:140px;object-fit:cover;border-radius:10px;margin-bottom:12px;border:1px solid rgba(240,236,228,0.06)">
                <?php else: ?>
                  <div style="width:100%;height:100px;border-radius:10px;background:rgba(240,236,228,0.04);margin-bottom:12px;display:flex;align-items:center;justify-content:center;font-size:32px;border:1px solid rgba(240,236,228,0.06)">📦</div>
                <?php endif ?>
              </a>

              <a href="product_detail.php?id=<?= (int)$p['id'] ?>" style="font-size:14px;font-weight:500;display:block;margin:0 0 4px;color:#f0ece4;text-decoration:none"><?= htmlspecialchars($p['name']) ?></a>
              <?php if (!empty($p['description'])): ?>
                <p style="font-size:12px;color:rgba(240,236,228,0.4);margin:0 0 8px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                  <?= htmlspecialchars($p['description']) ?>
                </p>
              <?php endif ?>

              <?php if (!$unavail && $pStock <= 5): ?>
                <p style="font-size:11px;font-weight:600;color:#fcd34d;margin:0 0 8px;letter-spacing:0.04em">
                  ⚠ Últimas <?= $pStock ?> unidade<?= $pStock !== 1 ? 's' : '' ?>!
                </p>
              <?php endif ?>

              <div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto">
                <span style="font-size:15px;font-weight:600"><?= fmtBrl((float)$p['price']) ?></span>
                <?php if ($unavail): ?>
                  <span style="font-size:11px;padding:4px 10px;border-radius:6px;background:rgba(226,75,74,0.1);color:#fda4af;font-weight:500">Indisponível</span>
                <?php else: ?>
                  <button type="button"
                    data-ajax-add="<?= (int)$p['id'] ?>"
                    style="padding:6px 14px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer">
                    <?= $inCart > 0 ? '+ 1 (tem ' . $inCart . ')' : '+ Carrinho' ?>
                  </button>
                <?php endif ?>
              </div>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </section>

    <!-- ── CARRINHO ───────────────────────────────────────────────────────── -->
    <aside style="position:sticky;top:76px">
      <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:24px;background:rgba(255,255,255,0.03)">
        <h2 style="font-family:'DM Serif Display',serif;font-size:22px;font-weight:400;margin:0 0 16px">Carrinho</h2>

        <!-- Mensagem de carrinho vazio (JS pode ocultar) -->
        <div id="cart-empty-msg" style="<?= !empty($_SESSION['cart']) ? 'display:none' : '' ?>">
          <p style="font-size:13px;color:rgba(240,236,228,0.35);text-align:center;padding:20px 0">Carrinho vazio.</p>
        </div>

        <!-- Corpo do carrinho (JS pode exibir quando vazio→com itens) -->
        <div id="cart-body" style="<?= empty($_SESSION['cart']) ? 'display:none' : '' ?>">
          <div id="cart-items" style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px">
            <?php foreach ($_SESSION['cart'] as $pid => $item): ?>
              <div id="cart-row-<?= (int)$pid ?>" style="display:flex;gap:10px;align-items:flex-start">
                <?php if (!empty($item['image_path'])): ?>
                  <img src="<?= htmlspecialchars($item['image_path']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid rgba(240,236,228,0.08);flex-shrink:0">
                <?php else: ?>
                  <div style="width:40px;height:40px;border-radius:6px;background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.08);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">📦</div>
                <?php endif ?>
                <div style="flex:1;min-width:0">
                  <p style="font-size:12px;font-weight:500;margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($item['name']) ?></p>
                  <div style="display:flex;align-items:center;gap:8px">
                    <input type="number" id="qty-<?= (int)$pid ?>" value="<?= (int)$item['quantity'] ?>" min="1"
                      onchange="updateCart(<?= (int)$pid ?>, this.value)"
                      style="width:56px;background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:6px;padding:4px 8px;font-size:12px;color:#f0ece4;outline:none">
                    <span id="sub-<?= (int)$pid ?>" style="font-size:12px;color:rgba(240,236,228,0.5)"><?= fmtBrl($item['price'] * $item['quantity']) ?></span>
                  </div>
                </div>
                <form method="post" style="margin:0">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="product_id" value="<?= (int)$pid ?>">
                  <button type="submit" style="background:transparent;border:none;color:rgba(240,236,228,0.3);cursor:pointer;font-size:16px;padding:4px" title="Remover">✕</button>
                </form>
              </div>
            <?php endforeach ?>
          </div>

          <div style="border-top:1px solid rgba(240,236,228,0.08);padding-top:14px;display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <span style="font-size:13px;color:rgba(240,236,228,0.5)">Total</span>
            <span id="cart-total" style="font-size:18px;font-weight:600"><?= cartTotal() ?></span>
          </div>

          <?php if ($user): ?>
            <a href="checkout.php"
              style="display:block;width:100%;padding:13px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:700;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer;text-align:center;text-decoration:none;box-sizing:border-box">
              Ir para o Checkout →
            </a>
          <?php else: ?>
            <button onclick="document.getElementById('auth-section').style.display='block';this.style.display='none';document.getElementById('auth-section').scrollIntoView({behavior:'smooth'})"
              style="width:100%;padding:13px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:600;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer">
              Finalizar Pedido
            </button>
          <?php endif ?>
        </div>
      </div>
    </aside>
  </div>

  <!-- ── LOGIN INLINE (aparece ao tentar finalizar sem login) ─────────────── -->
  <div id="auth-section" style="display:<?= $showAuth ? 'block' : 'none' ?>;margin-top:32px">
    <div style="max-width:480px;margin:0 auto;border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03)">
      <h2 style="font-family:'DM Serif Display',serif;font-size:24px;font-weight:400;margin:0 0 6px">Identificação.</h2>
      <p style="font-size:13px;color:rgba(240,236,228,0.35);margin:0 0 20px">Entre na sua conta para concluir o pedido.</p>

      <?php if ($showAuth && $message): ?>
        <div style="background:rgba(226,75,74,0.08);border:1px solid rgba(226,75,74,0.2);border-radius:8px;padding:10px 14px;font-size:13px;color:#f09595;margin-bottom:16px">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif ?>

      <form method="post" style="display:flex;flex-direction:column;gap:14px">
        <input type="hidden" name="action" value="login">
        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">E-mail</span>
          <input type="email" name="email" placeholder="seu@email.com" style="<?= $inputStyle ?>" required>
        </label>
        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Senha</span>
          <input type="password" name="password" placeholder="••••••••" style="<?= $inputStyle ?>" required>
        </label>
        <button type="submit" style="padding:13px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:600;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer">Entrar</button>
      </form>

      <p style="margin-top:16px;font-size:13px;color:rgba(240,236,228,0.35);text-align:center">
        Não tem conta?
        <a href="cart.php?action=goto_register" style="color:rgba(240,236,228,0.65);border-bottom:1px solid rgba(240,236,228,0.2);text-decoration:none">Cadastre-se</a>
      </p>
    </div>
  </div>

</main>

<script>
function updateCart(productId, qty) {
  const fd = new FormData();
  fd.append('action', 'ajax_update');
  fd.append('product_id', productId);
  fd.append('quantity', qty);

  fetch('cart.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.error) {
        showToast(data.error, 'error');
        const input = document.getElementById('qty-' + productId);
        if (input && data.max_qty !== undefined) input.value = data.max_qty;
      }
      if (data.total)         document.getElementById('cart-total').textContent = data.total;
      if (data.item_subtotal) document.getElementById('sub-' + productId).textContent = data.item_subtotal;
      if (data.item_count !== undefined) _updateHeaderCount(data.item_count);
    })
    .catch(() => {});
}

// Botões de adicionar ao carrinho via AJAX (catálogo do lado esquerdo)
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[data-ajax-add]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var pid = btn.getAttribute('data-ajax-add');
      var fd = new FormData();
      fd.append('action', 'ajax_add');
      fd.append('product_id', pid);
      fetch('cart.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function(data) {
          if (data.error) { showToast(data.error, 'error'); return; }
          showToast(data.message, 'success');
          _updateHeaderCount(data.item_count);
          btn.textContent = '+ 1 (tem ' + data.in_cart + ')';
          // Atualiza sidebar do carrinho em tempo real
          if (data.item) updateCartSidebar(data.item, data.cart_total);
        })
        .catch(() => showToast('Erro ao adicionar ao carrinho.', 'error'));
    });
  });
});

function _updateHeaderCount(count) {
  var a = document.querySelector('a[href="cart.php"]');
  if (a) a.textContent = count > 0 ? 'Carrinho (' + count + ')' : 'Carrinho';
}

function updateCartSidebar(item, cartTotal) {
  var emptyMsg  = document.getElementById('cart-empty-msg');
  var cartBody  = document.getElementById('cart-body');
  var cartItems = document.getElementById('cart-items');
  if (!cartItems) return;

  // Se estava vazio, mostra o corpo
  if (emptyMsg) emptyMsg.style.display = 'none';
  if (cartBody) cartBody.style.display = '';

  var pid = item.product_id;
  var existing = document.getElementById('cart-row-' + pid);

  if (existing) {
    // Atualiza linha existente
    var qtyInput = document.getElementById('qty-' + pid);
    var subSpan  = document.getElementById('sub-' + pid);
    if (qtyInput) qtyInput.value = item.quantity;
    if (subSpan)  subSpan.textContent = item.subtotal;
  } else {
    // Insere nova linha
    var imgHtml = item.image_path
      ? '<img src="' + _esc(item.image_path) + '" style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid rgba(240,236,228,0.08);flex-shrink:0">'
      : '<div style="width:40px;height:40px;border-radius:6px;background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.08);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">📦</div>';

    var row = document.createElement('div');
    row.id = 'cart-row-' + pid;
    row.style.cssText = 'display:flex;gap:10px;align-items:flex-start';
    row.innerHTML = imgHtml
      + '<div style="flex:1;min-width:0">'
      +   '<p style="font-size:12px;font-weight:500;margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + _esc(item.name) + '</p>'
      +   '<div style="display:flex;align-items:center;gap:8px">'
      +     '<input type="number" id="qty-' + pid + '" value="' + item.quantity + '" min="1"'
      +       ' onchange="updateCart(' + pid + ', this.value)"'
      +       ' style="width:56px;background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:6px;padding:4px 8px;font-size:12px;color:#f0ece4;outline:none">'
      +     '<span id="sub-' + pid + '" style="font-size:12px;color:rgba(240,236,228,0.5)">' + item.subtotal + '</span>'
      +   '</div>'
      + '</div>'
      + '<form method="post" style="margin:0">'
      +   '<input type="hidden" name="action" value="remove">'
      +   '<input type="hidden" name="product_id" value="' + pid + '">'
      +   '<button type="submit" style="background:transparent;border:none;color:rgba(240,236,228,0.3);cursor:pointer;font-size:16px;padding:4px" title="Remover">✕</button>'
      + '</form>';
    cartItems.appendChild(row);
  }

  // Atualiza totais
  var totalEl = document.getElementById('cart-total');
  if (totalEl) totalEl.textContent = cartTotal;
  var confirmTotal = document.getElementById('confirm-total');
  if (confirmTotal) confirmTotal.textContent = cartTotal;
}

function _esc(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php include 'partials/footer.php'; ?>
