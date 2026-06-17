FROM node:20-alpine AS node-build-stage

WORKDIR /app

COPY package*.json vite.config.js ./
COPY resources/ ./resources/

RUN npm ci --ignore-scripts
RUN npm run build

FROM php:8.4.6-fpm

WORKDIR /var/www

ENV DEBIAN_FRONTEND=noninteractive

RUN mv "${PHP_INI_DIR}/php.ini-production" "${PHP_INI_DIR}/php.ini" && rm "${PHP_INI_DIR}/php.ini-development"

COPY docker/production/php-fpm/99-overrides.ini /usr/local/etc/php/conf.d/99-overrides.ini
COPY docker/production/php-fpm/php-fpm-overrides.conf /usr/local/etc/php-fpm.d/zzz-app.conf

RUN apt-get update && apt-get install -y --no-install-recommends \
        zip \
        unzip \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libicu-dev \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libfcgi-bin \
        default-mysql-client \
    && pecl install \
        ds \
        redis \
    && docker-php-ext-enable \
        ds \
        redis \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        bcmath \
        intl \
        pcntl \
        gd \
        soap \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*


COPY composer.json composer.lock ./

COPY --from=composer:2.8.9 /usr/bin/composer /usr/local/bin/composer

# --no-scripts: o post-autoload-dump tenta executar "php artisan package:discover", mas
# o artisan ainda não existe aqui (o COPY . . vem depois). Os scripts são suprimidos
# e executados manualmente abaixo, após o código da aplicação estar disponível.
# Isso preserva o cache de camadas: composer install só re-executa quando
# composer.json ou composer.lock mudam, não a cada mudança de código PHP.
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress --no-scripts

# NOTE: este . se refere a pasta onde o docker compose up foi rodado
COPY . .

COPY --from=node-build-stage /app/public/build/ /var/www/public/build/

# Symlink public/storage -> ../storage/app/public embutido na imagem (substitui o
# storage:link). O alvo é o volume public-storage montado em runtime; o symlink em si
# não depende do host, então não precisamos mais de bind mount de ./public.
RUN ln -snf ../storage/app/public /var/www/public/storage

# Remove qualquer cache compilado que possa ter vindo do contexto de build
# (bootstrap/cache/config.php, rotas, eventos). Se um config.php gerado sem o
# .env real fosse assado na imagem, ele sobrescreveria a config de runtime
# (ex.: apontando cache/db para sqlite) e quebraria o app. O config:cache
# correto é gerado em runtime pelo `optimize` do deploy, com o .env montado.
RUN mkdir -p bootstrap/cache \
    && rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/events.php

# Scripts do post-autoload-dump executados após o COPY . ., quando o artisan já existe.
# Se novos scripts forem adicionados ao post-autoload-dump no composer.json,
# devem ser reproduzidos aqui também.
RUN php artisan package:discover --ansi

# NOTE: old one - RUN chown -R www-data:www-data /var/www
# pois em ambiente de desenvolvimento o laravel pode alterar arquivos
# que fogem desses dois diretórios.
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# HOME gravável para o www-data. Sem isso, ferramentas como o PsySH (tinker)
# resolvem o diretório de config/histórico relativo ao CWD (/var/www, só-leitura
# para www-data) e falham com "Writing to directory .config/psysh is not allowed".
RUN mkdir -p /home/www-data && chown www-data:www-data /home/www-data
ENV HOME=/home/www-data

# Metadados da imagem (padrão OCI)
LABEL org.opencontainers.image.title="{{project-name}} — PHP-FPM" \
      org.opencontainers.image.description="Runtime PHP 8.4 (FastCGI) que executa a aplicação Laravel; imagem compartilhada pelos serviços php-fpm, horizon e scheduler" \
      org.opencontainers.image.authors="Fabriciope <https://github.com/Fabriciope>" \
      org.opencontainers.image.source="https://github.com/Fabriciope/laravel-docker-stack" \
      org.opencontainers.image.licenses="MIT"

USER www-data

CMD ["php-fpm", "-F"]
