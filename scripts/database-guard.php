<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$appDir = rtrim((string) ($argv[1] ?? ''), DIRECTORY_SEPARATOR);
$expected = (string) ($argv[2] ?? 'agenda-enfas');

if ($appDir === '' || ! is_file($appDir.'/vendor/autoload.php') || ! is_file($appDir.'/bootstrap/app.php')) {
    fwrite(STDERR, "Diretório Laravel inválido: {$appDir}\n");
    exit(19);
}

require $appDir.'/vendor/autoload.php';

$app = require $appDir.'/bootstrap/app.php';
$app->useEnvironmentPath($appDir);
$app->make(Kernel::class)->bootstrap();

$configured = (string) config(
    'database.connections.'.config('database.default').'.database'
);

try {
    $actual = (string) (DB::selectOne('SELECT DATABASE() AS db')->db ?? '');
} catch (Throwable $e) {
    fwrite(STDERR, "Falha ao consultar o banco de dados: {$e->getMessage()}\n");
    exit(20);
}

if ($configured !== $expected) {
    fwrite(
        STDERR,
        "DB configurado inesperado: ".($configured !== '' ? $configured : 'vazio').". Esperado: {$expected}\n"
    );
    exit(21);
}

if ($actual !== $expected) {
    fwrite(
        STDERR,
        "SELECT DATABASE() retornou ".($actual !== '' ? $actual : 'vazio').". Esperado: {$expected}\n"
    );
    exit(22);
}

fwrite(STDOUT, $actual.PHP_EOL);
