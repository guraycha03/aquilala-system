# Aquilala Deployment Guide

This file is the quick reference for AI assistants and maintainers working on
the Aquilala production deployment. It intentionally contains **no passwords,
private keys, API keys, or `.env` values**.

## Hosting structure

| Item                | Value                                                 |
| ------------------- | ----------------------------------------------------- |
| Provider            | Microsoft Azure Linux VM                              |
| VM name             | `aquilala`                                          |
| Public IP           | `20.2.221.9`                                        |
| SSH user            | `guraycha03`                                        |
| Application path    | `/var/www/aquilala`                                 |
| Domain              | `aquilala.me`                                       |
| Repository          | `https://github.com/guraycha03/aquilala-system.git` |
| Production database | MySQL, configured only in the server `.env`         |
| Queue worker        | Supervisor program `aquilala-queue`                 |

The deployment target is an Azure **VM**, not Azure App Service. Laravel,
MySQL, Nginx, PHP, Node/Vite, Supervisor, and Certbot run on the VM.

## Secrets and credentials

- The SSH private key is a local file named `aquilala_key.pem`. It is ignored
  by Git and must never be copied into this repository or pasted into chat.
- The production `.env` is stored only on the server at
  `/var/www/aquilala/.env`. It contains `APP_KEY`, database credentials,
  mail credentials, VAPID values, and AI provider keys.
- The server-side database credential file is
  `/root/.aquilala-db-creds`. Do not download, commit, or disclose it.
- If a new AI assistant needs access, provide the key through the terminal or
  secret manager at execution time; do not put the secret in this document.

## Normal deployment: Windows PowerShell

Run these commands from the repository root. Replace no secrets in the
commands below.

```powershell
git status
php artisan test
git add app database config resources routes tests DEPLOYMENT_GUIDE.md
git commit -m "Describe the deployment change`n`nCo-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
git push origin main
```

Create a production backup before replacing application files:

```powershell
ssh -i .\aquilala_key.pem guraycha03@20.2.221.9 `
  "sudo bash /var/www/aquilala/deploy/backup-vps.sh /var/www/aquilala"
```

The backup script prints the backup filename. Keep that archive on the server
until the deployment has been tested.

### Preferred deployment from GitHub

This requires GitHub authentication to be configured on the VM. If it is not,
use the archive method below.

```powershell
ssh -i .\aquilala_key.pem guraycha03@20.2.221.9 `
  "sudo env GIT_REPO_URL=https://github.com/guraycha03/aquilala-system.git GIT_REPO_BRANCH=main APP_DIR=/var/www/aquilala SITE_DOMAIN=aquilala.me bash /var/www/aquilala/deploy/deploy-app.sh /root/BACKUP_FILE.tar.gz"
```

### Deployment without GitHub credentials on the VM

This method uploads the already committed source from the local machine and
keeps the production `.env`, uploads, and database from the backup:

```powershell
git archive --format=tar.gz --output=release.tar.gz HEAD
scp -i .\aquilala_key.pem release.tar.gz guraycha03@20.2.221.9:/tmp/release.tar.gz
```

On the VM, the release archive can be inserted into a deployment backup as
`app.tar.gz`, then deployed with the existing script. The application backup
must still contain `env/.env` and the database dump from `backup-vps.sh`.

## Post-deployment checks

```powershell
ssh -i .\aquilala_key.pem guraycha03@20.2.221.9 `
  "sudo supervisorctl status aquilala-queue; cd /var/www/aquilala; php artisan migrate:status --no-ansi"

curl.exe -sS -L -o NUL -w "HTTP %{http_code}`n" https://aquilala.me/
```

Then manually test login, admin AI history, member/archive actions, uploads,
store pages, and the mobile PWA. Check Laravel errors with:

```bash
sudo tail -n 100 /var/www/aquilala/storage/logs/laravel.log
sudo tail -n 100 /var/log/aquilala/queue-error.log
```

## Database safety

Normal deployments run migrations and preserve existing rows. They do not
reseed demo data. Never use `FORCE_DB=1` on production unless a deliberate
full database restore has been approved and the backup has been verified.

## Important files

- `deploy/backup-vps.sh` — creates a rollback backup.
- `deploy/deploy-app.sh` — restores the app, runs migrations, builds assets,
  caches Laravel, and configures the queue worker.
- `deploy/setup-server.sh` — provisions a new server; do not run it on the
  existing production VM during a normal release.
- `.env.example` — documents configuration names without real values.
