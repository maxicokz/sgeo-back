-- Create table for storing AI responses
CREATE TABLE IF NOT EXISTS ai_responses (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    prompt TEXT NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    response TEXT NOT NULL,
    language VARCHAR(10),
    metadata JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_ai_responses_created_at ON ai_responses(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_ai_responses_model_name ON ai_responses(model_name);
CREATE INDEX IF NOT EXISTS idx_ai_responses_language ON ai_responses(language);

-- Create index for metadata search
CREATE INDEX IF NOT EXISTS idx_ai_responses_metadata ON ai_responses USING GIN (metadata);

-- Add comment to table
COMMENT ON TABLE ai_responses IS 'Stores AI model responses from various providers via OpenRouter';
COMMENT ON COLUMN ai_responses.prompt IS 'The input prompt sent to the AI model';
COMMENT ON COLUMN ai_responses.model_name IS 'Name of the AI model used (e.g., chatgpt, gemini, perplexity)';
COMMENT ON COLUMN ai_responses.response IS 'The AI model response text';
COMMENT ON COLUMN ai_responses.language IS 'Detected language of the prompt (ISO 639-1 code)';
COMMENT ON COLUMN ai_responses.metadata IS 'Additional metadata (tokens used, response time, etc.)';

-- Create function to automatically update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger for updated_at
CREATE TRIGGER update_ai_responses_updated_at
    BEFORE UPDATE ON ai_responses
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Enable Row Level Security (RLS)
ALTER TABLE ai_responses ENABLE ROW LEVEL SECURITY;

-- Create policy to allow all operations (customize based on your needs)
CREATE POLICY "Enable all operations for authenticated users" ON ai_responses
    FOR ALL
    USING (true)
    WITH CHECK (true);
