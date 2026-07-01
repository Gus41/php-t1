<?php
require_once 'services/SessionsService.php';
require_once 'dao/ProductDAO.php';
require_once 'dao/SupplierDAO.php';
require_once 'services/ProductService.php';

$session = new SessionManager();
$user = $session->currentUser();

if (!$user || !$session->hasRole(['superuser', 'admin'])) {
    header('Location: index.php');
    exit;
}

$productDAO  = new ProductDAO();
$supplierDAO = new SupplierDAO();
$productService = new ProductService($productDAO, $supplierDAO);

$message     = '';
$messageType = 'success';
$formData    = $productService->getFormData();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete' && isset($_POST['product_id'])) {
        $productService->delete((int)$_POST['product_id']);
        header('Location: products.php?message=' . urlencode('Produto excluído com sucesso.'));
        exit;
    }

    $result = $productService->save($_POST, $_FILES);
    if ($result['success']) {
        $isEditing = isset($_POST['product_id']) && $_POST['product_id'] !== '';
        if ($isEditing) {
            header('Location: products.php?message=' . urlencode($result['message']));
            exit;
        }
        $message  = $result['message'];
        $formData = $productService->getFormData();
    } else {
        $message     = $result['message'];
        $messageType = 'error';
        $formData    = array_merge($formData, $_POST);
        $formData['product_id'] = isset($_POST['product_id']) && $_POST['product_id'] !== '' ? (int)$_POST['product_id'] : null;
    }
}

if (isset($_GET['message'])) {
    $message     = trim($_GET['message']);
    $messageType = 'success';
}

if (isset($_GET['edit_id'])) {
    $editId     = (int)$_GET['edit_id'];
    $loadedData = $productService->getFormData($editId);
    if ($loadedData['product_id'] !== null) {
        $formData = $loadedData;
    } else {
        $message = 'Produto não encontrado.';
    }
}

$perPage     = 10;
$page        = max(1, (int)($_GET['page'] ?? 1));
$searchQuery = trim($_GET['search'] ?? '');
$suppliers   = $supplierDAO->findAllWithAddress();

if (!empty($searchQuery)) {
    $products    = $productDAO->searchByNameOrSkuPaginated($searchQuery, $page, $perPage);
    $totalItems  = $productDAO->countSearch($searchQuery);
} else {
    $products    = $productDAO->findAllWithSupplierPaginated($page, $perPage);
    $totalItems  = $productDAO->countAll();
}
$totalPages = (int)ceil($totalItems / $perPage);

include 'partials/header.php';

$inputStyle   = "background:rgba(240,236,228,0.04);border:1px solid rgba(240,236,228,0.1);border-radius:8px;padding:11px 13px;font-size:13.5px;font-family:'DM Sans',sans-serif;color:#f0ece4;outline:none;width:100%;box-sizing:border-box";
$labelStyle   = "font-size:10.5px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;color:rgba(240,236,228,0.38);display:block;margin-bottom:7px";
$sectionStyle = "font-size:10px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:rgba(240,236,228,0.25);margin:1.75rem 0 1rem;padding-bottom:8px;border-bottom:1px solid rgba(240,236,228,0.06)";
?>
<main class="flex-grow flex justify-center px-6 py-12">
  <div style="width:100%;max-width:1200px">
    <div class="rg-form">

      <!-- FORM -->
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

        <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column">
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
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>" <?= (string)$formData['supplier_id'] === (string)$s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
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
              <textarea name="description" style="<?= $inputStyle ?>;min-height:90px;resize:vertical" required><?= htmlspecialchars($formData['description']) ?></textarea>
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

            <label style="display:flex;flex-direction:column">
              <span style="<?= $labelStyle ?>">Status</span>
              <select name="status" style="<?= $inputStyle ?>">
                <option value="ativo" <?= $formData['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= $formData['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
              </select>
            </label>

            <!-- Zona de upload de imagens -->
            <div style="grid-column:span 2">
              <span style="<?= $labelStyle ?>">Imagens do produto</span>

              <div id="drop-zone"
                onclick="document.getElementById('img-input').click()"
                ondragover="event.preventDefault();this.style.borderColor='rgba(240,236,228,0.4)'"
                ondragleave="this.style.borderColor='rgba(240,236,228,0.12)'"
                ondrop="handleDrop(event)"
                style="border:2px dashed rgba(240,236,228,0.12);border-radius:10px;padding:28px 20px;text-align:center;cursor:pointer;transition:border-color 0.2s;background:rgba(240,236,228,0.02)">
                <div style="font-size:28px;margin-bottom:8px">🖼</div>
                <p id="drop-label" style="font-size:13px;color:rgba(240,236,228,0.5);margin:0 0 4px">Arraste imagens ou <span style="color:#f0ece4;font-weight:500">clique para selecionar</span></p>
                <p style="font-size:11px;color:rgba(240,236,228,0.25);margin:0">JPG, PNG, GIF ou WebP — múltiplas permitidas</p>
              </div>
              <input type="file" id="img-input" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple
                style="display:none" onchange="addSelectedImages(this.files)">

              <div id="img-preview" style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px"></div>

              <?php if (!empty($formData['images'])): ?>
                <p style="font-size:11px;color:rgba(240,236,228,0.3);margin:16px 0 8px">Imagens cadastradas (clique no ✕ para remover ao salvar):</p>
                <div id="existing-images" style="display:flex;flex-wrap:wrap;gap:8px">
                  <?php foreach ($formData['images'] as $img): ?>
                    <div class="existing-img-wrap" data-path="<?= htmlspecialchars($img) ?>" style="position:relative">
                      <input type="hidden" name="keep_images[]" value="<?= htmlspecialchars($img) ?>">
                      <img src="<?= htmlspecialchars($img) ?>" alt=""
                        style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid rgba(240,236,228,0.12)">
                      <button type="button" onclick="removeExistingImage(this)"
                        style="position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;border:none;background:#fda4af;color:#0e0e0e;font-size:11px;cursor:pointer;line-height:1" title="Remover">✕</button>
                    </div>
                  <?php endforeach ?>
                </div>
              <?php endif ?>
            </div>
          </div>

          <button type="submit" style="width:100%;padding:13px;margin-top:1.75rem;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer">
            <?= $formData['product_id'] ? 'Salvar alterações' : 'Cadastrar produto' ?>
          </button>
        </form>
      </section>

      <!-- LIST -->
      <section style="max-width:660px;border:1px solid rgba(240,236,228,0.08);border-radius:18px;padding:28px;background:rgba(255,255,255,0.03)">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px">
          <div>
            <h2 style="font-family:'DM Serif Display',serif;font-size:28px;font-weight:400;margin:0">Produtos</h2>
            <p style="font-size:13px;font-weight:300;color:rgba(240,236,228,0.35);margin:8px 0 0">
              <?= $totalItems ?> produto<?= $totalItems !== 1 ? 's' : '' ?> cadastrado<?= $totalItems !== 1 ? 's' : '' ?>.
            </p>
          </div>
        </div>

        <form method="get" style="margin-bottom:20px;display:flex;gap:10px">
          <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Buscar por nome ou SKU" style="<?= $inputStyle ?>;flex:1">
          <button type="submit" style="padding:11px 20px;background:#f0ece4;color:#0e0e0e;border:none;border-radius:8px;font-size:12px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer">Buscar</button>
          <?php if (!empty($searchQuery)): ?>
            <a href="products.php" style="padding:11px 20px;background:rgba(240,236,228,0.1);color:#f0ece4;border:1px solid rgba(240,236,228,0.2);border-radius:8px;font-size:12px;font-weight:500;font-family:'DM Sans',sans-serif;letter-spacing:0.08em;text-transform:uppercase;text-decoration:none">Limpar</a>
          <?php endif ?>
        </form>

        <?php if (empty($products)): ?>
          <div style="border:1px solid rgba(240,236,228,0.08);border-radius:10px;padding:48px;text-align:center;font-size:13px;color:rgba(240,236,228,0.3)">
            Nenhum produto encontrado.
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;border:1px solid rgba(240,236,228,0.08);border-radius:10px">
            <table style="width:100%;border-collapse:collapse;font-size:13px;color:#f0ece4">
              <thead>
                <tr style="border-bottom:1px solid rgba(240,236,228,0.08)">
                  <?php foreach (['','Nome','Fornecedor','Preço','Estoque','Status','Ações'] as $col): ?>
                    <th style="padding:12px 14px;text-align:left;font-size:10px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:rgba(240,236,228,0.28);white-space:nowrap"><?= $col ?></th>
                  <?php endforeach ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($products as $i => $product):
                  $rowBg = $i % 2 === 0 ? 'transparent' : 'rgba(240,236,228,0.02)';
                  $stock = (int)($product['stock'] ?? 0);
                  $status = $product['status'] ?? 'ativo';
                  $badge = $status === 'ativo'
                    ? ['rgba(31,198,156,0.12)', '#5eead4', 'Ativo']
                    : ['rgba(240,236,228,0.07)', 'rgba(240,236,228,0.5)', 'Inativo'];
                ?>
                  <tr style="border-bottom:1px solid rgba(240,236,228,0.05);background:<?= $rowBg ?>"
                      onmouseover="this.style.background='rgba(240,236,228,0.05)'"
                      onmouseout="this.style.background='<?= $rowBg ?>'">
                    <td style="padding:10px 14px">
                      <?php if (!empty($product['image_path'])): ?>
                        <img src="<?= htmlspecialchars($product['image_path']) ?>" alt=""
                          style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid rgba(240,236,228,0.1)">
                      <?php else: ?>
                        <div style="width:36px;height:36px;border-radius:6px;background:rgba(240,236,228,0.05);border:1px solid rgba(240,236,228,0.08);display:flex;align-items:center;justify-content:center;font-size:16px">📦</div>
                      <?php endif ?>
                    </td>
                    <td style="padding:10px 14px;font-weight:500;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                      <?= htmlspecialchars($product['name']) ?>
                    </td>
                    <td style="padding:10px 14px;color:rgba(240,236,228,0.6);white-space:nowrap"><?= htmlspecialchars($product['supplier_name'] ?? '—') ?></td>
                    <td style="padding:10px 14px;white-space:nowrap">R$ <?= number_format((float)$product['price'], 2, ',', '.') ?></td>
                    <td style="padding:10px 14px;color:<?= $stock === 0 ? '#fda4af' : ($stock <= 5 ? '#fcd34d' : 'rgba(240,236,228,0.6)') ?>;white-space:nowrap"><?= $stock ?></td>
                    <td style="padding:10px 14px">
                      <span style="background:<?= $badge[0] ?>;color:<?= $badge[1] ?>;border:1px solid <?= $badge[1] ?>;border-radius:6px;padding:3px 9px;font-size:11px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase">
                        <?= $badge[2] ?>
                      </span>
                    </td>
                    <td style="padding:10px 14px;white-space:nowrap">
                      <a href="products.php?edit_id=<?= $product['id'] ?>" style="font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#7dd3fc;text-decoration:none;margin-right:12px">Editar</a>
                      <form method="post" style="display:inline-block;margin:0">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" style="font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#fda4af;background:transparent;border:none;cursor:pointer" onclick="return confirm('Excluir este produto?')">Excluir</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>

          <?php if ($totalPages > 1): ?>
            <div style="display:flex;gap:6px;justify-content:center;margin-top:18px;flex-wrap:wrap">
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="products.php?page=<?= $i ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>"
                   style="padding:7px 12px;border-radius:6px;font-size:12px;text-decoration:none;<?= $i === $page ? 'background:#f0ece4;color:#0e0e0e;font-weight:600' : 'border:1px solid rgba(240,236,228,0.1);color:rgba(240,236,228,0.5)' ?>">
                  <?= $i ?>
                </a>
              <?php endfor ?>
            </div>
          <?php endif ?>
        <?php endif ?>
      </section>
    </div>
  </div>
</main>
<script>
var selectedImages = [];

function addSelectedImages(files) {
  if (!files || !files.length) return;
  Array.from(files).forEach(function(file) {
    var key = file.name + '|' + file.size + '|' + file.lastModified;
    var exists = selectedImages.some(function(item) { return item.key === key; });
    if (!exists) selectedImages.push({ key: key, file: file });
  });
  syncImageInput();
  previewImages();
}

function syncImageInput() {
  var input = document.getElementById('img-input');
  try {
    var dt = new DataTransfer();
    selectedImages.forEach(function(item) { dt.items.add(item.file); });
    input.files = dt.files;
  } catch (err) {}
}

function removeSelectedImage(key) {
  selectedImages = selectedImages.filter(function(item) { return item.key !== key; });
  syncImageInput();
  previewImages();
}

function previewImages() {
  var container = document.getElementById('img-preview');
  container.innerHTML = '';
  if (!selectedImages.length) return;
  var label = document.getElementById('drop-label');
  if (label) label.innerHTML = '<span style="color:#f0ece4;font-weight:500">' + selectedImages.length + ' imagem(ns)</span> selecionada(s) — clique para adicionar mais';
  selectedImages.forEach(function(item) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var wrap = document.createElement('div');
      wrap.style.cssText = 'position:relative';
      var img = document.createElement('img');
      img.src = e.target.result;
      img.style.cssText = 'width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid rgba(240,236,228,0.3)';
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = '✕';
      btn.title = 'Remover';
      btn.style.cssText = 'position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;border:none;background:#fda4af;color:#0e0e0e;font-size:11px;cursor:pointer;line-height:1';
      btn.onclick = function() { removeSelectedImage(item.key); };
      wrap.appendChild(img);
      wrap.appendChild(btn);
      container.appendChild(wrap);
    };
    reader.readAsDataURL(item.file);
  });
}

function removeExistingImage(btn) {
  var wrap = btn.closest('.existing-img-wrap');
  if (wrap) wrap.remove();
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('drop-zone').style.borderColor = 'rgba(240,236,228,0.12)';
  var dt = e.dataTransfer;
  if (!dt.files.length) return;
  addSelectedImages(dt.files);
}
</script>
<?php include 'partials/footer.php'; ?>
