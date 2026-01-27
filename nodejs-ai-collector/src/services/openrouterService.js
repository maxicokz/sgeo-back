import axios from 'axios';
import { config } from '../config/config.js';

class OpenRouterService {
  constructor() {
    this.apiKey = config.openrouter.apiKey;
    this.apiUrl = config.openrouter.apiUrl;
    this.client = axios.create({
      baseURL: this.apiUrl,
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type': 'application/json',
        'HTTP-Referer': 'https://github.com/maxicokz/sgeo-back',
        'X-Title': 'SGEO AI Collector',
      },
    });
  }

  /**
   * Send a prompt to a specific AI model
   * @param {string} model - Model identifier
   * @param {string} prompt - The prompt to send
   * @param {Object} options - Additional options (webSearch: boolean)
   * @returns {Promise<Object>} Response from the AI model
   */
  async query(model, prompt, options = {}) {
    try {
      const startTime = Date.now();

      // Apply :online suffix for web search capability
      // See: https://openrouter.ai/docs/guides/features/plugins/web-search
      let modelWithSuffix = model;
      if (options.webSearch) {
        modelWithSuffix = `${model}:online`;
        console.log(`🌐 Web search enabled for ${model} -> ${modelWithSuffix}`);
      }

      // OpenAI models require 'max_completion_tokens' instead of 'max_tokens'
      const isOpenAIModel = model.startsWith('openai/');
      const maxTokensKey = isOpenAIModel ? 'max_completion_tokens' : 'max_tokens';

      // Remove custom options before spreading to avoid sending them to API
      const { webSearch, ...apiOptions } = options;

      const response = await this.client.post('/chat/completions', {
        model: modelWithSuffix,
        messages: [
          {
            role: 'user',
            content: prompt,
          },
        ],
        [maxTokensKey]: apiOptions.maxTokens || 1000,
        temperature: apiOptions.temperature || 0.7,
        top_p: apiOptions.topP || 1,
        ...apiOptions,
      });

      const endTime = Date.now();
      const responseTime = endTime - startTime;

      return {
        success: true,
        content: response.data.choices[0].message.content,
        model: response.data.model,
        metadata: {
          id: response.data.id,
          usage: response.data.usage,
          responseTime,
          finishReason: response.data.choices[0].finish_reason,
          // Perplexity citations (array of URLs)
          citations: response.data.citations,
          // Perplexity/OpenRouter annotations (detailed info with titles)
          // Web search results are standardized to url_citation format
          annotations: response.data.choices[0].message.annotations,
          // Flag indicating web search was enabled
          webSearchEnabled: !!options.webSearch,
        },
      };
    } catch (error) {
      console.error(`Error querying ${model}:`, error.response?.data || error.message);

      return {
        success: false,
        error: error.response?.data?.error?.message || error.message,
        model: model,
        metadata: {
          errorCode: error.response?.status,
          errorType: error.response?.data?.error?.type,
        },
      };
    }
  }

  /**
   * Query multiple models with the same prompt
   * @param {Array<string>} models - Array of model identifiers
   * @param {string} prompt - The prompt to send
   * @param {Object} options - Additional options
   * @returns {Promise<Array>} Array of responses
   */
  async queryMultiple(models, prompt, options = {}) {
    const promises = models.map((model) => this.query(model, prompt, options));
    return Promise.all(promises);
  }

  /**
   * Get available models from OpenRouter
   * @returns {Promise<Array>} Array of available models
   */
  async getAvailableModels() {
    try {
      const response = await this.client.get('/models');
      return response.data.data;
    } catch (error) {
      console.error('Error fetching available models:', error.response?.data || error.message);
      throw error;
    }
  }

  /**
   * Check if a model is available
   * @param {string} modelId - Model identifier
   * @returns {Promise<boolean>} True if model is available
   */
  async isModelAvailable(modelId) {
    try {
      const models = await this.getAvailableModels();
      return models.some((model) => model.id === modelId);
    } catch (error) {
      console.error('Error checking model availability:', error.message);
      return false;
    }
  }
}

export default new OpenRouterService();
