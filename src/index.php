<?php
require_once 'services/SessionsService.php';
require_once 'services/ProductService.php';
require_once 'dao/ProductDAO.php';
$session = new SessionManager();
$user = $session->currentUser();
$searchQuery = trim($_GET['search'] ?? '');
$productDAO = new ProductDAO();
if ($user) {
    $productService = new ProductService($productDAO, new SupplierDAO());
    $products = $searchQuery !== ''
        ? $productService->search($searchQuery)
        : $productService->getAll();
}
$title = 'Início — E-System';
include 'partials/header.php';

function fmtPrice(float $p): string {
    return number_format($p, 2, ',', '.');
}
?>
<main class="flex-grow ml-page">
    <?php if ($user): ?>
        <?php if ($searchQuery !== ''): ?>
            <p class="ml-page-sub" style="margin-bottom:12px">
                Resultados para <strong><?= htmlspecialchars($searchQuery) ?></strong>
                — <a href="index.php" class="ml-link">ver todos</a>
            </p>
        <?php endif ?>

        <?php if (!empty($products)): ?>
            <div class="ml-product-grid">
                <?php foreach ($products as $product):
                    $stock = (int)($product['stock'] ?? 0);
                    $unavail = $stock === 0;
                    $pid = (int)$product['id'];
                    $images = $productDAO->getAllImagePaths($pid);
                    if (empty($images) && !empty($product['image_path'])) {
                        $images = [$product['image_path']];
                    }
                    $hasCarousel = count($images) > 1;
                ?>
                    <article class="ml-product-card <?= $unavail ? 'unavailable' : '' ?>">
                        <?php if (!empty($images)): ?>
                            <div class="img-carousel img-carousel--card"
                                 data-images="<?= htmlspecialchars(json_encode($images), ENT_QUOTES) ?>">
                                <a href="product_detail.php?id=<?= $pid ?>" class="img-carousel-link">
                                    <img class="img-carousel-img" src="<?= htmlspecialchars($images[0]) ?>"
                                         alt="<?= htmlspecialchars($product['name'] ?? '') ?>">
                                </a>
                                <?php if ($hasCarousel): ?>
                                    <button type="button" class="img-carousel-arrow img-carousel-prev" aria-label="Imagem anterior">‹</button>
                                    <button type="button" class="img-carousel-arrow img-carousel-next" aria-label="Próxima imagem">›</button>
                                    <span class="img-carousel-counter">1/<?= count($images) ?></span>
                                <?php endif ?>
                            </div>
                        <?php else: ?>
                            <a href="product_detail.php?id=<?= $pid ?>">
                                <div class="ml-product-img-placeholder">📦</div>
                            </a>
                        <?php endif ?>
                        <div class="ml-product-card-body">
                            <h3>
                                <a href="product_detail.php?id=<?= (int)$product['id'] ?>">
                                    <?= htmlspecialchars($product['name'] ?? '') ?>
                                </a>
                            </h3>

                            <?php if ($unavail): ?>
                                <span class="ml-badge ml-badge-red">Indisponível</span>
                            <?php elseif ($stock <= 5): ?>
                                <span class="ml-badge ml-badge-warn">Últimas <?= $stock ?> un.</span>
                            <?php else: ?>
                                <span class="ml-badge ml-badge-green">Em estoque</span>
                            <?php endif ?>

                            <p class="ml-product-price">R$ <?= fmtPrice((float)($product['price'] ?? 0)) ?></p>

                            <?php if (!$unavail): ?>
                                <button type="button" data-ajax-add="<?= (int)$product['id'] ?>"
                                    class="ml-btn ml-btn-primary" style="width:100%;padding:9px">
                                    + Carrinho
                                </button>
                            <?php endif ?>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php else: ?>
            <div class="ml-card ml-hero">
                <p>Nenhum produto encontrado.</p>
            </div>
        <?php endif ?>

    <?php else: ?>
        <div class="ml-card ml-hero">
            <p class="text-sm uppercase tracking-widest" style="color:rgba(240,236,228,0.35);margin:0 0 12px;letter-spacing:0.35em;font-size:12px">Bem-vindo</p>
            <h2>Acesse sua conta</h2>
            <p>Entre ou cadastre-se para explorar o catálogo e fazer pedidos.</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                <a href="login.php" class="ml-btn ml-btn-secondary">Login</a>
                <a href="register.php" class="ml-btn ml-btn-primary">Cadastrar</a>
            </div>
        </div>
    <?php endif ?>
</main>
<?php if ($user): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[data-ajax-add]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var pid = btn.getAttribute('data-ajax-add');
      btn.disabled = true;
      var fd = new FormData();
      fd.append('action', 'ajax_add');
      fd.append('product_id', pid);
      fetch('cart.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function(data) {
          btn.disabled = false;
          if (data.error) { showToast(data.error, 'error'); return; }
          showToast(data.message, 'success');
          var a = document.querySelector('a[href="cart.php"]');
          if (a) a.textContent = data.item_count > 0 ? 'Carrinho (' + data.item_count + ')' : 'Carrinho';
          btn.textContent = 'No carrinho (' + data.in_cart + ')';
        })
        .catch(function() {
          btn.disabled = false;
          showToast('Erro ao adicionar ao carrinho.', 'error');
        });
    });
  });
});
</script>
<?php endif ?>
<?php include 'partials/footer.php';
