#!/bin/bash

echo "Reiniciando serviços para aplicar novas configurações..."

# Parar os workers de queue
echo "Parando queue workers..."
supervisorctl stop queue-worker

# Aguardar um pouco para garantir que os processos parem
sleep 5

# Reiniciar supervisord
echo "Reiniciando supervisord..."
supervisorctl reread
supervisorctl update

# Iniciar os workers novamente
echo "Iniciando queue workers..."
supervisorctl start queue-worker

# Verificar status
echo "Status dos serviços:"
supervisorctl status

echo "Serviços reiniciados com sucesso!"
echo ""
echo "Para monitorar os logs do queue worker:"
echo "tail -f storage/logs/queue-worker.log"
echo ""
echo "Para verificar o status das filas:"
echo "php artisan queue:monitor"
