import dotenv from 'dotenv';

dotenv.config();

export const config = {
  openrouter: {
    apiKey: process.env.OPENROUTER_API_KEY,
    apiUrl: process.env.OPENROUTER_API_URL || 'https://openrouter.ai/api/v1',
  },
  supabase: {
    url: process.env.SUPABASE_URL,
    anonKey: process.env.SUPABASE_ANON_KEY,
  },
  models: {
    chatgpt: process.env.MODEL_CHATGPT || 'openai/gpt-5.1-chat',
    deepseek: process.env.MODEL_DEEPSEEK || 'deepseek/deepseek-chat',
    copilot: process.env.MODEL_COPILOT || 'openai/gpt-4',
    perplexity: process.env.MODEL_PERPLEXITY || 'perplexity/sonar-pro-search',
    gemini: process.env.MODEL_GEMINI || 'google/gemini-2.5-flash',
  },
  app: {
    name: process.env.APP_NAME || 'AI Responses Collector',
    logLevel: process.env.LOG_LEVEL || 'info',
  },
};

// Validate required configuration
export function validateConfig() {
  const required = [
    { key: 'OPENROUTER_API_KEY', value: config.openrouter.apiKey },
    { key: 'SUPABASE_URL', value: config.supabase.url },
    { key: 'SUPABASE_ANON_KEY', value: config.supabase.anonKey },
  ];

  const missing = required.filter(({ value }) => !value).map(({ key }) => key);

  if (missing.length > 0) {
    throw new Error(`Missing required environment variables: ${missing.join(', ')}`);
  }
}
