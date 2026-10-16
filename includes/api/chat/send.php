<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dados JSON inválidos');
    }

    // CAMINHO CORRETO para o Controller (2 níveis acima)
    $controller_path = realpath(__DIR__ . '/../../controllers/ChatController.php');
    
    if (!$controller_path) {
        throw new Exception('ChatController não encontrado. Caminho tentado: ' . __DIR__ . '/../../controllers/ChatController.php');
    }
    
    require_once $controller_path;
    
    $chatController = new ChatController();
    
    // Validar dados
    $complaint_id = isset($input['complaint_id']) ? intval($input['complaint_id']) : 0;
    $message = isset($input['message']) ? trim($input['message']) : '';
    $is_from_victim = isset($input['is_from_victim']) ? (bool)$input['is_from_victim'] : false;
    
    // Usuário fixo para teste
    $user_id = $is_from_victim ? 1 : 2; // 1=denunciante, 2=agente
    
    if ($complaint_id <= 0) {
        throw new Exception('ID da denúncia inválido');
    }
    
    if (empty($message)) {
        throw new Exception('Mensagem não pode estar vazia');
    }
    
    // Enviar mensagem
    $result = $chatController->sendMessage($complaint_id, $user_id, $message, $is_from_victim);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit;
?>