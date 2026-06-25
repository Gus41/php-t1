<?php
require_once 'services/SessionsService.php';
require_once 'dao/ProductDAO.php';
require_once 'dao/OrderDAO.php';
require_once 'connection/db.php';

$session    = new SessionManager();
$user       = $session->currentUser();
$productDAO = new ProductDAO();
$orderDAO   = new OrderDAO();

if (!$user) {
    $_SESSION['redirect_after_auth'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// ── Carrega endereço do usuário ───────────────────────────────────────────────
$db      = Connection::getInstance();
$addrStmt = $db->prepare(
    'SELECT a.* FROM addresses a
     JOIN users u ON u.address_id = a.id
     WHERE u.id = :uid'
);
$addrStmt->execute([':uid' => $user['id']]);
$address = $addrStmt->fetch(PDO::FETCH_ASSOC) ?: null;

$shippingOptions = [
    'pac'      => ['label' => 'PAC (Correios)',   'prazo' => '5–7 dias úteis', 'cost' => 15.00],
    'sedex'    => ['label' => 'SEDEX (Correios)',  'prazo' => '1–2 dias úteis', 'cost' => 35.00],
    'retirada' => ['label' => 'Retirada na loja', 'prazo' => 'Combinado',       'cost' => 0.00],
];

$paymentOptions = [
    'pix'     => ['label' => 'PIX',                'desc' => 'Aprovação imediata'],
    'boleto'  => ['label' => 'Boleto Bancário',    'desc' => 'Vencimento em 3 dias úteis'],
    'credito' => ['label' => 'Cartão de Crédito',  'desc' => 'Em até 12x sem juros'],
];

$error          = '';
$selectedPay    = $_POST['payment_method']  ?? '';
$selectedShip   = $_POST['shipping_method'] ?? 'pac';

// ── POST: finaliza o pedido ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!array_key_exists($selectedPay, $paymentOptions)) {
        $error = 'Selecione uma forma de pagamento.';
    } elseif (!array_key_exists($selectedShip, $shippingOptions)) {
        $error = 'Selecione uma opção de entrega.';
    } else {
        $shippingCost  = $shippingOptions[$selectedShip]['cost'];
        $validatedCart = [];
        $subtotal      = 0.0;
        $cartErrors    = [];

        foreach ($_SESSION['cart'] as $item) {
            $product = $productDAO->findById($item['product_id']);
            if (!$product || $product->getStatus() !== 'ativo') {
                $cartErrors[] = '"' . htmlspecialchars($item['name']) . '" não está mais disponível.';
                unset($_SESSION['cart'][$item['product_id']]);
                continue;
            }
            if ($product->getStock() < $item['quantity']) {
                if ($product->getStock() === 0) {
                    $cartErrors[] = '"' . htmlspecialchars($product->getName()) . '" saiu do estoque.';
                    unset($_SESSION['cart'][$item['product_id']]);
                } else {
                    $cartErrors[] = '"' . htmlspecialchars($product->getName()) . '": quantidade ajustada para ' . $product->getStock() . ' un.';
                    $_SESSION['cart'][$item['product_id']]['quantity'] = $product->getStock();
                }
                continue;
            }
            $validatedCart[] = [
                'product_id' => $product->getId(),
                'quantity'   => $item['quantity'],
                'unit_price' => $product->getPrice(),
            ];
            $subtotal += $product->getPrice() * $item['quantity'];
        }

        if (!empty($cartErrors)) {
            $error = implode(' ', $cartErrors) . ' Por favor, revise.';
        } else {
            $total   = $subtotal + $shippingCost;
            $orderId = $orderDAO->create(
                (int)$user['id'],
                $total,
                $selectedPay,
                $shippingOptions[$selectedShip]['label'],
                $shippingCost
            );
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

// ── Monta resumo do carrinho ──────────────────────────────────────────────────
$cartItems = array_values($_SESSION['cart']);
$subtotal  = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$shipCost  = $shippingOptions[$selectedShip]['cost'] ?? 15.00;
$total     = $subtotal + $shipCost;

function fmtBrl(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}

$title = 'Checkout — E-System';
include 'partials/header.php';

$inputStyle = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:11px 13px;font-size:13px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
?>
<main class="flex-grow px-6 py-10" style="max-width:1100px;margin:0 auto;width:100%">

  <div style="display:flex;align-items:center;gap:16px;margin-bottom:28px">
    <a href="cart.php" style="font-size:12px;color:rgba(240,236,228,0.4);text-decoration:none;border:1px solid rgba(240,236,228,0.1);padding:7px 14px;border-radius:8px"
       onmouseover="this.style.color='#f0ece4'" onmouseout="this.style.color='rgba(240,236,228,0.4)'">← Carrinho</a>
    <div>
      <h1 style="font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;margin:0">Checkout.</h1>
    </div>
  </div>

  <?php if ($error): ?>
    <div style="background:rgba(226,75,74,0.1);border:1px solid rgba(226,75,74,0.3);border-radius:10px;padding:13px 18px;font-size:13px;color:#fecaca;margin-bottom:24px">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif ?>

  <form method="post" id="checkout-form">
  <div class="rg-checkout">

    <!-- ── ESQUERDA: endereço + pagamento + frete ──────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:20px">

      <!-- Endereço de entrega -->
      <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:26px;background:rgba(255,255,255,0.02)">
        <h2 style="font-family:'DM Serif Display',serif;font-size:20px;font-weight:400;margin:0 0 18px">Endereço de entrega.</h2>

        <?php if ($address): ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <?php
            $addrFields = [
              'Rua / Logradouro' => $address['street'],
              'Complemento'      => $address['complement'] ?: '—',
              'Bairro'           => $address['neighborhood'],
              'Cidade'           => $address['city'],
              'Estado'           => $address['state'],
              'CEP'              => $address['zip_code'],
            ];
            foreach ($addrFields as $lbl => $val):
            ?>
              <div>
                <p style="font-size:10px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.3);margin:0 0 3px"><?= $lbl ?></p>
                <p style="font-size:13px;font-weight:500;margin:0"><?= htmlspecialchars($val) ?></p>
              </div>
            <?php endforeach ?>
          </div>
          <a href="address.php" style="display:inline-block;margin-top:16px;font-size:11px;color:rgba(240,236,228,0.4);text-decoration:none;border-bottom:1px solid rgba(240,236,228,0.15)">Alterar endereço</a>
        <?php else: ?>
          <div style="background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.2);border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:14px">
            <span style="font-size:22px">⚠</span>
            <div>
              <p style="font-size:13px;font-weight:600;color:#fcd34d;margin:0 0 2px">Endereço não cadastrado</p>
              <p style="font-size:12px;color:rgba(240,236,228,0.45);margin:0">
                Cadastre um endereço para continuar.
                <a href="address.php" style="color:#fcd34d;text-decoration:none;border-bottom:1px solid rgba(252,211,77,0.3)">Ir para Meu Endereço</a>
              </p>
            </div>
          </div>
        <?php endif ?>
      </div>

      <!-- Forma de pagamento -->
      <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:26px;background:rgba(255,255,255,0.02)">
        <h2 style="font-family:'DM Serif Display',serif;font-size:20px;font-weight:400;margin:0 0 18px">Forma de pagamento.</h2>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($paymentOptions as $val => $opt): ?>
            <label id="pay-label-<?= $val ?>" style="display:flex;align-items:center;gap:14px;border:2px solid <?= $selectedPay === $val ? '#f0ece4' : 'rgba(240,236,228,0.1)' ?>;border-radius:12px;padding:14px 18px;cursor:pointer;transition:border-color .15s;background:<?= $selectedPay === $val ? 'rgba(240,236,228,0.04)' : 'transparent' ?>">
              <input type="radio" name="payment_method" value="<?= $val ?>" <?= $selectedPay === $val ? 'checked' : '' ?>
                onchange="selectOption('pay', '<?= $val ?>')"
                style="accent-color:#f0ece4;width:18px;height:18px;flex-shrink:0">
              <div>
                <p style="font-size:13px;font-weight:600;margin:0 0 2px"><?= $opt['label'] ?></p>
                <p style="font-size:11px;color:rgba(240,236,228,0.4);margin:0"><?= $opt['desc'] ?></p>
              </div>
            </label>
          <?php endforeach ?>
        </div>
      </div>

      <!-- Opção de entrega -->
      <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:26px;background:rgba(255,255,255,0.02)">
        <h2 style="font-family:'DM Serif Display',serif;font-size:20px;font-weight:400;margin:0 0 18px">Entrega.</h2>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($shippingOptions as $val => $opt): ?>
            <label id="ship-label-<?= $val ?>" style="display:flex;align-items:center;justify-content:space-between;border:2px solid <?= $selectedShip === $val ? '#f0ece4' : 'rgba(240,236,228,0.1)' ?>;border-radius:12px;padding:14px 18px;cursor:pointer;transition:border-color .15s;background:<?= $selectedShip === $val ? 'rgba(240,236,228,0.04)' : 'transparent' ?>">
              <div style="display:flex;align-items:center;gap:14px">
                <input type="radio" name="shipping_method" value="<?= $val ?>" <?= $selectedShip === $val ? 'checked' : '' ?>
                  onchange="selectOption('ship', '<?= $val ?>')"
                  style="accent-color:#f0ece4;width:18px;height:18px;flex-shrink:0">
                <div>
                  <p style="font-size:13px;font-weight:600;margin:0 0 2px"><?= $opt['label'] ?></p>
                  <p style="font-size:11px;color:rgba(240,236,228,0.4);margin:0"><?= $opt['prazo'] ?></p>
                </div>
              </div>
              <span style="font-size:13px;font-weight:700;color:<?= $opt['cost'] === 0.0 ? '#5eead4' : '#f0ece4' ?>;white-space:nowrap">
                <?= $opt['cost'] === 0.0 ? 'Grátis' : fmtBrl($opt['cost']) ?>
              </span>
            </label>
          <?php endforeach ?>
        </div>
      </div>

    </div>

    <!-- ── DIREITA: resumo do pedido ──────────────────────────────────── -->
    <aside style="position:sticky;top:80px">
      <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:24px;background:rgba(255,255,255,0.03)">
        <h2 style="font-family:'DM Serif Display',serif;font-size:20px;font-weight:400;margin:0 0 18px">Resumo.</h2>

        <!-- Itens -->
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px">
          <?php foreach ($cartItems as $item): ?>
            <div style="display:flex;gap:10px;align-items:center">
              <?php if (!empty($item['image_path'])): ?>
                <img src="<?= htmlspecialchars($item['image_path']) ?>" style="width:38px;height:38px;object-fit:cover;border-radius:6px;border:1px solid rgba(240,236,228,0.08);flex-shrink:0">
              <?php else: ?>
                <div style="width:38px;height:38px;border-radius:6px;background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.08);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">📦</div>
              <?php endif ?>
              <div style="flex:1;min-width:0">
                <p style="font-size:12px;font-weight:500;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($item['name']) ?></p>
                <p style="font-size:11px;color:rgba(240,236,228,0.4);margin:0">× <?= (int)$item['quantity'] ?></p>
              </div>
              <span style="font-size:12px;font-weight:600;white-space:nowrap"><?= fmtBrl($item['price'] * $item['quantity']) ?></span>
            </div>
          <?php endforeach ?>
        </div>

        <!-- Valores -->
        <div style="border-top:1px solid rgba(240,236,228,0.08);padding-top:14px;display:flex;flex-direction:column;gap:8px;margin-bottom:18px">
          <div style="display:flex;justify-content:space-between;font-size:13px;color:rgba(240,236,228,0.5)">
            <span>Subtotal</span><span><?= fmtBrl($subtotal) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:rgba(240,236,228,0.5)">
            <span>Frete</span>
            <span id="ship-cost-display" style="color:<?= $shipCost === 0.0 ? '#5eead4' : 'rgba(240,236,228,0.5)' ?>">
              <?= $shipCost === 0.0 ? 'Grátis' : fmtBrl($shipCost) ?>
            </span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:700;padding-top:8px;border-top:1px solid rgba(240,236,228,0.08)">
            <span>Total</span>
            <span id="total-display"><?= fmtBrl($total) ?></span>
          </div>
        </div>

        <button type="submit" <?= !$address ? 'disabled' : '' ?>
          style="width:100%;padding:14px;background:<?= $address ? '#f0ece4' : 'rgba(240,236,228,0.2)' ?>;color:<?= $address ? '#0e0e0e' : 'rgba(240,236,228,0.3)' ?>;border:none;border-radius:10px;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;letter-spacing:0.06em;text-transform:uppercase;cursor:<?= $address ? 'pointer' : 'not-allowed' ?>">
          <?= $address ? 'Confirmar Pedido' : 'Cadastre um endereço' ?>
        </button>

        <?php if (!$address): ?>
          <p style="font-size:11px;color:rgba(240,236,228,0.3);text-align:center;margin-top:10px">
            Você precisa ter um endereço cadastrado para finalizar.
          </p>
        <?php endif ?>
      </div>
    </aside>

  </div>
  </form>
</main>

<script>
var _subtotal = <?= $subtotal ?>;
var _shipCosts = <?= json_encode(array_map(fn($o) => $o['cost'], $shippingOptions)) ?>;

function selectOption(group, val) {
  var prefix = group === 'pay' ? 'pay-label-' : 'ship-label-';
  document.querySelectorAll('[id^="' + prefix + '"]').forEach(function(el) {
    el.style.borderColor = 'rgba(240,236,228,0.1)';
    el.style.background  = 'transparent';
  });
  var active = document.getElementById(prefix + val);
  if (active) {
    active.style.borderColor = '#f0ece4';
    active.style.background  = 'rgba(240,236,228,0.04)';
  }

  if (group === 'ship') {
    var cost  = _shipCosts[val] || 0;
    var total = _subtotal + cost;
    var costEl  = document.getElementById('ship-cost-display');
    var totalEl = document.getElementById('total-display');
    if (costEl)  { costEl.textContent  = cost === 0 ? 'Grátis' : 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits:2}); costEl.style.color = cost === 0 ? '#5eead4' : 'rgba(240,236,228,0.5)'; }
    if (totalEl) totalEl.textContent = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits:2});
  }
}

// Inicializa bordas ao carregar
document.addEventListener('DOMContentLoaded', function() {
  var payChecked  = document.querySelector('input[name="payment_method"]:checked');
  var shipChecked = document.querySelector('input[name="shipping_method"]:checked');
  if (payChecked)  selectOption('pay',  payChecked.value);
  if (shipChecked) selectOption('ship', shipChecked.value);
});
</script>

<?php include 'partials/footer.php'; ?>
