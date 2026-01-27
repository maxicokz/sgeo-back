-- Migration: Add topic column to ai_responses table
-- Description: Adds a topic field to associate prompts with themes/topics
-- The topic is NOT sent to AI, it's only for organization and CSV export

-- Add topic column
ALTER TABLE ai_responses ADD COLUMN IF NOT EXISTS topic VARCHAR(255);

-- Create index for topic
CREATE INDEX IF NOT EXISTS idx_ai_responses_topic ON ai_responses(topic);

-- Add comment
COMMENT ON COLUMN ai_responses.topic IS 'Topic/theme associated with the prompt (not sent to AI, for organization only)';
