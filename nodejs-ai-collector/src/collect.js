#!/usr/bin/env node

import { validateConfig } from './config/config.js';
import aiCollector from './services/aiCollector.js';
import supabaseService from './services/supabaseService.js';

// Validate configuration
try {
  validateConfig();
} catch (error) {
  console.error('❌ Configuration error:', error.message);
  console.error('Please check your .env file');
  process.exit(1);
}

// Parse command line arguments
const args = process.argv.slice(2);

function printUsage() {
  console.log(`
╔═══════════════════════════════════════════════════════════════════╗
║             AI Responses Collector - Usage Guide                  ║
╚═══════════════════════════════════════════════════════════════════╝

Usage:
  npm run collect -- [command] [options]

Commands:
  single <model> <prompt>     Collect response from a single model
  multiple <models> <prompt>  Collect responses from multiple models (comma-separated)
  all <prompt>                Collect responses from all available models
  compare <prompt>            Compare responses from all models
  stats                       Show collection statistics
  list                        List available models

Examples:
  npm run collect -- single chatgpt "What is AI?"
  npm run collect -- multiple chatgpt,gemini "Explain quantum computing"
  npm run collect -- all "What is the capital of Kazakhstan?"
  npm run collect -- compare "Describe machine learning"
  npm run collect -- stats
  npm run collect -- list

Available Models:
  ${aiCollector.getAvailableModels().join(', ')}
  `);
}

async function main() {
  const command = args[0];

  if (!command || command === 'help' || command === '--help' || command === '-h') {
    printUsage();
    return;
  }

  try {
    switch (command) {
      case 'list': {
        console.log('\n📋 Available Models:');
        const models = aiCollector.getAvailableModels();
        models.forEach((model, index) => {
          console.log(`  ${index + 1}. ${model}`);
        });
        console.log('');
        break;
      }

      case 'single': {
        const modelName = args[1];
        const prompt = args.slice(2).join(' ');

        if (!modelName || !prompt) {
          console.error('❌ Error: Model name and prompt are required');
          console.log('Usage: npm run collect -- single <model> <prompt>');
          process.exit(1);
        }

        const result = await aiCollector.collectSingle(modelName, prompt);
        console.log('\n📄 Result:');
        console.log(result.response);
        break;
      }

      case 'multiple': {
        const modelsStr = args[1];
        const prompt = args.slice(2).join(' ');

        if (!modelsStr || !prompt) {
          console.error('❌ Error: Models and prompt are required');
          console.log('Usage: npm run collect -- multiple <models> <prompt>');
          console.log('Example: npm run collect -- multiple chatgpt,gemini "Your prompt"');
          process.exit(1);
        }

        const models = modelsStr.split(',').map(m => m.trim());
        const results = await aiCollector.collectMultiple(models, prompt);

        console.log('\n📊 Results Summary:');
        console.log(`✅ Successful: ${results.successful.length}`);
        console.log(`❌ Failed: ${results.failed.length}`);
        break;
      }

      case 'all': {
        const prompt = args.slice(1).join(' ');

        if (!prompt) {
          console.error('❌ Error: Prompt is required');
          console.log('Usage: npm run collect -- all <prompt>');
          process.exit(1);
        }

        const results = await aiCollector.collectAll(prompt);
        console.log('\n📊 Results Summary:');
        console.log(`✅ Successful: ${results.successful.length}`);
        console.log(`❌ Failed: ${results.failed.length}`);
        break;
      }

      case 'compare': {
        const prompt = args.slice(1).join(' ');

        if (!prompt) {
          console.error('❌ Error: Prompt is required');
          console.log('Usage: npm run collect -- compare <prompt>');
          process.exit(1);
        }

        await aiCollector.compareModels(prompt);
        break;
      }

      case 'stats': {
        console.log('\n📊 Fetching statistics...');
        const stats = await supabaseService.getStatistics();

        console.log('\n╔═══════════════════════════════════════════════╗');
        console.log('║           Collection Statistics               ║');
        console.log('╚═══════════════════════════════════════════════╝');
        console.log(`\n📈 Total Responses: ${stats.total}`);

        console.log('\n🤖 By Model:');
        Object.entries(stats.byModel).forEach(([model, count]) => {
          console.log(`  ${model}: ${count}`);
        });

        console.log('\n🌐 By Language:');
        Object.entries(stats.byLanguage).forEach(([lang, count]) => {
          console.log(`  ${lang}: ${count}`);
        });
        console.log('');
        break;
      }

      default:
        console.error(`❌ Unknown command: ${command}`);
        printUsage();
        process.exit(1);
    }
  } catch (error) {
    console.error('\n❌ Error:', error.message);
    console.error(error.stack);
    process.exit(1);
  }
}

main();
