<?php
require_once 'services/SessionsService.php';
$session = new SessionManager();
$user = $session->currentUser();
include 'partials/header.php';
/*
    ...
*/
?>
<main class="flex-grow flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-5xl">
        <div class="rounded-[32px] border border-white/10 bg-black/95 p-8 shadow-[0_30px_90px_-30px_rgba(255,255,255,0.05)]">
            <?php if ($user): ?>
                <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Painel</p>
                        <p class="mt-2 text-3xl font-semibold text-white">Seu perfil</p>
                    </div>
                </div>
                <div class="grid gap-6">
                    <div class="rounded-[24px] border border-white/10 bg-white/5 p-6 text-slate-100">
                        <p class="text-sm text-slate-400 mb-4">Informações básicas</p>
                        <div class="space-y-3 text-sm text-white">
                            <p><span class="text-slate-500">Nome:</span> <?= htmlspecialchars($user['name']) ?></p>
                            <p><span class="text-slate-500">E-mail:</span> <?= htmlspecialchars($user['email']) ?></p>
                            <p><span class="text-slate-500">Telefone:</span> <?= htmlspecialchars($user['phone']) ?></p>
                            <p><span class="text-slate-500">Endereço:</span> <?= nl2br(htmlspecialchars($user['address'])) ?></p>
                            <p><span class="text-slate-500">Tipo:</span> <?= htmlspecialchars(ucfirst($user['role'])) ?></p>
                        </div>
                    </div>
                    <div class="rounded-[24px] border border-white/10 bg-white/5 p-6 text-slate-100">
                        <p class="text-sm text-slate-400 mb-4">Status da conta</p>
                        <?php if ($user['role'] === 'superuser'): ?>
                            <p class="text-white">Permissões completas. Você pode cadastrar admins.</p>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <p class="text-white">Perfil administrativo com acesso estendido.</p>
                        <?php else: ?>
                            <p class="text-white">Perfil padrão de cliente.</p>
                        <?php endif ?>
                    </div>
                </div>
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
