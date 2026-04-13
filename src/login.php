<?php
require_once 'connection/db.php';
require_once 'dao/UserDAO.php';
require_once 'services/SessionsService.php';

$session = new SessionManager();
$message = '';
$userDAO = new UserDAO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = 'Preencha e-mail e senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'E-mail inválido.';
    } else {
        try {
            $user = $userDAO->findByEmail($email);
            if (!$user) {
                $message = 'E-mail ou senha incorretos.';
            } else {
                $storedPassword = $user->getPassword();
                if ($storedPassword === $password || password_verify($password, $storedPassword)) {
                    $session->loginUser($user->toArray());
                    header('Location: index.php');
                    exit;
                }
                $message = 'E-mail ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao entrar: ' . $e->getMessage();
        }
    }
}

include 'partials/header.php';
?>
 <main class="flex-grow flex items-center justify-center px-6 py-16">
  <div style="width:100%;max-width:400px">

    <h1 style="font-family:'DM Serif Display',serif;font-size:32px;font-weight:400;letter-spacing:-0.01em;margin:0 0 6px">
      Bem-vindo.
    </h1>
    <p style="font-size:13px;color:var(--muted);margin:0 0 2.5rem;font-weight:300">
      Acesse sua conta para continuar.
    </p>

    <?php if ($message): ?>
      <div style="background:rgba(226,75,74,0.08);border:1px solid rgba(226,75,74,0.2);border-radius:8px;padding:10px 14px;font-size:13px;color:#f09595;margin-bottom:1.5rem">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif ?>

    <form method="post" class="space-y-5">
      <label class="block">
        <span style="display:block;font-size:11px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.4);margin-bottom:8px">E-mail</span>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          style="width:100%;background:rgba(240,236,228,0.04);border:1px solid var(--border);border-radius:8px;padding:12px 14px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--cream);outline:none;box-sizing:border-box"
          placeholder="seu@email.com" required>
      </label>

      <label class="block">
        <span style="display:block;font-size:11px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.4);margin-bottom:8px">Senha</span>
        <input type="password" name="password"
          style="width:100%;background:rgba(240,236,228,0.04);border:1px solid var(--border);border-radius:8px;padding:12px 14px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--cream);outline:none;box-sizing:border-box"
          placeholder="••••••••" required>
      </label>

      <button type="submit"
        style="width:100%;padding:13px;background:var(--cream);color:var(--bg);border:none;border-radius:8px;font-size:13px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer;transition:opacity 0.2s">
        Entrar
      </button>
    </form>

    <p style="margin-top:1.5rem;font-size:12.5px;color:rgba(240,236,228,0.3);text-align:center">
      Não tem conta? <a href="register.php" style="color:rgba(240,236,228,0.65);border-bottom:1px solid rgba(240,236,228,0.2);text-decoration:none">Cadastre-se</a>
    </p>

    <hr style="border:none;border-top:1px solid rgba(240,236,228,0.06);margin:2rem 0">

    <div style="background:rgba(240,236,228,0.03);border:1px solid rgba(240,236,228,0.07);border-radius:8px;padding:14px 16px">
      <p style="font-size:11px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.3);margin:0 0 8px">SuperUser seeded</p>
      <p style="font-size:12.5px;color:rgba(240,236,228,0.4);margin:3px 0">E-mail: <span style="color:rgba(240,236,228,0.65);font-weight:500">superuser@example.com</span></p>
      <p style="font-size:12.5px;color:rgba(240,236,228,0.4);margin:3px 0">Senha: <span style="color:rgba(240,236,228,0.65);font-weight:500">superpass123</span></p>
      <p style="font-size:11px;color:rgba(240,236,228,0.2);margin-top:6px">Este SuperUser já é criado no init.sql.</p>
    </div>

  </div>
</main>
<?php include 'partials/footer.php';
