# AI Responses Collector

Многомодельный сборщик ответов от различных AI-систем через OpenRouter с сохранением в Supabase.

## 🌟 Возможности

- ✅ Сбор ответов от множества AI моделей через единый API (OpenRouter)
- ✅ Поддержка ChatGPT, Bing, Copilot, Perplexity, Gemini
- ✅ Автоматическое определение языка запроса
- ✅ Сохранение данных в Supabase (PostgreSQL)
- ✅ Сбор метаданных (токены, время ответа, и т.д.)
- ✅ CLI интерфейс для удобной работы
- ✅ Поддержка параллельного опроса моделей
- ✅ Статистика и анализ собранных данных

## 📋 Требования

- Node.js >= 18.x
- npm или yarn
- Аккаунт OpenRouter с API ключом
- Проект Supabase с настроенной БД

## 🚀 Установка

1. **Перейдите в директорию проекта:**
```bash
cd nodejs-ai-collector
```

2. **Установите зависимости:**
```bash
npm install
```

3. **Настройте переменные окружения:**
```bash
cp .env.example .env
```

Отредактируйте `.env` и укажите:
- `OPENROUTER_API_KEY` - ваш API ключ от OpenRouter
- `SUPABASE_URL` - URL вашего проекта Supabase
- `SUPABASE_ANON_KEY` - анонимный ключ Supabase

4. **Настройте базу данных Supabase:**

Выполните SQL из файла `database/schema.sql` в вашем проекте Supabase:
- Откройте SQL Editor в панели Supabase
- Вставьте содержимое `database/schema.sql`
- Выполните запрос

## 🔧 Настройка OpenRouter

1. Зарегистрируйтесь на [OpenRouter](https://openrouter.ai/)
2. Пополните баланс аккаунта
3. Создайте API ключ в разделе Keys
4. Добавьте ключ в файл `.env`

## 🔧 Настройка Supabase

1. Создайте проект на [Supabase](https://supabase.com/)
2. Скопируйте URL проекта и anon key из Settings → API
3. Выполните SQL схему из `database/schema.sql`
4. Добавьте данные в файл `.env`

## 📖 Использование

### CLI Команды

**Список доступных моделей:**
```bash
npm run collect -- list
```

**Сбор ответа от одной модели:**
```bash
npm run collect -- single chatgpt "Что такое искусственный интеллект?"
```

**Сбор ответов от нескольких моделей:**
```bash
npm run collect -- multiple chatgpt,gemini,perplexity "Объясни квантовые вычисления"
```

**Сбор ответов от всех моделей:**
```bash
npm run collect -- all "Какая столица Казахстана?"
```

**Сравнение ответов моделей:**
```bash
npm run collect -- compare "Что такое машинное обучение?"
```

**Статистика собранных данных:**
```bash
npm run collect -- stats
```

**Справка:**
```bash
npm run collect -- help
```

### Программное использование

```javascript
import { aiCollector, supabaseService } from './src/index.js';

// Собрать ответ от одной модели
const result = await aiCollector.collectSingle('chatgpt', 'Ваш промпт');

// Собрать ответы от нескольких моделей
const results = await aiCollector.collectMultiple(
  ['chatgpt', 'gemini', 'perplexity'],
  'Ваш промпт'
);

// Собрать ответы от всех моделей
const allResults = await aiCollector.collectAll('Ваш промпт');

// Получить статистику
const stats = await supabaseService.getStatistics();
```

## 🗄️ Структура базы данных

Таблица `ai_responses`:

| Поле | Тип | Описание |
|------|-----|----------|
| id | UUID | Уникальный идентификатор |
| prompt | TEXT | Запрос (промпт) |
| model_name | VARCHAR(100) | Название модели |
| response | TEXT | Ответ модели |
| language | VARCHAR(10) | Язык запроса (ISO 639-1) |
| metadata | JSONB | Метаданные (токены, время и т.д.) |
| created_at | TIMESTAMP | Дата создания |
| updated_at | TIMESTAMP | Дата обновления |

## 🤖 Поддерживаемые модели

- **ChatGPT** - `openai/gpt-4-turbo-preview`
- **Bing** - `openai/gpt-4-turbo` (через OpenRouter)
- **Copilot** - `openai/gpt-4`
- **Perplexity** - `perplexity/llama-3.1-sonar-large-128k-online`
- **Gemini** - `google/gemini-pro-1.5`

Вы можете настроить модели в файле `.env`.

## 🌍 Определение языка

Система автоматически определяет язык запроса используя библиотеку `franc`. Поддерживается более 30 языков, включая:
- Русский (ru)
- Английский (en)
- Казахский (kk)
- И многие другие

## 📊 Метаданные

Для каждого ответа сохраняются следующие метаданные:
- ID запроса
- Использованные токены (prompt, completion, total)
- Время ответа (ms)
- Причина завершения (finish_reason)
- ID модели
- Временные метки запроса и ответа

## 🔍 Примеры запросов

**Анализ ответов на русском языке:**
```sql
SELECT model_name, COUNT(*)
FROM ai_responses
WHERE language = 'ru'
GROUP BY model_name;
```

**Средняя длина ответов по моделям:**
```sql
SELECT model_name, AVG(LENGTH(response)) as avg_length
FROM ai_responses
GROUP BY model_name;
```

**Ответы за последние 24 часа:**
```sql
SELECT *
FROM ai_responses
WHERE created_at > NOW() - INTERVAL '24 hours'
ORDER BY created_at DESC;
```

## 🛠️ Структура проекта

```
nodejs-ai-collector/
├── database/
│   └── schema.sql              # SQL схема для Supabase
├── src/
│   ├── config/
│   │   └── config.js           # Конфигурация
│   ├── services/
│   │   ├── aiCollector.js      # Основной коллектор
│   │   ├── openrouterService.js # Интеграция с OpenRouter
│   │   └── supabaseService.js  # Интеграция с Supabase
│   ├── utils/
│   │   └── languageDetector.js # Определение языка
│   ├── collect.js              # CLI скрипт
│   └── index.js                # Точка входа
├── .env.example                # Пример конфигурации
├── .gitignore
├── package.json
└── README.md
```

## 🔐 Безопасность

- Никогда не коммитьте файл `.env` в Git
- Храните API ключи в безопасности
- Используйте Row Level Security (RLS) в Supabase для защиты данных
- Регулярно ротируйте API ключи

## 🐛 Отладка

Для вывода дополнительной информации установите переменную окружения:
```bash
LOG_LEVEL=debug npm run collect -- all "Ваш промпт"
```

## 📝 Лицензия

MIT

## 👥 Автор

Создано для проекта SGEO Analytics Dashboard

## 🤝 Поддержка

Если у вас возникли проблемы или вопросы:
1. Проверьте, что все переменные окружения настроены правильно
2. Убедитесь, что у вас достаточно средств на балансе OpenRouter
3. Проверьте подключение к Supabase
4. Просмотрите логи для получения подробной информации об ошибках

## 📈 Roadmap

- [ ] Поддержка дополнительных моделей
- [ ] Web-интерфейс для управления
- [ ] Экспорт данных в различных форматах
- [ ] Расширенная аналитика ответов
- [ ] Batch-обработка множества промптов
- [ ] API endpoints для интеграции
