#!/usr/bin/env node

/**
 * Batch collection example - process multiple prompts from a file
 */

import { readFileSync } from 'fs';
import { aiCollector } from '../src/index.js';

async function batchCollect() {
  // Load sample prompts
  const prompts = JSON.parse(
    readFileSync('./examples/sample-prompts.json', 'utf-8')
  );

  const args = process.argv.slice(2);
  const category = args[0] || 'general';
  const models = args[1] ? args[1].split(',') : ['chatgpt', 'gemini'];

  console.log(`\n🚀 Batch Collection Started`);
  console.log(`Category: ${category}`);
  console.log(`Models: ${models.join(', ')}`);
  console.log(`Prompts: ${prompts[category]?.length || 0}\n`);

  if (!prompts[category]) {
    console.error(`❌ Category "${category}" not found`);
    console.log('Available categories:', Object.keys(prompts).join(', '));
    return;
  }

  let successCount = 0;
  let failCount = 0;

  for (const [index, prompt] of prompts[category].entries()) {
    console.log(`\n[${index + 1}/${prompts[category].length}] Processing: "${prompt}"`);
    console.log('─'.repeat(80));

    try {
      const results = await aiCollector.collectMultiple(models, prompt);
      successCount += results.successful.length;
      failCount += results.failed.length;

      console.log(`✅ Success: ${results.successful.length}, ❌ Failed: ${results.failed.length}`);

      // Add delay between requests to avoid rate limiting
      if (index < prompts[category].length - 1) {
        console.log('⏳ Waiting 2 seconds before next request...');
        await new Promise((resolve) => setTimeout(resolve, 2000));
      }
    } catch (error) {
      console.error(`❌ Error processing prompt: ${error.message}`);
      failCount += models.length;
    }
  }

  console.log('\n' + '='.repeat(80));
  console.log('📊 Batch Collection Summary');
  console.log('='.repeat(80));
  console.log(`Total prompts processed: ${prompts[category].length}`);
  console.log(`Total successful responses: ${successCount}`);
  console.log(`Total failed responses: ${failCount}`);
  console.log(`Success rate: ${((successCount / (successCount + failCount)) * 100).toFixed(2)}%`);
}

// Run batch collection
batchCollect().catch(console.error);
