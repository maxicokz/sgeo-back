/**
 * Test script for sources extraction
 */
import { extractSources } from './src/utils/sourcesExtractor.js';

// Simulate a DeepSeek response with markdown links
const testResponse = `Вот 3 полезных ресурса для изучения Python:

1. **Stepik "Python для начинающих"**
   [https://stepik.org/course/58852/promo](https://stepik.org/course/58852/promo)

2. **Real Python**
   [https://realpython.com](https://realpython.com)

3. **Learn Python**
   [https://www.learnpython.org](https://www.learnpython.org)`;

const testMetadata = {
  // DeepSeek doesn't typically provide citations in metadata
};

console.log('Testing sources extraction...\n');
console.log('='.repeat(80));
console.log('Test response text:');
console.log(testResponse);
console.log('='.repeat(80));

const sources = extractSources(testMetadata, testResponse);

console.log('\n' + '='.repeat(80));
console.log('RESULTS:');
console.log('='.repeat(80));
console.log(`Total sources extracted: ${sources.length}`);
console.log(JSON.stringify(sources, null, 2));
