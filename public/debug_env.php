<?php
$envPath = __DIR__ . '/.env';
$content = file_get_contents($envPath);
$content = str_replace(
    'CI_ENVIRONMENT = production',
    'CI_ENVIRONMENT = development',
    $content
);
file_put_contents($envPath, $content);
echo "OK";
