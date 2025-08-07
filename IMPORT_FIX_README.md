# Correção do Problema de Importação de Documentos

## Problema Identificado

O job de importação estava falhando com o erro **"There is no active session"** do MongoDB após aproximadamente 60 segundos de execução. Isso acontecia porque:

1. **Timeout de Sessão MongoDB**: Jobs longos excediam o timeout da sessão
2. **Configuração inadequada do Queue Worker**: Sem timeouts apropriados
3. **Processamento em uma única transação**: Todos os documentos sendo processados de uma só vez

## Soluções Implementadas

### 1. Job Otimizado (`ImportDocumentsJob.php`)
- ✅ Adicionado timeout de 30 minutos (`$timeout = 1800`)
- ✅ Processamento em lotes de 50 documentos
- ✅ Pausas entre lotes para aliviar carga no banco
- ✅ Melhor tratamento de erros e logs de progresso
- ✅ Transações menores para evitar timeout

### 2. Configuração MongoDB (`config/database.php`)
- ✅ Timeouts aumentados para 15 minutos
- ✅ Pool de conexões otimizado
- ✅ Retry automático configurado
- ✅ Heartbeat configurado para manter conexão ativa

### 3. Configuração do Queue Worker (`docker/supervisord.conf`)
- ✅ Timeout de 30 minutos (`--timeout=1800`)
- ✅ Limite de memória aumentado (`--memory=512`)
- ✅ Tempo de parada adequado (`stopwaitsecs=1830`)
- ✅ Tempo máximo de execução configurado (`--max-time=3600`)

### 4. Configurações PHP (`docker/php-uploads.ini`)
- ✅ Timeout de execução aumentado para 30 minutos
- ✅ Memória aumentada para 512MB
- ✅ Timeouts de socket configurados

### 5. Jobs Adicionais para Arquivos Grandes
- ✅ `OptimizedImportDocumentsJob`: Detecta arquivos grandes e processa em chunks paralelos
- ✅ `ProcessDocumentChunkJob`: Processa chunks de 100 documentos em jobs separados

## Como Aplicar as Correções

### 1. Reiniciar os Serviços
```bash
./restart-services.sh
```

### 2. Verificar Status do Sistema
```bash
./diagnose-import.sh
```

### 3. Usar o Comando de Importação
```bash
# Importação normal (modo fila)
php artisan documents:import temp/arquivo.csv USER_ID --read-groups=GROUP1,GROUP2 --write-groups=GROUP3

# Importação otimizada (recomendado para arquivos grandes)
php artisan documents:import temp/arquivo.csv USER_ID --read-groups=GROUP1,GROUP2 --write-groups=GROUP3 --optimized

# Importação síncrona (apenas para teste)
php artisan documents:import temp/arquivo.csv USER_ID --read-groups=GROUP1,GROUP2 --write-groups=GROUP3 --sync
```

## Monitoramento

### Logs em Tempo Real
```bash
# Queue worker
tail -f storage/logs/queue-worker.log

# Laravel
tail -f storage/logs/laravel.log

# Erros do queue worker
tail -f storage/logs/queue-worker-error.log
```

### Verificar Jobs Falhados
```bash
php artisan queue:failed
```

### Status das Filas
```bash
php artisan queue:monitor
```

## Configurações de Performance

### Para Arquivos Pequenos (< 10MB)
- Use o `ImportDocumentsJob` original otimizado
- Lotes de 50 documentos
- Timeout de 30 minutos

### Para Arquivos Grandes (> 10MB)
- Use o `OptimizedImportDocumentsJob`
- Processamento em chunks paralelos de 100 documentos
- Cada chunk em job separado

### Configurações Recomendadas de Produção

#### Supervisor
```ini
[program:queue-worker]
command=php artisan queue:work redis --tries=3 --timeout=1800 --memory=512 --sleep=3 --max-time=3600
numprocs=4  # Múltiplos workers
```

#### MongoDB
- Pool de conexões: 100
- Timeout de socket: 15 minutos
- Heartbeat: 10 segundos

#### PHP
- max_execution_time: 1800
- memory_limit: 512M
- upload_max_filesize: 200M

## Troubleshooting

### Se o Job Ainda Falhar
1. Verificar configurações do MongoDB
2. Aumentar timeout do PHP
3. Reduzir tamanho dos lotes
4. Usar processamento em chunks

### Logs Importantes
- `storage/logs/laravel.log`: Logs do job
- `storage/logs/queue-worker.log`: Status do worker
- MongoDB logs: Verificar conexões e timeouts

### Comandos Úteis
```bash
# Limpar jobs falhados
php artisan queue:flush

# Reiniciar queue worker
supervisorctl restart queue-worker

# Verificar status das filas
php artisan queue:work --once

# Processar jobs específicos
php artisan queue:work --queue=documents
```
