# Plano de Homologação - Servidor de Testes (10.1.7.75)

## Objetivo
Testar todas as melhorias e correções no ambiente de testes antes de replicar na produção, garantindo:
- ✅ Deploy sem erros
- ✅ Dados do MongoDB preservados
- ✅ Importação de documentos funcionando corretamente
- ✅ Datas salvas como MongoDB UTCDateTime
- ✅ Queue worker processando jobs automaticamente

---

## 📋 Checklist de Configuração Atual

### Servidor de Testes (10.1.7.75)
- **Deploy Path**: `/var/www/lagoaged`
- **Branch**: `develop`
- **Porta**: 8001
- **Containers Ativos**:
  - `lagoa-ged` (app Laravel)
  - `lagoa_mongo` (MongoDB)
  - `lagoa_redis` (Redis)
- **Status**: ✅ Rodando há 8 dias

### Servidor de Produção (10.1.7.76)
- **Deploy Path**: `/var/www/lagoaged-dep`
- **Branch**: `main`
- **Porta**: 8001
- **Documentos**: 334,951 (restaurados e funcionando)

---

## 🔧 Melhorias a Testar

### 1. Queue Worker com Auto-Restart no Deploy
**Problema**: Queue worker precisa de restart manual após alterações no código
**Solução**: Adicionar restart automático no processo de deploy

### 2. Logs de Debug (Opcional)
**Situação**: Logs temporários em `DocumentController.php`
**Ação**: Remover após homologação

### 3. Volumes Nomeados
**Situação**: Produção usa volumes nomeados (`mongodb_data_restored`)
**Ação**: Verificar se teste também deve usar volumes nomeados

---

## 📝 Roteiro de Testes

### Fase 1: Backup de Segurança
```bash
# 1. Fazer backup do banco MongoDB de testes
ssh deploy@10.1.7.75
docker exec lagoa_mongo mongodump --db lagoa_ged_db --out /tmp/backup_antes_homologacao
docker cp lagoa_mongo:/tmp/backup_antes_homologacao ./backup_homologacao_$(date +%Y%m%d_%H%M%S)

# 2. Contar documentos antes do teste
docker exec lagoa_mongo mongosh lagoa_ged_db --eval "db.documents.countDocuments()"
```

### Fase 2: Preparação do Deploy
```bash
# 1. Verificar branch develop está atualizada
git checkout develop
git pull origin develop

# 2. Adicionar melhoria de auto-restart do queue worker
# (arquivo deploy.php será modificado)

# 3. Fazer commit das alterações
git add deploy.php
git commit -m "feat: adicionar auto-restart do queue worker no deploy"
git push origin develop
```

### Fase 3: Deploy no Ambiente de Testes
```bash
# 1. Executar deploy
./vendor/bin/dep deploy develop

# 2. Observar saída do deploy
# - Verificar se artisan:cache não executa view:cache
# - Verificar se queue worker é reiniciado
# - Verificar se containers sobem corretamente
```

### Fase 4: Testes Funcionais

#### 4.1 Verificar Dados do MongoDB
```bash
ssh deploy@10.1.7.75
cd /var/www/lagoaged/current

# Contar documentos após deploy
docker exec lagoa_mongo mongosh lagoa_ged_db --eval "db.documents.countDocuments()"
docker exec lagoa_mongo mongosh lagoa_ged_db --eval "db.users.countDocuments()"

# ✅ SUCESSO: Números devem ser iguais ao backup
# ❌ FALHA: Se zerou, volumes foram recriados
```

#### 4.2 Testar Importação de Documentos
```bash
# 1. Preparar CSV de teste pequeno (10-20 linhas)
# 2. Fazer upload via interface web (http://10.1.7.75:8001)
# 3. Observar logs em tempo real

ssh deploy@10.1.7.75
docker exec -it lagoa-ged tail -f /var/www/html/storage/logs/laravel.log
```

**Verificações**:
- ✅ Job é despachado imediatamente
- ✅ Queue worker processa sem delay
- ✅ Log mostra "CSV Import Job: Finalizado"
- ✅ Notificação aparece na interface

#### 4.3 Verificar Formato das Datas no MongoDB
```bash
# Verificar último documento importado
docker exec lagoa_mongo mongosh lagoa_ged_db --eval '
  db.documents.findOne(
    {}, 
    {upload_date: 1, created_at: 1, updated_at: 1}
  )
'
```

**Resultado Esperado**:
```javascript
{
  _id: ObjectId("..."),
  upload_date: ISODate("2024-01-15T00:00:00.000Z"),  // ✅ UTCDateTime
  created_at: ISODate("2025-10-15T17:39:51.234Z"),   // ✅ UTCDateTime
  updated_at: ISODate("2025-10-15T17:39:51.234Z")    // ✅ UTCDateTime
}
```

**❌ FALHA se retornar**:
```javascript
{
  upload_date: {},      // Object vazio
  created_at: {},       // Object vazio
  updated_at: {}        // Object vazio
}
```

#### 4.4 Testar Pesquisa de Documentos
```bash
# 1. Acessar http://10.1.7.75:8001
# 2. Fazer pesquisa com filtros
# 3. Verificar se não há erros de TypeError
```

#### 4.5 Verificar Queue Worker Auto-Restart
```bash
# 1. Fazer uma alteração mínima no código (adicionar comentário)
# 2. Fazer novo deploy
# 3. Verificar nos logs do deploy se queue worker foi reiniciado

# Verificar se worker está rodando após deploy
ssh deploy@10.1.7.75
docker exec lagoa-ged supervisorctl status queue-worker
```

### Fase 5: Testes de Stress

#### 5.1 Importação Grande
```bash
# Importar CSV com 5000+ linhas
# Monitorar:
# - Tempo de processamento
# - Uso de memória
# - Se todos os documentos foram importados
```

#### 5.2 Deploy Consecutivos
```bash
# Fazer 3 deploys seguidos
./vendor/bin/dep deploy develop
# Esperar terminar
./vendor/bin/dep deploy develop
# Esperar terminar
./vendor/bin/dep deploy develop

# Verificar:
# - Dados permanecem intactos
# - Aplicação continua funcionando
# - Sem erros de permissão
```

---

## ✅ Critérios de Aprovação

Para considerar a homologação **APROVADA**, todos os itens devem estar ✅:

### Dados
- [ ] MongoDB mantém todos os documentos após deploy
- [ ] Contagem de usuários permanece a mesma
- [ ] Contagem de grupos permanece a mesma
- [ ] Sem volumes órfãos criados

### Importação
- [ ] CSV é processado sem erros
- [ ] Datas salvas como UTCDateTime (não Object vazio)
- [ ] Notificação de conclusão aparece
- [ ] Documentos aparecem na listagem

### Deploy
- [ ] Deploy completa sem erros
- [ ] Artisan cache não executa view:cache
- [ ] Queue worker reinicia automaticamente
- [ ] Storage/logs permanece com permissões corretas
- [ ] Containers sobem corretamente

### Performance
- [ ] Queue processa jobs em até 5 segundos
- [ ] Importação de 1000 docs em menos de 2 segundos
- [ ] Sem memory leaks no worker

### Interface
- [ ] Login via LDAP funcionando
- [ ] Pesquisa sem TypeError
- [ ] Download de documentos funciona
- [ ] Notificações aparecem (sininho)

---

## 🚀 Replicação para Produção

Após **TODOS** os critérios aprovados:

```bash
# 1. Merge develop -> main
git checkout main
git merge develop
git push origin main

# 2. Deploy em produção
./vendor/bin/dep deploy production

# 3. Monitorar logs de produção
ssh deploy@10.1.7.76
docker exec -it lagoa-ged tail -f /var/www/html/storage/logs/laravel.log
```

---

## 🆘 Rollback de Emergência

Se algo der errado no teste:

```bash
# Voltar para release anterior
./vendor/bin/dep rollback develop

# Restaurar backup do MongoDB
ssh deploy@10.1.7.75
docker cp ./backup_homologacao_YYYYMMDD_HHMMSS lagoa_mongo:/tmp/
docker exec lagoa_mongo mongorestore --db lagoa_ged_db --drop /tmp/backup_homologacao_YYYYMMDD_HHMMSS/lagoa_ged_db
```

---

## 📊 Log de Testes

| Data | Teste | Resultado | Observações |
|------|-------|-----------|-------------|
| YYYY-MM-DD | Deploy básico | ⏳ Pendente | - |
| YYYY-MM-DD | Importação pequena | ⏳ Pendente | - |
| YYYY-MM-DD | Importação grande | ⏳ Pendente | - |
| YYYY-MM-DD | Datas MongoDB | ⏳ Pendente | - |
| YYYY-MM-DD | Queue auto-restart | ⏳ Pendente | - |
| YYYY-MM-DD | Deploys consecutivos | ⏳ Pendente | - |

---

## 📝 Notas

- **Servidor de testes está rodando há 8 dias sem interrupções**
- **Produção tem 334,951 documentos e está estável**
- **Não fazer alterações em produção até homologação completa**
- **Sempre manter backup antes de qualquer teste**
