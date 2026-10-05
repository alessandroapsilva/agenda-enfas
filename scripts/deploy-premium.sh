#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
BUILD_ASSETS="${BUILD_ASSETS:-1}"

if [[ ! -f artisan ]]; then
    echo "ERRO: execute o deploy dentro do repositório Laravel."
    exit 1
fi

echo "==> ENFAS Agenda Premium deploy"
echo "Diretório: $ROOT_DIR"
echo "PHP: $($PHP_BIN -r 'echo PHP_VERSION;')"

if ! $PHP_BIN -r "exit(version_compare(PHP_VERSION, '8.4.1', '>=') ? 0 : 1);"; then
    echo "ERRO: o composer.lock atual exige PHP 8.4.1 ou superior."
    exit 1
fi

$PHP_BIN artisan down || true

bring_up() {
    $PHP_BIN artisan up >/dev/null 2>&1 || true
}
trap bring_up EXIT

echo "==> Dependências PHP"
COMPOSER_PATH="$(command -v "$COMPOSER_BIN" || true)"
if [[ -z "$COMPOSER_PATH" ]]; then
    echo "ERRO: Composer não encontrado no PATH."
    exit 1
fi

$PHP_BIN "$COMPOSER_PATH" install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-progress

echo "==> Migrations"
$PHP_BIN artisan migrate --force

echo "==> Seeders clínicos idempotentes"
$PHP_BIN artisan db:seed --class=ClinicalDocumentTemplateSeeder --force
$PHP_BIN artisan db:seed --class=ClinicalProtocolSeeder --force

if [[ "$BUILD_ASSETS" == "1" ]] && command -v npm >/dev/null 2>&1; then
    echo "==> Build frontend"
    npm ci --no-audit --no-fund
    npm run build
fi

echo "==> Limpeza e caches"
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan view:cache

if ! $PHP_BIN artisan route:cache; then
    echo "Aviso: route:cache não pôde ser gerado; mantendo rotas sem cache."
    $PHP_BIN artisan route:clear
fi

$PHP_BIN artisan storage:link >/dev/null 2>&1 || true

echo "==> Reiniciando filas"
$PHP_BIN artisan queue:restart || true

echo "==> Verificações"
$PHP_BIN artisan migrate:status >/dev/null
$PHP_BIN artisan route:list --name=appointments.record >/dev/null
$PHP_BIN artisan route:list --name=clinical-profile >/dev/null
$PHP_BIN artisan route:list --name=clinical-workflow >/dev/null
$PHP_BIN artisan route:list --name=clinical-prescriptions >/dev/null
$PHP_BIN artisan route:list --name=clinical-attachments.ocr >/dev/null

$PHP_BIN artisan up
trap - EXIT

echo "==> Deploy concluído."
