#!/bin/bash

# SCRIPT DE RECUPERAÇÃO URGENTE - LAGOA GED
# Este script recupera os dados MongoDB dos volumes órfãos

echo "🚑 INICIANDO RECUPERAÇÃO DOS DADOS DO MONGODB"
echo "=============================================="

# Parar todos os containers relacionados
echo "⏹️  Parando containers..."
docker compose down

# Volume com os dados mais recentes (24MB, atualizado hoje)
VOLUME_WITH_DATA="1a2b404226a906447d3c05931a3910a5250429059d578c61ab025d1336adde31"

echo "📦 Volume com dados identificado: $VOLUME_WITH_DATA"
echo "📊 Tamanho: 494.4MB"
echo "🕒 Última modificação: hoje (17/09/2025)"

# Criar um container temporário MongoDB para testar os dados
echo "🔍 Testando os dados no volume órfão..."
docker run --rm -d \
  --name mongo_recovery_test \
  -v ${VOLUME_WITH_DATA}:/data/db \
  -p 27018:27017 \
  mongo:latest

# Aguardar MongoDB inicializar
echo "⏳ Aguardando MongoDB inicializar..."
sleep 10

# Testar se os dados estão lá
echo "🧪 Testando conectividade e dados..."
docker exec mongo_recovery_test mongosh --eval "
try {
  const dbs = db.adminCommand('listDatabases');
  print('Databases encontrados:');
  dbs.databases.forEach(db => print('  - ' + db.name + ' (' + (db.sizeOnDisk/1024/1024).toFixed(2) + ' MB)'));
  
  // Verificar se lagoa_ged_db existe
  const collections = db.getSiblingDB('lagoa_ged_db').listCollectionNames();
  print('\\nColeções em lagoa_ged_db:');
  collections.forEach(col => {
    const count = db.getSiblingDB('lagoa_ged_db')[col].countDocuments();
    print('  - ' + col + ': ' + count + ' documentos');
  });
} catch(e) {
  print('Erro: ' + e);
}
"

echo ""
echo "🔧 Para recuperar completamente:"
echo "1. Confirme se os dados aparecem acima"
echo "2. Execute: ./recover-mongodb-data.sh"
echo ""
echo "⚠️  NÃO EXECUTE 'docker compose up' até confirmar os dados!"