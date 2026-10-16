<?php
// includes/functions.php
require_once 'config.php';

function generateComplaintCode() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function getProvinces() {
    return [
        'Maputo Cidade',
        'Maputo Província',
        'Gaza',
        'Inhambane',
        'Sofala',
        'Manica',
        'Tete',
        'Zambézia',
        'Nampula',
        'Cabo Delgado',
        'Niassa'
    ];
}

function getStatusBadge($status) {
    $badges = [
        'pendente' => '<span class="badge badge-warning">Pendente</span>',
        'andamento' => '<span class="badge badge-info">Em Andamento</span>',
        'resolvido' => '<span class="badge badge-success">Resolvido</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">' . $status . '</span>';
}

function getComplaintTypeText($type) {
    $types = [
        'intimo' => 'Agressor Íntimo (Marido, Namorado, Ex, etc.)',
        'nao_intimo' => 'Agressor Não Íntimo (Desconhecido)'
    ];
    return $types[$type] ?? $type;
}
?>