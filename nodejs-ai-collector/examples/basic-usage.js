#!/usr/bin/env node

/**
 * Basic usage examples for AI Responses Collector
 */

import { aiCollector, supabaseService } from '../src/index.js';

// Example 1: Collect response from a single model
async function example1() {
  console.log('\n=== Example 1: Single Model ===\n');

  try {
    const result = await aiCollector.collectSingle('chatgpt', 'What is AI?');
    console.log('Response:', result.response);
    console.log('Language:', result.language);
    console.log('Tokens used:', result.metadata.usage);
  } catch (error) {
    console.error('Error:', error.message);
  }
}

// Example 2: Collect responses from multiple models
async function example2() {
  console.log('\n=== Example 2: Multiple Models ===\n');

  try {
    const results = await aiCollector.collectMultiple(
      ['chatgpt', 'gemini'],
      'Explain machine learning in simple terms'
    );

    console.log(`Successful: ${results.successful.length}`);
    console.log(`Failed: ${results.failed.length}`);

    results.successful.forEach((result) => {
      console.log(`\n${result.modelName}:`, result.response.substring(0, 100) + '...');
    });
  } catch (error) {
    console.error('Error:', error.message);
  }
}

// Example 3: Compare models
async function example3() {
  console.log('\n=== Example 3: Compare Models ===\n');

  try {
    await aiCollector.compareModels('What is the future of AI?', ['chatgpt', 'gemini']);
  } catch (error) {
    console.error('Error:', error.message);
  }
}

// Example 4: Get statistics
async function example4() {
  console.log('\n=== Example 4: Statistics ===\n');

  try {
    const stats = await supabaseService.getStatistics();
    console.log('Total responses:', stats.total);
    console.log('By model:', stats.byModel);
    console.log('By language:', stats.byLanguage);
  } catch (error) {
    console.error('Error:', error.message);
  }
}

// Example 5: Get recent responses
async function example5() {
  console.log('\n=== Example 5: Recent Responses ===\n');

  try {
    const responses = await supabaseService.getResponses({ limit: 5 });

    responses.forEach((response, index) => {
      console.log(`\n${index + 1}. ${response.model_name}`);
      console.log(`   Prompt: ${response.prompt.substring(0, 50)}...`);
      console.log(`   Language: ${response.language}`);
      console.log(`   Date: ${response.created_at}`);
    });
  } catch (error) {
    console.error('Error:', error.message);
  }
}

// Run examples
async function runExamples() {
  const args = process.argv.slice(2);
  const exampleNumber = args[0];

  if (!exampleNumber) {
    console.log('Usage: node basic-usage.js <example-number>');
    console.log('\nAvailable examples:');
    console.log('  1 - Single model query');
    console.log('  2 - Multiple models query');
    console.log('  3 - Compare models');
    console.log('  4 - Get statistics');
    console.log('  5 - Get recent responses');
    console.log('  all - Run all examples');
    return;
  }

  if (exampleNumber === 'all') {
    await example1();
    await example2();
    await example3();
    await example4();
    await example5();
  } else {
    const examples = {
      '1': example1,
      '2': example2,
      '3': example3,
      '4': example4,
      '5': example5,
    };

    const example = examples[exampleNumber];
    if (example) {
      await example();
    } else {
      console.error('Invalid example number');
    }
  }
}

runExamples();
