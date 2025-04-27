FROM php:8.2-cli

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install zip pdo pdo_pgsql

# Установка Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Рабочая директория
WORKDIR /app

# Копируем только нужные файлы (оптимизация слоев Docker)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts

# Копируем остальные файлы
COPY . .

# Порт приложения
EXPOSE 8002

# Команда запуска
CMD ["sh", "-c", "make start"]