#!/usr/bin/env node

import { validateConfig } from './config/config.js';
import aiCollector from './services/aiCollector.js';
import openrouterService from './services/openrouterService.js';
import supabaseService from './services/supabaseService.js';

// Validate configuration on startup
try {
  validateConfig();
  console.log('✅ Configuration validated successfully');
} catch (error) {
  console.error('❌ Configuration error:', error.message);
  console.error('Please create a .env file based on .env.example');
  process.exit(1);
}

// Export services for programmatic use
export {
  aiCollector,
  openrouterService,
  supabaseService,
};

// If running directly (not imported), show welcome message
if (import.meta.url === `file://${process.argv[1]}`) {
  console.log(`
╔═══════════════════════════════════════════════════════════════════╗
║          AI Responses Collector - Multi-Model Integration         ║
╚═══════════════════════════════════════════════════════════════════╝

This service collects responses from multiple AI models via OpenRouter
and stores them in Supabase for analysis.

Usage:
  npm run collect -- [command] [options]

For help:
  npm run collect -- help

Available models: ${aiCollector.getAvailableModels().join(', ')}

Documentation: See README.md for detailed usage instructions.
  `);
}
