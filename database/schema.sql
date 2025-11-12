-- SGEO Analytics Database Schema

-- Drop existing tables
DROP TABLE IF EXISTS llm_responses CASCADE;
DROP TABLE IF EXISTS source_analytics CASCADE;
DROP TABLE IF EXISTS sources CASCADE;
DROP TABLE IF EXISTS prompts CASCADE;
DROP TABLE IF EXISTS topics CASCADE;
DROP TABLE IF EXISTS llm_systems CASCADE;
DROP TABLE IF EXISTS monitoring_runs CASCADE;
DROP TABLE IF EXISTS reports CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP
);

-- Topics table (20 priority topics)
CREATE TABLE topics (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    name_ru VARCHAR(255),
    name_en VARCHAR(255),
    description TEXT,
    strategic_importance INTEGER CHECK (strategic_importance BETWEEN 0 AND 10),
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- LLM Systems table
CREATE TABLE llm_systems (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    provider VARCHAR(100),
    model_id VARCHAR(255),
    version VARCHAR(50),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Prompts table
CREATE TABLE prompts (
    id SERIAL PRIMARY KEY,
    topic_id INTEGER REFERENCES topics(id) ON DELETE CASCADE,
    prompt_text TEXT NOT NULL,
    prompt_type VARCHAR(50),
    language VARCHAR(10) DEFAULT 'ru',
    version INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Monitoring Runs table
CREATE TABLE monitoring_runs (
    id SERIAL PRIMARY KEY,
    run_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'running',
    total_queries INTEGER DEFAULT 0,
    completed_queries INTEGER DEFAULT 0,
    failed_queries INTEGER DEFAULT 0,
    notes TEXT,
    completed_at TIMESTAMP
);

-- LLM Responses table
CREATE TABLE llm_responses (
    id SERIAL PRIMARY KEY,
    monitoring_run_id INTEGER REFERENCES monitoring_runs(id) ON DELETE CASCADE,
    topic_id INTEGER REFERENCES topics(id) ON DELETE CASCADE,
    llm_system_id INTEGER REFERENCES llm_systems(id) ON DELETE CASCADE,
    prompt_id INTEGER REFERENCES prompts(id) ON DELETE CASCADE,
    response_text TEXT,
    response_metadata JSONB,
    cited_sources JSONB,
    screenshot_url VARCHAR(500),

    -- NLP Scores (0-5)
    sentiment_score DECIMAL(3,2) CHECK (sentiment_score BETWEEN 0 AND 5),
    completeness_score DECIMAL(3,2) CHECK (completeness_score BETWEEN 0 AND 5),
    correctness_score DECIMAL(3,2) CHECK (correctness_score BETWEEN 0 AND 5),

    processing_time INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(monitoring_run_id, topic_id, llm_system_id, prompt_id)
);

-- Sources table
CREATE TABLE sources (
    id SERIAL PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE,
    url TEXT,
    title VARCHAR(500),
    source_type VARCHAR(100),
    is_official BOOLEAN DEFAULT false,

    -- E-E-A-T Scores (0-10)
    expertise_score DECIMAL(3,2) CHECK (expertise_score BETWEEN 0 AND 10),
    authoritativeness_score DECIMAL(3,2) CHECK (authoritativeness_score BETWEEN 0 AND 10),
    trustworthiness_score DECIMAL(3,2) CHECK (trustworthiness_score BETWEEN 0 AND 10),
    experience_score DECIMAL(3,2) CHECK (experience_score BETWEEN 0 AND 10),
    overall_eeat_score DECIMAL(4,2) CHECK (overall_eeat_score BETWEEN 0 AND 100),

    -- Additional metrics
    health_index DECIMAL(4,2) CHECK (health_index BETWEEN 0 AND 100),
    authorship_clarity DECIMAL(3,2),
    content_accuracy DECIMAL(3,2),
    reputation_score DECIMAL(3,2),
    security_score DECIMAL(3,2),
    freshness_score DECIMAL(3,2),

    citation_count INTEGER DEFAULT 0,
    last_cited_at TIMESTAMP,
    optimization_status VARCHAR(50) DEFAULT 'pending',
    optimization_priority INTEGER,

    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Source Analytics (historical data)
CREATE TABLE source_analytics (
    id SERIAL PRIMARY KEY,
    source_id INTEGER REFERENCES sources(id) ON DELETE CASCADE,
    monitoring_run_id INTEGER REFERENCES monitoring_runs(id) ON DELETE CASCADE,
    topic_id INTEGER REFERENCES topics(id),

    citation_count INTEGER DEFAULT 0,
    citation_position INTEGER,

    metrics JSONB,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reports table
CREATE TABLE reports (
    id SERIAL PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    title VARCHAR(500),
    period_start DATE,
    period_end DATE,

    summary TEXT,
    metrics JSONB,
    recommendations JSONB,

    file_path VARCHAR(500),
    generated_by INTEGER REFERENCES users(id),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_llm_responses_topic ON llm_responses(topic_id);
CREATE INDEX idx_llm_responses_llm_system ON llm_responses(llm_system_id);
CREATE INDEX idx_llm_responses_run ON llm_responses(monitoring_run_id);
CREATE INDEX idx_llm_responses_created ON llm_responses(created_at);

CREATE INDEX idx_sources_domain ON sources(domain);
CREATE INDEX idx_sources_citation_count ON sources(citation_count DESC);
CREATE INDEX idx_sources_eeat ON sources(overall_eeat_score DESC);

CREATE INDEX idx_source_analytics_source ON source_analytics(source_id);
CREATE INDEX idx_source_analytics_run ON source_analytics(monitoring_run_id);

CREATE INDEX idx_prompts_topic ON prompts(topic_id);

-- Insert default LLM systems
INSERT INTO llm_systems (name, provider, model_id, version) VALUES
('ChatGPT', 'OpenAI', 'gpt-4-turbo-preview', '4.0'),
('GPT-3.5', 'OpenAI', 'gpt-3.5-turbo', '3.5'),
('Bing Chat', 'Microsoft', 'bing-chat', '1.0'),
('Copilot', 'Microsoft', 'copilot', '1.0'),
('Perplexity', 'Perplexity', 'pplx-70b-online', '1.0'),
('Gemini', 'Google', 'gemini-pro', '1.0');

-- Insert default user (password: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@sgeo.kz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert 20 priority topics about Kazakhstan
INSERT INTO topics (name, name_ru, name_en, strategic_importance) VALUES
('Экономика Казахстана', 'Экономика Казахстана', 'Economy of Kazakhstan', 9),
('История Казахстана', 'История Казахстана', 'History of Kazakhstan', 10),
('Культура Казахстана', 'Культура Казахстана', 'Culture of Kazakhstan', 9),
('Туризм в Казахстане', 'Туризм в Казахстане', 'Tourism in Kazakhstan', 8),
('Образование в Казахстане', 'Образование в Казахстане', 'Education in Kazakhstan', 8),
('Технологии в Казахстане', 'Технологии в Казахстане', 'Technology in Kazakhstan', 9),
('Нефтегазовая отрасль', 'Нефтегазовая отрасль', 'Oil and Gas Industry', 10),
('Космическая программа', 'Космическая программа', 'Space Program (Baikonur)', 9),
('Алматы', 'Алматы', 'Almaty', 8),
('Астана/Нур-Султан', 'Астана/Нур-Султан', 'Astana/Nur-Sultan', 9),
('Казахская кухня', 'Казахская кухня', 'Kazakh Cuisine', 7),
('Природа Казахстана', 'Природа Казахстана', 'Nature of Kazakhstan', 8),
('Спорт в Казахстане', 'Спорт в Казахстане', 'Sports in Kazakhstan', 7),
('Каспийское море', 'Каспийское море', 'Caspian Sea', 8),
('Шелковый путь', 'Шелковый путь', 'Silk Road', 9),
('Политическая система', 'Политическая система', 'Political System', 8),
('Инвестиции в Казахстан', 'Инвестиции в Казахстан', 'Investment in Kazakhstan', 9),
('Экология Казахстана', 'Экология Казахстана', 'Ecology of Kazakhstan', 7),
('Медицина в Казахстане', 'Медицина в Казахстане', 'Healthcare in Kazakhstan', 7),
('Казахстанские традиции', 'Казахстанские традиции', 'Kazakh Traditions', 8);

-- Create views for analytics

-- View: Topic Performance Summary
CREATE VIEW v_topic_performance AS
SELECT
    t.id,
    t.name,
    t.strategic_importance,
    COUNT(DISTINCT lr.id) as total_responses,
    AVG(lr.sentiment_score) as avg_sentiment,
    AVG(lr.completeness_score) as avg_completeness,
    AVG(lr.correctness_score) as avg_correctness,
    COUNT(DISTINCT CASE WHEN lr.sentiment_score >= 4 THEN lr.id END) as positive_responses
FROM topics t
LEFT JOIN llm_responses lr ON t.id = lr.topic_id
GROUP BY t.id, t.name, t.strategic_importance;

-- View: Source Performance Summary
CREATE VIEW v_source_performance AS
SELECT
    s.id,
    s.domain,
    s.source_type,
    s.overall_eeat_score,
    s.health_index,
    s.citation_count,
    s.optimization_status,
    COUNT(DISTINCT sa.id) as analytics_entries
FROM sources s
LEFT JOIN source_analytics sa ON s.id = sa.source_id
GROUP BY s.id;

-- View: Latest Monitoring Run Summary
CREATE VIEW v_latest_monitoring_summary AS
SELECT
    mr.id,
    mr.run_date,
    mr.status,
    mr.total_queries,
    mr.completed_queries,
    mr.failed_queries,
    COUNT(DISTINCT lr.topic_id) as topics_covered,
    COUNT(DISTINCT lr.llm_system_id) as llm_systems_used,
    AVG(lr.sentiment_score) as avg_sentiment,
    AVG(lr.completeness_score) as avg_completeness,
    AVG(lr.correctness_score) as avg_correctness
FROM monitoring_runs mr
LEFT JOIN llm_responses lr ON mr.id = lr.monitoring_run_id
GROUP BY mr.id
ORDER BY mr.run_date DESC
LIMIT 10;
