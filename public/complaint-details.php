<?php
require_once '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fazer Denúncia - Sistema contra Feminicídio</title>
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
                <div class="nav-menu">
                    <a href="index.php" class="nav-link">Página Inicial</a>
                    <a href="check-status.php" class="nav-link">Verificar Status</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <h1 class="form-title">Fazer Denúncia</h1>
            
            <div class="form-options" style="text-align: center; margin-bottom: 2rem;">
                <a href="check-status.php" class="btn btn-secondary">Ver Status da Denúncia</a>
                <a href="make-complaint.php" class="btn btn-primary">Fazer Nova Denúncia</a>
            </div>

            <form action="process-complaint.php" method="POST">
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="anonymous-toggle" name="is_anonymous">
                        Fazer denúncia anônima
                    </label>
                </div>

                <div class="form-group" id="name-field">
                    <label for="complainant_name" class="form-label">Nome Completo *</label>
                    <input type="text" id="complainant_name" name="complainant_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="contact" class="form-label">Contacto Telefónico *</label>
                    <input type="tel" id="contact" name="contact" class="form-control" required placeholder="(84) 123-4567">
                </div>

                <div class="form-group">
                    <label for="complaint_type" class="form-label">Tipo de Feminicídio *</label>
                    <select id="complaint_type" name="complaint_type" class="form-select" required>
                        <option value="">Selecione o tipo</option>
                        <option value="intimo">Agressor Íntimo (Marido, Namorado, Ex, etc.)</option>
                        <option value="nao_intimo">Agressor Não Íntimo (Desconhecido)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="aggressor_relation" class="form-label">Relação com o Agressor</label>
                    <input type="text" id="aggressor_relation" name="aggressor_relation" class="form-control" placeholder="Ex: Marido, Namorado, Ex-namorado, etc.">
                </div>

                <div class="form-group">
                    <label for="incident_date" class="form-label">Data da Ocorrência *</label>
                    <input type="date" id="incident_date" name="incident_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="province" class="form-label">Província *</label>
                    <select id="province" name="province" class="form-select" required>
                        <option value="">Selecione a província</option>
                        <?php foreach(getProvinces() as $province): ?>
                            <option value="<?php echo $province; ?>"><?php echo $province; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="district" class="form-label">Distrito *</label>
                    <input type="text" id="district" name="district" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="neighborhood" class="form-label">Bairro *</label>
                    <input type="text" id="neighborhood" name="neighborhood" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="incident_details" class="form-label">Detalhes do Ocorrido *</label>
                    <textarea id="incident_details" name="incident_details" class="form-control" rows="6" required placeholder="Descreva em detalhes o que aconteceu..."></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Submeter Denúncia
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 DenúnciaFemi. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>
</html>