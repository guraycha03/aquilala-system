# Migration runbook: Hostinger VPS → Oracle Cloud Always Free

**Goal:** keep `aquilala.me` online for the final defense (end of November) and pay
**$0 extra** instead of **$21.83** for a Hostinger renewal you'd only use for ~1 week.

**Situation:** Hostinger VPS deployed Oct 16 → expires ~**Nov 16, 2026**. Defense: end of Nov.
Oracle Cloud **Always Free** tier (2 OCPU ARM + 12 GB RAM + 200 GB storage) fits your stack
(Laravel 12 + MySQL 8 + small uploads) with room to spare, and costs nothing *forever*.

**Key facts:**
- Hostinger VPS has **no pause/freeze** feature — only renew or cancel.
- After expiry the VPS is *suspended* (not deleted) for ~21–30 days → safety net, not a plan.
- You are inside the **30-day money-back window until ~Nov 15** — if migration finishes before
  then, cancel and request a refund → **your entire $11.16 comes back**.
- A "new Hostinger account" = a brand-new empty server. Same work as any other provider,
  and the intro promo is not guaranteed on monthly billing. Migrating is the better move.
- Domain `aquilala.me` is **separate** from the VPS. If it's registered at Hostinger, keep the
  registration and only change the DNS A record. (Email goes through **Resend**, not the server,
  so mail is unaffected either way.)

---

## 0. Do this FIRST (today, 1 minute)

- [ ] **Disable auto-renewal:** hPanel → *Billing → Subscriptions* → toggle **off** for the VPS.
      This guarantees no surprise $21.83 charge on Nov 16.

## 1. Timeline

| When | What |
|---|---|
| Today (Oct 23) | Disable auto-renew · start Oracle signup · run backup |
| Oct 24–25 | Provision Oracle server · restore · test thoroughly |
| By Nov 5 (ideally) | Flip DNS → SSL → run live for a few days as the real server |
| By ~Nov 15 | Cancel Hostinger VPS (request refund — still inside 30-day window) |
| Nov 16 | Original expiry — no charge, no action |
| End of Nov | **Final defense on Oracle — cost $0** |

Site stays online the whole time: Hostinger serves until DNS flips, Oracle serves after.

## 2. Oracle Cloud signup (free tier)

1. Go to <https://www.oracle.com/cloud/free/> → **Start for free**.
2. Requires an email + a **credit/debit card** for verification. You are **not charged** —
   Always Free resources are $0, and the $300 trial credit is only for paid resources you
   don't have to use.
3. Choose your **home region carefully — it can never be changed**:
   - Recommended: **Singapore (ap-singapore-1)** — closest to PH users.
   - Alternatives: **Tokyo (ap-tokyo-1)**, **Seoul (ap-seoul-1)**.
   - If ARM instances report "out of capacity" in your chosen region, retry later or pick a
     different region before finishing signup — capacity fluctuates. This is the #1 signup
     friction point; don't give up after one try.
4. Always Free limits (as of mid-2026): **2 OCPU / 12 GB RAM ARM (A1)** total,
   2 × tiny AMD micro instances, 200 GB block storage, 10 TB/month egress. Plenty for you.

## 3. Create the instance

In OCI console → *Compute → Instances → Create instance*:

| Setting | Value |
|---|---|
| Name | `aquilala` |
| Shape | **VM.Standard.A1.Flex** (Always Free eligible) |
| OCPU / Memory | **2 / 12 GB** (free max) |
| Image | **Canonical Ubuntu 24.04** (aarch64 — ARM) |
| Boot volume | **100 GB** (within the 200 GB free) |
| VCN | *Create virtual cloud network automatically* → keep defaults (internet gateway on) |
| SSH key | Generate & **download** the key pair; keep the `.pem`/private key safe |

- Security list: confirm ingress rules allow **TCP 22, 80, 443** from `0.0.0.0/0`
  (the guided VCN usually opens these; check *Networking → Virtual Cloud Networks →
  Default security list*).
- Connect from Windows PowerShell:
  `ssh -i oracle-key.pem ubuntu@<NEW-IP>`

## 3B. Option B — Google Cloud $300 trial (set this up even while trying Oracle)

If Oracle's card verification keeps failing, use **Google Cloud Free Trial** instead.
Verified facts (Oct 2026, cloud.google.com/signup-faqs):

- **$300 credit, valid 90 days** from signup → signup in late Oct 2026 lasts until ~**Jan 2027**,
  comfortably past the end-of-November defense.
- **It cannot charge you.** Google's own wording: *"you won't be charged unless you manually
  upgrade to a paid account."* After 90 days the trial auto-closes, resources stop (30-day
  grace, then deleted). The **only** way to be billed is clicking "Upgrade to paid" — don't,
  and the answer is $0. Card verification is a $0–1 temporary hold, far less picky than Oracle's
  (PH e-wallet cards usually pass).
- Cost math: `e2-medium` (4 GB) ≈ $20–25/mo → ~$70–90 of $300 for three months. You'd still
  have ~$200 unspent.

**Signup:** go to <https://cloud.google.com/free> → **Start for free** → Google account →
real PH address → card (temporary hold) → done.

**Create the VM** (Compute Engine → Create instance):

| Setting | Value |
|---|---|
| Name | `aquilala` |
| Region/Zone | **asia-east1** (Taiwan — closest to PH; asia-southeast1/Singapore also fine) |
| Series / Type | **E2 → e2-medium** (1 shared vCPU, 4 GB RAM) |
| Boot disk | **Ubuntu 24.04 LTS**, 50 GB (balanced persistent disk) |
| Firewall | ☑ **Allow HTTP traffic** ☑ **Allow HTTPS traffic** (SSH is allowed by default) |
| IP address | After creation: *Network interfaces → External IP → Reserve* → make it **static** (needed for DNS; free while attached to the running VM) |

**Copy files to the VM** with the gcloud CLI (install once, then `gcloud init` to log in —
it handles SSH users/keys automatically):

```powershell
gcloud compute scp deploy\setup-server.sh deploy\deploy-app.sh aquilala:/root/ --zone=<ZONE>
gcloud compute scp aquilala-backup-<timestamp>.tar.gz aquilala:/root/ --zone=<ZONE>
```

Then everything else is **unchanged**: step 4 (backup old server), the `setup-server.sh` +
`deploy-app.sh` runs, testing, DNS A record → the GCP static IP, and `certbot` all work
exactly as written — the scripts don't care which cloud they're on.

**Billing safety rules (all of them):**
1. Never click **Upgrade to paid** — that's the only path to a charge.
2. *Billing → Budgets & alerts* → create a $1 budget (alert only) for peace of mind.
3. No cancel needed — the trial stops itself. (Reminder: ~Jan 2027.)

**Parallel strategy:** run GCP as the guaranteed defense server now, and keep retrying Oracle
spaced out on other days. If Oracle eventually accepts you, migrate again later (scripts make
it a 1-hour job) and enjoy free-forever hosting. Hetzner (~$6–7/mo) remains the paid floor if
every free signup rejects your cards.

## 3C. Option C — Azure for Students (zero card needed)

Verified from azure.microsoft.com/free/students (Oct 2026):

- **No credit card required** (stated twice on the official page) — a student subscription has
  **no payment method attached at all**, so it *cannot* be charged. When the credit runs out,
  Microsoft emails you and (if you don't agree to move to pay-as-you-go — don't) disables the
  subscription. This is the strongest "won't be paid" guarantee of all three options.
- **$100 credit, valid 12 months**, plus **free monthly amounts for 12 months** — which include
  **750 free hours/month each of three VM sizes**: `B1s` (1 vCPU/1 GiB), `B2pts v2` (ARM, 2 vCPU/1 GiB),
  `B2ats v2` (AMD, 2 vCPU/1 GiB). 750 h ≥ 744 h in any month → the VM compute is **$0**, and the
  $100 covers the rest (disk ~$1.5–2/mo + static public IP ~$3.7/mo ≈ $5–6/mo → runs all year).
  If the free-hour allowance ever doesn't apply, the VM simply draws from the $100 — still ₱0.
- Eligibility: **full-time students, one account per person**, renewable yearly while enrolled.

**Signup:** <https://azure.microsoft.com/free/students> → **Start free** → sign in with a
(personal) Microsoft account → **verify student status**. Verification routes, in order of convenience:
1. **School email** (the page's advertised path — if your school issued you an email, use it),
2. **GitHub** (you have a GitHub account — choose this route if offered),
3. **Upload proof**: school ID / enrollment certificate / registration form.
   Approval: minutes to ~2 days. **Start this today** — it's the slowest part.

**Create the VM** (after approval, portal → Virtual machines → Create):

| Setting | Value |
|---|---|
| Name | `aquilala` |
| Region | **Southeast Asia** (Singapore) or **East Asia** (Hong Kong) — nearest PH; if greyed out, pick the nearest listed region (East US works, just higher latency) |
| Image | **Ubuntu 24.04 LTS** |
| Size | **Standard_B2ats_v2** (x86, 2 vCPU / 1 GiB, in the 750-h free set) — or `B2pts v2` (ARM; scripts are arch-agnostic) |
| Disk | Premium SSD, **32–64 GB** (small — your data is ~4 MB + DB) |
| Inbound ports | During creation: **SSH (22) + HTTP (80) + HTTPS (443)** (Networking tab → inbound ports; add NSG rules later if skipped) |
| Public IP | New public IPs are **Standard/static by default** — copy it for the DNS A record |

RAM is 1 GiB, which is the one tight spot — **`setup-server.sh` now auto-creates a 4 GB
swapfile on any VM under 3 GB RAM** (added specifically for this), MySQL's default 128 MB
buffer and php-fpm's default pool fit fine, and the Vite build completes using swap.

**Copy the files:** in the portal's **SSH-in-browser** pane there's an **upload** button in the
toolbar — upload `setup-server.sh`, `deploy-app.sh`, and the backup tarball to `/root/`.
(Alternative: Azure CLI on Windows → `az login` → `az vm scp ...`.)

Then steps 4–8 are unchanged: `setup-server.sh` → `deploy-app.sh` → DNS A record → `certbot`.

**Safety notes:** set *Cost Management → Budgets* alert at $25 (alert-only, peace of mind);
if a size/region shows "quota exceeded", try the other PH region or the `B1s` size;
if `B2ats v2` isn't listed in the region, use whichever of the three 750-h sizes is offered.
After defense you can keep it running on free hours + disk/IP from the $100 until the
12-month mark, then delete everything — still ₱0.

## 4. Back up the old (Hostinger) server

From your PC, in the repo root:

```powershell
scp deploy\backup-vps.sh root@<HOSTINGER-IP>:/root/
```

Then SSH in (`ssh root@<HOSTINGER-IP>` — or use hPanel → *Advanced → Terminal*):

```bash
sudo bash /root/backup-vps.sh
# prints the app path it found; verify it ends with ".../aquilala"
```

Download the result:

```powershell
scp root@<HOSTINGER-IP>:/root/aquilala-backup-*.tar.gz .
```

The archive contains `db.sql` (full DB dump), `env/.env` (all API keys, APP_KEY, VAPID keys),
`app.tar.gz` (code + uploads, minus vendor/node_modules), old server configs, and metadata.
**Keep it until defense is over — it's your rollback.**

## 5. Provision + deploy on the new server

```powershell
scp deploy\setup-server.sh deploy\deploy-app.sh ubuntu@<NEW-IP>:/root/
scp aquilala-backup-<timestamp>.tar.gz ubuntu@<NEW-IP>:/root/
```

SSH in and run:

```bash
sudo bash /root/setup-server.sh     # nginx + php8.3 + mysql8 + node22 + supervisor + certbot
sudo bash /root/deploy-app.sh /root/aquilala-backup-<timestamp>.tar.gz
```

`deploy-app.sh` restores code + `.env` + DB, builds the frontend, caches config, creates the
`storage` symlink, starts the **queue worker** (supervisor) and the scheduler cron.

Useful options (env vars): `SITE_DOMAIN=...`, `APP_DIR=...`, `FORCE_DB=1` (overwrite DB),
`GIT_REPO_URL=https://github.com/guraycha03/aquilala-system.git` (deploy from git instead).

## 6. Pre-DNS test checklist (site still on Hostinger)

```bash
curl -H 'Host: aquilala.me' http://<NEW-IP>/     # expect 200 or 302, not 500
```

Or add `IP  aquilala.me` to `C:\Windows\System32\drivers\etc\hosts` on your PC and browse normally.

- [ ] Login works (existing accounts/data present)
- [ ] Product images + uploads render
- [ ] Excel import/export works (phpspreadsheet)
- [ ] A queue job runs (anything dispatched → check `supervisorctl status aquilala-queue`)
- [ ] Test email arrives (Resend — keys restored from `.env`)
- [ ] Groq AI assistant answers
- [ ] `tail -f /var/www/aquilala/storage/logs/laravel.log` shows no errors

## 7. DNS cutover

1. Lower the **TTL** of the `@` (and `www`) A record to **300 s** a day early if you can
   (hPanel → *DNS Zone*, or wherever the domain's DNS lives — Cloudflare/Namecheap/etc.).
2. Point the **A record** `@` → `<NEW-IP>` (and `www` → `<NEW-IP>`).
3. Propagation: minutes to ~24 h; check at <https://whatsmydns.net/#/A/aquilala.me>.
4. Keep Hostinger running until you've verified the new server serves real traffic.

## 8. SSL / HTTPS

```bash
sudo certbot --nginx -d aquilala.me -d www.aquilala.me
```

- Verify `https://aquilala.me` padlock, then re-check the PWA: **VAPID keys are unchanged**
  (same `.env`), so existing push subscriptions keep working. HTTPS is required for push —
  do this before announcing the new URL internally.

## 9. Cancel Hostinger (only after step 6–8 are green)

1. Download any last backups you want from hPanel.
2. *Billing → Subscriptions* → cancel the VPS (auto-renew already off in step 0).
3. **If before ~Nov 15:** ask support in live chat for the **30-day money-back refund**
   of the $11.16. Best case: **whole migration cost = $0 and you got it back.**
4. Domain registration (if at Hostinger) stays untouched — only the VPS is cancelled.

## 10. Rollback plan

While the Hostinger VPS is still active (before Nov 16): if the new server has a serious
problem, just point the DNS A record back to `<HOSTINGER-IP>` — with TTL 300 the revert is fast.
That's why Hostinger is kept until Oracle is proven.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| `route:cache` fails | **Expected** — the app uses closure routes (uncacheable). The scripts skip it on purpose. |
| `out of capacity` on A1 shape | Try another availability domain / region, or retry later. |
| 500 on `/` | `tail -f storage/logs/laravel.log`; usually permissions (`chown -R www-data:www-data /var/www/aquilala`) or missing `.env` value. |
| Queue jobs not running | `supervisorctl status aquilala-queue`, `tail -f /var/log/aquilala/queue-error.log` |
| Images 404 | `php artisan storage:link --force` and check `public/storage` symlink. |
| Oracle stops serving / instance reclaimed | Always Free ARM instances can be reclaimed if **idle** for long periods. Keep the site monitored — a free UptimeRobot ping every 5 min both monitors uptime and keeps it active. |
| SSH key on Windows | `ssh -i oracle-key.pem ubuntu@IP` (Windows OpenSSH enforces key file ACLs; if denied, use `icacls oracle-key.pem /inheritance:r /grant:r "%USERNAME%:R"`). |

## After defense

Oracle Always Free = **$0/month forever** — you can keep the server running as a portfolio
project. Total spend for this episode: **$0 extra** (possibly −$11.16 if the refund goes
through), vs **$21.83** for a single Hostinger renewal month.

## Notes for future you

- Commit `deploy/` to git — this repo previously had **zero** deployment automation; these
  scripts are now the documented procedure.
- Scripts are LF-only (see `.gitattributes`); if you edit them on Windows, keep that true.
- Shorter-term hardening (after defense): Docker Compose or an Ansible playbook, plus an
  automated nightly `mysqldump` cron. Not urgent now.
