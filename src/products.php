<?php
require_once 'services/SessionsService.php';
require_once 'dao/ProductDAO.php';
require_once 'dao/SupplierDAO.php';
require_once 'services/ProductService.php';

$session = new SessionManager();
$user = $session->currentUser();

$canManageProducts = $user && $session->hasRole(['superuser', 'admin']);

if (!$canManageProducts) {
    header('Location: index.php');
    exit;
}

$productDAO = new ProductDAO();
$supplierDAO = new SupplierDAO();
$productService = new ProductService($productDAO, $supplierDAO);
$message = '';
$messageType = 'success';
$formData = $productService->getFormData();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete' && isset($_POST['product_id'])) {
        $productService->delete((int)$_POST['product_id']);
        header('Location: products.php?message=' . urlencode('Produto excluído com sucesso.'));
        exit;
    }

    $result = $productService->save($_POST);
    if ($result['success']) {
        $isEditing = isset($_POST['product_id']) && $_POST['product_id'] !== '';
        $message = $result['message'];
        $messageType = 'success';

        if ($isEditing) {
            header('Location: products.php?message=' . urlencode($result['message']));
            exit;
        }

        $formData = $productService->getFormData();
    } else {
        $message = $result['message'];
        $messageType = 'error';
        $formData = array_merge($formData, $_POST);
        $formData['product_id'] = isset($_POST['product_id']) && $_POST['product_id'] !== '' ? (int)$_POST['product_id'] : null;
    }
}

if (isset($_GET['message'])) {
    $message = trim($_GET['message']);
    $messageType = 'success';
}

if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $loadedData = $productService->getFormData($editId);
    if ($loadedData['product_id'] !== null) {
        $formData = $loadedData;
    } else {
        $message = 'Produto não encontrado.';
    }
}

$products = $productDAO->findAllWithSupplier();
$suppliers = $supplierDAO->findAllWithAddress();

include 'partials/header.php';
?>
<main class="flex-grow flex justify-center px-6 py-12">
  <div style="width:100%;max-width:1200px">
    <div style="display:grid;grid-template-columns:1fr 1.3fr;gap:30px">

      <section style="border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03)">
        <h1 style="font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;letter-spacing:-0.01em;margin:0 0 8px">
          <?= $formData['product_id'] ? 'Editar Produto' : 'Cadastrar Produto' ?>.
        </h1>
        <p style="font-size:13px;font-weight:300;color:rgba(240,236,228,0.35);margin:0 0 2rem">Use o formulário para criar ou atualizar um produto.</p>

        <?php if ($message): ?>
          <div style="background:<?= $messageType === 'error' ? 'rgba(226,75,74,0.08)' : 'rgba(31,198,156,0.12)' ?>;border:1px solid <?= $messageType === 'error' ? 'rgba(226,75,74,0.2)' : 'rgba(31,198,156,0.22)' ?>;border-radius:8px;padding:12px 14px;font-size:13px;color:<?= $messageType === 'error' ? '#f09595' : '#d5f7ef' ?>;margin-bottom:1.25rem">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif ?>

        <?php
          $inputStyle = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:11px 13px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
          $labelStyle = "font-size:10.5px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.38);display:block;margin-bottom:7px";
          $sectionStyle = "font-size:10px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:rgba(240,236,228,0.25);margin:1.75rem 0 1rem;padding-bottom:8px;border-bottom:1px solid rgba(240,236,228,0.06)";
        ?>

        <form method="post" style="display:flex;flex-direction:column">
          <input type="hidden" name="action" value="save">
          <?php if ($formData['product_id']): ?>
            <input type="hidden" name="product_id" value="<?= htmlspecialchars((string)$formData['product_id']) ?>">
          <?php endif ?>

          <p style="<?= $sectionStyle ?>">Dados do produto</p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <label style="display:flex;flex-direction:column;grid-column:span 2">
              <span style="<?= $labelStyle ?>">Fornecedor</span>
              <select name="supplier_id" style="<?= $inputStyle ?>" required>
                <option value="">Selecione um fornecedor</option>
                <?php foreach ($suppliers as $supplier): ?>
                  <option value="<?= htmlspecialchars((string)$supplier['id']) ?>" <?= (string)$formData['supplier_id'] === (string)$supplier['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($supplier['name']) ?>
                  </option>
                <?php endforeach ?>
              </select>
            </label>

            <label style="display:flex;flex-direction:column;grid-column:span 2">
              <span style="<?= $labelStyle ?>">Nome</span>
              <input type="text" name="name" value="<?= htmlspecialchars($formData['name']) ?>" placeholder="Nome do produto" style="<?= $inputStyle ?>" required>
            </label>

            <label style="display:flex;flex-direction:column;grid-column:span 2">
              <span style="<?= $labelStyle ?>">Descrição</span>
              <textarea name="description" placeholder="Descrição do produto" style="<?= $inputStyle ?>;min-height:110px;resize:vertical" required><?= htmlspecialchars($formData['description']) ?></textarea>
            </label>

            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Categoria</span>
              <input type="text" name="category" value="<?= htmlspecialchars($formData['category']) ?>" placeholder="Categoria" style="<?= $inputStyle ?>">
            </label>

            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">SKU</span>
              <input type="text" name="sku" value="<?= htmlspecialchars($formData['sku']) ?>" placeholder="SKU / código" style="<?= $inputStyle ?>" required>
            </label>

            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Preço</span>
              <input type="number" name="price" step="0.01" min="0.01" value="<?= htmlspecialchars($formData['price']) ?>" placeholder="0,00" style="<?= $inputStyle ?>" required>
            </label>

            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Estoque</span>
              <input type="number" name="stock" step="1" min="0" value="<?= htmlspecialchars($formData['stock']) ?>" placeholder="0" style="<?= $inputStyle ?>" required>
            </label>

            <label style="display:flex;flex-direction:column;grid-column:span 2">
              <span style="<?= $labelStyle ?>">Status</span>
              <select name="status" style="<?= $inputStyle ?>">
                <option value="ativo" <?= $formData['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= $formData['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
              </select>
            </label>
          </div>

          <button type="submit" style="width:100%;padding:13px;margin-top:1.75rem;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer">
            <?= $formData['product_id'] ? 'Salvar alterações' : 'Cadastrar produto' ?>
          </button>
        </form>
      </section>

      <section style="max-width: 660px; border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03)">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px">
          <div>
            <h2 style="font-family:'DM Serif Display',serif;font-size:28px;font-weight:400;margin:0">Produtos</h2>
            <p style="font-size:13px;font-weight:300;color:rgba(240,236,228,0.35);margin:8px 0 0">Lista de produtos cadastrados.</p>
          </div>
        </div>

        <?php if (empty($products)): ?>
          <div style="border:1px solid rgba(240,236,228,0.08);border-radius:10px;padding:48px;text-align:center;font-size:13px;color:rgba(240,236,228,0.3);letter-spacing:0.04em">
            Nenhum produto encontrado.
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;border:1px solid rgba(240,236,228,0.08);border-radius:10px">
            <table style="width:100%;border-collapse:collapse;font-size:13px;color:#f0ece4">
              <thead>
                <tr style="border-bottom:1px solid rgba(240,236,228,0.08)">
                  <?php foreach (['ID','Nome','Fornecedor','SKU','Preço','Estoque','Status','Ações'] as $col): ?>
                    <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:rgba(240,236,228,0.28);white-space:nowrap">
                      <?= $col ?>
                    </th>
                  <?php endforeach ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($products as $i => $product):
                  $rowBg = $i % 2 === 0 ? 'transparent' : 'rgba(240,236,228,0.02)';
                  $status = $product['status'] ?? 'ativo';
                  $statusBadge = $status === 'ativo'
                    ? ['rgba(31,198,156,0.12)', '#5eead4', 'Ativo']
                    : ['rgba(240,236,228,0.07)', 'rgba(240,236,228,0.5)', 'Inativo'];
                ?>
                  <tr style="border-bottom:1px solid rgba(240,236,228,0.05);background:<?= $rowBg ?>;transition:background 0.15s"
                      onmouseover="this.style.background='rgba(240,236,228,0.05)'"
                      onmouseout="this.style.background='<?= $rowBg ?>'">
                    <td style="padding:14px 16px;color:rgba(240,236,228,0.3);font-size:12px;font-weight:500;white-space:nowrap">#<?= htmlspecialchars((string)$product['id']) ?></td>
                    <td style="padding:14px 16px;color:#f0ece4;font-weight:500;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($product['name']) ?></td>
                    <td style="padding:14px 16px;color:rgba(240,236,228,0.75);white-space:nowrap"><?= htmlspecialchars($product['supplier_name'] ?? '—') ?></td>
                    <td style="padding:14px 16px;color:rgba(240,236,228,0.45);white-space:nowrap"><?= htmlspecialchars($product['sku']) ?></td>
                    <td style="padding:14px 16px;color:rgba(240,236,228,0.75);white-space:nowrap">R$ <?= number_format((float)$product['price'], 2, ',', '.') ?></td>
                    <td style="padding:14px 16px;color:rgba(240,236,228,0.75);white-space:nowrap"><?= htmlspecialchars((string)$product['stock']) ?></td>
                    <td style="padding:14px 16px">
                      <span style="background:<?= $statusBadge[0] ?>;color:<?= $statusBadge[1] ?>;border:1px solid <?= $statusBadge[1] ?>;border-radius:6px;padding:3px 10px;font-size:11px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;white-space:nowrap">
                        <?= $statusBadge[2] ?>
                      </span>
                    </td>
                    <td style="padding:14px 16px;white-space:nowrap">
                      <a href="products.php?edit_id=<?= htmlspecialchars((string)$product['id']) ?>" style="font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#7dd3fc;text-decoration:none;margin-right:14px">Editar</a>
                      <form method="post" style="display:inline-block;margin:0">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?= htmlspecialchars((string)$product['id']) ?>">
                        <button type="submit" style="font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#fda4af;background:transparent;border:none;cursor:pointer" onclick="return confirm('Deseja excluir este produto?');">Excluir</button>
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
<?php include 'partials/footer.php'; ?>
