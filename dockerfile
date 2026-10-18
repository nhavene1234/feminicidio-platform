FROM php:8.2-apache

# Instala os drivers necessários para o PHP se conectar ao PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql

# Altera a raiz do documento do Apache para a pasta 'public' do seu projeto
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Copia todos os ficheiros do seu repositório para o servidor
COPY . /var/www/html/

# Ativa o módulo de reescrita do Apache
RUN a2enmod rewrite

EXPOSE 80
