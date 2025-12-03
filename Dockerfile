FROM php:8.2-fpm

# 1. Install dependencies sistem & Nginx
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx

# 2. Install ekstensi PHP yang dibutuhkan Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 3. Get Composer terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Set working directory
WORKDIR /var/www

# 5. Copy file project Anda ke dalam image
COPY . /var/www

# 6. Install library Laravel via Composer
RUN composer install --no-dev --optimize-autoloader

# 7. Atur permission folder storage (PENTING)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# 8. Copy konfigurasi Nginx (Lihat Langkah B di bawah)
COPY nginx.conf /etc/nginx/sites-available/default

# 9. Perintah untuk menjalankan server saat deploy selesai
CMD service nginx start && php-fpm