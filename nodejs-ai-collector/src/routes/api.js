import express from 'express';
import aiCollector from '../services/aiCollector.js';
import supabaseService from '../services/supabaseService.js';
import { detectLanguage } from '../utils/languageDetector.js';

const router = express.Router();

/**
 * GET /api/models
 * Get list of available AI models
 */
router.get('/models', (req, res) => {
  try {
    const models = aiCollector.getAvailableModels();
    res.json({
      success: true,
      models: models.map(name => ({
        id: name,
        name: name.charAt(0).toUpperCase() + name.slice(1),
      })),
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * POST /api/collect
 * Collect responses from selected AI models
 * Body: { prompt: string, models: string[] }
 */
router.post('/collect', async (req, res) => {
  try {
    const { prompt, models } = req.body;

    if (!prompt) {
      return res.status(400).json({
        success: false,
        error: 'Prompt is required',
      });
    }

    if (!models || !Array.isArray(models) || models.length === 0) {
      return res.status(400).json({
        success: false,
        error: 'At least one model must be selected',
      });
    }

    // Detect language
    const language = detectLanguage(prompt);

    // Collect responses
    const results = await aiCollector.collectMultiple(models, prompt);

    res.json({
      success: true,
      language,
      results: {
        successful: results.successful.length,
        failed: results.failed.length,
        total: results.total,
      },
      responses: results.successful.map(r => ({
        id: r.id,
        model: r.modelName,
        response: r.response,
        language: r.language,
        metadata: r.metadata,
        createdAt: r.createdAt,
      })),
      errors: results.failed,
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * GET /api/responses
 * Get collected responses
 * Query params: limit, modelName, language
 */
router.get('/responses', async (req, res) => {
  try {
    const { limit, modelName, language } = req.query;

    const options = {
      limit: limit ? parseInt(limit) : 50,
    };

    if (modelName) {
      options.modelName = modelName;
    }

    if (language) {
      options.language = language;
    }

    const responses = await supabaseService.getResponses(options);

    res.json({
      success: true,
      count: responses.length,
      responses: responses.map(r => ({
        id: r.id,
        prompt: r.prompt,
        model: r.model_name,
        response: r.response,
        language: r.language,
        metadata: r.metadata,
        createdAt: r.created_at,
      })),
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * GET /api/responses/:id
 * Get a specific response by ID
 */
router.get('/responses/:id', async (req, res) => {
  try {
    const { id } = req.params;

    const response = await supabaseService.getResponseById(id);

    if (!response) {
      return res.status(404).json({
        success: false,
        error: 'Response not found',
      });
    }

    res.json({
      success: true,
      response: {
        id: response.id,
        prompt: response.prompt,
        model: response.model_name,
        response: response.response,
        language: response.language,
        metadata: response.metadata,
        createdAt: response.created_at,
      },
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * GET /api/statistics
 * Get collection statistics
 */
router.get('/statistics', async (req, res) => {
  try {
    const stats = await supabaseService.getStatistics();

    res.json({
      success: true,
      statistics: stats,
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * GET /api/sample-prompts
 * Get sample prompts for testing
 */
router.get('/sample-prompts', (req, res) => {
  const prompts = {
    general: [
      'What is artificial intelligence?',
      'Explain quantum computing',
      'How does machine learning work?',
    ],
    russian: [
      'Что такое искусственный интеллект?',
      'Объясни квантовые вычисления',
      'Как работает машинное обучение?',
    ],
    kazakhstan: [
      'Какая столица Казахстана?',
      'Расскажи о культуре Казахстана',
      'Какие языки используются в Казахстане?',
    ],
  };

  res.json({
    success: true,
    prompts,
  });
});

export default router;
