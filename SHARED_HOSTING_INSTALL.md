# Инструкция по установке на Shared Hosting (без SSH)

## 📋 Требования

- PHP 8.0 или выше
- PostgreSQL 12+ (или Supabase)
- FTP/File Manager доступ
- Панель управления хостингом (cPanel/Plesk/другая)

---

## 🚀 Пошаговая установка

### Шаг 1: Подготовка файлов

#### Вариант А: Скачать готовую сборку с vendor/

1. Скачайте полный архив с зависимостями:
   ```
   https://github.com/maxicokz/sgeo-back/releases/latest/download/sgeo-analytics-full.zip
   ```

2. Распакуйте локально на компьютере

#### Вариант Б: Установить зависимости локально

Если у вас есть Composer на локальном компьютере:

```bash
# На вашем компьютере
composer install --no-dev --optimize-autoloader
```

Затем запакуйте всю папку в ZIP.

---

### Шаг 2: Создание базы данных

#### Через cPanel:

1. Войдите в **cPanel**
2. Откройте **PostgreSQL Databases**
3. Создайте новую базу данных:
   - Database Name: `sgeo_analytics`
4. Создайте пользователя:
   - Username: выберите имя
   - Password: создайте надёжный пароль
5. Добавьте пользователя к базе данных с правами **ALL PRIVILEGES**
6. Запомните данные:
   - Host (обычно `localhost`)
   - Database name
   - Username
   - Password

#### Через Supabase (альтернатива):

1. Зарегистрируйтесь на [supabase.com](https://supabase.com)
2. Создайте новый проект
3. Перейдите в **Project Settings** → **Database**
4. Скопируйте Connection String
5. Запомните данные для подключения

---

### Шаг 3: Загрузка файлов

#### Через FTP (FileZilla/WinSCP):

1. Подключитесь к вашему хостингу по FTP
2. Перейдите в корневую директорию сайта:
   - `public_html/` или
   - `www/` или
   - `htdocs/`
3. Загрузите **все файлы** из архива
4. Убедитесь, что структура такая:
   ```
   public_html/
   ├── config/
   ├── database/
   ├── public/          ← это DocumentRoot
   ├── src/
   ├── vendor/          ← обязательно!
   ├── views/
   └── ...
   ```

#### Через File Manager в cPanel:

1. Войдите в **File Manager**
2. Перейдите в `public_html/`
3. Нажмите **Upload** и загрузите ZIP файл
4. После загрузки нажмите **Extract**
5. Удалите ZIP файл

---

### Шаг 4: Настройка DocumentRoot

#### В cPanel:

1. Откройте **Domains** → **Domains**
2. Найдите ваш домен
3. Нажмите **Manage**
4. Измените **Document Root** на:
   ```
   /home/username/public_html/public
   ```
   (добавьте `/public` в конец пути)
5. Сохраните изменения

#### В Plesk:

1. Откройте **Hosting Settings**
2. Найдите **Document Root**
3. Измените на путь с `/public` в конце
4. Примените изменения

---

### Шаг 5: Web-установщик

1. Откройте в браузере:
   ```
   https://your-domain.com/install.php
   ```

2. Следуйте шагам мастера установки:

   **Шаг 1: Welcome**
   - Нажмите "Start Installation"

   **Шаг 2: Configuration**
   - Введите данные базы данных PostgreSQL
   - Введите OpenRouter API key
   - Укажите URL вашего сайта
   - Нажмите "Test & Save Configuration"

   **Шаг 3: Database Setup**
   - Нажмите "Import Database Schema"
   - Подождите завершения импорта

   **Шаг 4: Finalize**
   - Нажмите "Complete Installation"

   **Шаг 5: Complete**
   - Готово! Нажмите "Go to Dashboard"

3. Войдите в систему:
   - **Username**: `admin`
   - **Password**: `admin123`

4. ⚠️ **ВАЖНО**: Сразу смените пароль!

---

### Шаг 6: Удаление установщика

После успешной установки:

1. Через FTP или File Manager удалите файл:
   ```
   public/install.php
   ```

2. Это важно для безопасности!

---

## 🔧 Настройка Cron задач (автоматизация)

### Через cPanel:

1. Откройте **Cron Jobs** в cPanel
2. Добавьте следующие задачи:

**Ежедневный сбор данных (2:00 AM):**
```
0 2 * * * /usr/bin/php /home/username/public_html/cli/collect.php
```

**Еженедельный отчёт (понедельник, 8:00 AM):**
```
0 8 * * 1 /usr/bin/php /home/username/public_html/cli/generate-report.php weekly
```

**Примечание**: Замените `/home/username/public_html/` на ваш реальный путь.

### Как узнать путь к PHP:

В cPanel создайте временный PHP файл `test.php`:
```php
<?php echo PHP_BINARY; ?>
```

Откройте в браузере, скопируйте путь, удалите файл.

---

## 📊 Получение OpenRouter API ключа

1. Перейдите на [openrouter.ai](https://openrouter.ai)
2. Зарегистрируйтесь или войдите
3. Перейдите в **Settings** → **API Keys**
4. Нажмите **Create API Key**
5. Скопируйте ключ (начинается с `sk-or-v1-...`)
6. Добавьте баланс для использования моделей
7. Используйте ключ при установке

---

## 🔍 Проверка установки

### 1. Проверьте структуру файлов:

```
✓ public/index.php существует
✓ vendor/ директория существует
✓ .env файл создан
✓ logs/ директория существует
```

### 2. Проверьте права доступа:

Убедитесь что эти директории доступны для записи:
- `logs/`
- `reports/`
- `temp/`
- `cache/`

В File Manager: выделите папку → Change Permissions → установите **755**

### 3. Проверьте подключение к БД:

Создайте временный файл `test-db.php` в `public/`:
```php
<?php
require '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
try {
    $pdo = new PDO(
        'pgsql:host='.$_ENV['DB_HOST'].';dbname='.$_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD']
    );
    echo "✅ Database connection OK!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
```

Откройте в браузере, проверьте результат, удалите файл.

---

## ❗ Решение проблем

### Ошибка 500 Internal Server Error

1. Проверьте `.htaccess` файлы (должны быть в корне и в `public/`)
2. Проверьте права доступа на директории (755 для папок, 644 для файлов)
3. Проверьте логи ошибок в cPanel → Error Log

### Ошибка "vendor/autoload.php not found"

1. Убедитесь что папка `vendor/` загружена полностью
2. Если нет - установите Composer локально и загрузите vendor/
3. Или скачайте готовую сборку с vendor/

### Ошибка подключения к базе данных

1. Проверьте данные в `.env` файле
2. Убедитесь что PostgreSQL доступен
3. Проверьте что пользователь БД имеет все права
4. Попробуйте подключиться через phpPgAdmin

### Blank white page (пустая страница)

1. Включите отображение ошибок - в `.env`:
   ```
   APP_DEBUG=true
   ```
2. Проверьте PHP версию (должна быть 8.0+)
3. Проверьте логи в `logs/` директории

### Нет доступа к /install.php

1. Проверьте что DocumentRoot указывает на `/public`
2. Полный путь должен быть: `https://your-domain.com/install.php`
3. Проверьте что файл загружен в `public/install.php`

---

## 📞 Поддержка

Если у вас возникли проблемы:

1. Проверьте логи в `logs/` директории
2. Проверьте Error Log в cPanel
3. Создайте issue на GitHub
4. Email: support@sgeo.kz

---

## ✅ Контрольный список

- [ ] Создана база данных PostgreSQL
- [ ] Все файлы загружены через FTP
- [ ] Папка vendor/ загружена
- [ ] DocumentRoot настроен на /public
- [ ] Web-installer пройден успешно
- [ ] Вход в систему работает (admin/admin123)
- [ ] Пароль администратора изменён
- [ ] install.php удалён
- [ ] Cron задачи настроены
- [ ] OpenRouter API ключ работает

---

🎉 **Готово! Приложение работает на вашем хостинге!**
