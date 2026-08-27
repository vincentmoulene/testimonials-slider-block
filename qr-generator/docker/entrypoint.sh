#!/bin/sh
set -e

# Bring the schema up to date before serving. Safe to run on every boot: an
# already-migrated database is a no-op, and concurrent instances are protected
# by the migrations table itself.
if [ "${RUN_MIGRATIONS_ON_BOOT:-1}" = "1" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec docker-entrypoint "$@"
