import dotenv from 'dotenv';

dotenv.config();

async function checkModels() {
  try {
    const response = await fetch('https://openrouter.ai/api/v1/models', {
      headers: {
        'Authorization': `Bearer ${process.env.OPENROUTER_API_KEY}`
      }
    });

    const data = await response.json();
    const models = data.data;

    console.log('\n🔍 Searching for Gemini models:');
    const gemini = models.filter(m => m.id.toLowerCase().includes('gemini')).slice(0, 5);
    gemini.forEach(m => console.log(`  - ${m.id}`));

    console.log('\n🔍 Searching for Perplexity models:');
    const perplexity = models.filter(m => m.id.toLowerCase().includes('perplexity')).slice(0, 5);
    perplexity.forEach(m => console.log(`  - ${m.id}`));

    console.log('\n✅ Available ChatGPT/OpenAI models:');
    const openai = models.filter(m => m.id.includes('openai/gpt') && !m.id.includes('oss')).slice(0, 10);
    openai.forEach(m => console.log(`  - ${m.id}`));

  } catch (error) {
    console.error('Error:', error.message);
  }
}

checkModels();
