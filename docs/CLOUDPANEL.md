# Deploy CloudPanel — SIGH ENFAS

Host: **sigh.enfas.com.br**

## Topologia inicial

CloudPanel/Nginx + PHP-FPM + Laravel 13 + MySQL/MariaDB. Redis pode ser ativado para cache, fila e sessão.

## DNS

Crie registro A de `sigh.enfas.com.br` apontando para a VPS.

## Site

No CloudPanel, use site PHP e configure o Document Root para a pasta `public` do projeto.

## Deploy

```bash
git clone <repositorio> sigh.enfas.com.br
cd sigh.enfas.com.br
git checkout sigh-enfas-enterprise
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm ci
npm run build
php artisan storage:link
php artisan optimize
```

## Produção

```env
APP_NAME="SIGH ENFAS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sigh.enfas.com.br
SIGH_DOMAIN=sigh.enfas.com.br
SIGH_ORGANIZATION=ENFAS
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigh_enfas
DB_USERNAME=sigh_enfas
DB_PASSWORD=ALTERAR
SESSION_SECURE_COOKIE=true
```

Nunca versione o `.env` real.

## Checklist

SSL ativo; banco sem porta pública; firewall; backup diário; teste de restauração; monitoramento; logs; MFA administrativo; homologação separada; auditoria clínica.
