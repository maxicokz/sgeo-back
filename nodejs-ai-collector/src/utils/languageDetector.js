import { franc } from 'franc';

/**
 * Language code mapping from franc (ISO 639-3) to ISO 639-1
 */
const languageMap = {
  eng: 'en', // English
  rus: 'ru', // Russian
  spa: 'es', // Spanish
  fra: 'fr', // French
  deu: 'de', // German
  ita: 'it', // Italian
  por: 'pt', // Portuguese
  nld: 'nl', // Dutch
  pol: 'pl', // Polish
  ukr: 'uk', // Ukrainian
  jpn: 'ja', // Japanese
  kor: 'ko', // Korean
  zho: 'zh', // Chinese
  ara: 'ar', // Arabic
  hin: 'hi', // Hindi
  tur: 'tr', // Turkish
  vie: 'vi', // Vietnamese
  tha: 'th', // Thai
  swe: 'sv', // Swedish
  dan: 'da', // Danish
  fin: 'fi', // Finnish
  nor: 'no', // Norwegian
  ces: 'cs', // Czech
  ron: 'ro', // Romanian
  hun: 'hu', // Hungarian
  heb: 'he', // Hebrew
  ind: 'id', // Indonesian
  msa: 'ms', // Malay
  ell: 'el', // Greek
  bul: 'bg', // Bulgarian
  hrv: 'hr', // Croatian
  srp: 'sr', // Serbian
  slk: 'sk', // Slovak
  slv: 'sl', // Slovenian
  cat: 'ca', // Catalan
  kaz: 'kk', // Kazakh
};

/**
 * Detect language of text
 * @param {string} text - Text to analyze
 * @returns {string} ISO 639-1 language code or 'unknown'
 */
export function detectLanguage(text) {
  if (!text || typeof text !== 'string' || text.trim().length === 0) {
    return 'unknown';
  }

  try {
    // franc returns ISO 639-3 code
    const detected = franc(text, { minLength: 3 });

    // If detection failed or uncertain
    if (detected === 'und' || !detected) {
      return 'unknown';
    }

    // Map to ISO 639-1 or return the detected code
    return languageMap[detected] || detected;
  } catch (error) {
    console.error('Error detecting language:', error.message);
    return 'unknown';
  }
}

/**
 * Detect language with confidence score
 * @param {string} text - Text to analyze
 * @returns {Object} Object with language code and confidence
 */
export function detectLanguageWithConfidence(text) {
  if (!text || typeof text !== 'string' || text.trim().length === 0) {
    return { language: 'unknown', confidence: 0 };
  }

  try {
    const detected = franc(text, { minLength: 3 });

    if (detected === 'und' || !detected) {
      return { language: 'unknown', confidence: 0 };
    }

    const language = languageMap[detected] || detected;

    // franc doesn't provide confidence scores, so we estimate based on text length
    const confidence = Math.min(text.length / 100, 1);

    return { language, confidence };
  } catch (error) {
    console.error('Error detecting language with confidence:', error.message);
    return { language: 'unknown', confidence: 0 };
  }
}

/**
 * Get language name from code
 * @param {string} code - ISO 639-1 language code
 * @returns {string} Language name
 */
export function getLanguageName(code) {
  const names = {
    en: 'English',
    ru: 'Russian',
    es: 'Spanish',
    fr: 'French',
    de: 'German',
    it: 'Italian',
    pt: 'Portuguese',
    nl: 'Dutch',
    pl: 'Polish',
    uk: 'Ukrainian',
    ja: 'Japanese',
    ko: 'Korean',
    zh: 'Chinese',
    ar: 'Arabic',
    hi: 'Hindi',
    tr: 'Turkish',
    vi: 'Vietnamese',
    th: 'Thai',
    sv: 'Swedish',
    da: 'Danish',
    fi: 'Finnish',
    no: 'Norwegian',
    cs: 'Czech',
    ro: 'Romanian',
    hu: 'Hungarian',
    he: 'Hebrew',
    id: 'Indonesian',
    ms: 'Malay',
    el: 'Greek',
    bg: 'Bulgarian',
    hr: 'Croatian',
    sr: 'Serbian',
    sk: 'Slovak',
    sl: 'Slovenian',
    ca: 'Catalan',
    kk: 'Kazakh',
  };

  return names[code] || 'Unknown';
}
