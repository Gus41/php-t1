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

        // ── Carregar dados existentes ────────────────────────────────────────────
        $existing       = $productId !== null ? $this->dao->findById($productId) : null;
        $existingImages = $productId !== null ? $this->dao->findImagesByProductId($productId) : [];
        $imagePath      = $existing ? $existing->getImagePath() : null;

        // ── Processar múltiplos uploads ──────────────────────────────────────────
        $allowed     = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $uploadDir   = __DIR__ . '/../uploads/products/';
        $uploadedPaths = [];

        $rawFiles = $files['images'] ?? null;
        $hasUploads = $rawFiles && is_array($rawFiles['name'])
            && count(array_filter($rawFiles['error'], fn($e) => $e !== UPLOAD_ERR_NO_FILE)) > 0;

        if ($hasUploads) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            foreach ($rawFiles['name'] as $i => $originalName) {
                $err = $rawFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                if ($err === UPLOAD_ERR_NO_FILE) continue;
                if ($err !== UPLOAD_ERR_OK) {
                    return ['success' => false, 'message' => 'Erro ao enviar arquivo "' . htmlspecialchars($originalName) . '".'];
                }
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $rawFiles['tmp_name'][$i]);
                finfo_close($finfo);
                if (!in_array($mime, $allowed, true)) {
                    return ['success' => false, 'message' => 'Formato inválido em "' . htmlspecialchars($originalName) . '". Use JPG, PNG, GIF ou WebP.'];
                }
                $ext = explode('/', $mime)[1];
                if ($ext === 'jpeg') $ext = 'jpg';
                $filename = uniqid('prod_', true) . '.' . $ext;
                if (!move_uploaded_file($rawFiles['tmp_name'][$i], $uploadDir . $filename)) {
                    return ['success' => false, 'message' => 'Não foi possível salvar "' . htmlspecialchars($originalName) . '".'];
                }
                $uploadedPaths[] = 'uploads/products/' . $filename;
            }
            // Remove imagens antigas ao substituir
            foreach ($existingImages as $oldPath) {
                $file = __DIR__ . '/../' . $oldPath;
                if (file_exists($file)) @unlink($file);
            }
            $imagePath = $uploadedPaths[0] ?? $imagePath;
        }

        // Imagens finais da galeria
        $finalImages = !empty($uploadedPaths) ? $uploadedPaths : $existingImages;

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

        // Salva galeria se houve uploads novos
        if (!empty($uploadedPaths)) {
            $this->dao->saveImages($savedId, $uploadedPaths);
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
        $images  = $this->dao->findImagesByProductId($id);
        $product = $this->dao->findById($id);
        $this->dao->delete($id);
        foreach ($images as $path) {
            $file = __DIR__ . '/../' . $path;
            if (file_exists($file)) @unlink($file);
        }
        if ($product && $product->getImagePath()) {
            $file = __DIR__ . '/../' . $product->getImagePath();
            if (file_exists($file)) @unlink($file);
        }
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

        if ($productId === null) return $empty;

        $product = $this->dao->findById($productId);
        if (!$product) return $empty;

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
            'image_path'  => $product->getImagePath(),
            'images'      => $this->dao->findImagesByProductId($product->getId()),
        ];
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
