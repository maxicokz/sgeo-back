# Инструкция по развертыванию SGEO Analytics на хостинге

## Быстрое развертывание

### Вариант 1: Общий хостинг (Shared Hosting)

#### Требования
- PHP 8.0 или выше
- PostgreSQL 12 или выше (или доступ к Supabase)
- Composer
- SSL сертификат (рекомендуется)

#### Шаги развертывания

1. **Загрузка файлов**
   ```bash
   # Через FTP/SFTP загрузите все файлы в директорию на хостинге
   # Обычно это: /home/username/public_html/ или /var/www/html/
   ```

2. **Установка зависимостей**
   ```bash
   # Подключитесь по SSH к хостингу
   cd /path/to/your/application
   composer install --no-dev --optimize-autoloader
   ```

3. **Настройка окружения**
   ```bash
   cp .env.example .env
   nano .env
   ```

   Заполните:
   ```env
   DB_HOST=your_postgres_host
   DB_PORT=5432
   DB_NAME=sgeo_analytics
   DB_USER=your_db_user
   DB_PASSWORD=your_db_password

   OPENROUTER_API_KEY=your_api_key

   APP_URL=https://your-domain.com
   APP_ENV=production
   APP_DEBUG=false
   ```

4. **Создание базы данных**
   ```bash
   # Через phpPgAdmin или командную строку
   psql -h your_host -U your_user -d postgres
   CREATE DATABASE sgeo_analytics;
   \c sgeo_analytics
   \i database/schema.sql
   \q
   ```

5. **Настройка веб-сервера**

   В `.htaccess` уже настроено перенаправление на `public/index.php`

   Убедитесь, что DocumentRoot указывает на `/path/to/sgeo-back/public`

6. **Установка прав**
   ```bash
   chmod 755 logs reports temp
   chown www-data:www-data logs reports temp
   ```

7. **Тест**
   Откройте: `https://your-domain.com`

   Логин: `admin`
   Пароль: `admin123`

---

### Вариант 2: VPS/Dedicated Server (Ubuntu/Debian)

#### Полная установка с нуля

```bash
# 1. Обновление системы
sudo apt update && sudo apt upgrade -y

# 2. Установка PHP 8.0 и необходимых расширений
sudo apt install -y php8.0 php8.0-fpm php8.0-pgsql php8.0-curl php8.0-json php8.0-mbstring php8.0-xml php8.0-zip

# 3. Установка PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# 4. Установка Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 5. Установка Nginx
sudo apt install -y nginx

# 6. Клонирование проекта
cd /var/www
sudo git clone https://github.com/maxicokz/sgeo-back.git
cd sgeo-back

# 7. Запуск скрипта развертывания
sudo ./deploy.sh

# 8. Настройка PostgreSQL
sudo -u postgres psql
CREATE DATABASE sgeo_analytics;
CREATE USER sgeo_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE sgeo_analytics TO sgeo_user;
\q

# 9. Импорт схемы
sudo -u postgres psql sgeo_analytics < database/schema.sql

# 10. Настройка Nginx
sudo nano /etc/nginx/sites-available/sgeo
```

Конфигурация Nginx:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/sgeo-back/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# 11. Активация сайта
sudo ln -s /etc/nginx/sites-available/sgeo /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# 12. SSL (Let's Encrypt)
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

---

### Вариант 3: Docker (Рекомендуется)

Создайте `docker-compose.yml`:

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "80:80"
    environment:
      - DB_HOST=postgres
      - DB_NAME=sgeo_analytics
      - DB_USER=sgeo_user
      - DB_PASSWORD=secure_password
      - OPENROUTER_API_KEY=${OPENROUTER_API_KEY}
    volumes:
      - ./logs:/var/www/html/logs
      - ./reports:/var/www/html/reports
    depends_on:
      - postgres

  postgres:
    image: postgres:14
    environment:
      - POSTGRES_DB=sgeo_analytics
      - POSTGRES_USER=sgeo_user
      - POSTGRES_PASSWORD=secure_password
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./database/schema.sql:/docker-entrypoint-initdb.d/schema.sql

volumes:
  postgres_data:
```

Создайте `Dockerfile`:

```dockerfile
FROM php:8.0-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip

# Enable Apache modules
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data logs reports temp

# Configure Apache
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf

EXPOSE 80
```

Запуск:
```bash
docker-compose up -d
```

---

## Настройка Supabase (альтернатива PostgreSQL)

1. Создайте проект на [supabase.com](https://supabase.com)
2. Получите строку подключения
3. В `.env` укажите:
   ```env
   DB_HOST=db.xxxxxxxxx.supabase.co
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres
   DB_PASSWORD=your_supabase_password
   ```
4. Выполните SQL из `database/schema.sql` в SQL Editor на Supabase

---

## Настройка OpenRouter

1. Зарегистрируйтесь на [openrouter.ai](https://openrouter.ai)
2. Создайте API ключ
3. Добавьте баланс для использования моделей
4. Укажите ключ в `.env`:
   ```env
   OPENROUTER_API_KEY=sk-or-v1-xxxxx
   ```

---

## Автоматизация

### Настройка Cron задач

```bash
crontab -e
```

Добавьте:
```cron
# Ежедневный сбор данных в 2:00 AM
0 2 * * * cd /path/to/sgeo-back && php cli/collect.php

# Еженедельный отчет каждый понедельник в 8:00 AM
0 8 * * 1 cd /path/to/sgeo-back && php cli/generate-report.php weekly

# Ежемесячный отчет 1-го числа в 9:00 AM
0 9 1 * * cd /path/to/sgeo-back && php cli/generate-report.php monthly
```

---

## Проверка работоспособности

```bash
# Проверка подключения к БД
php -r "require 'vendor/autoload.php'; \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); \$dotenv->load(); \$pdo = new PDO('pgsql:host='.\$_ENV['DB_HOST'].';dbname='.\$_ENV['DB_NAME'], \$_ENV['DB_USER'], \$_ENV['DB_PASSWORD']); echo 'OK';"

# Проверка API endpoint
curl http://your-domain.com/api/health

# Ручной запуск сбора данных
php cli/collect.php

# Генерация тестового отчета
php cli/generate-report.php weekly
```

---

## Мониторинг

### Логи

```bash
# Логи приложения
tail -f logs/$(date +%Y-%m-%d).log

# Логи Nginx
tail -f /var/log/nginx/error.log

# Логи PHP-FPM
tail -f /var/log/php8.0-fpm.log
```

---

## Обновление приложения

```bash
cd /path/to/sgeo-back
git pull origin main
composer install --no-dev --optimize-autoloader
sudo systemctl restart nginx
sudo systemctl restart php8.0-fpm
```

---

## Устранение проблем

### Ошибка подключения к БД
- Проверьте настройки в `.env`
- Убедитесь, что PostgreSQL запущен: `sudo systemctl status postgresql`
- Проверьте права доступа в `pg_hba.conf`

### Ошибка 500
- Проверьте логи: `tail -f logs/*.log`
- Включите режим отладки: `APP_DEBUG=true` в `.env`
- Проверьте права на директории: `chmod 755 logs reports temp`

### Проблемы с OpenRouter API
- Проверьте API ключ
- Убедитесь, что есть баланс на аккаунте
- Проверьте лимиты запросов

---

## Контакты для поддержки

- Email: support@sgeo.kz
- Telegram: @sgeo_support
- GitHub Issues: https://github.com/maxicokz/sgeo-back/issues

---

✅ **Приложение готово к использованию!**
