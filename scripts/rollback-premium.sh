#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/home/agendaenfas/htdocs/agenda.enfas.com.br}"
BACKUP_ROOT="${BACKUP_ROOT:-/home/agendaenfas/backups}"
PHP_BIN="${PHP_BIN:-php}"
BACKUP="${1:-}"

if [[ -z "$BACKUP" ]]; then
  BACKUP="$(find "$BACKUP_ROOT" -maxdepth 1 -type d -name 'pre-premium-*' | sort | tail -1)"
fi

[[ -n "$BACKUP" && -d "$BACKUP/app" ]] || {
  echo "Backup de rollback não encontrado." >&2
  exit 1
}

cd "$APP_DIR"
"$PHP_BIN" artisan down --retry=30 || true

rsync -a --delete   --exclude='.env'   --exclude='storage'   "$BACKUP/app/" "$APP_DIR/"

cd "$APP_DIR"
chown -R agendaenfas:agendaenfas storage bootstrap/cache

sudo -u agendaenfas "$PHP_BIN" artisan optimize:clear
sudo -u agendaenfas "$PHP_BIN" artisan queue:restart
sudo -u agendaenfas "$PHP_BIN" artisan up

echo "Rollback de arquivos concluído a partir de: $BACKUP"
echo "ATENÇÃO: migrations destrutivas não são revertidas automaticamente."
