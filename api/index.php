<?php

// A funcao da Vercel so pode gravar arquivos temporarios em /tmp.
$storage = sys_get_temp_dir().'/revisar';
foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs', 'bootstrap'] as $directory) {
    $path = $storage.'/'.$directory;
    if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
        throw new RuntimeException('Nao foi possivel preparar o armazenamento temporario.');
    }
}

$paths = [
    'LARAVEL_STORAGE_PATH' => $storage,
    'VIEW_COMPILED_PATH' => $storage.'/framework/views',
    'APP_PACKAGES_CACHE' => $storage.'/bootstrap/packages.php',
    'APP_SERVICES_CACHE' => $storage.'/bootstrap/services.php',
    'APP_CONFIG_CACHE' => $storage.'/bootstrap/config.php',
    'APP_ROUTES_CACHE' => $storage.'/bootstrap/routes.php',
    'APP_EVENTS_CACHE' => $storage.'/bootstrap/events.php',
];
foreach ($paths as $name => $value) {
    putenv("$name=$value");
    $_ENV[$name] = $_SERVER[$name] = $value;
}

if (getenv('VERCEL')) {
    // A plataforma termina TLS antes de encaminhar a requisicao ao PHP.
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
}

// A pasta da funcao nao faz parte da URL base da aplicacao.
// Sem isso, Symfony remove /api das rotas como /api/pessoas.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
