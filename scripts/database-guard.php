<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$expected = $argv[1] ?? 'agenda-enfas';

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
