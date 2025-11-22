/**
 * Extract sources/citations from AI response metadata and text
 */

/**
 * Extract sources from OpenRouter metadata and response text
 * @param {Object} metadata - OpenRouter response metadata
 * @param {string} responseText - The AI response text
 * @returns {Array} Array of source objects
 */
export function extractSources(metadata = {}, responseText = '') {
  const sources = [];
  const seenUrls = new Set();

  console.log('🔍 [sourcesExtractor] Starting extraction...');
  console.log('🔍 [sourcesExtractor] Response text length:', responseText?.length || 0);

  // 1. Extract from Perplexity annotations (preferred - has titles)
  if (metadata.annotations && Array.isArray(metadata.annotations)) {
    console.log('🔍 [sourcesExtractor] Found metadata.annotations:', metadata.annotations.length);
    metadata.annotations.forEach(annotation => {
      if (annotation.type === 'url_citation' && annotation.url_citation) {
        const url = annotation.url_citation.url;
        const title = annotation.url_citation.title || url;

        if (url && !seenUrls.has(url)) {
          sources.push({
            type: 'citation',
            url: url,
            title: title,
            source: 'perplexity',
          });
          seenUrls.add(url);
        }
      }
    });
  }

  // 2. Extract from metadata.citations (Perplexity simple format - array of URLs)
  if (metadata.citations && Array.isArray(metadata.citations)) {
    console.log('🔍 [sourcesExtractor] Found metadata.citations:', metadata.citations.length);
    metadata.citations.forEach(citation => {
      // If citation is a string (URL)
      if (typeof citation === 'string' && !seenUrls.has(citation)) {
        sources.push({
          type: 'citation',
          url: citation,
          title: citation,
          source: 'metadata',
        });
        seenUrls.add(citation);
      }
      // If citation is an object
      else if (citation && citation.url && !seenUrls.has(citation.url)) {
        sources.push({
          type: 'citation',
          url: citation.url,
          title: citation.title || citation.url,
          source: 'metadata',
        });
        seenUrls.add(citation.url);
      }
    });
  }

  // 3. Extract from metadata.sources (alternative format)
  if (metadata.sources && Array.isArray(metadata.sources)) {
    console.log('🔍 [sourcesExtractor] Found metadata.sources:', metadata.sources.length);
    metadata.sources.forEach(source => {
      const url = source.url || source.link || source.href;
      if (url && !seenUrls.has(url)) {
        sources.push({
          type: 'source',
          url: url,
          title: source.title || source.name || url,
          source: 'metadata',
        });
        seenUrls.add(url);
      }
    });
  }

  // 4. Extract markdown links [text](url) - do this before plain URLs
  if (responseText) {
    const markdownLinkRegex = /\[([^\]]+)\]\((https?:\/\/[^)]+)\)/g;
    const matches = [...responseText.matchAll(markdownLinkRegex)];
    console.log('🔍 [sourcesExtractor] Markdown links found:', matches.length);

    for (const match of matches) {
      const title = match[1];
      const url = match[2];
      console.log('🔍 [sourcesExtractor] Markdown link:', { title, url });

      if (!seenUrls.has(url)) {
        sources.push({
          type: 'markdown_link',
          url: url,
          title: title,
          source: 'text',
        });
        seenUrls.add(url);
      }
    }
  }

  // 5. Extract plain URLs from response text using regex (skip if already found in markdown)
  if (responseText) {
    const urlRegex = /(https?:\/\/[^\s<>"{}|\\^`\[\]]+)/g;
    const matches = [...responseText.matchAll(urlRegex)];
    console.log('🔍 [sourcesExtractor] Plain URLs found:', matches.length);

    for (const match of matches) {
      const url = match[1];
      // Clean up common punctuation at the end
      const cleanUrl = url.replace(/[.,;:!?)\]]+$/, '');

      if (!seenUrls.has(cleanUrl)) {
        console.log('🔍 [sourcesExtractor] Plain URL:', cleanUrl);
        sources.push({
          type: 'url',
          url: cleanUrl,
          title: cleanUrl,
          source: 'text',
        });
        seenUrls.add(cleanUrl);
      }
    }
  }

  console.log('🔍 [sourcesExtractor] Total sources extracted:', sources.length);
  console.log('🔍 [sourcesExtractor] Sources:', JSON.stringify(sources, null, 2));

  return sources;
}

/**
 * Format sources for display
 * @param {Array} sources - Array of source objects
 * @returns {string} Formatted sources as HTML
 */
export function formatSourcesAsHtml(sources) {
  if (!sources || sources.length === 0) {
    return '<p>Нет источников</p>';
  }

  const items = sources.map((source, index) => {
    const domain = extractDomain(source.url);
    return `
      <div class="source-item">
        <span class="source-number">[${index + 1}]</span>
        <a href="${escapeHtml(source.url)}" target="_blank" rel="noopener noreferrer" class="source-link">
          ${escapeHtml(source.title)}
        </a>
        <span class="source-domain">(${escapeHtml(domain)})</span>
      </div>
    `;
  }).join('');

  return `<div class="sources-list">${items}</div>`;
}

/**
 * Extract domain from URL
 * @param {string} url - URL string
 * @returns {string} Domain name
 */
function extractDomain(url) {
  try {
    const urlObj = new URL(url);
    return urlObj.hostname.replace('www.', '');
  } catch (error) {
    return 'unknown';
  }
}

/**
 * Escape HTML for safe display
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return String(text).replace(/[&<>"']/g, m => map[m]);
}
