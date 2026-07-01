<?php
require_once 'services/SessionsService.php';
require_once 'dao/ProductDAO.php';

$session    = new SessionManager();
$user       = $session->currentUser();
$productDAO = new ProductDAO();

$productId = (int)($_GET['id'] ?? 0);
if (!$productId) {
    header('Location: index.php');
    exit;
}

$product = $productDAO->findById($productId);
if (!$product || ($product->getStatus() !== 'ativo' && !$session->hasRole(['admin', 'superuser']))) {
    header('Location: index.php');
    exit;
}

$images = $productDAO->getAllImagePaths($productId);
if (empty($images) && $product->getImagePath()) {
    $images = [$product->getImagePath()];
}
$mainImage = $images[0] ?? null;

$stock     = $product->getStock();
$inCart    = $_SESSION['cart'][$productId]['quantity'] ?? 0;
$canAdd    = $stock > 0 && $product->getStatus() === 'ativo';

$stockColor = $stock === 0 ? '#fda4af' : ($stock <= 5 ? '#fcd34d' : '#5eead4');
$stockLabel = $stock === 0 ? 'Indisponível' : ($stock <= 5 ? "Últimas $stock un.!" : "$stock unidades");

$title = htmlspecialchars($product->getName()) . ' — E-System';
include 'partials/header.php';
?>
<main class="flex-grow px-6 py-10" style="max-width:1100px;margin:0 auto;width:100%">

  <!-- Voltar -->
  <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:rgba(240,236,228,0.4);text-decoration:none;margin-bottom:24px;border:1px solid rgba(240,236,228,0.1);padding:7px 14px;border-radius:8px;transition:color .15s"
     onmouseover="this.style.color='#f0ece4'" onmouseout="this.style.color='rgba(240,236,228,0.4)'">
    ← Voltar
  </a>

  <div class="rg-detail">

    <!-- ── IMAGENS DO PRODUTO ─────────────────────────────────────────────── -->
    <section>
      <?php if ($mainImage): ?>
        <div class="img-carousel img-carousel--detail"
             id="product-carousel"
             data-images="<?= htmlspecialchars(json_encode(array_values($images)), ENT_QUOTES) ?>">
          <div class="img-carousel-frame">
            <img id="main-product-img" class="img-carousel-img" src="<?= htmlspecialchars($mainImage) ?>"
              alt="<?= htmlspecialchars($product->getName()) ?>">
            <?php if (count($images) > 1): ?>
              <button type="button" class="img-carousel-arrow img-carousel-prev" aria-label="Imagem anterior">‹</button>
              <button type="button" class="img-carousel-arrow img-carousel-next" aria-label="Próxima imagem">›</button>
              <span class="img-carousel-counter">1/<?= count($images) ?></span>
            <?php endif ?>
          </div>
        </div>
      <?php else: ?>
        <div style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;overflow:hidden;background:rgba(255,255,255,0.02)">
          <div style="width:100%;height:440px;display:flex;align-items:center;justify-content:center;font-size:64px;opacity:0.2">📦</div>
        </div>
      <?php endif ?>
      <?php if (count($images) > 1): ?>
        <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
          <?php foreach ($images as $i => $img): ?>
            <button type="button" data-thumb-index="<?= $i ?>"
              style="padding:0;border:2px solid <?= $i === 0 ? '#f0ece4' : 'transparent' ?>;border-radius:8px;background:transparent;cursor:pointer;opacity:<?= $i === 0 ? '1' : '0.65' ?>"
              class="thumb-btn">
              <img src="<?= htmlspecialchars($img) ?>" alt=""
                style="width:64px;height:64px;object-fit:cover;border-radius:6px;display:block">
            </button>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </section>

    <!-- ── INFO + COMPRA ────────────────────────────────────────────────── -->
    <section style="position:sticky;top:80px">

      <!-- Badges de categoria e SKU -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <?php if ($product->getCategory()): ?>
          <span style="font-size:11px;padding:4px 12px;border-radius:20px;background:rgba(240,236,228,0.06);border:1px solid rgba(240,236,228,0.1);color:rgba(240,236,228,0.5);letter-spacing:0.06em">
            <?= htmlspecialchars($product->getCategory()) ?>
          </span>
        <?php endif ?>
        <span style="font-size:11px;padding:4px 12px;border-radius:20px;background:rgba(240,236,228,0.06);border:1px solid rgba(240,236,228,0.1);color:rgba(240,236,228,0.4);letter-spacing:0.08em;font-family:monospace">
          <?= htmlspecialchars($product->getSku()) ?>
        </span>
      </div>

      <h1 style="font-family:'DM Serif Display',serif;font-size:32px;font-weight:400;margin:0 0 8px;line-height:1.15">
        <?= htmlspecialchars($product->getName()) ?>
      </h1>

      <?php if ($product->getSupplierName()): ?>
        <p style="font-size:13px;color:rgba(240,236,228,0.4);margin:0 0 20px">
          por <span style="color:rgba(240,236,228,0.65)"><?= htmlspecialchars($product->getSupplierName()) ?></span>
        </p>
      <?php endif ?>

      <p style="font-size:36px;font-weight:700;margin:0 0 6px;letter-spacing:-0.02em">
        R$ <?= number_format($product->getPrice(), 2, ',', '.') ?>
      </p>
      <p style="font-size:13px;font-weight:600;color:<?= $stockColor ?>;margin:0 0 20px">
        <?= $stockLabel ?>
        <?php if ($stock <= 5 && $stock > 0): ?> ⚠<?php endif ?>
      </p>

      <?php if ($product->getDescription()): ?>
        <p style="font-size:14px;color:rgba(240,236,228,0.6);line-height:1.7;margin:0 0 28px">
          <?= nl2br(htmlspecialchars($product->getDescription())) ?>
        </p>
      <?php endif ?>

      <?php if ($canAdd): ?>
        <!-- Seletor de quantidade + botão -->
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px">
          <div style="display:flex;align-items:center;border:1px solid rgba(240,236,228,0.1);border-radius:8px;overflow:hidden">
            <button type="button" onclick="changeQty(-1)"
              style="width:38px;height:42px;background:rgba(240,236,228,0.04);border:none;color:#f0ece4;font-size:18px;cursor:pointer;line-height:1">−</button>
            <span id="qty-val" style="width:42px;text-align:center;font-size:14px;font-weight:600;color:#f0ece4">1</span>
            <button type="button" onclick="changeQty(1)"
              style="width:38px;height:42px;background:rgba(240,236,228,0.04);border:none;color:#f0ece4;font-size:18px;cursor:pointer;line-height:1">+</button>
          </div>
          <button id="add-btn" type="button" onclick="addToCart()"
            style="flex:1;padding:13px 20px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer;transition:opacity .2s">
            + Adicionar ao Carrinho
          </button>
        </div>
        <?php if ($inCart > 0): ?>
          <p id="in-cart-note" style="font-size:12px;color:rgba(240,236,228,0.4);margin:0 0 14px">
            Você já tem <strong><?= $inCart ?></strong> unidade<?= $inCart !== 1 ? 's' : '' ?> no carrinho.
          </p>
        <?php else: ?>
          <p id="in-cart-note" style="font-size:12px;color:rgba(240,236,228,0.4);margin:0 0 14px;display:none"></p>
        <?php endif ?>
        <a href="cart.php" style="display:block;text-align:center;padding:12px;border:1px solid rgba(240,236,228,0.1);border-radius:8px;font-size:12px;color:rgba(240,236,228,0.5);text-decoration:none;letter-spacing:0.06em;text-transform:uppercase"
           onmouseover="this.style.borderColor='rgba(240,236,228,0.25)';this.style.color='#f0ece4'"
           onmouseout="this.style.borderColor='rgba(240,236,228,0.1)';this.style.color='rgba(240,236,228,0.5)'">
          Ver carrinho
        </a>
      <?php else: ?>
        <div style="padding:14px 18px;background:rgba(226,75,74,0.08);border:1px solid rgba(226,75,74,0.2);border-radius:8px;font-size:13px;color:#fda4af">
          Produto indisponível no momento.
        </div>
      <?php endif ?>

    </section>
  </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var carousel = document.getElementById('product-carousel');
  var thumbs = document.querySelectorAll('.thumb-btn');

  function highlightThumb(index) {
    thumbs.forEach(function(btn) {
      var active = parseInt(btn.getAttribute('data-thumb-index'), 10) === index;
      btn.style.borderColor = active ? '#f0ece4' : 'transparent';
      btn.style.opacity = active ? '1' : '0.65';
    });
  }

  thumbs.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var index = parseInt(btn.getAttribute('data-thumb-index'), 10);
      if (carousel && carousel._carouselShow) carousel._carouselShow(index);
    });
  });

  if (carousel) {
    carousel.addEventListener('carouselchange', function(e) {
      highlightThumb(e.detail.index);
    });
  }
});

var _maxStock = <?= $stock ?>;
var _qty      = 1;
var _pid      = <?= $productId ?>;

function changeQty(delta) {
  var next = _qty + delta;
  if (next < 1) return;
  if (next > _maxStock) { showToast('Estoque máximo: ' + _maxStock + ' unidades.', 'warning'); return; }
  _qty = next;
  document.getElementById('qty-val').textContent = _qty;
}

function addToCart() {
  var btn = document.getElementById('add-btn');
  btn.disabled = true;
  btn.style.opacity = '0.6';

  var added = 0;
  var done  = function() {
    btn.disabled = false;
    btn.style.opacity = '1';
  };

  // Adiciona _qty vezes via ajax_add (que incrementa 1 por vez)
  var calls = 0;
  function next() {
    if (calls >= _qty) { done(); return; }
    calls++;
    var fd = new FormData();
    fd.append('action', 'ajax_add');
    fd.append('product_id', _pid);
    fetch('cart.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(function(data) {
        if (data.error) {
          showToast(data.error, 'error');
          done();
          return;
        }
        added++;
        // Atualiza contador do header
        var a = document.querySelector('a[href="cart.php"]');
        if (a) a.textContent = data.item_count > 0 ? 'Carrinho (' + data.item_count + ')' : 'Carrinho';
        if (calls < _qty) { next(); return; }
        // Concluído
        showToast('"<?= addslashes($product->getName()) ?>" × ' + added + ' adicionado' + (added > 1 ? 's' : '') + ' ao carrinho!', 'success');
        var note = document.getElementById('in-cart-note');
        note.style.display = '';
        note.innerHTML = 'Você tem <strong>' + data.in_cart + '</strong> unidade' + (data.in_cart !== 1 ? 's' : '') + ' no carrinho.';
        // Limita qty ao restante disponível
        _maxStock = _maxStock - added;
        if (_maxStock <= 0) {
          btn.textContent = 'Esgotado';
          btn.disabled = true;
          btn.style.background = 'rgba(240,236,228,0.15)';
        }
        _qty = 1;
        document.getElementById('qty-val').textContent = _qty;
        done();
      })
      .catch(function() {
        showToast('Erro ao adicionar ao carrinho.', 'error');
        done();
      });
  }
  next();
}

</script>

<?php include 'partials/footer.php'; ?>
