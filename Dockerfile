# Usamos a imagem oficial do PHP 8.2 com Apache
FROM php:8.2-apache

# Instala as extensões essenciais do PHP para trabalhar com MySQL (PDO e MySQLi)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilita o mod_rewrite do Apache (necessário para manipulação de rotas)
RUN a2enmod rewrite

# Copia todos os arquivos do seu diretório local para dentro do diretório padrão do Apache no container
COPY . /var/www/html/

# Ajusta as permissões de propriedade dos arquivos para o usuário do Apache
RUN chown -R www-data:www-data /var/www/html

# O Render injeta dinamicamente a porta através da variável de ambiente PORT (geralmente 10000)
# Substituímos a porta padrão do Apache (80) pela porta exigida pelo Render
ENV PORT=8080
RUN sed -i -e 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE 8080