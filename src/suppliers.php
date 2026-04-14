<?php
require_once 'services/SessionsService.php';
require_once 'dao/SupplierDAO.php';
require_once 'dao/AddressDAO.php';
require_once 'models/Supplier.php';
require_once 'models/Address.php';

$session = new SessionManager();
$user = $session->currentUser();
if (!$user || !$session->hasRole(['superuser', 'admin'])) {
    header('Location: index.php');
    exit;
}

$supplierDAO = new SupplierDAO();
$addressDAO = new AddressDAO();
$message = '';
$errors = [];
$editSupplier = null;
$editAddress = null;

$formData = [
    'supplier_id' => null,
    'name' => '',
    'phone' => '',
    'email' => '',
    'street' => '',
    'complement' => '',
    'neighborhood' => '',
    'city' => '',
    'state' => '',
    'zip_code' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && isset($_POST['supplier_id'])) {
        $supplierDAO->delete((int)$_POST['supplier_id']);
        header('Location: suppliers.php?message=' . urlencode('Fornecedor excluído com sucesso.'));
        exit;
    }

    $formData = array_merge($formData, $_POST);
    $name = trim($formData['name']);
    $phone = trim($formData['phone']);
    $email = trim($formData['email']);
    $street = trim($formData['street']);
    $complement = trim($formData['complement']);
    $neighborhood = trim($formData['neighborhood']);
    $city = trim($formData['city']);
    $state = trim($formData['state']);
    $zipCode = trim($formData['zip_code']);

    if ($name === '' || $phone === '' || $email === '' || $street === '' || $neighborhood === '' || $city === '' || $state === '' || $zipCode === '') {
        $errors[] = 'Preencha todos os campos obrigatórios.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-mail inválido.';
    }

    $supplierId = isset($formData['supplier_id']) ? (int)$formData['supplier_id'] : null;
    if ($supplierDAO->emailExists($email, $supplierId ?: null)) {
        $errors[] = 'Já existe um fornecedor com este e-mail.';
    }

    if (empty($errors)) {
        $address = new Address(
            null,
            $street,
            $complement !== '' ? $complement : null,
            $city,
            $state,
            $neighborhood,
            $zipCode
        );

        if ($supplierId) {
            $editSupplier = $supplierDAO->findById($supplierId);
            if ($editSupplier) {
                $address->setId($editSupplier->getAddressId());
            }
        }

        $addressId = $addressDAO->save($address);
        $supplier = new Supplier(
            $supplierId ?: null,
            $name,
            $phone,
            $email,
            $addressId
        );
        $supplierDAO->save($supplier);

        $message = $supplierId ? 'Fornecedor atualizado com sucesso.' : 'Fornecedor cadastrado com sucesso.';
        if ($supplierId) {
            header('Location: suppliers.php?message=' . urlencode($message));
            exit;
        }

        $formData = [
            'supplier_id' => null,
            'name' => '',
            'phone' => '',
            'email' => '',
            'street' => '',
            'complement' => '',
            'neighborhood' => '',
            'city' => '',
            'state' => '',
            'zip_code' => '',
        ];
    }
}

if (isset($_GET['message'])) {
    $message = trim($_GET['message']);
}

if (isset($_GET['edit_id'])) {
    $editSupplier = $supplierDAO->findById((int)$_GET['edit_id']);
    if ($editSupplier) {
        $formData['supplier_id'] = $editSupplier->getId();
        $formData['name'] = $editSupplier->getName();
        $formData['phone'] = $editSupplier->getPhone();
        $formData['email'] = $editSupplier->getEmail();
        $editAddress = $addressDAO->findById($editSupplier->getAddressId());
        if ($editAddress) {
            $formData['street'] = $editAddress->getStreet();
            $formData['complement'] = $editAddress->getComplement() ?? '';
            $formData['neighborhood'] = $editAddress->getNeighborhood();
            $formData['city'] = $editAddress->getCity();
            $formData['state'] = $editAddress->getState();
            $formData['zip_code'] = $editAddress->getZipCode();
        }
    } else {
        $message = 'Fornecedor não encontrado.';
    }
}

$suppliers = $supplierDAO->findAllWithAddress();

include 'partials/header.php';
?>
<main class="flex-grow flex justify-center px-6 py-12">
  <div style="width:100%;max-width:1100px">
    <div style="display:grid;grid-template-columns:1fr 1.3fr;gap:30px">

      <section style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03)">
        <h1 style="font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;letter-spacing:-0.01em;margin:0 0 8px"><?= $formData['supplier_id'] ? 'Editar Fornecedor' : 'Cadastrar Fornecedor' ?>.</h1>
        <p style="font-size:13px;font-weight:300;color:rgba(240,236,228,0.35);margin:0 0 2rem">Use o formulário para criar ou atualizar um fornecedor.</p>

        <?php if ($message): ?>
          <div style="background:rgba(31,198,156,0.12);border:1px solid rgba(31,198,156,0.22);border-radius:8px;padding:12px 14px;font-size:13px;color:#d5f7ef;margin-bottom:1.25rem">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif ?>

        <?php if (!empty($errors)): ?>
          <div style="background:rgba(226,75,74,0.08);border:1px solid rgba(226,75,74,0.2);border-radius:8px;padding:12px 14px;margin-bottom:1.25rem;color:#f09595;font-size:13px">
            <?php foreach ($errors as $error): ?>
              <p style="margin:0 0 8px"><?= htmlspecialchars($error) ?></p>
            <?php endforeach ?>
          </div>
        <?php endif ?>

        <?php
          $inputStyle = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:11px 13px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
          $labelStyle = "font-size:10.5px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.38);display:block;margin-bottom:7px";
          $sectionStyle = "font-size:10px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:rgba(240,236,228,0.25);margin:1.75rem 0 1rem;padding-bottom:8px;border-bottom:1px solid rgba(240,236,228,0.06)";
        ?>

        <form method="post" style="display:flex;flex-direction:column">
          <input type="hidden" name="action" value="save">
          <?php if ($formData['supplier_id']): ?>
            <input type="hidden" name="supplier_id" value="<?= htmlspecialchars($formData['supplier_id']) ?>">
          <?php endif ?>

          <p style="<?= $sectionStyle ?>">Dados do fornecedor</p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Nome</span>
              <input type="text" name="name" value="<?= htmlspecialchars($formData['name']) ?>" placeholder="Nome do fornecedor" style="<?= $inputStyle ?>" required>
            </label>
            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Telefone</span>
              <input type="text" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>" placeholder="(00) 00000-0000" style="<?= $inputStyle ?>" required>
            </label>
            <label style="display:flex;flex-direction:column;grid-column:span 2">
              <span style="<?= $labelStyle ?>">E-mail</span>
              <input type="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" placeholder="contato@fornecedor.com" style="<?= $inputStyle ?>" required>
            </label>
          </div>

          <p style="<?= $sectionStyle ?>">Endereço</p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <label style="display:flex;flex-direction:column;grid-column:span 2">
              <span style="<?= $labelStyle ?>">Rua</span>
              <input type="text" name="street" value="<?= htmlspecialchars($formData['street']) ?>" placeholder="Rua, avenida..." style="<?= $inputStyle ?>" required>
            </label>
            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Complemento</span>
              <input type="text" name="complement" value="<?= htmlspecialchars($formData['complement']) ?>" placeholder="Apto, sala..." style="<?= $inputStyle ?>">
            </label>
            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Bairro</span>
              <input type="text" name="neighborhood" value="<?= htmlspecialchars($formData['neighborhood']) ?>" placeholder="Bairro" style="<?= $inputStyle ?>" required>
            </label>
            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Cidade</span>
              <input type="text" name="city" value="<?= htmlspecialchars($formData['city']) ?>" placeholder="Cidade" style="<?= $inputStyle ?>" required>
            </label>
            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Estado</span>
              <input type="text" name="state" value="<?= htmlspecialchars($formData['state']) ?>" placeholder="UF" style="<?= $inputStyle ?>" required>
            </label>
            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">CEP</span>
              <input type="text" name="zip_code" value="<?= htmlspecialchars($formData['zip_code']) ?>" placeholder="00000-000" style="<?= $inputStyle ?>" required>
            </label>
          </div>

          <button type="submit" style="width:100%;padding:13px;margin-top:1.75rem;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer">
            <?= $formData['supplier_id'] ? 'Salvar alterações' : 'Cadastrar fornecedor' ?>
          </button>
        </form>
      </section>

      <section style="max-width: 660px; border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03)">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px">
          <div>
            <h2 style="font-family:'DM Serif Display',serif;font-size:28px;font-weight:400;margin:0">Fornecedores</h2>
            <p style="font-size:13px;font-weight:300;color:rgba(240,236,228,0.35);margin:8px 0 0">Lista de fornecedores cadastrados.</p>
          </div>
          <a href="suppliers.php" style="font-size:12px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#f0ece4;text-decoration:none;border:1px solid rgba(240,236,228,0.12);padding:9px 14px;border-radius:10px;">Novo fornecedor</a>
        </div>

        <?php if (empty($suppliers)): ?>
          <div style="border:1px solid rgba(240,236,228,0.08);border-radius:10px;padding:48px;text-align:center;font-size:13px;color:rgba(240,236,228,0.3);letter-spacing:0.04em">
            Nenhum fornecedor encontrado.
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;border:1px solid rgba(240,236,228,0.08);border-radius:10px">
            <table style="width:100%;border-collapse:collapse;font-size:13px;color:#f0ece4">
              <thead>
                <tr style="border-bottom:1px solid rgba(240,236,228,0.08)">
                  <?php foreach (['ID','Nome','E-mail','Telefone','Endereço','Ações'] as $col): ?>
                    <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:rgba(240,236,228,0.28);white-space:nowrap">
                      <?= $col ?>
                    </th>
                  <?php endforeach ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($suppliers as $i => $supplier):
                  $rowBg = $i % 2 === 0 ? 'transparent' : 'rgba(240,236,228,0.02)';
                  $address = htmlspecialchars(trim(implode(', ', array_filter([
                    $supplier['street'] ?? '',
                    $supplier['complement'] ?? '',
                    $supplier['neighborhood'] ?? '',
                    $supplier['city'] ?? '',
                    $supplier['state'] ?? '',
                    $supplier['zip_code'] ?? '',
                  ]))));
                ?>
                  <tr style="border-bottom:1px solid rgba(240,236,228,0.05);background:<?= $rowBg ?>;transition:background 0.15s"
                      onmouseover="this.style.background='rgba(240,236,228,0.05)'"
                      onmouseout="this.style.background='<?= $rowBg ?>'">
                    <td style="padding:14px 16px;color:rgba(240,236,228,0.3);font-size:12px;font-weight:500;white-space:nowrap">#<?= htmlspecialchars($supplier['id']) ?></td>
                    <td style="padding:14px 16px;color:#f0ece4;font-weight:500;"><?= htmlspecialchars($supplier['name']) ?></td>
                    <td style="padding:14px 16px;color:rgba(240,236,228,0.45);white-space:nowrap"><?= htmlspecialchars($supplier['email']) ?></td>
                    <td style="padding:14px 16px;color:rgba(240,236,228,0.75);white-space:nowrap"><?= htmlspecialchars($supplier['phone']) ?></td>
                    <td style="padding:14px 16px;color:rgba(240,236,228,0.75);max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $address ?></td>
                    <td style="padding:14px 16px;white-space:nowrap">
                      <a href="suppliers.php?edit_id=<?= htmlspecialchars($supplier['id']) ?>" style="font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#7dd3fc;text-decoration:none;margin-right:14px">Editar</a>
                      <form method="post" style="display:inline-block;margin:0">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="supplier_id" value="<?= htmlspecialchars($supplier['id']) ?>">
                        <button type="submit" style="font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#fda4af;background:transparent;border:none;cursor:pointer" onclick="return confirm('Deseja excluir este fornecedor?');">Excluir</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
        <?php endif ?>
      </section>
    </div>
  </div>
</main>
<?php include 'partials/footer.php';
