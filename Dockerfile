FROM php:8.2-apache

# Instala os drivers necessários para o PHP se conectar ao PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql

# Copia todos os ficheiros do seu repositório para o servidor
COPY . /var/www/html/

# Copia os recursos estáticos para dentro da pasta public no servidor do Render
RUN cp -r /var/www/html/css /var/www/html/public/css || true
RUN cp -r /var/www/html/js /var/www/html/public/js || true
RUN cp -r /var/www/html/assets /var/www/html/public/assets || true

# Altera a raiz do documento do Apache para a pasta 'public' do seu projeto
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Ativa o módulo de reescrita do Apache
RUN a2enmod rewrite

EXPOSE 80
