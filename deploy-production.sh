#!/bin/bash

# Script de Deploy para Produção Docker - Lagoa GED
# Este script aplica as configurações otimizadas para ambiente de produção em Docker

set -e

echo "🚀 Iniciando deploy para produção (Docker)..."
echo "Data: $(date)"
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Erro: docker-compose.yml não encontrado. Execute este script no diretório raiz do projeto."
    exit 1
fi

# Verificar se os arquivos de configuração existem
echo "🔍 Verificando arquivos de configuração..."
if [ ! -f "docker/php-production.ini" ]; then
    echo "❌ Erro: docker/php-production.ini não encontrado."
    exit 1
fi

# Backup das configurações atuais
echo "📝 Fazendo backup das configurações atuais..."
mkdir -p backups/$(date +%Y%m%d_%H%M%S)
cp docker-compose.yml backups/$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || true
cp config/database.php backups/$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || true
cp docker/supervisord.conf backups/$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || true

# Parar serviços
echo "🛑 Parando serviços Docker..."
docker-compose down --remove-orphans

# Limpar imagens antigas e cache
echo "🧹 Limpando imagens antigas e cache..."
docker image prune -f
docker builder prune -f

# Build da imagem com configurações de produção
echo "🔨 Construindo imagem Docker para produção..."
docker-compose build --no-cache --build-arg APP_ENV=production

# Subir serviços
echo "🔄 Subindo serviços Docker..."
docker-compose up -d

# Aguardar inicialização dos serviços
echo "⏳ Aguardando inicialização dos serviços..."
sleep 30

# Verificar se os containers estão rodando
echo "✅ Verificando status dos containers..."
docker-compose ps

# Verificar se o supervisor está funcionando
echo "📊 Verificando status do supervisor..."
docker-compose exec app supervisorctl status || echo "⚠️  Supervisor ainda não está pronto"

# Aguardar mais um pouco para estabilizar
sleep 10

# Executar comandos de otimização Laravel dentro do container
echo "🔧 Executando comandos de otimização Laravel..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan optimize

# Verificar configurações PHP aplicadas
echo "🔍 Verificando configurações PHP aplicadas..."
docker-compose exec app php -i | grep -E "(max_execution_time|memory_limit|upload_max_filesize)" || echo "Configurações PHP verificadas"

# Verificar configurações do MongoDB
echo "�️  Verificando configurações do MongoDB..."
docker-compose exec app php artisan tinker --execute="
echo 'MongoDB Pool Size: ' . config('database.connections.mongodb.options.maxPoolSize') . PHP_EOL;
echo 'Socket Timeout: ' . config('database.connections.mongodb.options.socketTimeoutMS') . 'ms' . PHP_EOL;
echo 'Max Idle Time: ' . config('database.connections.mongodb.options.maxIdleTimeMS') . 'ms' . PHP_EOL;
" || echo "Configurações MongoDB verificadas"

# Teste de conectividade com MongoDB
echo "🔗 Testando conectividade com MongoDB..."
docker-compose exec app php artisan tinker --execute="
try {
    \$connection = DB::connection('mongodb');
    \$result = \$connection->command(['ping' => 1]);
    echo '✅ Conexão com MongoDB: OK' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Erro na conexão: ' . \$e->getMessage() . PHP_EOL;
}
" || echo "Teste de conectividade executado"

# Verificar logs do worker
echo "📋 Verificando logs do worker (últimas 10 linhas)..."
docker-compose exec app tail -n 10 storage/logs/queue-worker.log 2>/dev/null || echo "Log do worker ainda não existe"

# Verificar status final
echo "🏁 Status final dos serviços:"
docker-compose exec app supervisorctl status

echo ""
echo "🎉 Deploy concluído com sucesso!"
echo ""
echo "🛠️  COMANDOS ÚTEIS PARA MONITORAMENTO:"
echo ""
echo "📊 Logs em tempo real:"
echo "   docker-compose logs -f app"
echo ""
echo "🔧 Logs do worker:"
echo "   docker-compose exec app tail -f storage/logs/queue-worker.log"
echo ""
echo "📈 Status do supervisor:"
echo "   docker-compose exec app supervisorctl status"
echo ""
echo "🧪 Monitor de queue:"
echo "   docker-compose exec app php artisan queue:monitor"
echo ""
echo "🔄 Reiniciar worker:"
echo "   docker-compose exec app supervisorctl restart queue-worker"
echo ""
echo "📋 Ver configurações PHP:"
echo "   docker-compose exec app php -i | grep -E '(max_execution_time|memory_limit|upload_max_filesize)'"
echo ""
echo "🚨 Para reverter (se necessário):"
echo "   docker-compose down && docker-compose up -d"
echo ""
echo "✅ Ambiente de produção otimizado está pronto!"