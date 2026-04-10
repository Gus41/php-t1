<?php
require_once 'services/SessionsService.php';
require_once 'dao/UserDAO.php';
require_once 'dao/AddressDAO.php';
require_once 'models/Address.php';

$session = new SessionManager();
$userSession = $session->currentUser();
if (!$userSession) {
    header('Location: login.php');
    exit;
}

$userDAO = new UserDAO();
$addressDAO = new AddressDAO();
$currentUser = $userDAO->findById($userSession['id']);
$addressData = $addressDAO->findByUserId($userSession['id']);
$address = $addressData['address'];
$addressId = $addressData['address_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete']) && $addressId !== null) {
        $addressDAO->delete($addressId);
        $userDAO->updateAddressId($userSession['id'], null);
        $userSession['address'] = '';
        $session->loginUser($userSession);
        $address = null;
        $addressId = null;
        $message = 'Endereço excluído com sucesso.';
    } else {
        $street = trim($_POST['street'] ?? '');
        $complement = trim($_POST['complement'] ?? '');
        $neighborhood = trim($_POST['neighborhood'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $zipCode = trim($_POST['zip_code'] ?? '');

        if ($street === '' || $city === '' || $state === '' || $neighborhood === '' || $zipCode === '') {
            $message = 'Preencha todos os campos obrigatórios do endereço.';
        } else {
            $addressModel = new Address(
                $addressId,
                $street,
                $complement !== '' ? $complement : null,
                $city,
                $state,
                $neighborhood,
                $zipCode
            );
            $savedId = $addressDAO->save($addressModel);
            if ($addressId === null) {
                $userDAO->updateAddressId($userSession['id'], $savedId);
            }
            $addressId = $savedId;
            $address = $addressDAO->findById($savedId);
            $currentUser = $userDAO->findById($userSession['id']);
            $session->loginUser($currentUser->toArray());
            $message = 'Endereço salvo com sucesso.';
        }
    }
}

include 'partials/header.php'; ?>
<main class="flex-grow flex justify-center px-6 py-12">
  <div style="width:100%;max-width:480px">

    <h1 style="font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;letter-spacing:-0.01em;margin:0 0 4px">Meu Endereço.</h1>
    <p style="font-size:13px;font-weight:300;color:rgba(240,236,228,0.35);margin:0 0 2.5rem">
      <?= $addressId !== null ? 'Edite ou remova seu endereço cadastrado.' : 'Cadastre seu endereço de entrega.' ?>
    </p>

    <?php if ($message): ?>
      <div style="background:rgba(226,75,74,0.08);border:1px solid rgba(226,75,74,0.2);border-radius:8px;padding:10px 14px;font-size:13px;color:#f09595;margin-bottom:1.5rem">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif ?>

    <?php
      $inputStyle = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:11px 13px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
      $labelStyle = "font-size:10.5px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.38);display:block;margin-bottom:7px";
    ?>

    <form method="post" style="display:flex;flex-direction:column;gap:14px">

      <label style="display:flex;flex-direction:column">
        <span style="<?= $labelStyle ?>">Rua</span>
        <input type="text" name="street"
          value="<?= htmlspecialchars($address?->getStreet() ?? ($_POST['street'] ?? '')) ?>"
          placeholder="Nome da rua" style="<?= $inputStyle ?>" required>
      </label>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Complemento</span>
          <input type="text" name="complement"
            value="<?= htmlspecialchars($address?->getComplement() ?? ($_POST['complement'] ?? '')) ?>"
            placeholder="Apto, bloco..." style="<?= $inputStyle ?>">
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Bairro</span>
          <input type="text" name="neighborhood"
            value="<?= htmlspecialchars($address?->getNeighborhood() ?? ($_POST['neighborhood'] ?? '')) ?>"
            placeholder="Seu bairro" style="<?= $inputStyle ?>" required>
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Cidade</span>
          <input type="text" name="city"
            value="<?= htmlspecialchars($address?->getCity() ?? ($_POST['city'] ?? '')) ?>"
            placeholder="Sua cidade" style="<?= $inputStyle ?>" required>
        </label>

        <label style="display:flex;flex-direction:column">
          <span style="<?= $labelStyle ?>">Estado</span>
          <input type="text" name="state"
            value="<?= htmlspecialchars($address?->getState() ?? ($_POST['state'] ?? '')) ?>"
            placeholder="UF" style="<?= $inputStyle ?>" required>
        </label>
      </div>

      <label style="display:flex;flex-direction:column">
        <span style="<?= $labelStyle ?>">CEP</span>
        <input type="text" name="zip_code"
          value="<?= htmlspecialchars($address?->getZipCode() ?? ($_POST['zip_code'] ?? '')) ?>"
          placeholder="00000-000" style="<?= $inputStyle ?>" required>
      </label>

      <!-- Botões -->
      <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px">

        <button type="submit"
          style="width:100%;padding:13px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer;transition:opacity 0.2s"
          onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
          Salvar Endereço
        </button>

        <?php if ($addressId !== null): ?>
          <button type="submit" name="delete" value="1"
            style="width:100%;padding:13px;background:transparent;color:rgba(240,236,228,0.35);border:1px solid rgba(240,236,228,0.1);border-radius:8px;font-size:12px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer;transition:all 0.2s"
            onmouseover="this.style.borderColor='rgba(226,75,74,0.4)';this.style.color='#f09595';this.style.background='rgba(226,75,74,0.06)'"
            onmouseout="this.style.borderColor='rgba(240,236,228,0.1)';this.style.color='rgba(240,236,228,0.35)';this.style.background='transparent'">
            Excluir Endereço
          </button>
        <?php endif ?>

      </div>

    </form>
  </div>
</main>
 <?php include 'partials/footer.php';
