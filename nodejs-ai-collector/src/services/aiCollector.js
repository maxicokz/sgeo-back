import openrouterService from './openrouterService.js';
import supabaseService from './supabaseService.js';
import { detectLanguage } from '../utils/languageDetector.js';
import { extractSources } from '../utils/sourcesExtractor.js';
import { config } from '../config/config.js';

class AICollector {
  constructor() {
    this.models = config.models;
  }

  /**
   * Collect response from a single AI model
   * @param {string} modelName - Name of the model (chatgpt, gemini, etc.)
   * @param {string} prompt - The prompt to send
   * @param {Object} options - Additional options (webSearch: boolean)
   * @returns {Promise<Object>} Collected response
   */
  async collectSingle(modelName, prompt, options = {}) {
    const webSearchStr = options.webSearch ? ' [Web Search]' : '';
    console.log(`\n📤 Querying ${modelName}${webSearchStr}...`);

    // Get model ID from config
    const modelId = this.models[modelName.toLowerCase()];
    if (!modelId) {
      throw new Error(`Unknown model: ${modelName}. Available: ${Object.keys(this.models).join(', ')}`);
    }

    // Detect language (disabled - returns null)
    const language = detectLanguage(prompt);

    // Query the model
    const startTime = Date.now();
    const result = await openrouterService.query(modelId, prompt, options);
    const endTime = Date.now();

    if (!result.success) {
      console.error(`❌ Error from ${modelName}: ${result.error}`);
      throw new Error(`Failed to get response from ${modelName}: ${result.error}`);
    }

    console.log(`✅ Response received from ${modelName} (${endTime - startTime}ms)`);

    // Extract sources from response and metadata
    console.log('🔍 [aiCollector] Calling extractSources...');
    const sources = extractSources(result.metadata, result.content);
    console.log('🔍 [aiCollector] Extracted sources:', sources);
    if (sources.length > 0) {
      console.log(`🔗 Found ${sources.length} source(s)`);
    } else {
      console.log('⚠️ No sources found');
    }

    // Prepare data for storage
    const responseData = {
      prompt,
      modelName: modelName.toLowerCase(),
      response: result.content,
      language,
      sources,
      metadata: {
        ...result.metadata,
        modelId,
        requestedAt: new Date(startTime).toISOString(),
        completedAt: new Date(endTime).toISOString(),
      },
    };

    // Save to Supabase
    console.log(`💾 Saving to database...`);
    console.log('🔍 [aiCollector] responseData.sources:', responseData.sources);
    const saved = await supabaseService.saveResponse(responseData);
    console.log(`✅ Saved with ID: ${saved.id}`);

    return {
      ...responseData,
      id: saved.id,
      createdAt: saved.created_at,
    };
  }

  /**
   * Collect responses from multiple AI models
   * @param {Array<string>} modelNames - Array of model names
   * @param {string} prompt - The prompt to send
   * @param {Object} options - Additional options
   * @returns {Promise<Array>} Array of collected responses
   */
  async collectMultiple(modelNames, prompt, options = {}) {
    console.log(`\n🚀 Starting collection from ${modelNames.length} models in parallel...`);
    console.log(`📝 Prompt: "${prompt.substring(0, 100)}${prompt.length > 100 ? '...' : ''}"`);

    // Execute all requests in parallel
    const promises = modelNames.map((modelName) =>
      this.collectSingle(modelName, prompt, options)
        .then((result) => ({ status: 'fulfilled', value: result }))
        .catch((error) => ({
          status: 'rejected',
          reason: { model: modelName, error: error.message },
        }))
    );

    const settled = await Promise.all(promises);

    const results = settled
      .filter((result) => result.status === 'fulfilled')
      .map((result) => result.value);

    const errors = settled
      .filter((result) => result.status === 'rejected')
      .map((result) => result.reason);

    console.log(`\n📊 Collection complete: ${results.length} successful, ${errors.length} failed`);

    return {
      successful: results,
      failed: errors,
      total: modelNames.length,
    };
  }

  /**
   * Collect responses from all available models
   * @param {string} prompt - The prompt to send
   * @param {Object} options - Additional options
   * @returns {Promise<Array>} Array of collected responses
   */
  async collectAll(prompt, options = {}) {
    const allModels = Object.keys(this.models);
    return this.collectMultiple(allModels, prompt, options);
  }

  /**
   * Get available model names
   * @returns {Array<string>} Array of model names
   */
  getAvailableModels() {
    return Object.keys(this.models);
  }

  /**
   * Compare responses from multiple models
   * @param {string} prompt - The prompt to send
   * @param {Array<string>} modelNames - Models to compare (optional, defaults to all)
   * @returns {Promise<Object>} Comparison results
   */
  async compareModels(prompt, modelNames = null) {
    const models = modelNames || this.getAvailableModels();
    const results = await this.collectMultiple(models, prompt);

    console.log('\n📊 Comparison Results:');
    console.log('='.repeat(80));

    results.successful.forEach((result, index) => {
      console.log(`\n${index + 1}. ${result.modelName.toUpperCase()}`);
      console.log('-'.repeat(80));
      console.log(`Response: ${result.response.substring(0, 200)}${result.response.length > 200 ? '...' : ''}`);
      console.log(`Response time: ${result.metadata.responseTime}ms`);
      console.log(`Tokens used: ${JSON.stringify(result.metadata.usage)}`);
    });

    if (results.failed.length > 0) {
      console.log('\n❌ Failed Models:');
      results.failed.forEach((failed) => {
        console.log(`- ${failed.model}: ${failed.error}`);
      });
    }

    return results;
  }
}

export default new AICollector();
