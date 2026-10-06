# Deploy no CloudPanel

Domínio principal recomendado: `sigassaude.enfas.com.br`.

## DNS
Crie um registro DNS `A` para `sigassaude.enfas.com.br` apontando para o IP público da VPS onde o CloudPanel está instalado.

## Estratégia de publicação
Para a primeira versão, use um único domínio público:

- Frontend: `https://sigassaude.enfas.com.br`
- API: `https://sigassaude.enfas.com.br/api`

Isso reduz configuração de CORS, cookies e certificados. Internamente o Nginx/CloudPanel encaminha `/` para o frontend e `/api` para o serviço FastAPI.

## Produção
- Ative SSL/Let's Encrypt no CloudPanel.
- Use `APP_ENV=production`.
- Gere segredos exclusivos e nunca versione o `.env` real.
- PostgreSQL, Redis e MinIO devem ficar sem exposição pública direta.
- Restrinja SSH e banco por firewall.
- Configure backups fora da VPS e teste restauração.

## Evolução futura
Se houver necessidade operacional, separar depois:

- `portal.enfas.com.br` — portal do paciente
- `api.sigassaude.enfas.com.br` — API externa/parceiros
- `status.enfas.com.br` — status público

Não é necessário comprar outro domínio para o sistema.
