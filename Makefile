.PHONY: build up down restart logs seed fresh ps shell

# ── Build all images ──────────────────────────────────────────────────────────
build:
	docker compose build --no-cache

# ── Start all containers ──────────────────────────────────────────────────────
up:
	docker compose up -d

# ── Build + start ─────────────────────────────────────────────────────────────
deploy: build up

# ── Stop ─────────────────────────────────────────────────────────────────────
down:
	docker compose down

# ── Restart ───────────────────────────────────────────────────────────────────
restart:
	docker compose restart

# ── Logs ─────────────────────────────────────────────────────────────────────
logs:
	docker compose logs -f --tail=100

# ── Seed demo data ────────────────────────────────────────────────────────────
seed:
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan db:seed --force

# ── Full reset (drops tenant DBs, re-migrates, re-seeds) ─────────────────────
fresh:
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan migrate:fresh --force
	docker compose exec app php artisan db:seed --force

# ── Container status ──────────────────────────────────────────────────────────
ps:
	docker compose ps

# ── Shell into app container ──────────────────────────────────────────────────
shell:
	docker compose exec app sh
