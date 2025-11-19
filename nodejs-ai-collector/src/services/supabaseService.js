import { createClient } from '@supabase/supabase-js';
import { config } from '../config/config.js';

class SupabaseService {
  constructor() {
    this.client = createClient(config.supabase.url, config.supabase.anonKey);
  }

  /**
   * Save AI response to database
   * @param {Object} data - Response data
   * @param {string} data.prompt - The input prompt
   * @param {string} data.modelName - Name of the AI model
   * @param {string} data.response - The AI response
   * @param {string} data.language - Detected language
   * @param {Object} data.metadata - Additional metadata
   * @returns {Promise<Object>} Saved record
   */
  async saveResponse(data) {
    try {
      const { data: result, error } = await this.client
        .from('ai_responses')
        .insert([
          {
            prompt: data.prompt,
            model_name: data.modelName,
            response: data.response,
            language: data.language,
            metadata: data.metadata || {},
          },
        ])
        .select()
        .single();

      if (error) throw error;

      return result;
    } catch (error) {
      console.error('Error saving response to Supabase:', error);
      throw error;
    }
  }

  /**
   * Get all responses
   * @param {Object} options - Query options
   * @param {number} options.limit - Number of records to fetch
   * @param {string} options.modelName - Filter by model name
   * @param {string} options.language - Filter by language
   * @returns {Promise<Array>} Array of responses
   */
  async getResponses(options = {}) {
    try {
      let query = this.client
        .from('ai_responses')
        .select('*')
        .order('created_at', { ascending: false });

      if (options.limit) {
        query = query.limit(options.limit);
      }

      if (options.modelName) {
        query = query.eq('model_name', options.modelName);
      }

      if (options.language) {
        query = query.eq('language', options.language);
      }

      const { data, error } = await query;

      if (error) throw error;

      return data;
    } catch (error) {
      console.error('Error fetching responses from Supabase:', error);
      throw error;
    }
  }

  /**
   * Get response by ID
   * @param {string} id - Response ID
   * @returns {Promise<Object>} Response record
   */
  async getResponseById(id) {
    try {
      const { data, error } = await this.client
        .from('ai_responses')
        .select('*')
        .eq('id', id)
        .single();

      if (error) throw error;

      return data;
    } catch (error) {
      console.error('Error fetching response by ID:', error);
      throw error;
    }
  }

  /**
   * Get statistics
   * @returns {Promise<Object>} Statistics object
   */
  async getStatistics() {
    try {
      const { data, error } = await this.client
        .from('ai_responses')
        .select('model_name, language, created_at');

      if (error) throw error;

      const stats = {
        total: data.length,
        byModel: {},
        byLanguage: {},
      };

      data.forEach((record) => {
        // Count by model
        stats.byModel[record.model_name] = (stats.byModel[record.model_name] || 0) + 1;

        // Count by language
        if (record.language) {
          stats.byLanguage[record.language] = (stats.byLanguage[record.language] || 0) + 1;
        }
      });

      return stats;
    } catch (error) {
      console.error('Error fetching statistics:', error);
      throw error;
    }
  }

  /**
   * Delete a response by ID
   * @param {string} id - Response ID
   * @returns {Promise<void>}
   */
  async deleteResponse(id) {
    try {
      const { error } = await this.client
        .from('ai_responses')
        .delete()
        .eq('id', id);

      if (error) throw error;
    } catch (error) {
      console.error('Error deleting response:', error);
      throw error;
    }
  }

  /**
   * Delete all responses
   * @returns {Promise<void>}
   */
  async deleteAllResponses() {
    try {
      const { error } = await this.client
        .from('ai_responses')
        .delete()
        .neq('id', '00000000-0000-0000-0000-000000000000'); // Delete all

      if (error) throw error;
    } catch (error) {
      console.error('Error deleting all responses:', error);
      throw error;
    }
  }
}

export default new SupabaseService();
