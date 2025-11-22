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
   * @param {Object} options - Additional options
   * @returns {Promise<Object>} Response from the AI model
   */
  async query(model, prompt, options = {}) {
    try {
      const startTime = Date.now();

      const response = await this.client.post('/chat/completions', {
        model: model,
        messages: [
          {
            role: 'user',
            content: prompt,
          },
        ],
        max_tokens: options.maxTokens || 1000,
        temperature: options.temperature || 0.7,
        top_p: options.topP || 1,
        ...options,
      });

      const endTime = Date.now();
      const responseTime = endTime - startTime;

      // Log full response to see if there are citations/sources
      console.log('🔍 [openrouterService] Full response.data:', JSON.stringify(response.data, null, 2));
      console.log('🔍 [openrouterService] choices[0]:', JSON.stringify(response.data.choices[0], null, 2));

      return {
        success: true,
        content: response.data.choices[0].message.content,
        model: response.data.model,
        metadata: {
          id: response.data.id,
          usage: response.data.usage,
          responseTime,
          finishReason: response.data.choices[0].finish_reason,
          // Include all extra fields from response.data that might contain citations
          citations: response.data.citations || response.data.choices[0].citations,
          sources: response.data.sources || response.data.choices[0].sources,
          // Include full response.data for debugging
          rawResponse: response.data,
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
