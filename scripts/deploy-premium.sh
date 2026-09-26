#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/home/agendaenfas/htdocs/agenda.enfas.com.br}"
RELEASE_DIR="${RELEASE_DIR:-/home/agendaenfas/agenda-enfas-premium}"
BACKUP_DIR="${BACKUP_DIR:-/home/agendaenfas/backups}"
PHP_BIN="${PHP_BIN:-php}"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="${BACKUP_DIR}/pre-premium-${STAMP}"

log() { printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"; }

fail() {
  printf '\nERRO: %s\n' "$*" >&2
  exit 1
}

[[ -d "$APP_DIR" ]] || fail "APP_DIR não existe: $APP_DIR"
[[ -d "$RELEASE_DIR/.git" ]] || fail "RELEASE_DIR não é um clone Git: $RELEASE_DIR"

mkdir -p "$BACKUP"

log "Validando branch premium"
cd "$RELEASE_DIR"
CURRENT_BRANCH="$(git branch --show-current)"
[[ "$CURRENT_BRANCH" == "feature/agenda-premium" ]] || fail "Branch atual: $CURRENT_BRANCH"

git fetch origin feature/agenda-premium
git status --porcelain | grep -q . && fail "Clone premium possui alterações locais."
git pull --ff-only origin feature/agenda-premium

log "Instalando dependências de validação no clone"
composer install --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build

log "Executando testes no clone"
"$PHP_BIN" artisan test

log "Preparando dependências de produção no clone"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

log "Backup dos arquivos atuais"
rsync -a   --exclude='.env'   --exclude='storage/logs/*'   --exclude='vendor'   --exclude='node_modules'   "$APP_DIR/" "$BACKUP/app/"

cp "$APP_DIR/.env" "$BACKUP/.env"

log "Backup do banco pelo comando ENFAS"
cd "$APP_DIR"
"$PHP_BIN" artisan enfas:backup --retention=30

log "Colocando aplicação em manutenção"
"$PHP_BIN" artisan down --retry=30 || true

restore_on_error() {
  code=$?
  if [[ $code -ne 0 ]]; then
    printf '\nFalha no deploy. Restaurando arquivos anteriores...\n' >&2
    rsync -a --delete       --exclude='.env'       --exclude='storage'       "$BACKUP/app/" "$APP_DIR/"
    cd "$APP_DIR"
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction || true
    chown -R agendaenfas:agendaenfas "$APP_DIR" || true
    "$PHP_BIN" artisan optimize:clear || true
    "$PHP_BIN" artisan up || true
  fi
  exit $code
}
trap restore_on_error ERR

log "Publicando código premium"
rsync -a --delete --chown=agendaenfas:agendaenfas   --exclude='.git'   --exclude='.github'   --exclude='.env'   --exclude='storage'   --exclude='node_modules'   "$RELEASE_DIR/" "$APP_DIR/"

log "Preservando storage e permissões"
cd "$APP_DIR"
mkdir -p storage bootstrap/cache
chown -R agendaenfas:agendaenfas storage bootstrap/cache

log "Migrations"
sudo -u agendaenfas "$PHP_BIN" artisan migrate --force

log "Caches"
sudo -u agendaenfas "$PHP_BIN" artisan optimize:clear
sudo -u agendaenfas "$PHP_BIN" artisan config:cache
sudo -u agendaenfas "$PHP_BIN" artisan route:cache
sudo -u agendaenfas "$PHP_BIN" artisan view:cache

log "Fila"
sudo -u agendaenfas "$PHP_BIN" artisan queue:restart

log "Go/No-Go"
sudo -u agendaenfas "$PHP_BIN" artisan enfas:production-check --no-deep

log "Retirando manutenção"
sudo -u agendaenfas "$PHP_BIN" artisan up

trap - ERR

printf '\nDEPLOY PREMIUM CONCLUÍDO\n'
printf 'Backup local: %s\n' "$BACKUP"
