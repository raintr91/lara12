<?php

$parseCorsAllowedOrigins = static function (): array {
    $raw = (string) env('CORS_ALLOWED_ORIGINS', '');
    $parts = array_map('trim', explode(',', $raw));
    $out = [];
    foreach ($parts as $p) {
        if ($p === '') {
            continue;
        }
        if (preg_match('#^https?://#i', $p)) {
            $out[] = $p;
            continue;
        }
        // Chỉ hostname (vd. portal-base.local.com) → https://… — trùng origin trình duyệt qua gateway TLS.
        $out[] = 'https://' . ltrim($p, '/');
    }

    return array_values(array_unique($out));
};

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
    | Chỉ danh sách trong CORS_ALLOWED_ORIGINS (cách nhau dấu phẩy). Có thể ghi full origin (https://…) hoặc chỉ hostname.
    | Không dùng allowed_origins_patterns — tránh regex lệch môi trường dev.
    */
    'allowed_origins' => $parseCorsAllowedOrigins(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
