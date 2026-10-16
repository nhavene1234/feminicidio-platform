<?php
class ChatController {
    private $db;
    
    public function __construct() {
        // Caminho ABSOLUTO correto para config.php
        $config_path = realpath(__DIR__ . '/../../includes/config.php');
        
        if (!$config_path || !file_exists($config_path)) {
            throw new Exception('Arquivo config.php não encontrado. Procurado em: ' . $config_path);
        }
        
        require_once $config_path;
        
        try {
            $this->db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Conexão com banco falhou: ' . $e->getMessage());
        }
    }
    
    public function sendMessage($complaint_id, $user_id, $message, $is_from_victim) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO chat_messages 
                (complaint_id, user_id, message, is_from_victim, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $success = $stmt->execute([
                intval($complaint_id), 
                intval($user_id), 
                htmlspecialchars($message), 
                $is_from_victim ? 1 : 0
            ]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message_id' => $this->db->lastInsertId()
                ];
            } else {
                return ['success' => false, 'error' => 'Falha ao executar query'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Erro no banco: ' . $e->getMessage()];
        }
    }
    
    public function getMessages($complaint_id, $last_message_id = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT cm.*, u.name as user_name
                FROM chat_messages cm
                LEFT JOIN users u ON cm.user_id = u.id
                WHERE cm.complaint_id = ? AND cm.id > ?
                ORDER BY cm.created_at ASC
            ");
            
            $stmt->execute([intval($complaint_id), intval($last_message_id)]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'messages' => $messages
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false, 
                'error' => $e->getMessage(),
                'messages' => []
            ];
        }
    }
}
?>