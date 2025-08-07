#!/bin/bash

echo "=== DIAGNÓSTICO DO SISTEMA DE IMPORTAÇÃO ==="
echo ""

echo "1. Verificando status dos serviços..."
supervisorctl status

echo ""
echo "2. Verificando configurações PHP..."
php -i | grep -E "(max_execution_time|memory_limit|upload_max_filesize|post_max_size)"

echo ""
echo "3. Verificando tamanho dos logs..."
ls -lh storage/logs/

echo ""
echo "4. Verificando últimas linhas do queue worker..."
tail -n 20 storage/logs/queue-worker.log

echo ""
echo "5. Verificando jobs falhados na fila..."
php artisan queue:failed

echo ""
echo "6. Verificando conexão MongoDB..."
php artisan tinker --execute="echo 'MongoDB conectado: ' . (DB::connection('mongodb')->getPdo() ? 'SIM' : 'NÃO');"

echo ""
echo "7. Verificando configurações de timeout..."
cat config/database.php | grep -A 10 mongodb

echo ""
echo "=== FIM DO DIAGNÓSTICO ==="
