import axios from 'axios';
import { config } from '../config/config.js';

class OpenAIService {
  constructor() {
    this.apiKey = config.openai.apiKey;
    this.model = config.openai.model;
    this.apiUrl = 'https://api.openai.com/v1';
    this.client = axios.create({
      baseURL: this.apiUrl,
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type': 'application/json',
      },
    });
  }

  /**
   * Check if OpenAI is configured
   * @returns {boolean}
   */
  isConfigured() {
    return !!this.apiKey;
  }

  /**
   * Send a prompt to OpenAI ChatGPT
   * @param {string} prompt - The prompt to send
   * @param {Object} options - Additional options
   * @returns {Promise<Object>} Response from OpenAI
   */
  async query(prompt, options = {}) {
    if (!this.isConfigured()) {
      return {
        success: false,
        error: 'OpenAI API key not configured. Set OPENAI_API_KEY in .env',
        model: 'openai',
        metadata: {},
      };
    }

    try {
      const startTime = Date.now();

      const response = await this.client.post('/chat/completions', {
        model: options.model || this.model,
        messages: [
          {
            role: 'user',
            content: prompt,
          },
        ],
        max_tokens: options.maxTokens || 4096,
        temperature: options.temperature || 0.7,
        top_p: options.topP || 1,
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
        },
      };
    } catch (error) {
      console.error('Error querying OpenAI:', error.response?.data || error.message);

      return {
        success: false,
        error: error.response?.data?.error?.message || error.message,
        model: 'openai',
        metadata: {
          errorCode: error.response?.status,
          errorType: error.response?.data?.error?.type,
        },
      };
    }
  }
}

export default new OpenAIService();
