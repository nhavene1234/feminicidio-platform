<?php
session_start();
// Configuração ESPECÍFICA para denunciante
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['victim_id'] = 1; // ID específico do denunciante
    $_SESSION['user_name'] = 'Denunciante Teste';
    $_SESSION['user_type'] = 'victim';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Chat - Denunciante</title>
    <style>
        .chat-container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #28a745; background: white; }
        .messages { height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0; background: #f8f9fa; }
        .message { margin: 10px 0; padding: 10px; border-radius: 8px; max-width: 80%; word-wrap: break-word; }
        .victim { background: #e3f2fd; margin-left: auto; text-align: right; border: 1px solid #bbdefb; }
        .agent { background: #d4edda; margin-right: auto; border: 1px solid #c3e6cb; }
        .input-area { display: flex; gap: 10px; align-items: center; }
        .input-area input { flex: 1; padding: 12px; border: 1px solid #28a745; border-radius: 6px; font-size: 14px; }
        .input-area button { padding: 12px 24px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .input-area button:disabled { background: #6c757d; cursor: not-allowed; }
        .message-time { font-size: 11px; color: #666; margin-top: 5px; }
        .header { background: #28a745; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 5px 5px 0 0; }
        .status { margin-top: 10px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="header">
            <h2>👤 Chat da Sua Denúncia #1</h2>
            <div><strong>Você:</strong> <?php echo $_SESSION['user_name']; ?></div>
        </div>
        
        <div class="messages" id="messages-container">
            <div class="message agent">
                <strong>👮 Agente:</strong> Olá! Em que posso ajudar?
                <div class="message-time"><?php echo date('H:i'); ?></div>
            </div>
        </div>
        
        <div class="input-area">
            <input type="text" id="message-input" placeholder="Digite sua mensagem para o agente..." maxlength="500">
            <button onclick="sendMessage()" id="send-btn">📤 Enviar</button>
        </div>
        
        <div id="status" class="status">Conectado...</div>
    </div>

    <script>
        let lastMessageId = 0;
        const complaintId = 1;
        const isVictim = true; // DENUNCIANTE
        
        function loadMessages() {
            fetch('./includes/api/chat/messages.php?complaint_id=' + complaintId + '&last_id=' + lastMessageId)
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
                                <strong>${msg.is_from_victim == 1 ? '👤 Você' : '👮 Agente'}:</strong>
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
        
        function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            const btn = document.getElementById('send-btn');
            
            if (!message) {
                alert('Por favor, digite uma mensagem!');
                return;
            }
            
            // Feedback visual
            btn.textContent = '⏳ Enviando...';
            btn.disabled = true;
            
            fetch('./includes/api/chat/send.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    complaint_id: complaintId,
                    message: message,
                    is_from_victim: isVictim // TRUE para denunciante
                })
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                console.log('Resposta:', data);
                
                if (data.success) {
                    input.value = '';
                    loadMessages(); // Recarregar mensagens
                    document.getElementById('status').textContent = '✓ Mensagem enviada!';
                } else {
                    throw new Error(data.error || 'Erro desconhecido');
                }
            })
            .catch(err => {
                console.error('Erro:', err);
                alert('Erro ao enviar: ' + err.message);
                document.getElementById('status').textContent = '✗ Erro ao enviar';
            })
            .finally(() => {
                btn.textContent = '📤 Enviar';
                btn.disabled = false;
                input.focus();
            });
        }
        
        function formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        }
        
        // Event listeners
        document.getElementById('message-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
        
        // Carregar mensagens a cada 3 segundos (mais lento para performance)
        setInterval(loadMessages, 3000);
        loadMessages(); // Carregar inicial
    </script>
</body>
</html>