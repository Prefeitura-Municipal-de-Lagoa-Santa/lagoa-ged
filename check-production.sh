#!/bin/bash

# Script de Verificação do Status da Produção - Lagoa GED
# Verificação rápida do status dos serviços e configurações

echo "🔍 Verificação do Status da Produção - Lagoa GED"
echo "================================================"
echo ""

# Verificar se os containers estão rodando
echo "📦 Status dos Containers:"
docker-compose ps
echo ""

# Verificar status do supervisor
echo "📊 Status do Supervisor:"
docker-compose exec app supervisorctl status
echo ""

# Verificar configurações PHP críticas
echo "🔧 Configurações PHP Críticas:"
docker-compose exec app php -r "
echo 'Max Execution Time: ' . ini_get('max_execution_time') . 's' . PHP_EOL;
echo 'Memory Limit: ' . ini_get('memory_limit') . PHP_EOL;
echo 'Upload Max Filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;
echo 'Post Max Size: ' . ini_get('post_max_size') . PHP_EOL;
echo 'Default Socket Timeout: ' . ini_get('default_socket_timeout') . 's' . PHP_EOL;
"
echo ""

# Verificar configurações MongoDB
echo "🗄️  Configurações MongoDB:"
docker-compose exec app php -r "
\$config = config('database.connections.mongodb.options');
echo 'Max Pool Size: ' . \$config['maxPoolSize'] . PHP_EOL;
echo 'Socket Timeout: ' . \$config['socketTimeoutMS'] . 'ms' . PHP_EOL;
echo 'Max Idle Time: ' . \$config['maxIdleTimeMS'] . 'ms' . PHP_EOL;
echo 'Server Selection Timeout: ' . \$config['serverSelectionTimeoutMS'] . 'ms' . PHP_EOL;
"
echo ""

# Teste de conectividade MongoDB
echo "🔗 Teste de Conectividade MongoDB:"
docker-compose exec app php -r "
try {
    \$connection = DB::connection('mongodb');
    \$result = \$connection->command(['ping' => 1]);
    echo '✅ MongoDB: Conectado' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ MongoDB: Erro - ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# Verificar jobs na queue
echo "📋 Status da Queue:"
docker-compose exec app php artisan queue:monitor --once 2>/dev/null || echo "Monitor de queue não disponível"
echo ""

# Verificar logs recentes do worker
echo "📝 Últimas 5 linhas do log do worker:"
docker-compose exec app tail -n 5 storage/logs/queue-worker.log 2>/dev/null || echo "Log do worker não existe ainda"
echo ""

# Verificar espaço em disco
echo "💾 Uso de Espaço em Disco:"
docker-compose exec app df -h /var/www/html
echo ""

# Verificar uso de memória do container
echo "🧠 Uso de Memória do Container:"
docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}" $(docker-compose ps -q)
echo ""

echo "✅ Verificação completa!"
echo ""
echo "💡 Para monitoramento contínuo:"
echo "   🔄 Status: ./check-production.sh"
echo "   📊 Logs: docker-compose logs -f app"
echo "   🔧 Worker: docker-compose exec app tail -f storage/logs/queue-worker.log"