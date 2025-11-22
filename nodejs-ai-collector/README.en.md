# AI Responses Collector

Multi-model AI response collector using OpenRouter with Supabase storage.

## 🌟 Features

- ✅ Collect responses from multiple AI models via unified API (OpenRouter)
- ✅ Support for ChatGPT 5.1, DeepSeek, Copilot, Perplexity, Gemini
- ✅ Automatic language detection for prompts
- ✅ Data storage in Supabase (PostgreSQL)
- ✅ Metadata collection (tokens, response time, etc.)
- ✅ CLI interface for easy usage
- ✅ Parallel model querying support
- ✅ Statistics and analysis of collected data

## 📋 Requirements

- Node.js >= 18.x
- npm or yarn
- OpenRouter account with API key
- Supabase project with configured database

## 🚀 Installation

1. **Navigate to project directory:**
```bash
cd nodejs-ai-collector
```

2. **Install dependencies:**
```bash
npm install
```

3. **Configure environment variables:**
```bash
cp .env.example .env
```

Edit `.env` and set:
- `OPENROUTER_API_KEY` - your OpenRouter API key
- `SUPABASE_URL` - your Supabase project URL
- `SUPABASE_ANON_KEY` - Supabase anonymous key

4. **Set up Supabase database:**

Execute SQL from `database/schema.sql` in your Supabase project:
- Open SQL Editor in Supabase dashboard
- Paste contents of `database/schema.sql`
- Run the query

## 📖 Usage

### CLI Commands

**List available models:**
```bash
npm run collect -- list
```

**Collect response from a single model:**
```bash
npm run collect -- single chatgpt "What is artificial intelligence?"
```

**Collect responses from multiple models:**
```bash
npm run collect -- multiple chatgpt,gemini,perplexity "Explain quantum computing"
```

**Collect responses from all models:**
```bash
npm run collect -- all "What is the capital of Kazakhstan?"
```

**Compare model responses:**
```bash
npm run collect -- compare "What is machine learning?"
```

**Get collection statistics:**
```bash
npm run collect -- stats
```

**Show help:**
```bash
npm run collect -- help
```

### Programmatic Usage

```javascript
import { aiCollector, supabaseService } from './src/index.js';

// Collect response from a single model
const result = await aiCollector.collectSingle('chatgpt', 'Your prompt');

// Collect responses from multiple models
const results = await aiCollector.collectMultiple(
  ['chatgpt', 'gemini', 'perplexity'],
  'Your prompt'
);

// Collect responses from all models
const allResults = await aiCollector.collectAll('Your prompt');

// Get statistics
const stats = await supabaseService.getStatistics();
```

## 🗄️ Database Schema

Table `ai_responses`:

| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Unique identifier |
| prompt | TEXT | Request (prompt) |
| model_name | VARCHAR(100) | Model name |
| response | TEXT | Model response |
| language | VARCHAR(10) | Request language (ISO 639-1) |
| metadata | JSONB | Metadata (tokens, time, etc.) |
| created_at | TIMESTAMP | Creation date |
| updated_at | TIMESTAMP | Update date |

## 🤖 Supported Models

- **ChatGPT 5.1** - `openai/gpt-5.1-chat` (latest version)
- **DeepSeek V3** - `deepseek/deepseek-chat` (efficient and cost-effective)
- **Copilot** - `openai/gpt-4`
- **Perplexity** - `perplexity/sonar-pro-search`
- **Gemini** - `google/gemini-2.5-flash`

You can configure models in the `.env` file. See [MODELS.md](MODELS.md) for details.

## 🌍 Language Detection

The system automatically detects prompt language using the `franc` library. Supports over 30 languages, including:
- Russian (ru)
- English (en)
- Kazakh (kk)
- And many more

## 📊 Metadata

The following metadata is saved for each response:
- Request ID
- Tokens used (prompt, completion, total)
- Response time (ms)
- Finish reason
- Model ID
- Request and response timestamps

## 🔐 Security

- Never commit the `.env` file to Git
- Keep API keys secure
- Use Row Level Security (RLS) in Supabase to protect data
- Regularly rotate API keys

## 📝 License

MIT

## 👥 Author

Created for SGEO Analytics Dashboard project
