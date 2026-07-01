<?php

require_once __DIR__ . '/../dao/ProductDAO.php';
require_once __DIR__ . '/../dao/SupplierDAO.php';
require_once __DIR__ . '/../models/Product.php';

class ProductService {
    private ProductDAO $dao;
    private SupplierDAO $supplierDao;

    public function __construct(ProductDAO $dao, SupplierDAO $supplierDao) {
        $this->dao = $dao;
        $this->supplierDao = $supplierDao;
    }

    public function save(array $data, array $files = []): array {
        $productId = isset($data['product_id']) && $data['product_id'] !== '' ? (int)$data['product_id'] : null;
        $supplierId = isset($data['supplier_id']) && $data['supplier_id'] !== '' ? (int)$data['supplier_id'] : null;
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $category = trim($data['category'] ?? '');
        $priceRaw = trim((string)($data['price'] ?? ''));
        $stockRaw = trim((string)($data['stock'] ?? ''));
        $sku = trim($data['sku'] ?? '');
        $status = trim($data['status'] ?? 'ativo');

        if ($name === '') {
            return ['success' => false, 'message' => 'O nome do produto é obrigatório.'];
        }

        if ($description === '') {
            return ['success' => false, 'message' => 'A descrição do produto é obrigatória.'];
        }

        if ($supplierId === null || $supplierId <= 0) {
            return ['success' => false, 'message' => 'Selecione um fornecedor para o produto.'];
        }

        $supplier = $this->supplierDao->findById($supplierId);
        if (!$supplier) {
            return ['success' => false, 'message' => 'Fornecedor informado não foi encontrado.'];
        }

        if ($priceRaw === '' || !is_numeric($priceRaw) || (float)$priceRaw <= 0) {
            return ['success' => false, 'message' => 'O preço deve ser maior que zero.'];
        }

        if ($stockRaw === '' || filter_var($stockRaw, FILTER_VALIDATE_INT) === false || (int)$stockRaw < 0) {
            return ['success' => false, 'message' => 'O estoque não pode ser negativo.'];
        }

        if ($sku === '') {
            return ['success' => false, 'message' => 'O SKU do produto é obrigatório.'];
        }

        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $status = 'ativo';
        }

        if ($this->skuExists($sku, $productId)) {
            return ['success' => false, 'message' => 'Já existe um produto cadastrado com esse SKU.'];
        }

        $existingPaths = $productId !== null ? $this->dao->getAllImagePaths($productId) : [];
        $keepImages = array_values(array_filter(
            (array)($data['keep_images'] ?? []),
            fn($p) => is_string($p) && $p !== '' && in_array($p, $existingPaths, true)
        ));

        $removedPaths = array_diff($existingPaths, $keepImages);
        $newPaths = $this->processImageUploads($files['images'] ?? null);
        if (isset($newPaths['error'])) {
            return ['success' => false, 'message' => $newPaths['error']];
        }

        $allImages = array_values(array_merge($keepImages, $newPaths));
        $imagePath = $allImages[0] ?? null;

        $product = new Product(
            $productId,
            $supplierId,
            $name,
            $description,
            $category !== '' ? $category : null,
            (float)$priceRaw,
            (int)$stockRaw,
            $sku,
            $status,
            $supplier->getName(),
            null,
            $imagePath
        );

        $savedId = $this->dao->save($product);
        $this->dao->saveImages($savedId, $allImages);

        foreach ($removedPaths as $path) {
            $this->unlinkImage($path);
        }

        return [
            'success' => true,
            'message' => $productId ? 'Produto atualizado com sucesso.' : 'Produto cadastrado com sucesso.',
        ];
    }

    public function getAll(): array {
        return $this->dao->findAllWithSupplier();
    }

    public function search(string $query): array {
        return $this->dao->searchByNameOrSku($query);
    }

    public function delete(int $id): void {
        foreach ($this->dao->getAllImagePaths($id) as $path) {
            $this->unlinkImage($path);
        }
        $this->dao->delete($id);
    }

    public function getFormData(?int $productId = null): array {
        $empty = [
            'product_id'  => null,
            'supplier_id' => '',
            'name'        => '',
            'description' => '',
            'category'    => '',
            'price'       => '',
            'stock'       => '',
            'sku'         => '',
            'status'      => 'ativo',
            'image_path'  => null,
            'images'      => [],
        ];

        if ($productId === null) {
            return $empty;
        }

        $product = $this->dao->findById($productId);
        if (!$product) {
            return $empty;
        }

        $images = $this->dao->getAllImagePaths($productId);

        return [
            'product_id'  => $product->getId(),
            'supplier_id' => $product->getSupplierId(),
            'name'        => $product->getName(),
            'description' => $product->getDescription(),
            'category'    => $product->getCategory() ?? '',
            'price'       => number_format($product->getPrice(), 2, '.', ''),
            'stock'       => (string)$product->getStock(),
            'sku'         => $product->getSku(),
            'status'      => $product->getStatus(),
            'image_path'  => $images[0] ?? $product->getImagePath(),
            'images'      => $images,
        ];
    }

    private function processImageUploads(?array $fileBag): array {
        if (!$fileBag || !isset($fileBag['name'])) {
            return [];
        }

        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $names = $fileBag['name'];
        if (!is_array($names)) {
            $names = [$names];
            $fileBag = [
                'name'     => [$fileBag['name']],
                'type'     => [$fileBag['type']],
                'tmp_name' => [$fileBag['tmp_name']],
                'error'    => [$fileBag['error']],
                'size'     => [$fileBag['size']],
            ];
        }

        $saved = [];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        foreach ($names as $i => $name) {
            if (($fileBag['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = $fileBag['tmp_name'][$i];
            $mime = finfo_file($finfo, $tmp);
            if (!in_array($mime, $allowed, true)) {
                finfo_close($finfo);
                return ['error' => 'Formato inválido em "' . $name . '". Use JPG, PNG, GIF ou WebP.'];
            }
            $ext = explode('/', $mime)[1];
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            $filename = uniqid('prod_', true) . '.' . $ext;
            if (!move_uploaded_file($tmp, $uploadDir . $filename)) {
                finfo_close($finfo);
                return ['error' => 'Não foi possível salvar a imagem "' . $name . '".'];
            }
            $saved[] = 'uploads/products/' . $filename;
        }

        finfo_close($finfo);
        return $saved;
    }

    private function unlinkImage(string $path): void {
        $file = __DIR__ . '/../' . ltrim($path, '/');
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    private function skuExists(string $sku, ?int $excludeId = null): bool {
        $existingProduct = $this->dao->findBySku($sku);

        if ($existingProduct === null) {
            return false;
        }

        if ($excludeId !== null && $existingProduct->getId() === $excludeId) {
            return false;
        }

        return true;
    }
}
