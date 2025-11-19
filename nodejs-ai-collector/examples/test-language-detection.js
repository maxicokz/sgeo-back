import { detectLanguage, detectLanguageWithConfidence } from '../src/utils/languageDetector.js';

console.log('🧪 Тестирование определения языка\n');
console.log('='.repeat(60));

const testCases = [
  // Русский
  { text: 'Привет', expected: 'ru' },
  { text: 'Что такое искусственный интеллект?', expected: 'ru' },
  { text: 'Объясни квантовые вычисления простыми словами', expected: 'ru' },

  // Английский
  { text: 'Hello', expected: 'en' },
  { text: 'What is artificial intelligence?', expected: 'en' },
  { text: 'Explain quantum computing in simple terms', expected: 'en' },

  // Казахский
  { text: 'Сәлем', expected: 'kk' },
  { text: 'Қазақстанның астанасы қайда?', expected: 'kk' },

  // Узбекский
  { text: 'Salom', expected: 'uz' },
  { text: 'Sun\'iy intellekt nima?', expected: 'uz' },
];

testCases.forEach(({ text, expected }, index) => {
  const result = detectLanguage(text);
  const detailed = detectLanguageWithConfidence(text);

  const status = result === expected ? '✅' : '❌';

  console.log(`\nTest ${index + 1}: ${status}`);
  console.log(`  Text: "${text}"`);
  console.log(`  Expected: ${expected}`);
  console.log(`  Detected: ${result}`);
  console.log(`  Confidence: ${(detailed.confidence * 100).toFixed(0)}%`);
  console.log(`  Text length: ${text.length} chars`);
});

console.log('\n' + '='.repeat(60));
