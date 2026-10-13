# ==============================================================================
# AR Tilik — On-Premise Enterprise Container
# Stack: PHP 8.5 (FPM/Apache) + Python 3.14 (Forensic Worker) + Debian Base
# ==============================================================================

FROM debian:bookworm-slim

LABEL maintainer="AR Tilik Engineering Team <support@tilik.arsetontong.top>"
LABEL description="AR Tilik Academic Writing Similarity & Evidence Checker (On-Premise Edition)"
LABEL version="1.3.0"

ENV DEBIAN_FRONTEND=noninteractive \
    TZ=Asia/Jakarta \
    PYTHONUNBUFFERED=1 \
    VIRTUAL_ENV=/opt/venv \
    PATH="/opt/venv/bin:$PATH"

# 1. Pasang dependensi dasar & repository Sury PHP 8.5
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    gnupg2 \
    lsb-release \
    apt-transport-https \
    supervisor \
    apache2 \
    git \
    unzip \
    libcap2-bin \
    tzdata \
    && curl -sSLo /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
    && echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# 2. Pasang PHP 8.5 & modul-modul penting CodeIgniter 4
RUN apt-get update && apt-get install -y --no-install-recommends \
    php8.5-cli \
    php8.5-fpm \
    php8.5-mysql \
    php8.5-intl \
    php8.5-mbstring \
    php8.5-curl \
    php8.5-gd \
    php8.5-zip \
    php8.5-xml \
    php8.5-bcmath \
    php8.5-opcache \
    python3 \
    python3-venv \
    python3-pip \
    libmupdf-dev \
    && rm -rf /var/lib/apt/lists/*

# 3. Pasang Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 4. Konfigurasi Apache & FastCGI PHP 8.5 FPM
RUN a2enmod rewrite actions proxy_fcgi setenvif headers ssl \
    && a2enconf php8.5-fpm

COPY <<EOF /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.5-fpm.sock|fcgi://localhost"
    </FilesMatch>

    ErrorLog \${APACHE_LOG_DIR}/artilik_error.log
    CustomLog \${APACHE_LOG_DIR}/artilik_access.log combined
</VirtualHost>
EOF

# 5. Salin source code & pasang Python Virtualenv
WORKDIR /var/www/html
COPY . /var/www/html

# Inisialisasi Python Virtualenv dengan requirements.txt
RUN python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --no-cache-dir --upgrade pip setuptools wheel \
    && if [ -f requirements.txt ]; then /opt/venv/bin/pip install --no-cache-dir -r requirements.txt; fi

# Pasang dependensi PHP melalui Composer (tanpa dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Berikan izin direktori writable dan storage
RUN chown -R www-data:www-data /var/www/html/writable /var/www/html/storage /var/www/html/public \
    && chmod -R 775 /var/www/html/writable /var/www/html/storage \
    && chmod +x /var/www/html/artilik

# 6. Konfigurasi Supervisord untuk Apache, PHP-FPM, dan Worker Daemon
COPY <<EOF /etc/supervisor/conf.d/artilik.conf
[supervisord]
nodaemon=true
logfile=/var/log/supervisord.log
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=/usr/sbin/php-fpm8.5 -F
autostart=true
autorestart=true
priority=10

[program:apache2]
command=/usr/sbin/apache2ctl -D FOREGROUND
autostart=true
autorestart=true
priority=20

[program:artilik-worker]
command=/var/www/html/artilik worker
directory=/var/www/html
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/artilik_worker.log
priority=30
EOF

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/artilik.conf"]
