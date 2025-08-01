<!DOCTYPE html>
<html>
<head>
    <title>Teste de Notificações</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .response { margin-top: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <h1>Sistema de Notificações - Teste</h1>
    
    <div>
        <h2>Criar Notificações de Teste</h2>
        <button class="btn" onclick="createNotification('success', 'Sucesso!', 'Esta é uma notificação de sucesso')">Criar Notificação de Sucesso</button>
        <button class="btn" onclick="createNotification('error', 'Erro!', 'Esta é uma notificação de erro')">Criar Notificação de Erro</button>
        <button class="btn" onclick="createNotification('warning', 'Aviso!', 'Esta é uma notificação de aviso')">Criar Notificação de Aviso</button>
        <button class="btn" onclick="createNotification('info', 'Informação', 'Esta é uma notificação informativa')">Criar Notificação de Info</button>
    </div>
    
    <div>
        <h2>Testar API</h2>
        <button class="btn" onclick="getNotifications()">Buscar Notificações</button>
        <button class="btn" onclick="getUnreadCount()">Contar Não Lidas</button>
        <button class="btn" onclick="markAllAsRead()">Marcar Todas como Lidas</button>
    </div>
    
    <div id="response" class="response" style="display:none;"></div>

    <script>
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        async function createNotification(type, title, message) {
            try {
                const response = await fetch('/api/notifications/test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ type, title, message })
                });
                
                const data = await response.json();
                showResponse('Notificação criada', data);
            } catch (error) {
                showResponse('Erro', { error: error.message });
            }
        }
        
        async function getNotifications() {
            try {
                const response = await fetch('/api/notifications');
                const data = await response.json();
                showResponse('Notificações', data);
            } catch (error) {
                showResponse('Erro', { error: error.message });
            }
        }
        
        async function getUnreadCount() {
            try {
                const response = await fetch('/api/notifications/count');
                const data = await response.json();
                showResponse('Contagem', data);
            } catch (error) {
                showResponse('Erro', { error: error.message });
            }
        }
        
        async function markAllAsRead() {
            try {
                const response = await fetch('/api/notifications/read-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                showResponse('Marcação', data);
            } catch (error) {
                showResponse('Erro', { error: error.message });
            }
        }
        
        function showResponse(title, data) {
            const responseDiv = document.getElementById('response');
            responseDiv.innerHTML = `<h3>${title}</h3><pre>${JSON.stringify(data, null, 2)}</pre>`;
            responseDiv.style.display = 'block';
        }
    </script>
</body>
</html>
