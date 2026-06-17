FROM nginx:1.28.0

COPY docker/development/nginx/nginx.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/public

# Cria o diretório de logs (o bind mount popula o conteúdo em runtime)
RUN mkdir -p /var/www/storage/logs/nginx

# Metadados da imagem (padrão OCI)
LABEL org.opencontainers.image.title="{{project-name}} — Nginx (dev)" \
      org.opencontainers.image.description="Servidor web de desenvolvimento: ponto de entrada interno, serve estáticos/assets (do host via bind mount) e repassa o PHP ao php-fpm via FastCGI" \
      org.opencontainers.image.authors="Fabriciope <https://github.com/Fabriciope>" \
      org.opencontainers.image.source="https://github.com/Fabriciope/laravel-docker-stack" \
      org.opencontainers.image.licenses="MIT"
