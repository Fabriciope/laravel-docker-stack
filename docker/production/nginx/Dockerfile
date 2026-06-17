# Compila os assets do Vite para que o Nginx sirva /build/* diretamente,
# sem depender de um bind mount de ./public do host.
FROM node:20-alpine AS node-build-stage

WORKDIR /app

COPY package*.json vite.config.js ./
COPY resources/ ./resources/

RUN npm ci --ignore-scripts
RUN npm run build

FROM nginx:1.28.0

COPY docker/production/nginx/nginx.conf /etc/nginx/conf.d/default.conf

COPY . /var/www

# Assets compilados do Vite (public/build é gitignored, então não vem do COPY . acima).
COPY --from=node-build-stage /app/public/build/ /var/www/public/build/

WORKDIR /var/www/public

# Symlink public/storage -> ../storage/app/public embutido na imagem (alvo é o volume
# public-storage montado em runtime). Garante que o Nginx sirva /storage/* sem bind mount.
RUN ln -snf ../storage/app/public /var/www/public/storage \
    && chown -R nginx:nginx /var/www/public \
    && mkdir -p /var/www/storage/logs/nginx \
    && chown -R nginx:nginx /var/www/storage/logs

# Metadados da imagem (padrão OCI)
LABEL org.opencontainers.image.title="{{project-name}} — Nginx" \
      org.opencontainers.image.description="Servidor web: ponto de entrada interno do tráfego, serve estáticos/assets e repassa o PHP ao php-fpm via FastCGI" \
      org.opencontainers.image.authors="Fabriciope <https://github.com/Fabriciope>" \
      org.opencontainers.image.source="https://github.com/Fabriciope/laravel-docker-stack" \
      org.opencontainers.image.licenses="MIT"
