# Быстрая установка на Shared Hosting (Plesk/cPanel)

## ⚠️ Важно: Решение проблемы с vendor/

Если вы видите ошибку:
```
Failed to open stream: No such file or directory in .../vendor/autoload.php
```

Это означает что отсутствуют зависимости Composer. Следуйте инструкциям ниже:

---

## 🚀 Вариант 1: Загрузка готовой сборки (РЕКОМЕНДУЕТСЯ)

### Шаг 1: Скачайте полную версию

**Option A:** Используйте готовую сборку с vendor/

Скачайте архив со всеми зависимостями:
- Если есть GitHub Release: скачайте `sgeo-analytics-full.zip`
- Или создайте сборку локально (см. ниже)

### Шаг 2: Создание полной сборки локально

На вашем компьютере (Windows/Mac/Linux):

```bash
# 1. Установите Composer если его нет
# Windows: скачайте с https://getcomposer.org
# Mac: brew install composer
# Linux: apt-get install composer

# 2. Клонируйте или скачайте проект
git clone https://github.com/maxicokz/sgeo-back.git
cd sgeo-back

# 3. Установите зависимости
composer install --no-dev --optimize-autoloader

# 4. Проверьте что появилась папка vendor/
ls -la vendor/

# 5. Создайте архив всего проекта
# Windows: используйте WinRAR/7-Zip
# Mac/Linux:
zip -r sgeo-analytics-full.zip . -x "*.git*"
```

### Шаг 3: Загрузите через FTP

1. Подключитесь к хостингу через FTP (FileZilla/WinSCP)
2. Перейдите в `httpdocs/` или `public_html/`
3. Загрузите **ВСЕ файлы**, включая:
   ```
   ✅ vendor/          ← ОБЯЗАТЕЛЬНО!
   ✅ src/
   ✅ public/
   ✅ config/
   ✅ database/
   ✅ views/
   ✅ composer.json
   ✅ все остальные файлы
   ```

4. Убедитесь что папка `vendor/` загружена полностью (это может занять время)

---

## 🔧 Вариант 2: Через Plesk File Manager

### Шаг 1: Загрузка архива

1. Войдите в **Plesk Panel**
2. Откройте **Files** → **File Manager**
3. Перейдите в `httpdocs/`
4. Нажмите **Upload Files**
5. Загрузите ZIP архив `sgeo-analytics-full.zip`

### Шаг 2: Распаковка

1. Найдите загруженный ZIP файл
2. Нажмите правой кнопкой → **Extract Files**
3. Подтвердите распаковку
4. Дождитесь завершения (может занять несколько минут)
5. Удалите ZIP файл

### Шаг 3: Проверка структуры

Убедитесь что структура выглядит так:
```
httpdocs/
├── public/
│   ├── index.php
│   ├── install.php
│   └── setup-dependencies.php
├── vendor/              ← Должна быть!
│   ├── autoload.php
│   └── ... (много файлов)
├── src/
├── config/
└── ...
```

---

## 📋 Настройка Plesk

### 1. Измените Document Root

1. Откройте **Hosting Settings**
2. Найдите **Document root**
3. Измените на:
   ```
   /httpdocs/public
   ```
   (добавьте `/public` в конец)
4. Нажмите **OK**

### 2. Проверьте версию PHP

1. Откройте **PHP Settings**
2. Убедитесь что выбран **PHP 8.0** или выше
3. Сохраните изменения

### 3. Создайте базу данных PostgreSQL

1. Откройте **Databases** → **PostgreSQL**
2. Нажмите **Add Database**
3. Заполните:
   - Database name: `sgeo_analytics`
   - User name: создайте нового пользователя
   - Password: придумайте надёжный пароль
4. Запишите данные:
   ```
   Host: localhost
   Port: 5432
   Database: sgeo_analytics
   Username: [ваш username]
   Password: [ваш password]
   ```

---

## 🌐 Установка через браузер

### Шаг 1: Проверьте зависимости

Откройте в браузере:
```
https://back.maxico.kz/
```

Если увидите страницу **"Missing Dependencies"**, значит папка vendor/ не загружена. Вернитесь к шагу загрузки файлов.

### Шаг 2: Запустите установщик

Если зависимости на месте, откроется:
```
https://back.maxico.kz/install.php
```

Следуйте инструкциям:

**Шаг 1:** Welcome - нажмите "Start Installation"

**Шаг 2:** Configuration
```
Database Host: localhost
Database Port: 5432
Database Name: sgeo_analytics
Database User: [ваш username]
Database Password: [ваш password]
OpenRouter API Key: sk-or-v1-... (получите на openrouter.ai)
Application URL: https://back.maxico.kz
```

**Шаг 3:** Database Setup - нажмите "Import Database Schema"

**Шаг 4:** Finalize - нажмите "Complete Installation"

**Шаг 5:** Complete! Войдите:
- Username: `admin`
- Password: `admin123`

---

## ✅ Проверка установки

### 1. Проверьте что vendor/ существует

В Plesk File Manager:
```
httpdocs/vendor/autoload.php  ← должен существовать
httpdocs/vendor/composer/     ← должна быть папка
```

### 2. Проверьте права доступа

Установите права на директории:
```
logs/    → 755
reports/ → 755
temp/    → 755
```

В File Manager: выделите папку → Change Permissions → установите 755

### 3. Проверьте .htaccess

Файлы `.htaccess` должны быть:
- `/httpdocs/.htaccess`
- `/httpdocs/public/.htaccess`

---

## 🆘 Если всё ещё не работает

### Проблема: vendor/autoload.php not found

**Решение:**
1. Убедитесь что папка `vendor/` действительно загружена
2. Проверьте размер папки - должна быть 10-20 MB
3. Убедитесь что внутри есть файл `vendor/autoload.php`
4. Попробуйте загрузить повторно

### Проблема: Ошибка 500

**Решение:**
1. Проверьте версию PHP (должна быть 8.0+)
2. Проверьте логи в Plesk → Logs → Error Log
3. Включите отладку:
   - Создайте файл `.env`
   - Добавьте строку: `APP_DEBUG=true`
4. Обновите страницу - увидите детальную ошибку

### Проблема: База данных не подключается

**Решение:**
1. Проверьте что PostgreSQL включен в Plesk
2. Проверьте данные подключения в `.env`
3. Попробуйте подключиться через phpPgAdmin

---

## 📞 Получение помощи

1. **Логи приложения:** `httpdocs/logs/[дата].log`
2. **Логи Plesk:** Plesk → Logs → Error Log
3. **Email поддержки:** support@sgeo.kz
4. **GitHub Issues:** https://github.com/maxicokz/sgeo-back/issues

---

## 📦 Контрольный список

- [ ] Папка `vendor/` загружена и содержит файлы
- [ ] Document Root установлен на `/httpdocs/public`
- [ ] PHP версия 8.0 или выше
- [ ] PostgreSQL база данных создана
- [ ] OpenRouter API ключ получен
- [ ] Установщик успешно завершён
- [ ] Вход в систему работает
- [ ] Пароль изменён на безопасный

---

## 🎉 Готово!

После успешной установки:
- Dashboard: `https://back.maxico.kz/`
- API Health: `https://back.maxico.kz/api/health`
- Логи: `httpdocs/logs/`

**Не забудьте удалить `install.php` и `setup-dependencies.php` после установки!**
