# ── Stage 1: Build Vue SPA ──────────────────────────────────────────────────
FROM node:22-alpine AS frontend

WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci --legacy-peer-deps

COPY frontend/ .
RUN npm run build

# ── Stage 2: Nginx (serves SPA + proxies /api to PHP-FPM) ───────────────────
FROM nginx:1.27-alpine

COPY --from=frontend /app/dist /var/www/frontend
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
