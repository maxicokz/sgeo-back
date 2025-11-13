# Руководство по установке

Пошаговое руководство по настройке AI Responses Collector.

## 1. Предварительные требования

### Node.js
Убедитесь, что у вас установлен Node.js версии 18 или выше:
```bash
node --version
```

Если Node.js не установлен, скачайте его с [nodejs.org](https://nodejs.org/)

### Git
Убедитесь, что у вас установлен Git:
```bash
git --version
```

## 2. Клонирование и настройка проекта

1. **Перейдите в директорию с Node.js коллектором:**
```bash
cd nodejs-ai-collector
```

2. **Установите зависимости:**
```bash
npm install
```

## 3. Настройка OpenRouter

### Шаг 1: Регистрация
1. Перейдите на [openrouter.ai](https://openrouter.ai/)
2. Создайте аккаунт или войдите
3. Пополните баланс (минимум $5 рекомендуется)

### Шаг 2: Получение API ключа
1. Перейдите в раздел **Keys** в вашем профиле
2. Нажмите **Create Key**
3. Скопируйте ключ (он будет показан только один раз!)

### Шаг 3: Проверка доступных моделей
Посетите [openrouter.ai/models](https://openrouter.ai/models) для просмотра доступных моделей и их цен.

## 4. Настройка Supabase

### Шаг 1: Создание проекта
1. Перейдите на [supabase.com](https://supabase.com/)
2. Создайте аккаунт или войдите
3. Нажмите **New Project**
4. Заполните форму:
   - Имя проекта: `ai-responses-collector`
   - Database Password: (создайте надежный пароль)
   - Region: выберите ближайший регион
5. Нажмите **Create new project**

### Шаг 2: Получение учетных данных
1. Перейдите в **Settings** → **API**
2. Найдите и скопируйте:
   - **Project URL** (например: https://xxxxx.supabase.co)
   - **anon public** key

### Шаг 3: Создание таблицы
1. Перейдите в **SQL Editor**
2. Нажмите **New query**
3. Откройте файл `database/schema.sql` из проекта
4. Скопируйте весь SQL код
5. Вставьте в редактор запросов Supabase
6. Нажмите **Run** (или Ctrl/Cmd + Enter)

Вы должны увидеть сообщение об успешном выполнении.

### Шаг 4: Проверка таблицы
1. Перейдите в **Table Editor**
2. Вы должны увидеть таблицу `ai_responses` с колонками:
   - id
   - prompt
   - model_name
   - response
   - language
   - metadata
   - created_at
   - updated_at

## 5. Настройка переменных окружения

1. **Создайте .env файл:**
```bash
npm run setup
```

Или вручную:
```bash
cp .env.example .env
```

2. **Откройте .env в текстовом редакторе:**
```bash
nano .env
# или
code .env
# или используйте любой другой редактор
```

3. **Заполните значения:**
```env
# OpenRouter Configuration
OPENROUTER_API_KEY=sk-or-v1-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
OPENROUTER_API_URL=https://openrouter.ai/api/v1

# Supabase Configuration
SUPABASE_URL=https://xxxxxxxxx.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.xxxxxxxx...

# AI Models Configuration (оставьте по умолчанию или настройте)
MODEL_CHATGPT=openai/gpt-4-turbo-preview
MODEL_BING=openai/gpt-4-turbo
MODEL_COPILOT=openai/gpt-4
MODEL_PERPLEXITY=perplexity/llama-3.1-sonar-large-128k-online
MODEL_GEMINI=google/gemini-pro-1.5
```

4. **Сохраните файл**

## 6. Проверка установки

### Тест 1: Проверка конфигурации
```bash
npm start
```

Вы должны увидеть приветственное сообщение без ошибок.

### Тест 2: Список моделей
```bash
npm run collect -- list
```

Должен показать список доступных моделей.

### Тест 3: Простой запрос
```bash
npm run collect -- single chatgpt "Hello, world!"
```

Если все настроено правильно, вы получите ответ от ChatGPT.

### Тест 4: Проверка базы данных
```bash
npm run collect -- stats
```

Должен показать статистику (включая только что добавленный ответ).

## 7. Решение проблем

### Ошибка: "Missing required environment variables"
- Убедитесь, что файл `.env` создан
- Проверьте, что все переменные заполнены
- Убедитесь, что нет пробелов вокруг знака `=`

### Ошибка: "OpenRouter API error"
- Проверьте правильность API ключа
- Убедитесь, что на балансе достаточно средств
- Проверьте подключение к интернету

### Ошибка: "Supabase connection error"
- Проверьте правильность URL и ключа Supabase
- Убедитесь, что проект Supabase активен
- Проверьте, что таблица `ai_responses` создана

### Ошибка: "relation "ai_responses" does not exist"
- Выполните SQL из файла `database/schema.sql` в Supabase
- Убедитесь, что запрос выполнился без ошибок

### Ошибка: "permission denied for table ai_responses"
- Проверьте настройки Row Level Security в Supabase
- Временно можно отключить RLS для тестирования (не рекомендуется в продакшене)

## 8. Следующие шаги

После успешной установки:

1. **Изучите примеры:**
```bash
npm run example 1
npm run example 2
npm run example 3
```

2. **Попробуйте batch-сбор:**
```bash
npm run batch general chatgpt,gemini
```

3. **Прочитайте README.md** для изучения всех возможностей

4. **Настройте модели** в `.env` согласно вашим потребностям

## 9. Безопасность

⚠️ **ВАЖНО:**

1. **Никогда** не коммитьте файл `.env` в Git
2. **Не делитесь** API ключами
3. Используйте **разные ключи** для разработки и продакшена
4. Регулярно **ротируйте** API ключи
5. Настройте **Row Level Security** в Supabase для защиты данных

## 10. Обновление

Для обновления зависимостей:
```bash
npm update
```

Для проверки устаревших пакетов:
```bash
npm outdated
```

## Поддержка

Если возникли проблемы:
1. Проверьте логи на наличие детальных ошибок
2. Убедитесь, что все шаги выполнены правильно
3. Проверьте баланс OpenRouter
4. Проверьте статус Supabase проекта
