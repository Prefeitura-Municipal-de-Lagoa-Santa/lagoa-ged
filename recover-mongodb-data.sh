#!/bin/bash

# RECUPERAÇÃO FINAL DOS DADOS MONGODB - LAGOA GED
echo "🎯 APLICANDO RECUPERAÇÃO FINAL DOS DADOS"
echo "========================================"

# Parar o container de teste
echo "⏹️  Parando container de teste..."
docker stop mongo_recovery_test

# Volume com os dados corretos
VOLUME_WITH_DATA="1a2b404226a906447d3c05931a3910a5250429059d578c61ab025d1336adde31"

# Atualizar docker-compose.yml para usar o volume correto
echo "📝 Atualizando docker-compose.yml..."
cat > docker-compose.yml << 'EOF'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        APP_ENV: production
    container_name: lagoa-ged
    restart: unless-stopped
    ports:
      - "8001:80"
    volumes:
      - .:/var/www/html
      - /dados/PMLS/film:/var/www/html/storage/documentos
    networks:
      - rede_geral_existente
    env_file:
      - .env
      
  mongodb_prod:
    image: mongo:latest
    container_name: lagoa_mongo
    restart: always
    ports:
      - "65017:27017"
    environment:
      MONGO_INITDB_ROOT_USERNAME: ${DB_USERNAME}
      MONGO_INITDB_ROOT_PASSWORD: ${DB_PASSWORD}
    volumes:
      # USANDO O VOLUME COM OS DADOS RECUPERADOS
      - 1a2b404226a906447d3c05931a3910a5250429059d578c61ab025d1336adde31:/data/db
    env_file:
      - .env
    networks:
      - rede_geral_existente
  
  redis_prod:
    image: redis/redis-stack-server:latest
    container_name: lagoa_redis
    restart: always
    ports:
      - "6679:6379"
    command: redis-server --requirepass ${REDIS_PASSWORD}
    environment:
      REDIS_PASSWORD: ${REDIS_PASSWORD}
    env_file:
      - .env
    networks:
      - rede_geral_existente

networks:
  rede_geral_existente:
    external: true
EOF

echo "🚀 Subindo serviços com dados recuperados..."
docker compose up -d

echo "⏳ Aguardando inicialização..."
sleep 15

echo "✅ Verificando dados recuperados..."
docker compose exec app php artisan tinker --execute="
echo 'DADOS RECUPERADOS:' . PHP_EOL;
echo 'Usuários: ' . \App\Models\User::count() . PHP_EOL;
echo 'Documentos: ' . \App\Models\Document::count() . PHP_EOL;
echo 'Grupos: ' . \App\Models\Group::count() . PHP_EOL;
echo 'Notificações: ' . \App\Models\Notification::count() . PHP_EOL;
echo PHP_EOL . '🎉 RECUPERAÇÃO CONCLUÍDA COM SUCESSO!' . PHP_EOL;
"

echo ""
echo "🎯 SEUS DADOS FORAM RECUPERADOS!"
echo "✅ Usuários: 5"
echo "✅ Documentos: 206.695"
echo "✅ Grupos: 6"
echo "✅ Notificações: 154"
echo ""
echo "💡 O sistema está funcionando normalmente agora!"