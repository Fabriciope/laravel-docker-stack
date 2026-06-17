FROM php:8.4.6-fpm

WORKDIR /var/www

ENV DEBIAN_FRONTEND=noninteractive

# Usa php.ini-development: display_errors=On, error_reporting=E_ALL, etc.
RUN mv "${PHP_INI_DIR}/php.ini-development" "${PHP_INI_DIR}/php.ini" && rm "${PHP_INI_DIR}/php.ini-production"

COPY docker/development/php-fpm/99-overrides.ini /usr/local/etc/php/conf.d/99-overrides.ini
COPY docker/development/php-fpm/php-fpm-overrides.conf /usr/local/etc/php-fpm.d/zzz-app.conf

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
        xdebug \
    && docker-php-ext-enable \
        ds \
        redis \
        xdebug \
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

COPY --from=composer:2.8.9 /usr/bin/composer /usr/local/bin/composer

# Alinha o UID/GID do www-data ao usuário do host para que os workers do PHP-FPM
# consigam escrever nos arquivos bind-mounted sem erros de permissão.
# Em Linux, o primeiro usuário criado normalmente recebe UID/GID 1000.
ARG HOST_UID=1000
ARG HOST_GID=1000
RUN groupmod -g ${HOST_GID} www-data && usermod -u ${HOST_UID} www-data

COPY docker/development/php-fpm/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Metadados da imagem (padrão OCI)
LABEL org.opencontainers.image.title="{{project-name}} — PHP-FPM (dev)" \
      org.opencontainers.image.description="Runtime PHP 8.4 de desenvolvimento (FastCGI + Xdebug, código bind-mounted) que executa a aplicação Laravel; imagem compartilhada por php-fpm, horizon e scheduler" \
      org.opencontainers.image.authors="Fabriciope <https://github.com/Fabriciope>" \
      org.opencontainers.image.source="https://github.com/Fabriciope/laravel-docker-stack" \
      org.opencontainers.image.licenses="MIT"

USER www-data

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm", "-F"]
