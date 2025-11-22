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

/**
 * DELETE /api/responses/:id
 * Delete a specific response by ID
 */
router.delete('/responses/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await supabaseService.deleteResponse(id);

    res.json({
      success: true,
      message: 'Response deleted successfully',
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * POST /api/responses/delete-multiple
 * Delete multiple responses by IDs
 */
router.post('/responses/delete-multiple', async (req, res) => {
  try {
    const { ids } = req.body;

    if (!ids || !Array.isArray(ids) || ids.length === 0) {
      return res.status(400).json({
        success: false,
        error: 'IDs array is required',
      });
    }

    await supabaseService.deleteMultipleResponses(ids);

    res.json({
      success: true,
      message: `${ids.length} response(s) deleted successfully`,
      count: ids.length,
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * DELETE /api/responses
 * Delete all responses
 */
router.delete('/responses', async (req, res) => {
  try {
    await supabaseService.deleteAllResponses();

    res.json({
      success: true,
      message: 'All responses deleted successfully',
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * GET /api/export/csv
 * Export responses to CSV
 */
router.get('/export/csv', async (req, res) => {
  try {
    const { limit, modelName, language } = req.query;

    const options = {
      limit: limit ? parseInt(limit) : 1000,
    };

    if (modelName) {
      options.modelName = modelName;
    }

    if (language) {
      options.language = language;
    }

    const responses = await supabaseService.getResponses(options);

    // Generate CSV
    const headers = ['ID', 'Date', 'Model', 'Language', 'Prompt', 'Response', 'Tokens Used'];
    const rows = responses.map(r => [
      r.id,
      r.created_at,
      r.model_name,
      r.language || 'N/A',
      `"${(r.prompt || '').replace(/"/g, '""')}"`,
      `"${(r.response || '').replace(/"/g, '""')}"`,
      r.metadata?.usage?.total_tokens || 'N/A',
    ]);

    const csv = [
      headers.join(','),
      ...rows.map(row => row.join(','))
    ].join('\n');

    res.setHeader('Content-Type', 'text/csv');
    res.setHeader('Content-Disposition', `attachment; filename="ai-responses-${Date.now()}.csv"`);
    res.send(csv);
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

// ============================================
// Prompt Sets Routes
// ============================================

/**
 * GET /api/prompt-sets
 * Get all saved prompt sets
 */
router.get('/prompt-sets', async (req, res) => {
  try {
    const promptSets = await supabaseService.getPromptSets();
    res.json({
      success: true,
      promptSets: promptSets,
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * GET /api/prompt-sets/:id
 * Get a specific prompt set by ID
 */
router.get('/prompt-sets/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const promptSet = await supabaseService.getPromptSetById(id);
    res.json({
      success: true,
      promptSet: promptSet,
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * POST /api/prompt-sets
 * Create a new prompt set
 * Body: { name: string, prompts: string[] }
 */
router.post('/prompt-sets', async (req, res) => {
  try {
    const { name, prompts } = req.body;

    if (!name || !name.trim()) {
      return res.status(400).json({
        success: false,
        error: 'Prompt set name is required',
      });
    }

    if (!prompts || !Array.isArray(prompts) || prompts.length === 0) {
      return res.status(400).json({
        success: false,
        error: 'At least one prompt is required',
      });
    }

    const promptSet = await supabaseService.savePromptSet({
      name: name.trim(),
      prompts: prompts.filter(p => p && p.trim()).map(p => p.trim()),
    });

    res.json({
      success: true,
      promptSet: promptSet,
    });
  } catch (error) {
    // Handle unique constraint violation
    if (error.message.includes('duplicate key') || error.code === '23505') {
      return res.status(409).json({
        success: false,
        error: 'A prompt set with this name already exists',
      });
    }

    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * PUT /api/prompt-sets/:id
 * Update a prompt set
 * Body: { name?: string, prompts?: string[] }
 */
router.put('/prompt-sets/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { name, prompts } = req.body;

    const updateData = {};
    if (name !== undefined) updateData.name = name.trim();
    if (prompts !== undefined) {
      updateData.prompts = prompts.filter(p => p && p.trim()).map(p => p.trim());
    }

    const promptSet = await supabaseService.updatePromptSet(id, updateData);

    res.json({
      success: true,
      promptSet: promptSet,
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * DELETE /api/prompt-sets/:id
 * Delete a prompt set by ID
 */
router.delete('/prompt-sets/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await supabaseService.deletePromptSet(id);
    res.json({
      success: true,
      message: 'Prompt set deleted successfully',
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

/**
 * DELETE /api/prompt-sets/by-name/:name
 * Delete a prompt set by name
 */
router.delete('/prompt-sets/by-name/:name', async (req, res) => {
  try {
    const { name } = req.params;
    await supabaseService.deletePromptSetByName(decodeURIComponent(name));
    res.json({
      success: true,
      message: 'Prompt set deleted successfully',
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

export default router;
