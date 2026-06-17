#!/bin/sh
set -e

# Instala dependências composer automaticamente no primeiro start (fresh clone).
# Em runs subsequentes, vendor/ já existe no bind mount do host e este bloco é pulado.
if [ ! -d /var/www/vendor ]; then
    echo "[dev-entrypoint] vendor/ não encontrado. Executando composer install..."
    composer install --no-interaction --prefer-dist
fi

exec "$@"
