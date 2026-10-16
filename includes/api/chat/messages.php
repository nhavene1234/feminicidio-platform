<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // CAMINHO CORRETO para o Controller
    $controller_path = realpath(__DIR__ . '/../../controllers/ChatController.php');
    
    if (!$controller_path) {
        throw new Exception('ChatController não encontrado');
    }
    
    require_once $controller_path;
    
    $chatController = new ChatController();
    
    $complaint_id = isset($_GET['complaint_id']) ? intval($_GET['complaint_id']) : 0;
    $last_message_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    if ($complaint_id <= 0) {
        throw new Exception('ID da denúncia é obrigatório');
    }
    
    $result = $chatController->getMessages($complaint_id, $last_message_id);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'messages' => []
    ], JSON_UNESCAPED_UNICODE);
}

exit;
?>