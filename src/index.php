<?php
require_once 'services/SessionsService.php';
require_once 'services/ProductService.php';
$session = new SessionManager();
$user = $session->currentUser();
$searchQuery = trim($_GET['search'] ?? '');
if ($user) {
    $productService = new ProductService(new ProductDAO(), new SupplierDAO());
    $products = $searchQuery !== ''
        ? $productService->search($searchQuery)
        : $productService->getAll();
}
include 'partials/header.php';
?>
<main class="flex-grow flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-5xl">
        <div class="rounded-[32px] border border-white/10  p-8  ">
            <?php if ($user): ?>
                <form method="get" class="mb-6 flex flex-col gap-3 sm:flex-row">
                    <input
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($searchQuery) ?>"
                        placeholder="Buscar produtos por nome ou SKU"
                        class="rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm text-white outline-none focus:border-sky-500"
                    />
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-2xl bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                            Buscar
                        </button>
                        <?php if ($searchQuery !== ''): ?>
                            <a href="index.php" class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-slate-200">
                                Limpar
                            </a>
                        <?php endif ?>
                    </div>
                </form>

                <?php if ($products): ?>
                    <div class="grid gap-4">
                        <?php foreach ($products as $product): ?>
                            <?php
                                $stock  = (int) ($product['stock'] ?? 0);
                                $status = $product['status'] ?? 'inativo';
                                $createdAt = !empty($product['created_at'])
                                    ? date('d/m/Y', strtotime($product['created_at']))
                                    : null;
                            ?>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 hover:bg-white/10 transition-colors <?= $stock === 0 ? 'opacity-60' : '' ?>">

                                <!-- Imagem + Cabeçalho -->
                                <div class="flex gap-4 mb-3">
                                    <?php if (!empty($product['image_path'])): ?>
                                        <a href="product_detail.php?id=<?= (int)$product['id'] ?>" style="flex-shrink:0">
                                          <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name'] ?? '') ?>"
                                              class="rounded-xl object-cover" style="width:72px;height:72px;border:1px solid rgba(255,255,255,0.08)">
                                        </a>
                                    <?php else: ?>
                                        <a href="product_detail.php?id=<?= (int)$product['id'] ?>" class="rounded-xl shrink-0 flex items-center justify-center text-2xl" style="width:72px;height:72px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);text-decoration:none">📦</a>
                                    <?php endif ?>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-2">
                                            <a href="product_detail.php?id=<?= (int)$product['id'] ?>" class="text-base font-medium text-white leading-snug truncate" style="text-decoration:none">
                                                <?= htmlspecialchars($product['name'] ?? '') ?>
                                            </a>
                                            <?php if ($stock === 0): ?>
                                                <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-red-500/15 text-red-400">Indisponível</span>
                                            <?php else: ?>
                                                <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400">Disponível</span>
                                            <?php endif ?>
                                        </div>
                                        <?php if (!empty($product['supplier_name'])): ?>
                                            <p class="text-xs text-slate-400 mt-0.5">por <?= htmlspecialchars($product['supplier_name']) ?></p>
                                        <?php endif ?>
                                    </div>
                                </div>

                                <?php if (!empty($product['description'])): ?>
                                    <p class="text-sm text-slate-400 mb-3 leading-relaxed line-clamp-2">
                                        <?= htmlspecialchars($product['description']) ?>
                                    </p>
                                <?php endif ?>

                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php if (!empty($product['sku'])): ?>
                                        <span class="text-xs bg-white/5 border border-white/10 rounded-lg px-2.5 py-1 text-slate-400">
                                            SKU <span class="text-slate-200 font-medium"><?= htmlspecialchars($product['sku']) ?></span>
                                        </span>
                                    <?php endif ?>
                                    <?php if (!empty($product['category'])): ?>
                                        <span class="text-xs bg-white/5 border border-white/10 rounded-lg px-2.5 py-1 text-slate-400"><?= htmlspecialchars($product['category']) ?></span>
                                    <?php endif ?>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-white/10 gap-3">
                                    <p class="text-lg font-semibold text-white">
                                        R$ <?= number_format((float)($product['price'] ?? 0), 2, ',', '.') ?>
                                    </p>
                                    <div class="flex items-center gap-3">
                                        <?php if ($stock === 0): ?>
                                          <span class="text-xs font-medium text-red-400">Sem estoque</span>
                                        <?php elseif ($stock <= 5): ?>
                                          <span class="text-xs font-semibold text-amber-400">⚠ Últimas <?= $stock ?> un.!</span>
                                        <?php else: ?>
                                          <span class="text-xs font-medium text-emerald-400"><?= $stock ?> un.</span>
                                        <?php endif ?>
                                        <?php if ($stock > 0 && $user): ?>
                                            <button type="button"
                                              data-ajax-add="<?= (int)$product['id'] ?>"
                                              class="rounded-lg px-3 py-1.5 text-xs font-semibold"
                                              style="background:#f0ece4;color:#0e0e0e;border:none;cursor:pointer">
                                              + Carrinho
                                            </button>
                                        <?php endif ?>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach ?>
                    </div>
                <?php else: ?>
                    <p class="text-slate-400 text-sm">Nenhum produto cadastrado.</p>
                <?php endif ?>
                
            <?php else: ?>
                <div class="rounded-[24px] border border-white/10 bg-white/5 p-8 text-center">
                    <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Bem-vindo</p>
                    <p class="mt-4 text-lg text-slate-100">Acesse sua conta ou crie um cadastro para explorar o catálogo.</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <a href="login.php" class="rounded-lg border border-white bg-transparent px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Login</a>
                        <a href="register.php" class="rounded-lg border border-white bg-transparent px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Cadastrar</a>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
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
          // Atualiza contador do header
          var a = document.querySelector('a[href="cart.php"]');
          if (a) a.textContent = data.item_count > 0 ? 'Carrinho (' + data.item_count + ')' : 'Carrinho';
          btn.textContent = '✓ No carrinho (' + data.in_cart + ')';
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
