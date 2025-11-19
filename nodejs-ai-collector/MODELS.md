# Доступные AI модели

Руководство по настройке моделей для AI Responses Collector.

## 🔧 Настройка моделей

Модели настраиваются в файле `.env` через переменные окружения.

## ✅ Рекомендуемые модели (протестированы)

### OpenAI (ChatGPT, Bing, Copilot)

```env
MODEL_CHATGPT=openai/gpt-4-turbo
MODEL_BING=openai/gpt-4-turbo
MODEL_COPILOT=openai/gpt-4
```

**Доступные варианты:**
- `openai/gpt-4-turbo` - GPT-4 Turbo (рекомендуется)
- `openai/gpt-4` - GPT-4 базовый
- `openai/gpt-3.5-turbo` - GPT-3.5 (дешевле)
- `openai/gpt-4o` - GPT-4o (если доступен)
- `openai/gpt-4o-mini` - GPT-4o mini (быстрее и дешевле)

### Perplexity

```env
MODEL_PERPLEXITY=perplexity/sonar-pro-search
```

**Доступные варианты:**
- `perplexity/sonar-pro-search` - Sonar Pro с поиском (рекомендуется)
- `perplexity/sonar` - Базовый Sonar
- `perplexity/sonar-pro` - Sonar Pro без поиска

**Примечание:** Некоторые старые модели Perplexity (например, `llama-3.1-sonar-*`) больше не доступны на OpenRouter.

### Google Gemini

```env
MODEL_GEMINI=google/gemini-2.5-flash
```

**Доступные варианты:**
- `google/gemini-2.5-flash` - Gemini 2.5 Flash (рекомендуется - быстро и дешево)
- `google/gemini-2.5-pro` - Gemini 2.5 Pro (более мощная версия)
- `google/gemini-3-pro-preview` - Gemini 3 Pro Preview (новейшая версия, может быть дороже)

**Примечание:** Старые модели (`google/gemini-pro`, `google/gemini-pro-1.5`) больше не доступны. Используйте Gemini 2.5.

## 🔍 Как проверить доступные модели

### Метод 1: Веб-сайт OpenRouter

1. Перейдите на https://openrouter.ai/models
2. Найдите нужную модель
3. Скопируйте её ID (например, `openai/gpt-4-turbo`)

### Метод 2: API запрос

```bash
curl https://openrouter.ai/api/v1/models \
  -H "Authorization: Bearer YOUR_API_KEY" | jq '.data[] | .id'
```

### Метод 3: Через наш скрипт

```bash
cd nodejs-ai-collector
npm install  # если ещё не установлено
node check-models.js
```

## 💰 Цены моделей

Приблизительные цены на OpenRouter (актуальны на начало 2025):

| Модель | Цена за 1M токенов (input/output) |
|--------|-----------------------------------|
| GPT-4 Turbo | $10 / $30 |
| GPT-3.5 Turbo | $0.50 / $1.50 |
| GPT-4o mini | $0.15 / $0.60 |
| Gemini Pro | $0.50 / $1.50 |
| Perplexity Sonar | $1 / $1 |

**Примечание:** Цены могут меняться. Проверяйте актуальные на https://openrouter.ai/models

## ⚠️ Устранение ошибок

### Ошибка: "No endpoints found for..."

**Причина:** Модель не существует или недоступна.

**Решение:**
1. Проверьте название модели на https://openrouter.ai/models
2. Обновите `.env` файл с правильным ID модели
3. Перезапустите сервер

**Пример:**
```
❌ Неправильно: MODEL_GEMINI=google/gemini-pro-1.5
✅ Правильно:  MODEL_GEMINI=google/gemini-pro
```

### Ошибка: "Insufficient funds"

**Причина:** Недостаточно средств на балансе OpenRouter.

**Решение:**
1. Перейдите на https://openrouter.ai/credits
2. Пополните баланс
3. Попробуйте снова

## 📝 Примеры конфигураций

### Конфигурация по умолчанию (баланс цена/качество)

```env
MODEL_CHATGPT=openai/gpt-4-turbo
MODEL_BING=openai/gpt-4-turbo
MODEL_COPILOT=openai/gpt-4
MODEL_PERPLEXITY=perplexity/sonar-pro-search
MODEL_GEMINI=google/gemini-2.5-flash
```

### Бюджетная конфигурация

```env
MODEL_CHATGPT=openai/gpt-3.5-turbo
MODEL_BING=openai/gpt-3.5-turbo
MODEL_COPILOT=openai/gpt-3.5-turbo
MODEL_PERPLEXITY=perplexity/sonar
MODEL_GEMINI=google/gemini-2.5-flash
```

### Премиум конфигурация

```env
MODEL_CHATGPT=openai/gpt-4-turbo
MODEL_BING=openai/gpt-4-turbo
MODEL_COPILOT=openai/gpt-4
MODEL_PERPLEXITY=perplexity/sonar-pro-search
MODEL_GEMINI=google/gemini-2.5-pro
```

## 🔄 Обновление моделей

Когда вы изменяете `.env`:

1. **Остановите сервер** (Ctrl+C)
2. **Измените .env файл**
3. **Перезапустите сервер**:
   ```bash
   npm run web
   ```

Сервер не подхватывает изменения `.env` автоматически!

## 📊 Добавление новых моделей

Вы можете добавить любую модель из OpenRouter:

1. Найдите модель на https://openrouter.ai/models
2. Добавьте в `.env`:
   ```env
   MODEL_CUSTOM=provider/model-name
   ```
3. Добавьте в `src/config/config.js`:
   ```javascript
   export const config = {
     models: {
       chatgpt: process.env.MODEL_CHATGPT,
       custom: process.env.MODEL_CUSTOM, // добавить здесь
     }
   }
   ```
4. Перезапустите сервер

## 🌐 Актуальный список моделей

Всегда проверяйте актуальный список на:
- https://openrouter.ai/models
- https://openrouter.ai/docs/models

OpenRouter регулярно добавляет новые модели и удаляет устаревшие.

## 💡 Советы

1. **Тестируйте одну модель сначала** - не запускайте сразу все, чтобы не тратить деньги
2. **Следите за балансом** - некоторые модели дорогие
3. **Используйте кеширование** - OpenRouter поддерживает кеширование промптов
4. **Проверяйте лимиты** - у некоторых моделей есть лимиты на количество запросов

## 🆘 Поддержка

Если модель не работает:
1. Проверьте, что она есть на https://openrouter.ai/models
2. Проверьте баланс OpenRouter
3. Проверьте синтаксис ID модели
4. Перезапустите сервер после изменения `.env`
5. Проверьте логи сервера для детальной информации
