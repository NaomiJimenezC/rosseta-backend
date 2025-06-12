#!/bin/sh

until pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}"
do
  echo "Esperando a la base de datos..."
  sleep 5
done

echo "Base de datos PostgreSQL lista."

php artisan migrate --force

# Iniciar el servidor PHP-FPM
exec php-fpm
