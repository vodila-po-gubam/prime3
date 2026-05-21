<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$data     = $_POST;
$formType = $data['form_type'] ?? 'unknown';

unset($data['form_type']);

$stmt = $pdo->prepare("
    INSERT INTO submissions (form_type, data)
    VALUES (:type, :data)
");

$stmt->execute([
    ':type' => $formType,
    ':data' => json_encode($data, JSON_UNESCAPED_UNICODE),
]);

echo json_encode([
    'success' => true,
    'message' => 'Спасибо! Ваша заявка принята.',
], JSON_UNESCAPED_UNICODE);
