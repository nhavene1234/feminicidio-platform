// script.js
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para denúncia anônima
    const anonymousToggle = document.getElementById('anonymous-toggle');
    if (anonymousToggle) {
        anonymousToggle.addEventListener('change', function() {
            const nameField = document.getElementById('name-field');
            if (this.checked) {
                nameField.style.display = 'none';
            } else {
                nameField.style.display = 'block';
            }
        });
    }

    // Formatação de telefone
    const phoneInput = document.getElementById('contact');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                if (value.length > 10) {
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                }
            }
            e.target.value = value;
        });
    }

    // Validação de formulário
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--danger)';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
            }
        });
    });
});