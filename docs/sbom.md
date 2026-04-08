# SBOM — Software Bill of Materials

> **Проект:** SGEO Analytics Backend (`maxicokz/sgeo-analytics`)  
> **Дата составления:** 2026-04-08  
> **Стандарт:** неформальный SBOM (аналог CycloneDX / SPDX в упрощённом виде)

---

## Среда выполнения

| Компонент | Версия | Назначение | CVE-статус |
|-----------|--------|------------|------------|
| **PHP** | ≥ 8.0 (рекомендуется 8.2+) | Язык выполнения | Нет известных критических CVE для 8.2.x на дату составления. PHP 8.0 — EOL с ноября 2023 — **рекомендуется обновление до 8.2+**. |

---

## Зависимости Composer (runtime)

| Пакет | Требуемая версия | Актуальная стабильная | Назначение | Лицензия | CVE-статус |
|-------|------------------|-----------------------|------------|----------|------------|
| **vlucas/phpdotenv** | `^5.5` | 5.6.x | Загрузка переменных окружения из `.env` файла | MIT | Нет известных критических CVE |
| **guzzlehttp/guzzle** | `^7.0` | 7.9.x | HTTP-клиент для запросов к OpenRouter API | MIT | Нет известных критических CVE для 7.9.x |
| **dompdf/dompdf** | `^2.0` | 2.0.x | Генерация PDF-отчётов из HTML/CSS | LGPL-2.1 | Нет известных критических CVE для 2.0.x. Исторически CVE-2022-28368 касался v1.x (Remote Code Execution через PHP в HTML) — устранён включением `DOMPDF_ENABLE_PHP=false` по умолчанию в v2. |

---

## PHP-расширения (встроенные)

| Расширение | Назначение |
|------------|------------|
| `ext-pdo` | Работа с базой данных (PDO + pgsql) |
| `ext-json` | JSON encode/decode (audit context, MFA backup codes) |
| `ext-curl` | HTTP-запросы (используется Guzzle) |
| `ext-hash` | HMAC-SHA1 для TOTP (MfaService) |
| `ext-mbstring` | Мультибайтные строки (SecretsManager::mask) |

---

## Зависимости разработки (dev)

Не определены в текущем `composer.json`. Рекомендуется добавить:
- `phpunit/phpunit` — тестирование
- `phpstan/phpstan` — статический анализ
- `squizlabs/php_codesniffer` — проверка стиля кода

---

## Политика обновлений

- **Критические CVE:** устранять в течение **48 часов** после публикации.
- **Высокие CVE:** устранять в течение **7 дней**.
- **Плановые обновления:** ежемесячно (`composer update`), с тестированием в staging.
- Мониторинг: [https://github.com/advisories](https://github.com/advisories), Packagist security advisories.

---

## Команды аудита

```bash
# Проверить уязвимости в зависимостях
composer audit

# Обновить зависимости
composer update --with-all-dependencies

# Показать установленные версии
composer show
```
