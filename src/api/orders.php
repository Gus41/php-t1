<?php
/**
 * API REST — Consulta de Pedidos
 *
 * GET /api/orders.php?order_id=X       → retorna pedido + itens
 * GET /api/orders.php?client=NomeCliente → retorna lista de pedidos do cliente
 */

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../dao/OrderDAO.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$orderDAO = new OrderDAO();

// ── Busca por número do pedido ─────────────────────────────────────────────
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
    $order   = $orderDAO->findById($orderId);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido não encontrado.']);
        exit;
    }

    $items = $orderDAO->findAllItemsByOrderId($orderId);

    echo json_encode([
        'order' => [
            'id'           => (int)$order['id'],
            'client'       => $order['user_name'],
            'email'        => $order['user_email'],
            'status'       => $order['status'],
            'total'        => (float)$order['total'],
            'created_at'   => $order['created_at'],
            'sent_at'      => $order['sent_at'],
            'cancelled_at' => $order['cancelled_at'],
        ],
        'items' => array_map(fn($it) => [
            'product_name' => $it['product_name'],
            'quantity'     => (int)$it['quantity'],
            'unit_price'   => (float)$it['unit_price'],
            'subtotal'     => round((float)$it['unit_price'] * (int)$it['quantity'], 2),
            'image_path'   => $it['image_path'],
        ], $items),
    ]);
    exit;
}

// ── Busca por nome do cliente ──────────────────────────────────────────────
if (isset($_GET['client']) && trim($_GET['client']) !== '') {
    $client = trim($_GET['client']);
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $orders = $orderDAO->search($client, $page, 20);
    $total  = $orderDAO->countSearch($client);

    if (empty($orders)) {
        http_response_code(404);
        echo json_encode(['error' => 'Nenhum pedido encontrado para "' . htmlspecialchars($client) . '".']);
        exit;
    }

    echo json_encode([
        'query'  => $client,
        'total'  => $total,
        'page'   => $page,
        'orders' => array_map(fn($o) => [
            'id'           => (int)$o['id'],
            'client'       => $o['user_name'],
            'email'        => $o['user_email'],
            'status'       => $o['status'],
            'total'        => (float)$o['total'],
            'created_at'   => $o['created_at'],
            'sent_at'      => $o['sent_at'],
            'cancelled_at' => $o['cancelled_at'],
        ], $orders),
    ]);
    exit;
}

// ── Parâmetros inválidos ────────────────────────────────────────────────────
http_response_code(400);
echo json_encode([
    'error' => 'Parâmetros inválidos. Use ?order_id=X ou ?client=NomeCliente',
    'usage' => [
        'by_order'  => '/api/orders.php?order_id=1',
        'by_client' => '/api/orders.php?client=João',
    ],
]);
