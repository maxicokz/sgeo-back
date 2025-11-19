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
  uzn: 'uz', // Northern Uzbek
  uzs: 'uz', // Southern Uzbek
  uig: 'ug', // Uyghur
  tgk: 'tg', // Tajik
  kir: 'ky', // Kyrgyz
  tuk: 'tk', // Turkmen
  aze: 'az', // Azerbaijani
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
    // Use higher minLength for better accuracy (10 chars minimum)
    // Whitelist most common languages for better accuracy
    const detected = franc(text, {
      minLength: 10,
      only: [
        'eng', 'rus', 'spa', 'fra', 'deu', 'ita', 'por', 'pol', 'ukr',
        'jpn', 'kor', 'zho', 'ara', 'hin', 'tur', 'vie', 'tha',
        'kaz', 'uzn', 'uzs', 'tgk', 'kir', 'tuk', 'aze', 'uig'
      ]
    });

    // If detection failed or uncertain
    if (detected === 'und' || !detected) {
      // For short texts, try without length restriction
      if (text.trim().length < 10) {
        const fallback = franc(text, { minLength: 3 });
        if (fallback && fallback !== 'und') {
          console.log(`Short text detected as: ${fallback} (less reliable)`);
          return languageMap[fallback] || fallback;
        }
      }
      return 'unknown';
    }

    const result = languageMap[detected] || detected;
    console.log(`Language detected: ${detected} → ${result} (text length: ${text.length})`);

    return result;
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
    const detected = franc(text, {
      minLength: 10,
      only: [
        'eng', 'rus', 'spa', 'fra', 'deu', 'ita', 'por', 'pol', 'ukr',
        'jpn', 'kor', 'zho', 'ara', 'hin', 'tur', 'vie', 'tha',
        'kaz', 'uzn', 'uzs', 'tgk', 'kir', 'tuk', 'aze', 'uig'
      ]
    });

    if (detected === 'und' || !detected) {
      // Fallback for short texts
      if (text.trim().length < 10) {
        const fallback = franc(text, { minLength: 3 });
        if (fallback && fallback !== 'und') {
          return {
            language: languageMap[fallback] || fallback,
            confidence: 0.3 // Low confidence for short texts
          };
        }
      }
      return { language: 'unknown', confidence: 0 };
    }

    const language = languageMap[detected] || detected;

    // Estimate confidence based on text length
    // Longer texts = higher confidence
    let confidence;
    if (text.length < 10) {
      confidence = 0.3;
    } else if (text.length < 30) {
      confidence = 0.5;
    } else if (text.length < 100) {
      confidence = 0.7;
    } else {
      confidence = 0.9;
    }

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
    uz: 'Uzbek',
    ug: 'Uyghur',
    tg: 'Tajik',
    ky: 'Kyrgyz',
    tk: 'Turkmen',
    az: 'Azerbaijani',
  };

  return names[code] || 'Unknown';
}
