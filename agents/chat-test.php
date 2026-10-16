<?php
session_start();
// Configuração ESPECÍFICA para agente
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2;
    $_SESSION['agent_id'] = 2; // ID específico do agente
    $_SESSION['user_name'] = 'Agente Teste';
    $_SESSION['user_type'] = 'agent';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Chat - Agente</title>
    <style>
        .chat-container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #007bff; background: white; }
        .messages { height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0; background: #f8f9fa; }
        .message { margin: 10px 0; padding: 10px; border-radius: 8px; max-width: 80%; word-wrap: break-word; }
        .victim { background: #e3f2fd; margin-left: auto; text-align: right; border: 1px solid #bbdefb; }
        .agent { background: #d4edda; margin-right: auto; border: 1px solid #c3e6cb; }
        .input-area { display: flex; gap: 10px; align-items: center; }
        .input-area input { flex: 1; padding: 12px; border: 1px solid #007bff; border-radius: 6px; font-size: 14px; }
        .input-area button { padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .input-area button:disabled { background: #6c757d; cursor: not-allowed; }
        .message-time { font-size: 11px; color: #666; margin-top: 5px; }
        .header { background: #007bff; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 5px 5px 0 0; }
        .status { margin-top: 10px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="header">
            <h2>👮 Chat da Denúncia #1</h2>
            <div><strong>Agente:</strong> <?php echo $_SESSION['user_name']; ?></div>
        </div>
        
        <div class="messages" id="messages-container">
            <div class="message victim">
                <strong>👤 Denunciante:</strong> Preciso de ajuda!
                <div class="message-time"><?php echo date('H:i'); ?></div>
            </div>
        </div>
        
        <div class="input-area">
            <input type="text" id="message-input" placeholder="Digite sua resposta..." maxlength="500">
            <button onclick="sendMessage()" id="send-btn">📤 Enviar</button>
        </div>
        
        <div id="status" class="status">Conectado...</div>
    </div>

    <script>
        let lastMessageId = 0;
        const complaintId = 1;
        const isVictim = false; // AGENTE
        
        function loadMessages() {
            fetch('../includes/api/chat/messages.php?complaint_id=' + complaintId + '&last_id=' + lastMessageId)
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        const container = document.getElementById('messages-container');
                        
                        data.messages.forEach(msg => {
                            lastMessageId = Math.max(lastMessageId, msg.id);
                            
                            const div = document.createElement('div');
                            div.className = `message ${msg.is_from_victim == 1 ? 'victim' : 'agent'}`;
                            div.innerHTML = `
                                <strong>${msg.is_from_victim == 1 ? '👤 Denunciante' : '👮 Você'}:</strong>
                                ${msg.message}
                                <div class="message-time">${formatTime(msg.created_at)}</div>
                            `;
                            container.appendChild(div);
                        });
                        
                        container.scrollTop = container.scrollHeight;
                        document.getElementById('status').textContent = `✓ Atualizado - ${new Date().toLocaleTimeString()}`;
                    }
                })
                .catch(err => {
                    console.error('Erro ao carregar:', err);
                    document.getElementById('status').textContent = '✗ Erro de conexão';
                });
        }
        
        // URL BASE CORRETA para AGENTE
const API_BASE = '../includes/api/chat/';

function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    const btn = document.getElementById('send-btn');
    
    if (!message) return;
    
    btn.textContent = '⏳...';
    btn.disabled = true;
    
    // URL CORRETA para agente
    fetch(API_BASE + 'send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            complaint_id: complaintId,
            message: message,
            is_from_victim: isVictim
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            input.value = '';
            loadMessages();
        } else {
            throw new Error(data.error || 'Erro desconhecido');
        }
    })
    .catch(err => {
        alert('Erro: ' + err.message);
    })
    .finally(() => {
        btn.textContent = '📤 Enviar';
        btn.disabled = false;
    });
}

function loadMessages() {
    fetch(API_BASE + 'messages.php?complaint_id=' + complaintId + '&last_id=' + lastMessageId)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.messages.length > 0) {
            // ... resto do código igual
        }
    });
}
        
        function formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        }
        
        document.getElementById('message-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
        
        setInterval(loadMessages, 3000);
        loadMessages();
    </script>
</body>
</html>