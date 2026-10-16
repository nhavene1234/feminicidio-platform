<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
checkAuth();

$complaint_code = $_GET['code'] ?? '';

if (!$complaint_code) {
    header('Location: dashboard.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT c.*, u.name as agent_name, u.post as agent_post, u.user_type 
                          FROM complaints c 
                          LEFT JOIN users u ON c.assigned_agent_id = u.id 
                          WHERE c.complaint_code = ?");
    $stmt->execute([$complaint_code]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$complaint) {
        die("Denúncia não encontrada!");
    }
    
    // Buscar atualizações
    $updates_stmt = $pdo->prepare("SELECT cu.*, u.name as user_name, u.user_type 
                                  FROM complaint_updates cu 
                                  JOIN users u ON cu.user_id = u.id 
                                  WHERE cu.complaint_id = ? 
                                  ORDER BY cu.created_at DESC");
    $updates_stmt->execute([$complaint['id']]);
    $updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Erro ao carregar denúncia: " . $e->getMessage());
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $description = $_POST['description'] ?? '';
    
    try {
        if ($action === 'update_status') {
            $new_status = $_POST['status'];
            $pdo->prepare("UPDATE complaints SET status = ?, assigned_agent_id = ? WHERE id = ?")
                ->execute([$new_status, $_SESSION['user_id'], $complaint['id']]);
            
            // Registrar a atualização
            $pdo->prepare("INSERT INTO complaint_updates (complaint_id, user_id, update_type, description) 
                          VALUES (?, ?, 'status_change', ?)")
                ->execute([$complaint['id'], $_SESSION['user_id'], "Status alterado para: " . $new_status]);
                
        } elseif ($action === 'add_note') {
            $pdo->prepare("INSERT INTO complaint_updates (complaint_id, user_id, update_type, description) 
                          VALUES (?, ?, 'note', ?)")
                ->execute([$complaint['id'], $_SESSION['user_id'], $description]);
                
        } elseif ($action === 'make_referral') {
            $to_agent_type = $_POST['to_agent_type'];
            $reason = $_POST['reason'];
            $pdo->prepare("INSERT INTO referrals (complaint_id, from_agent_id, to_agent_type, reason) 
                          VALUES (?, ?, ?, ?)")
                ->execute([$complaint['id'], $_SESSION['user_id'], $to_agent_type, $reason]);
                
            // Registrar atualização
            $pdo->prepare("INSERT INTO complaint_updates (complaint_id, user_id, update_type, description) 
                          VALUES (?, ?, 'referral', ?)")
                ->execute([$complaint['id'], $_SESSION['user_id'], "Caso encaminhado para: " . $to_agent_type . " - " . $reason]);
                
        } elseif ($action === 'inas_support') {
            $support_type = $_POST['support_type'];
            $details = $_POST['details'];
            $scheduled_date = $_POST['scheduled_date'] ?? null;
            
            // Registrar o tipo de apoio do INAS
            $support_types = [
                'conversa_presencial' => 'Conversa Presencial Marcada',
                'apoio_psicossocial' => 'Apoio Psicossocial',
                'acolhimento_temporario' => 'Acolhimento Temporário',
                'reintegracao_social' => 'Apoio à Reintegração Social',
                'visita_delegacao' => 'Chamada para Delegação'
            ];
            
            $description = "Ação INAS: " . $support_types[$support_type];
            if ($details) $description .= " - " . $details;
            if ($scheduled_date) $description .= " (Agendado para: " . date('d/m/Y', strtotime($scheduled_date)) . ")";
            
            $pdo->prepare("INSERT INTO complaint_updates (complaint_id, user_id, update_type, description) 
                          VALUES (?, ?, 'inas_support', ?)")
                ->execute([$complaint['id'], $_SESSION['user_id'], $description]);
                
        } elseif ($action === 'start_chat') {
            // Iniciar chat com a vítima
            $message = $_POST['message'];
            $pdo->prepare("INSERT INTO complaint_updates (complaint_id, user_id, update_type, description) 
                          VALUES (?, ?, 'chat_start', ?)")
                ->execute([$complaint['id'], $_SESSION['user_id'], "Chat iniciado com a vítima: " . $message]);
        }
        
        header("Location: view-complaint.php?code=" . $complaint_code . "&success=1");
        exit();
        
    } catch(PDOException $e) {
        $error = "Erro ao processar ação: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Denúncia - Sistema contra Feminicídio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .complaint-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .detail-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .actions-panel {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .updates-timeline {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .timeline-item {
            padding: 1rem;
            border-left: 3px solid var(--primary);
            margin-bottom: 1rem;
            background: #f8f9fa;
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .inas-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .inas-action-card {
            background: linear-gradient(135deg, #FF9800, #FF5722);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .inas-action-card:hover {
            transform: translateY(-3px);
        }
        
        .inas-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .chat-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 1rem;
            overflow: hidden;
        }
        
        .chat-messages {
            height: 300px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8f9fa;
        }
        
        .chat-input {
            display: flex;
            padding: 1rem;
            border-top: 1px solid #eee;
        }
        
        .message {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 10px;
            max-width: 80%;
        }
        
        .message-agent {
            background: var(--primary);
            color: white;
            margin-left: auto;
        }
        
        .message-victim {
            background: white;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="dashboard.php">
                        <i class="fas fa-shield-heart"></i>
                        DenúnciaFemi
                    </a>
                </div>
                <div class="nav-menu">
                    <span style="color: white; margin-right: 1rem;">
                        <?php echo $_SESSION['user_name']; ?>
                        <?php echo getUserTypeBadge($_SESSION['user_type']); ?>
                    </span>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="logout.php" class="nav-link">Sair</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="complaint-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1>Denúncia #<?php echo $complaint['complaint_code']; ?></h1>
                        <p>Status: <?php echo getStatusBadge($complaint['status']); ?></p>
                    </div>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Ação realizada com sucesso!
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="details-grid">
                <div class="detail-card">
                    <h3>Informações da Vítima</h3>
                    <p><strong>Nome:</strong> <?php echo $complaint['is_anonymous'] ? 'Anônimo' : htmlspecialchars($complaint['complainant_name']); ?></p>
                    <p><strong>Contacto:</strong> <?php echo htmlspecialchars($complaint['complainant_contact']); ?></p>
                    <p><strong>Tipo de Feminicídio:</strong> <?php echo getComplaintTypeText($complaint['complaint_type']); ?></p>
                    <?php if ($complaint['aggressor_relation']): ?>
                    <p><strong>Relação com Agressor:</strong> <?php echo htmlspecialchars($complaint['aggressor_relation']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="detail-card">
                    <h3>Localização</h3>
                    <p><strong>Data da Ocorrência:</strong> <?php echo date('d/m/Y', strtotime($complaint['incident_date'])); ?></p>
                    <p><strong>Província:</strong> <?php echo htmlspecialchars($complaint['province']); ?></p>
                    <p><strong>Distrito:</strong> <?php echo htmlspecialchars($complaint['district']); ?></p>
                    <p><strong>Bairro:</strong> <?php echo htmlspecialchars($complaint['neighborhood']); ?></p>
                </div>
            </div>

            <div class="detail-card">
                <h3>Detalhes do Ocorrido</h3>
                <div style="white-space: pre-wrap; background: #f8f9fa; padding: 1rem; border-radius: 5px;">
                    <?php echo htmlspecialchars($complaint['incident_details']); ?>
                </div>
            </div>

            <!-- Painel de Ações Gerais -->
            <div class="actions-panel">
                <h3>Ações Disponíveis</h3>
                <div class="action-buttons">
                    <!-- Alterar Status -->
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="update_status">
                        <select name="status" onchange="this.form.submit()" class="form-select" style="width: auto; display: inline-block;">
                            <option value="pendente" <?php echo $complaint['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="andamento" <?php echo $complaint['status'] == 'andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                            <option value="resolvido" <?php echo $complaint['status'] == 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                        </select>
                    </form>

                    <!-- Adicionar Nota -->
                    <button onclick="openModal('note-modal')" class="btn btn-info">
                        <i class="fas fa-sticky-note"></i> Adicionar Nota
                    </button>

                    <!-- Encaminhar -->
                    <button onclick="openModal('referral-modal')" class="btn btn-warning">
                        <i class="fas fa-share"></i> Encaminhar
                    </button>

                    <!-- Ligar para Vítima -->
                    <a href="tel:<?php echo $complaint['complainant_contact']; ?>" class="btn btn-success">
                        <i class="fas fa-phone"></i> Ligar para Vítima
                    </a>
                </div>

                <!-- Ações Específicas do INAS -->
                <?php if ($_SESSION['user_type'] === 'inas'): ?>
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #FF9800;">
                    <h4 style="color: #FF9800; margin-bottom: 1rem;">
                        <i class="fas fa-hands-helping"></i> Ações de Apoio INAS
                    </h4>
                    
                    <div class="inas-actions-grid">
                        <div class="inas-action-card" onclick="openModal('conversa-modal')">
                            <div class="inas-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h4>Conversa Presencial</h4>
                            <p>Marcar encontro</p>
                        </div>
                        
                        <div class="inas-action-card" onclick="openModal('psicossocial-modal')">
                            <div class="inas-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h4>Apoio Psicossocial</h4>
                            <p>Suporte emocional</p>
                        </div>
                        
                        <div class="inas-action-card" onclick="openModal('acolhimento-modal')">
                            <div class="inas-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <h4>Acolhimento Temporário</h4>
                            <p>Abrigo seguro</p>
                        </div>
                        
                        <div class="inas-action-card" onclick="openModal('reintegracao-modal')">
                            <div class="inas-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4>Reintegração Social</h4>
                            <p>Inserção social</p>
                        </div>
                        
                        <div class="inas-action-card" onclick="openModal('delegacao-modal')">
                            <div class="inas-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <h4>Chamar para Delegação</h4>
                            <p>Atendimento local</p>
                        </div>
                        
                        <div class="inas-action-card" onclick="openModal('chat-modal')">
                            <div class="inas-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h4>Iniciar Chat</h4>
                            <p>Conversa online</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Timeline de Atualizações -->
            <div class="updates-timeline">
                <h3>Histórico de Atualizações</h3>
                <?php if ($updates): ?>
                    <?php foreach($updates as $update): ?>
                        <div class="timeline-item">
                            <div class="timeline-header">
                                <strong><?php echo htmlspecialchars($update['user_name']); ?></strong>
                                <small><?php echo date('d/m/Y H:i', strtotime($update['created_at'])); ?></small>
                            </div>
                            <p><?php echo htmlspecialchars($update['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhuma atualização registrada ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal para Adicionar Nota -->
    <div id="note-modal" class="modal">
        <div class="modal-content">
            <h3>Adicionar Nota</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_note">
                <div class="form-group">
                    <textarea name="description" class="form-control" rows="4" placeholder="Digite sua nota..." required></textarea>
                </div>
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Salvar Nota</button>
                    <button type="button" onclick="closeModal('note-modal')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Encaminhar -->
    <div id="referral-modal" class="modal">
        <div class="modal-content">
            <h3>Encaminhar Caso</h3>
            <form method="POST">
                <input type="hidden" name="action" value="make_referral">
                <div class="form-group">
                    <label class="form-label">Encaminhar para:</label>
                    <select name="to_agent_type" class="form-select" required>
                        <option value="">Selecione o tipo de agente</option>
                        <?php if ($_SESSION['user_type'] !== 'police'): ?>
                        <option value="police">Polícia</option>
                        <?php endif; ?>
                        <?php if ($_SESSION['user_type'] !== 'hospital'): ?>
                        <option value="hospital">Hospital</option>
                        <?php endif; ?>
                        <?php if ($_SESSION['user_type'] !== 'inas'): ?>
                        <option value="inas">INAS</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Motivo do encaminhamento:</label>
                    <textarea name="reason" class="form-control" rows="4" placeholder="Digite o motivo do encaminhamento..." required></textarea>
                </div>
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Encaminhar</button>
                    <button type="button" onclick="closeModal('referral-modal')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Conversa Presencial -->
    <div id="conversa-modal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-calendar-check"></i> Marcar Conversa Presencial</h3>
            <form method="POST">
                <input type="hidden" name="action" value="inas_support">
                <input type="hidden" name="support_type" value="conversa_presencial">
                
                <div class="form-group">
                    <label class="form-label">Data do Encontro:</label>
                    <input type="date" name="scheduled_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Local do Encontro:</label>
                    <input type="text" name="details" class="form-control" placeholder="Ex: Delegação INAS Maputo, Sala 2" required>
                </div>
                
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Marcar Encontro</button>
                    <button type="button" onclick="closeModal('conversa-modal')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Apoio Psicossocial -->
    <div id="psicossocial-modal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-heart"></i> Apoio Psicossocial</h3>
            <form method="POST">
                <input type="hidden" name="action" value="inas_support">
                <input type="hidden" name="support_type" value="apoio_psicossocial">
                
                <div class="form-group">
                    <label class="form-label">Tipo de Apoio:</label>
                    <select name="details" class="form-select" required>
                        <option value="">Selecione o tipo de apoio</option>
                        <option value="Aconselhamento psicológico individual">Aconselhamento Psicológico Individual</option>
                        <option value="Terapia em grupo">Terapia em Grupo</option>
                        <option value="Acompanhamento emocional">Acompanhamento Emocional</option>
                        <option value="Orientação familiar">Orientação Familiar</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Observações:</label>
                    <textarea name="additional_details" class="form-control" rows="3" placeholder="Detalhes adicionais..."></textarea>
                </div>
                
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Registrar Apoio</button>
                    <button type="button" onclick="closeModal('psicossocial-modal')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Acolhimento Temporário -->
    <div id="acolhimento-modal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-home"></i> Acolhimento Temporário</h3>
            <form method="POST">
                <input type="hidden" name="action" value="inas_support">
                <input type="hidden" name="support_type" value="acolhimento_temporario">
                
                <div class="form-group">
                    <label class="form-label">Centro de Acolhimento:</label>
                    <select name="details" class="form-select" required>
                        <option value="">Selecione o centro</option>
                        <option value="Centro de Acolhimento Maputo">Centro de Acolhimento Maputo</option>
                        <option value="Casa Abrigo Beira">Casa Abrigo Beira</option>
                        <option value="Centro Nampula">Centro Nampula</option>
                        <option value="Abrigo Temporário Inhambane">Abrigo Temporário Inhambane</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Duração Estimada:</label>
                    <select name="duration" class="form-select">
                        <option value="1-2 semanas">1-2 semanas</option>
                        <option value="3-4 semanas">3-4 semanas</option>
                        <option value="1-2 meses">1-2 meses</option>
                        <option value="A definir">A definir</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Organizar Acolhimento</button>
                    <button type="button" onclick="closeModal('acolhimento-modal')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Reintegração Social -->
    <div id="reintegracao-modal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-users"></i> Apoio à Reintegração Social</h3>
            <form method="POST">
                <input type="hidden" name="action" value="inas_support">
                <input type="hidden" name="support_type" value="reintegracao_social">
                
                <div class="form-group">
                    <label class="form-label">Tipo de Reintegração:</label>
                    <select name="details" class="form-select" required>
                        <option value="">Selecione o tipo</option>
                        <option value="Inserção profissional">Inserção Profissional</option>
                        <option value="Formação profissional">Formação Profissional</option>
                        <option value="Apoio ao empreendedorismo">Apoio ao Empreendedorismo</option>
                        <option value="Reintegração familiar">Reintegração Familiar</option>
                        <option value="Apoio habitacional">Apoio Habitacional</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Plano de Ação:</label>
                    <textarea name="action_plan" class="form-control" rows="3" placeholder="Descreva o plano de reintegração..."></textarea>
                </div>
                
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Iniciar Reintegração</button>
                    <button type="button" onclick="closeModal('reintegracao-modal')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Chamar para Delegação -->
    <div id="delegacao-modal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-building"></i> Chamar para Delegação</h3>
            <form method="POST">
                <input type="hidden" name="action" value="inas_support">
                <input type="hidden" name="support_type" value="visita_delegacao">
                
                <div class="form-group">
                    <label class="form-label">Delegação:</label>
                    <select name="details" class="form-select" required>
                        <option value="">Selecione a delegação</option>
                        <option value="Delegação INAS Maputo Cidade">Delegação INAS Maputo Cidade</option>
                        <option value="Delegação INAS Matola">Delegação INAS Matola</option>
                        <option value="Delegação INAS Beira">Delegação INAS Beira</option>
                        <option value="Delegação INAS Nampula">Delegação INAS Nampula</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Data Sugerida:</label>
                    <input type="date" name="scheduled_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Motivo da Visita:</label>
                    <textarea name="visit_reason" class="form-control" rows="3" placeholder="Descreva o motivo da visita..." required></textarea>
                </div>
                
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Convidar para Delegação</button>
                    <button type="button" onclick="closeModal('delegacao-modal')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Chat -->
    <div id="chat-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <h3><i class="fas fa-comments"></i> Iniciar Chat com a Vítima</h3>
            
            <div class="chat-container">
                <div class="chat-messages" id="chat-messages">
                    <!-- Mensagens do chat aparecerão aqui -->
                    <div class="message message-agent">
                        <strong>Agente INAS:</strong><br>
                        Olá! Sou <?php echo $_SESSION['user_name']; ?> do INAS. Como posso ajudá-la hoje?
                    </div>
                </div>
                
                <form method="POST" class="chat-input">
                    <input type="hidden" name="action" value="start_chat">
                    <input type="text" name="message" class="form-control" placeholder="Digite sua mensagem..." required style="flex: 1;">
                    <button type="submit" class="btn btn-primary" style="margin-left: 0.5rem;">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
            
            <div style="margin-top: 1rem; text-align: center;">
                <button type="button" onclick="closeModal('chat-modal')" class="btn btn-secondary">Fechar Chat</button>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 DenúnciaFemi. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Fechar modais clicando fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Fechar com ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.getElementsByClassName('modal');
                for (let modal of modals) {
                    modal.style.display = 'none';
                }
            }
        });
        
        // Auto-focus no campo de mensagem do chat
        document.getElementById('chat-modal').addEventListener('shown', function() {
            document.querySelector('#chat-modal input[name="message"]').focus();
        });
    </script>
</body>
</html>