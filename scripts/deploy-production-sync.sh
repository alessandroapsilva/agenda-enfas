#!/usr/bin/env bash
set -Eeuo pipefail

SRC="${SRC:-/home/agendaenfas/agenda-enfas-premium}"
APP="${APP:-/home/agendaenfas/htdocs/agenda.enfas.com.br}"
BRANCH="${BRANCH:-feature/agenda-premium}"
EXPECTED_HEAD="${EXPECTED_HEAD:-}"

STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="/home/agendaenfas/backups"
BACKUP="$BACKUP_DIR/agenda-full-$STAMP.tar.gz"

MAINT=0
PUBLISHED=0

finish() {
    code=$?

    if [[ $code -ne 0 ]]; then
        echo
        echo "==> Falha detectada; garantindo aplicação online."

        if [[ $PUBLISHED -eq 1 && -f "$BACKUP" ]]; then
            echo "==> Restaurando backup de código."
            tar -xzf "$BACKUP" -C "$APP" || true

            cd "$APP" || true
            sudo -u agendaenfas php artisan optimize:clear || true
            sudo -u agendaenfas php artisan config:cache || true
            sudo -u agendaenfas php artisan route:cache || true
            sudo -u agendaenfas php artisan view:cache || true
        fi
    fi

    if [[ $MAINT -eq 1 ]]; then
        cd "$APP" 2>/dev/null || true
        sudo -u agendaenfas php artisan up >/dev/null 2>&1 || true
    fi

    exit $code
}
trap finish EXIT

echo "=================================================="
echo " ENFAS AGENDA - DEPLOY INTEGRAL"
echo "=================================================="

[[ -d "$SRC/.git" ]] || { echo "SRC não encontrado: $SRC"; exit 1; }
[[ -f "$APP/artisan" ]] || { echo "APP não encontrado: $APP"; exit 1; }

HTTP_BEFORE="$(curl -L -sS -o /dev/null -w "%{http_code}" --max-time 20 https://agenda.enfas.com.br/ || true)"
echo "HTTP antes: $HTTP_BEFORE"
[[ "$HTTP_BEFORE" == "200" ]] || { echo "Produção não está saudável."; exit 1; }

git config --global --add safe.directory "$SRC" 2>/dev/null || true

cd "$SRC"
git fetch origin
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

HEAD="$(git rev-parse --short HEAD)"
echo "Release: $HEAD"

if [[ -n "$EXPECTED_HEAD" && "$HEAD" != "$EXPECTED_HEAD" ]]; then
    echo "ERRO: esperado $EXPECTED_HEAD e encontrado $HEAD"
    exit 1
fi

echo "==> PHP lint"
find app routes database config tests -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null

echo "==> Testes críticos"
sudo -u agendaenfas php artisan test     tests/Feature/EnterpriseAccessAndSettingsTest.php     tests/Feature/PremiumAvailabilityTest.php     tests/Feature/PremiumWaitlistFlowTest.php     tests/Feature/PremiumRouteRegistrationTest.php

echo "==> Backup de código"
mkdir -p "$BACKUP_DIR"
cd "$APP"
tar -czf "$BACKUP"     --exclude='./.env'     --exclude='./storage'     --exclude='./vendor'     --exclude='./node_modules'     --exclude='./public/build'     --exclude='./bootstrap/cache/*.php'     .

echo "Backup: $BACKUP"

echo "==> Manutenção"
sudo -u agendaenfas php artisan down --retry=5
MAINT=1

echo "==> Sincronizando aplicação completa"
rsync -a     --chown=agendaenfas:agendaenfas     --exclude='.git/'     --exclude='.env'     --exclude='storage/'     --exclude='vendor/'     --exclude='node_modules/'     --exclude='public/build/'     --exclude='bootstrap/cache/*.php'     "$SRC/" "$APP/"

PUBLISHED=1

cd "$APP"

echo "==> Dependências PHP"
sudo -u agendaenfas composer install     --no-dev     --optimize-autoloader     --no-interaction     --prefer-dist     --no-progress

echo "==> Migrations"
sudo -u agendaenfas php artisan migrate --force

echo "==> Build frontend"
sudo -u agendaenfas npm ci --ignore-scripts --no-audit --no-fund
sudo -u agendaenfas npm run build

echo "==> Caches"
sudo -u agendaenfas php artisan optimize:clear
sudo -u agendaenfas php artisan config:cache
sudo -u agendaenfas php artisan route:cache
sudo -u agendaenfas php artisan view:cache
sudo -u agendaenfas php artisan queue:restart || true
sudo -u agendaenfas php artisan storage:link >/dev/null 2>&1 || true

echo "==> Rotas críticas"
for route in     dashboard     agenda.index     agenda.best-slots     waitlist.index     relationship.index     system-settings.index     password.request     microsoft.redirect     enfas.whatsapp     v11.confirmations
do
    sudo -u agendaenfas php artisan route:list --name="$route" | grep -q "$route"
    echo "OK $route"
done

echo "==> Health-check"
sudo -u agendaenfas php artisan enfas:confirmation-health

echo "==> Online"
sudo -u agendaenfas php artisan up
MAINT=0

HTTP_ROOT="$(curl -L -sS -o /dev/null -w "%{http_code}" --max-time 20 https://agenda.enfas.com.br/ || true)"
HTTP_LOGIN="$(curl -L -sS -o /dev/null -w "%{http_code}" --max-time 20 https://agenda.enfas.com.br/login || true)"

echo "HTTP /: $HTTP_ROOT"
echo "HTTP /login: $HTTP_LOGIN"

[[ "$HTTP_ROOT" == "200" ]] || exit 1
[[ "$HTTP_LOGIN" == "200" ]] || exit 1

echo "=================================================="
echo " DEPLOY INTEGRAL CONCLUÍDO"
echo " Release: $HEAD"
echo " Backup:  $BACKUP"
echo "=================================================="

trap - EXIT
