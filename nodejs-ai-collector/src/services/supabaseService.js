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
   * @param {Array} data.sources - Array of sources/citations
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
            sources: data.sources || [],
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
   * @param {number} options.offset - Offset for pagination
   * @param {string} options.modelName - Filter by model name
   * @param {string} options.language - Filter by language
   * @param {boolean} options.count - Whether to return total count
   * @returns {Promise<Object|Array>} Array of responses or object with data and count
   */
  async getResponses(options = {}) {
    try {
      const limit = options.limit || 20;
      const offset = options.offset || 0;

      let query = this.client
        .from('ai_responses')
        .select('*', { count: options.count ? 'exact' : undefined })
        .order('created_at', { ascending: false });

      if (options.modelName) {
        query = query.eq('model_name', options.modelName);
      }

      if (options.language) {
        query = query.eq('language', options.language);
      }

      // Use range for pagination
      query = query.range(offset, offset + limit - 1);

      const { data, error, count } = await query;

      if (error) throw error;

      // If count is requested, return both data and count
      if (options.count) {
        return { data, count };
      }

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
   * Delete multiple responses by IDs
   * @param {Array<string>} ids - Array of response IDs
   * @returns {Promise<void>}
   */
  async deleteMultipleResponses(ids) {
    try {
      const { error } = await this.client
        .from('ai_responses')
        .delete()
        .in('id', ids);

      if (error) throw error;
    } catch (error) {
      console.error('Error deleting multiple responses:', error);
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

  // ============================================
  // Prompt Sets Methods
  // ============================================

  /**
   * Get all prompt sets
   * @returns {Promise<Array>} Array of prompt sets
   */
  async getPromptSets() {
    try {
      const { data, error } = await this.client
        .from('prompt_sets')
        .select('*')
        .order('created_at', { ascending: false });

      if (error) throw error;

      return data;
    } catch (error) {
      console.error('Error fetching prompt sets:', error);
      throw error;
    }
  }

  /**
   * Get prompt set by ID
   * @param {string} id - Prompt set ID
   * @returns {Promise<Object>} Prompt set record
   */
  async getPromptSetById(id) {
    try {
      const { data, error } = await this.client
        .from('prompt_sets')
        .select('*')
        .eq('id', id)
        .single();

      if (error) throw error;

      return data;
    } catch (error) {
      console.error('Error fetching prompt set by ID:', error);
      throw error;
    }
  }

  /**
   * Save a prompt set
   * @param {Object} data - Prompt set data
   * @param {string} data.name - Name of the prompt set
   * @param {Array<string>} data.prompts - Array of prompts
   * @returns {Promise<Object>} Saved prompt set
   */
  async savePromptSet(data) {
    try {
      const { data: result, error } = await this.client
        .from('prompt_sets')
        .insert([
          {
            name: data.name,
            prompts: data.prompts,
          },
        ])
        .select()
        .single();

      if (error) throw error;

      return result;
    } catch (error) {
      console.error('Error saving prompt set:', error);
      throw error;
    }
  }

  /**
   * Update a prompt set
   * @param {string} id - Prompt set ID
   * @param {Object} data - Updated data
   * @param {string} data.name - New name
   * @param {Array<string>} data.prompts - New prompts array
   * @returns {Promise<Object>} Updated prompt set
   */
  async updatePromptSet(id, data) {
    try {
      const updateData = {};
      if (data.name !== undefined) updateData.name = data.name;
      if (data.prompts !== undefined) updateData.prompts = data.prompts;

      const { data: result, error } = await this.client
        .from('prompt_sets')
        .update(updateData)
        .eq('id', id)
        .select()
        .single();

      if (error) throw error;

      return result;
    } catch (error) {
      console.error('Error updating prompt set:', error);
      throw error;
    }
  }

  /**
   * Delete a prompt set by ID
   * @param {string} id - Prompt set ID
   * @returns {Promise<void>}
   */
  async deletePromptSet(id) {
    try {
      const { error } = await this.client
        .from('prompt_sets')
        .delete()
        .eq('id', id);

      if (error) throw error;
    } catch (error) {
      console.error('Error deleting prompt set:', error);
      throw error;
    }
  }

  /**
   * Delete a prompt set by name
   * @param {string} name - Prompt set name
   * @returns {Promise<void>}
   */
  async deletePromptSetByName(name) {
    try {
      const { error } = await this.client
        .from('prompt_sets')
        .delete()
        .eq('name', name);

      if (error) throw error;
    } catch (error) {
      console.error('Error deleting prompt set by name:', error);
      throw error;
    }
  }
}

export default new SupabaseService();
