#!/usr/bin/env node

import express from 'express';
import cors from 'cors';
import path from 'path';
import { fileURLToPath } from 'url';
import { validateConfig } from './config/config.js';
import apiRouter from './routes/api.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Validate configuration
try {
  validateConfig();
  console.log('✅ Configuration validated successfully');
} catch (error) {
  console.error('❌ Configuration error:', error.message);
  console.error('Please create a .env file based on .env.example');
  process.exit(1);
}

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from public directory
app.use(express.static(path.join(__dirname, '../public')));

// API routes
app.use('/api', apiRouter);

// Main page
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/index.html'));
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Error:', err);
  res.status(500).json({
    success: false,
    error: err.message || 'Internal server error',
  });
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({
    success: false,
    error: 'Not found',
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`
╔═══════════════════════════════════════════════════════════════════╗
║        AI Responses Collector - Web Interface Started            ║
╚═══════════════════════════════════════════════════════════════════╝

🌐 Server running at: http://localhost:${PORT}
📊 API endpoint: http://localhost:${PORT}/api

Available endpoints:
  GET  /api/models           - Get available AI models
  POST /api/collect          - Collect AI responses
  GET  /api/responses        - Get collected responses
  GET  /api/responses/:id    - Get specific response
  GET  /api/statistics       - Get statistics
  GET  /api/sample-prompts   - Get sample prompts

Press Ctrl+C to stop the server
  `);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('\n🛑 Shutting down gracefully...');
  process.exit(0);
});

process.on('SIGINT', () => {
  console.log('\n🛑 Shutting down gracefully...');
  process.exit(0);
});

export default app;
