<?php
require_once 'services/SessionsService.php';
require_once 'services/ProductService.php';
$session = new SessionManager();
$user = $session->currentUser();
if($user) {
    $productService = new ProductService(new ProductDAO(), new SupplierDAO());
    $products = $productService->getAll();
}
include 'partials/header.php';
?>
<main class="flex-grow flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-5xl">
        <div class="rounded-[32px] border border-white/10  p-8  ">
            <?php if ($user): ?>
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
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 hover:bg-white/10 transition-colors">

                                <!-- Cabeçalho -->
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div>
                                        <p class="text-base font-medium text-white leading-snug">
                                            <?= htmlspecialchars($product['name'] ?? '') ?>
                                        </p>
                                        <?php if (!empty($product['supplier_name'])): ?>
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                Fornecedor:
                                                <span class="text-slate-300 font-medium">
                                                    <?= htmlspecialchars($product['supplier_name']) ?>
                                                </span>
                                            </p>
                                        <?php endif ?>
                                    </div>
                                    <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full
                                        <?= $status === 'ativo'
                                            ? 'bg-emerald-500/15 text-emerald-400'
                                            : 'bg-red-500/15 text-red-400' ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </div>

                                <!-- Descrição -->
                                <?php if (!empty($product['description'])): ?>
                                    <p class="text-sm text-slate-400 mb-3 leading-relaxed line-clamp-2">
                                        <?= htmlspecialchars($product['description']) ?>
                                    </p>
                                <?php endif ?>

                                <!-- Tags -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php if (!empty($product['sku'])): ?>
                                        <span class="text-xs bg-white/5 border border-white/10 rounded-lg px-2.5 py-1 text-slate-400">
                                            SKU <span class="text-slate-200 font-medium"><?= htmlspecialchars($product['sku']) ?></span>
                                        </span>
                                    <?php endif ?>
                                    <?php if (!empty($product['category'])): ?>
                                        <span class="text-xs bg-white/5 border border-white/10 rounded-lg px-2.5 py-1 text-slate-400">
                                            <?= htmlspecialchars($product['category']) ?>
                                        </span>
                                    <?php endif ?>
                                    <?php if ($createdAt): ?>
                                        <span class="text-xs bg-white/5 border border-white/10 rounded-lg px-2.5 py-1 text-slate-400">
                                            <?= $createdAt ?>
                                        </span>
                                    <?php endif ?>
                                </div>

                                <!-- Rodapé -->
                                <div class="flex items-center justify-between pt-3 border-t border-white/10">
                                    <p class="text-lg font-semibold text-white">
                                        R$ <?= number_format((float)($product['price'] ?? 0), 2, ',', '.') ?>
                                    </p>
                                    <span class="text-xs font-medium
                                        <?php if ($stock === 0): ?>
                                            text-red-400
                                        <?php elseif ($stock <= 5): ?>
                                            text-amber-400
                                        <?php else: ?>
                                            text-emerald-400
                                        <?php endif ?>">
                                        <?= $stock === 0 ? 'Sem estoque' : $stock . ' em estoque' ?>
                                    </span>
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
<?php include 'partials/footer.php';
