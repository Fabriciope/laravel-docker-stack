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

# storage/ está no .dockerignore, então o COPY . /var/www acima NÃO trouxe storage.
# Recriamos o esqueleto que o Nginx precisa: storage/app/public é o alvo do symlink
# abaixo (recebe o bind mount :ro de ./storage/app/public em runtime) e storage/logs/nginx
# recebe os access/error logs via bind mount. Ambos ficam vazios na imagem — o conteúdo
# vem dos mounts. Sem o mkdir, o symlink ficaria pendurado e o dir de logs não existiria.
RUN mkdir -p /var/www/storage/app/public /var/www/storage/logs/nginx \
    && ln -snf ../storage/app/public /var/www/public/storage \
    && chown -R nginx:nginx /var/www/public /var/www/storage

# Metadados da imagem (padrão OCI)
LABEL org.opencontainers.image.title="{{project-name}} — Nginx" \
      org.opencontainers.image.description="Servidor web: ponto de entrada interno do tráfego, serve estáticos/assets e repassa o PHP ao php-fpm via FastCGI" \
      org.opencontainers.image.authors="Fabriciope <https://github.com/Fabriciope>" \
      org.opencontainers.image.source="https://github.com/Fabriciope/laravel-docker-stack" \
      org.opencontainers.image.licenses="MIT"
