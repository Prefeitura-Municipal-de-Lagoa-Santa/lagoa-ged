<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema em Manutenção</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 60px 40px;
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .icon svg {
            width: 60px;
            height: 60px;
            fill: white;
        }

        h1 {
            color: #2d3748;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .message {
            color: #4a5568;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .details {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
        }

        .details p {
            color: #718096;
            font-size: 14px;
            margin: 10px 0;
        }

        .details strong {
            color: #2d3748;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #e2e8f0;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .status {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-weight: 600;
            font-size: 16px;
        }

        .logo {
            margin-top: 30px;
            opacity: 0.5;
        }

        @media (max-width: 640px) {
            .container {
                padding: 40px 30px;
            }

            h1 {
                font-size: 24px;
            }

            .message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
        </div>

        <h1>Sistema em Manutenção</h1>
        
        <p class="message">
            Estamos realizando melhorias no sistema de Gestão de Documentos.<br>
            Em breve estaremos de volta com novidades!
        </p>

        <div class="status">
            <span class="spinner"></span>
            Atualizando sistema...
        </div>

        <div class="details">
            <p><strong>Previsão de retorno:</strong> Em alguns instantes</p>
            <p><strong>O que estamos fazendo:</strong> Instalando atualizações e melhorias</p>
            <p>Por favor, aguarde ou tente novamente em alguns minutos.</p>
        </div>

        <div class="logo">
            <p style="color: #a0aec0; font-size: 14px;">
                Prefeitura Municipal de Lagoa Santa<br>
                Sistema de Gestão de Documentos
            </p>
        </div>
    </div>

    <script>
        // Auto-refresh a cada 10 segundos
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
</body>
</html>
