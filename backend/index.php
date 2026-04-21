<?php
// Simple root redirect or API info
header('Content-Type: application/json');
echo json_encode([
    'service' => 'Civic Connect API',
    'status' => 'running',
    'docs' => 'See /api/ for endpoints'
]);