# Usar PHP 8.2 con Apache como base
FROM php:8.2-apache

# Actualizar e instalar dependencias del sistema y Python
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    libmariadb-dev \
    && rm -rf /var/lib/apt/lists/*

# extensiones de PHP (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Crear un entorno virtual para Python e instalar psutil
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"
RUN pip install psutil

# Habilitar el módulo de reescritura de Apache (útil para APIs)
RUN a2enmod rewrite

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Copiar todo el código del backend al contenedor
COPY . /var/www/html/

# Ajustar permisos para que Apache pueda leer y ejecutar
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80