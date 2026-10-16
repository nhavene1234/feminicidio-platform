<?php
// public/process-complaint.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coletar dados do formulário
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $complainant_name = $is_anonymous ? NULL : $_POST['complainant_name'];
    
    // Juntar código do país + contacto
    $contact = '+258 ' . $_POST['contact'];
    
    $complaint_type = $_POST['complaint_type'];
    $aggressor_relation = $_POST['aggressor_relation'];
    $incident_date = $_POST['incident_date'];
    $province = $_POST['province'];
    $district = $_POST['district'];
    $neighborhood = $_POST['neighborhood'];
    $incident_details = $_POST['incident_details'];
    
    // Gerar código único
    $complaint_code = generateComplaintCode();
    
    try {
        // Inserir no banco de dados
        $stmt = $pdo->prepare("INSERT INTO complaints 
            (complaint_code, complainant_name, complainant_contact, complaint_type, 
             aggressor_relation, incident_date, province, district, neighborhood, 
             incident_details, is_anonymous) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $complaint_code,
            $complainant_name,
            $contact,
            $complaint_type,
            $aggressor_relation,
            $incident_date,
            $province,
            $district,
            $neighborhood,
            $incident_details,
            $is_anonymous
        ]);
        
        // Página de sucesso
        ?>
        <!DOCTYPE html>
        <html lang="pt">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Denúncia Submetida - Sistema contra Feminicídio</title>
            <link rel="stylesheet" href="../css/style.css">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        </head>
        <body>
            <header>
                <nav class="navbar">
                    <div class="nav-container">
                        <div class="nav-logo">
                            <a href="index.php">
                                <i class="fas fa-shield-heart"></i>
                                DenúnciaFemi
                            </a>
                        </div>
                    </div>
                </nav>
            </header>

            <main>
                <div class="form-container" style="text-align: center;">
                    <div class="success-icon" style="font-size: 4rem; color: var(--success); margin-bottom: 1rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="form-title">Denúncia Submetida com Sucesso!</h1>
                    
                    <div class="complaint-code" style="background: #f8f9fa; padding: 2rem; border-radius: 10px; margin: 2rem 0;">
                        <h3>Seu Código de Acompanhamento:</h3>
                        <div style="font-size: 2rem; font-weight: bold; color: var(--primary); letter-spacing: 2px;">
                            <?php echo $complaint_code; ?>
                        </div>
                        <p style="margin-top: 1rem; color: var(--gray);">
                            Guarde este código para acompanhar o andamento da sua denúncia.
                        </p>
                    </div>

                    <div class="action-buttons" style="margin-top: 2rem;">
                        <a href="check-status.php?code=<?php echo $complaint_code; ?>" class="btn btn-primary">
                            <i class="fas fa-search"></i> Acompanhar esta Denúncia
                        </a>
                        <a href="make-complaint.php" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Fazer Nova Denúncia
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Página Inicial
                        </a>
                    </div>
                </div>
            </main>

           
        </body>
        </html>
        <?php
        
    } catch(PDOException $e) {
        die("Erro ao submeter denúncia: " . $e->getMessage());
    }
} else {
    header('Location: make-complaint.php');
    exit();
}
?>