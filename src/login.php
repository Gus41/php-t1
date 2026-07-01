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
                    $redirect = $_SESSION['redirect_after_auth'] ?? 'index.php';
                    unset($_SESSION['redirect_after_auth']);
                    header('Location: ' . $redirect);
                    exit;
                }
                $message = 'E-mail ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao entrar: ' . $e->getMessage();
        }
    }
}

$title = 'Login — E-System';
include 'partials/header.php';
?>
<main class="flex-grow flex items-center justify-center ml-page">
  <div class="ml-card" style="width:100%;max-width:400px">

    <h1 class="ml-page-title">Bem-vindo.</h1>
    <p class="ml-page-sub">Acesse sua conta para continuar.</p>

    <?php if ($message): ?>
      <div class="ml-alert-error"><?= htmlspecialchars($message) ?></div>
    <?php endif ?>

    <form method="post" style="display:flex;flex-direction:column;gap:16px">
      <label>
        <span class="ml-label">E-mail</span>
        <input type="email" name="email" class="ml-input" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          placeholder="seu@email.com" required>
      </label>
      <label>
        <span class="ml-label">Senha</span>
        <input type="password" name="password" class="ml-input" placeholder="••••••••" required>
      </label>
      <button type="submit" class="ml-btn ml-btn-primary" style="width:100%">Entrar</button>
    </form>

    <p style="margin-top:1.25rem;font-size:12.5px;color:rgba(240,236,228,0.3);text-align:center">
      Não tem conta? <a href="register.php" class="ml-link" style="border-bottom:1px solid rgba(240,236,228,0.2);text-decoration:none">Cadastre-se</a>
    </p>

    <hr style="border:none;border-top:1px solid rgba(240,236,228,0.06);margin:2rem 0">

    <div style="background:rgba(240,236,228,0.03);border:1px solid rgba(240,236,228,0.07);border-radius:8px;padding:14px 16px">
      <p style="font-size:11px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.3);margin:0 0 8px">SuperUser seeded</p>
      <p style="font-size:12.5px;color:rgba(240,236,228,0.4);margin:3px 0">E-mail: <span style="color:rgba(240,236,228,0.65);font-weight:500">superuser@example.com</span></p>
      <p style="font-size:12.5px;color:rgba(240,236,228,0.4);margin:3px 0">Senha: <span style="color:rgba(240,236,228,0.65);font-weight:500">superpass123</span></p>
    </div>
  </div>
</main>
<?php include 'partials/footer.php';
