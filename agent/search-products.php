<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/algolia.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Agent-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

$token = isset($_SERVER['HTTP_X_AGENT_TOKEN']) ? (string) $_SERVER['HTTP_X_AGENT_TOKEN'] : '';
if (!hash_equals(AGENT_SECRET_TOKEN, $token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Nao autorizado']);
    exit;
}

$input = file_get_contents('php://input');
$payload = json_decode($input, true);
$query = '';

if (is_array($payload) && isset($payload['query'])) {
    $query = trim((string) $payload['query']);
}

if ($query === '' && isset($_GET['query'])) {
    $query = trim((string) $_GET['query']);
}

if ($query === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Query nao informada']);
    exit;
}

$hits = searchAlgolia($query);
if ($hits === []) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar produtos']);
    exit;
}

$products = formatProducts($hits);

echo json_encode([
    'total' => count($products),
    'query' => $query,
    'products' => $products,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
