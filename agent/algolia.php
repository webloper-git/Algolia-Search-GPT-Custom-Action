<?php

require_once __DIR__ . '/config.php';

function searchAlgolia(string $query): array
{
    $url = sprintf(
        'https://%s-dsn.algolia.net/1/indexes/%s/query',
        rawurlencode(ALGOLIA_APP_ID),
        rawurlencode(ALGOLIA_INDEX_NAME)
    );

    $payload = [
        'query' => $query,
        'hitsPerPage' => 5,
        'attributesToRetrieve' => ['name', 'sku', 'price', 'url', 'description', 'image_url'],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Algolia-Application-Id: ' . ALGOLIA_APP_ID,
            'X-Algolia-API-Key: ' . ALGOLIA_SEARCH_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '' || $httpCode < 200 || $httpCode >= 300) {
        return [];
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data['hits']) || !is_array($data['hits'])) {
        return [];
    }

    return $data['hits'];
}

function formatProducts(array $hits): array
{
    $products = [];

    foreach ($hits as $hit) {
        $rawPrice = null;

        if (isset($hit['price']['BRL']['default'])) {
            $rawPrice = $hit['price']['BRL']['default'];
        } elseif (isset($hit['price']['USD']['default'])) {
            $rawPrice = $hit['price']['USD']['default'];
        } elseif (isset($hit['price'])) {
            $rawPrice = $hit['price'];
        }

        $formattedPrice = 'Consulte o preço';
        if (is_numeric($rawPrice)) {
            $formattedPrice = 'R$ ' . number_format((float) $rawPrice, 2, ',', '.');
        }

        $description = isset($hit['description']) ? (string) $hit['description'] : '';
        if (strlen($description) > 150) {
            $description = substr($description, 0, 150) . '...';
        }

        $products[] = [
            'name' => isset($hit['name']) ? (string) $hit['name'] : '',
            'sku' => isset($hit['sku']) ? (string) $hit['sku'] : '',
            'price' => $formattedPrice,
            'url' => isset($hit['url']) ? (string) $hit['url'] : '',
            'description' => $description,
        ];
    }

    return $products;
}
