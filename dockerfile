FROM php:8.2-apache

# Instala os drivers necessários para o PHP se conectar ao PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql

# Copia os arquivos do seu repositório para a pasta pública do servidor Apache
COPY . /var/www/html/

# Ativa o módulo de reescrita do Apache (caso use arquivos .htaccess no futuro)
RUN a2enmod rewrite

# Expõe a porta padrão do Apache
EXPOSE 80
