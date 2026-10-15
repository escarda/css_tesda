<?php
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'success' => true,
    'message' => 'CSS TESDA API is working',
    'server' => 'Hostinger',
    'timestamp' => date('c')
], JSON_UNESCAPED_SLASHES);