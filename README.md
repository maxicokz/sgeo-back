# SGEO Analytics Dashboard

**Система мониторинга и аналитики представления Казахстана в AI-системах**

Веб-приложение для отслеживания и оптимизации представления информации о Казахстане и партнёрах в системах искусственного интеллекта (ChatGPT, Bing, Copilot, Perplexity, Gemini).

## 🎯 Основная цель

Обеспечить контроль качества и корректности информации о Казахстане в ответах больших языковых моделей (LLM) через систему непрерывного мониторинга, анализа и оптимизации контента.

## 🔧 Технологический стек

- **Backend**: PHP 8.0+
- **Database**: PostgreSQL 12+
- **API**: OpenRouter (для доступа к LLM моделям)
- **Reports**: DomPDF (HTML → PDF)
- **Frontend**: Vanilla JS + Chart.js

## 📊 Основные возможности

### 1. Модуль сбора данных
- Автоматический сбор ответов от 5+ LLM-систем
- 20 приоритетных тем × 5 LLM × 5 промптов
- AI-скрейпинг: ответы, ссылки, метаданные
- Контроль версий моделей и журнал запусков

### 2. Модуль аналитики
- **NLP-оценка контента** (шкала 0-5):
  - Тональность (Sentiment)
  - Полнота информации (Completeness)
  - Корректность данных (Correctness)

- **Source E-E-A-T Scorecard** (0-100):
  - Expertise (экспертность)
  - Authoritativeness (авторитетность)
  - Trustworthiness (надёжность)
  - Experience (опыт)

- Индекс здоровья источника (0-100)
- Выявление ключевых рисков и уязвимостей

### 3. Модуль отчётности
- Автоматическая генерация PDF-отчётов
- Еженедельные и итоговые аналитические отчёты
- Сравнительная аналитика "до/после"

## 🚀 Установка и деплой

### Требования

- PHP >= 8.0
- PostgreSQL >= 12
- Composer
- Apache/Nginx с mod_rewrite

### Шаг 1: Клонирование и установка зависимостей

```bash
# Клонировать репозиторий
git clone https://github.com/maxicokz/sgeo-back.git
cd sgeo-back

# Установить зависимости через Composer
composer install
```

### Шаг 2: Настройка окружения

```bash
# Создать файл .env из примера
cp .env.example .env

# Отредактировать .env и указать настройки
nano .env
```

Основные настройки в `.env`:

```env
# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=sgeo_analytics
DB_USER=postgres
DB_PASSWORD=your_secure_password

# OpenRouter API
OPENROUTER_API_KEY=your_openrouter_api_key

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### Шаг 3: Создание базы данных

```bash
# Подключиться к PostgreSQL
psql -U postgres

# Создать базу данных
CREATE DATABASE sgeo_analytics;

# Выйти из psql
\q

# Импортировать схему
psql -U postgres -d sgeo_analytics -f database/schema.sql
```

### Шаг 4: Настройка веб-сервера

#### Apache

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/sgeo-back/public

    <Directory /path/to/sgeo-back/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [L]
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sgeo_error.log
    CustomLog ${APACHE_LOG_DIR}/sgeo_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/sgeo-back/public;

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

### Шаг 5: Настройка прав доступа

```bash
# Создать необходимые директории
mkdir -p logs reports temp

# Установить права доступа
chmod 755 logs reports temp
chown -R www-data:www-data logs reports temp

# Для Apache
sudo chown -R www-data:www-data /path/to/sgeo-back

# Для Nginx
sudo chown -R nginx:nginx /path/to/sgeo-back
```

### Шаг 6: Запуск приложения

1. Перезапустите веб-сервер:
```bash
# Apache
sudo systemctl restart apache2

# Nginx
sudo systemctl restart nginx
sudo systemctl restart php8.0-fpm
```

2. Откройте браузер и перейдите по адресу: `http://your-domain.com`

3. Войдите с учетными данными по умолчанию:
   - **Username**: `admin`
   - **Password**: `admin123`

⚠️ **ВАЖНО**: Сразу после первого входа смените пароль администратора!

## 📖 Использование

### Запуск сбора данных

1. Войдите в дашборд
2. Нажмите "Start Data Collection"
3. Процесс займет 10-30 минут в зависимости от количества тем
4. Следите за прогрессом в реальном времени

### Генерация отчетов

1. Перейдите в раздел "Reports"
2. Нажмите "Generate Report"
3. Выберите тип отчета (weekly/monthly/final)
4. Скачайте сгенерированный PDF

### API Endpoints

Основные API endpoints:

- `POST /api/collection/start` - Запуск сбора данных
- `GET /api/run/status?run_id={id}` - Статус запуска
- `POST /api/report/generate` - Генерация отчета
- `GET /api/dashboard` - Данные дашборда
- `GET /api/topics` - Список тем
- `GET /api/sources` - Список источников

## 🔐 Безопасность

- Все пароли хешируются с использованием `password_hash()`
- Защита от CSRF-атак
- Подготовленные SQL-запросы (prepared statements)
- Валидация и санитизация всех входных данных
- Логирование всех критических операций

## 🛠 Настройка автоматизации

### Cron задачи для автоматического сбора данных

```bash
# Открыть crontab
crontab -e

# Добавить задачу для ежедневного сбора данных в 2:00 AM
0 2 * * * php /path/to/sgeo-back/cli/collect.php

# Еженедельный отчет каждый понедельник в 8:00 AM
0 8 * * 1 php /path/to/sgeo-back/cli/generate-report.php weekly
```

## 📊 Структура проекта

```
sgeo-back/
├── config/              # Конфигурационные файлы
├── database/            # SQL схемы и миграции
├── logs/                # Логи приложения
├── public/              # Публичная директория (DocumentRoot)
│   ├── css/            # CSS стили
│   ├── js/             # JavaScript файлы
│   └── index.php       # Точка входа
├── reports/             # Сгенерированные отчеты
├── src/                 # Исходный код приложения
│   ├── Controllers/    # Контроллеры
│   ├── Database/       # Классы работы с БД
│   ├── Models/         # Модели данных
│   ├── Services/       # Бизнес-логика
│   └── Utils/          # Вспомогательные функции
├── temp/                # Временные файлы
├── views/               # Шаблоны страниц
├── .env                 # Конфигурация окружения
├── .env.example         # Пример конфигурации
├── composer.json        # PHP зависимости
└── README.md           # Документация
```

## 🐛 Отладка

### Включение режима отладки

В `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Просмотр логов

```bash
# Последние ошибки
tail -f logs/$(date +%Y-%m-%d).log

# Все логи за сегодня
cat logs/$(date +%Y-%m-%d).log
```

## 🤝 Поддержка

Для вопросов и поддержки:
- Email: support@sgeo.kz
- GitHub Issues: https://github.com/maxicokz/sgeo-back/issues

## 📝 Лицензия

Copyright © 2024 SGEO Analytics Team
All rights reserved.

## 🎉 Авторы

Разработано командой SGEO Analytics для мониторинга представления Казахстана в AI-системах.
