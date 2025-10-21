#!/bin/sh
set -e

# Cria o diretório para o socket do PHP-FPM
# O '-p' garante que o comando não falhe se o diretório já existir.
mkdir -p /run/php

# Garante que as pastas necessárias do Laravel existem
# Nota: /bin/sh não suporta expansão de chaves, então criamos individualmente
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/private
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/bootstrap/cache

# Ajusta permissões para o usuário do PHP-FPM (www-data)
# Nota: Não altera permissões da pasta documentos (montada de outro servidor)
chown -R www-data:www-data /var/www/html/storage/framework /var/www/html/storage/logs /var/www/html/storage/app /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX /var/www/html/storage/framework /var/www/html/storage/logs /var/www/html/storage/app /var/www/html/bootstrap/cache 2>/dev/null || true

# Instala as dependências do Composer sem rodar scripts (evita package:discover durante o startup)
composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# Limpa caches do Laravel para garantir que o .env seja respeitado
php artisan config:clear || true
php artisan cache:clear || true
php artisan config:cache || true
php artisan view:clear || true

# Inicia o Supervisor, que gerencia o Nginx e o PHP-FPM
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf