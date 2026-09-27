#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/home/agendaenfas/htdocs/agenda.enfas.com.br}"
RELEASE_DIR="${RELEASE_DIR:-/home/agendaenfas/agenda-enfas-premium}"
BACKUP_DIR="${BACKUP_DIR:-/home/agendaenfas/backups}"
APP_USER="${APP_USER:-agendaenfas}"
APP_GROUP="${APP_GROUP:-agendaenfas}"
EXPECTED_DB="${EXPECTED_DB:-agenda-enfas}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
SMOKE_URL="${SMOKE_URL:-https://agenda.enfas.com.br}"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="${BACKUP_DIR}/pre-premium-${STAMP}"

log() { printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"; }

fail() {
  printf '\nERRO: %s\n' "$*" >&2
  exit 1
}

run_as_app() {
  sudo -u "$APP_USER" "$@"
}

database_guard() {
  local actual
  local guard="$RELEASE_DIR/scripts/database-guard.php"

  [[ -f "$guard" ]] \
    || fail "Verificador de banco não encontrado no clone premium: $guard"

  if ! actual="$(run_as_app "$PHP_BIN" "$guard" "$APP_DIR" "$EXPECTED_DB")"; then
    fail "Falha na validação do banco de produção."
  fi

  actual="$(printf '%s' "$actual" | tail -n1 | tr -d '\r\n[:space:]')"

  [[ "$actual" == "$EXPECTED_DB" ]] \
    || fail "Verificador retornou '${actual:-vazio}'. Esperado: $EXPECTED_DB"

  log "Banco validado: $EXPECTED_DB"
}

[[ -d "$APP_DIR" ]] || fail "APP_DIR não existe: $APP_DIR"
[[ -f "$APP_DIR/.env" ]] || fail ".env de produção não encontrado em $APP_DIR"
[[ -d "$RELEASE_DIR/.git" ]] || fail "RELEASE_DIR não é um clone Git: $RELEASE_DIR"
command -v "$PHP_BIN" >/dev/null 2>&1 || fail "PHP não encontrado: $PHP_BIN"
command -v "$COMPOSER_BIN" >/dev/null 2>&1 || fail "Composer não encontrado: $COMPOSER_BIN"
command -v rsync >/dev/null 2>&1 || fail "rsync não encontrado"
command -v curl >/dev/null 2>&1 || fail "curl não encontrado"

mkdir -p "$BACKUP"
chmod 700 "$BACKUP"

log "Validando branch premium"
cd "$RELEASE_DIR"

CURRENT_BRANCH="$(git branch --show-current)"
[[ "$CURRENT_BRANCH" == "feature/agenda-premium" ]] || fail "Branch atual: $CURRENT_BRANCH"

git fetch origin feature/agenda-premium
git status --porcelain | grep -q . && fail "Clone premium possui alterações locais."
git pull --ff-only origin feature/agenda-premium

RELEASE_COMMIT="$(git rev-parse HEAD)"
REMOTE_COMMIT="$(git rev-parse origin/feature/agenda-premium)"
[[ "$RELEASE_COMMIT" == "$REMOTE_COMMIT" ]] || fail "Clone não está exatamente no HEAD remoto."

log "Instalando dependências de validação no clone"
"$COMPOSER_BIN" install --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build

log "Executando suíte de testes"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan test --display-warnings

log "Executando gates críticos de interface, CEP e lista de espera"
"$PHP_BIN" artisan test --filter=PremiumVisualSmokeTest
"$PHP_BIN" artisan test --filter=PremiumCepLookupTest
"$PHP_BIN" artisan test --filter=PremiumWaitlistFlowTest

log "Validando rotas e views no clone"
"$PHP_BIN" artisan route:list >/dev/null
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan view:clear

log "Preparando dependências de produção no clone"
"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

log "Pré-flight do banco de produção"
database_guard

log "Backup dos arquivos atuais"
rsync -a \
  --exclude='.env' \
  --exclude='storage/logs/*' \
  --exclude='vendor' \
  --exclude='node_modules' \
  "$APP_DIR/" "$BACKUP/app/"

cp "$APP_DIR/.env" "$BACKUP/.env"
chmod 600 "$BACKUP/.env"

log "Backup do banco pelo comando ENFAS"
cd "$APP_DIR"
run_as_app "$PHP_BIN" artisan enfas:backup --retention=30

LATEST_DB_BACKUP="$(find "$BACKUP_DIR" -maxdepth 1 -type f -name 'agenda-db-*.sql.gz' -printf '%T@ %p\n' 2>/dev/null | sort -nr | head -n1 | cut -d' ' -f2-)"
[[ -n "$LATEST_DB_BACKUP" && -s "$LATEST_DB_BACKUP" ]] || fail "Backup de banco não foi localizado ou está vazio."

log "Backup de banco confirmado: $(basename "$LATEST_DB_BACKUP")"

log "Colocando aplicação em manutenção"
run_as_app "$PHP_BIN" artisan down --retry=30

restore_on_error() {
  code=$?

  if [[ $code -ne 0 ]]; then
    printf '\nFalha no deploy. Restaurando código anterior...\n' >&2
    printf 'ATENÇÃO: migrations já aplicadas não são revertidas automaticamente.\n' >&2
    printf 'Backup do banco: %s\n' "$LATEST_DB_BACKUP" >&2

    rsync -a --delete \
      --exclude='.env' \
      --exclude='storage' \
      "$BACKUP/app/" "$APP_DIR/" || true

    cd "$APP_DIR"

    run_as_app "$COMPOSER_BIN" install \
      --no-dev \
      --prefer-dist \
      --optimize-autoloader \
      --no-interaction || true

    chown -R "$APP_USER:$APP_GROUP" "$APP_DIR" || true

    run_as_app "$PHP_BIN" artisan optimize:clear || true
    run_as_app "$PHP_BIN" artisan up || true
  fi

  exit $code
}
trap restore_on_error ERR

log "Publicando código premium"
rsync -a --delete --chown="$APP_USER:$APP_GROUP" \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.env' \
  --exclude='storage' \
  --exclude='node_modules' \
  "$RELEASE_DIR/" "$APP_DIR/"

log "Preservando storage e permissões"
cd "$APP_DIR"
mkdir -p storage bootstrap/cache
chown -R "$APP_USER:$APP_GROUP" storage bootstrap/cache

log "Revalidando banco imediatamente antes das migrations"
run_as_app "$PHP_BIN" artisan optimize:clear
database_guard

log "Migrations pendentes"
run_as_app "$PHP_BIN" artisan migrate:status

log "Aplicando migrations"
run_as_app "$PHP_BIN" artisan migrate --force

log "Validando schema/rotas após migration"
run_as_app "$PHP_BIN" artisan route:list >/dev/null
run_as_app "$PHP_BIN" artisan view:cache

log "Caches de produção"
run_as_app "$PHP_BIN" artisan optimize:clear
run_as_app "$PHP_BIN" artisan config:cache
run_as_app "$PHP_BIN" artisan route:cache
run_as_app "$PHP_BIN" artisan view:cache

log "Fila"
run_as_app "$PHP_BIN" artisan queue:restart

log "Go/No-Go interno"
run_as_app "$PHP_BIN" artisan enfas:production-check --no-deep

log "Retirando manutenção"
run_as_app "$PHP_BIN" artisan up

log "Smoke HTTP"
HEALTH_BODY="$(curl -fsS --max-time 20 "${SMOKE_URL%/}/healthz")"
printf '%s\n' "$HEALTH_BODY"
printf '%s' "$HEALTH_BODY" | grep -Eq '"status"[[:space:]]*:[[:space:]]*"ok"' \
  || fail "Healthcheck HTTP não retornou status ok."

LOGIN_STATUS="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 20 "${SMOKE_URL%/}/login")"
[[ "$LOGIN_STATUS" == "200" ]] || fail "Smoke /login retornou HTTP $LOGIN_STATUS."

trap - ERR

printf '\nDEPLOY PREMIUM CONCLUÍDO\n'
printf 'Commit: %s\n' "$RELEASE_COMMIT"
printf 'Backup local: %s\n' "$BACKUP"
printf 'Backup banco: %s\n' "$LATEST_DB_BACKUP"
