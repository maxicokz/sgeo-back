// SGEO Analytics Dashboard JavaScript

/**
 * Start data collection (batch mode for Plesk compatibility)
 */
async function startCollection() {
    // Show mode selection
    const mode = confirm(
        '✅ Рекомендуется: Пакетный режим (для Plesk shared hosting)\n' +
        '❌ Не рекомендуется: Полный режим (может превысить лимит времени)\n\n' +
        'OK = Пакетный режим\nОтмена = Полный режим'
    );

    if (mode) {
        // Batch mode (recommended for Plesk)
        startBatchCollection();
    } else {
        // Full mode (may timeout on shared hosting)
        startFullCollection();
    }
}

/**
 * Start batch collection (Plesk-safe)
 */
async function startBatchCollection() {
    // Create progress modal
    const modal = createProgressModal();
    document.body.appendChild(modal);

    let runId = null;
    let completed = false;

    try {
        while (!completed) {
            const formData = new FormData();
            if (runId) formData.append('run_id', runId);
            formData.append('batch_size', '2'); // 2 topics per batch
            formData.append('csrf_token', getCsrfToken());

            const response = await fetch('/api/collect-batch.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Unknown error');
            }

            runId = data.run_id;
            completed = data.completed;

            // Update progress
            updateProgressModal(modal, data.progress, data.message);

            if (!completed) {
                // Wait 2 seconds before next batch to avoid rate limiting
                await sleep(2000);
            }
        }

        // Success
        setTimeout(() => {
            modal.remove();
            alert('✅ Сбор данных завершен успешно!');
            window.location.reload();
        }, 1000);

    } catch (error) {
        console.error('Batch collection error:', error);
        modal.remove();
        alert('❌ Ошибка сбора данных: ' + error.message);
    }
}

/**
 * Start full collection (original mode)
 */
async function startFullCollection() {
    if (!confirm('⚠️ Внимание: Полный режим может превысить лимиты времени на shared hosting.\n\nПродолжить?')) {
        return;
    }

    try {
        const response = await fetch('/api/collection/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                topic_ids: [],  // Empty = all topics
                llm_models: []  // Empty = all models
            })
        });

        const data = await response.json();

        if (data.success) {
            alert(`Сбор данных запущен! Run ID: ${data.run_id}`);
            monitorRunProgress(data.run_id);
        } else {
            alert('Ошибка запуска: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error starting collection:', error);
        alert('Ошибка запуска сбора данных. Проверьте консоль.');
    }
}

/**
 * Create progress modal
 */
function createProgressModal() {
    const modal = document.createElement('div');
    modal.id = 'batch-progress-modal';
    modal.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center;">
            <div style="background: white; padding: 30px; border-radius: 10px; min-width: 400px; max-width: 500px;">
                <h3 style="margin-top: 0;">🚀 Сбор данных</h3>
                <div style="margin: 20px 0;">
                    <div style="background: #f0f0f0; height: 30px; border-radius: 15px; overflow: hidden;">
                        <div id="progress-bar" style="background: linear-gradient(to right, #3498db, #2ecc71); height: 100%; width: 0%; transition: width 0.3s;"></div>
                    </div>
                    <div id="progress-text" style="margin-top: 10px; text-align: center; color: #666;"></div>
                </div>
                <p style="margin: 0; font-size: 12px; color: #999; text-align: center;">Пожалуйста, не закрывайте эту страницу</p>
            </div>
        </div>
    `;
    return modal;
}

/**
 * Update progress modal
 */
function updateProgressModal(modal, progress, message) {
    const progressBar = modal.querySelector('#progress-bar');
    const progressText = modal.querySelector('#progress-text');

    progressBar.style.width = progress + '%';
    progressText.textContent = message;
}

/**
 * Get CSRF token from session
 */
function getCsrfToken() {
    // Try to get from meta tag first
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) return metaTag.content;

    // Fallback: try to get from cookie
    const match = document.cookie.match(/csrf_token=([^;]+)/);
    return match ? match[1] : '';
}

/**
 * Sleep helper
 */
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Monitor run progress
 */
async function monitorRunProgress(runId) {
    const checkProgress = async () => {
        try {
            const response = await fetch(`/api/run/status?run_id=${runId}`);
            const data = await response.json();

            if (data.success) {
                console.log(`Run ${runId} progress: ${data.progress}%`);

                if (data.run.status === 'completed') {
                    alert('Data collection completed!');
                    window.location.reload();
                    return;
                } else if (data.run.status === 'failed') {
                    alert('Data collection failed!');
                    return;
                }

                // Check again in 10 seconds
                setTimeout(checkProgress, 10000);
            }
        } catch (error) {
            console.error('Error monitoring progress:', error);
        }
    };

    checkProgress();
}

/**
 * Generate report
 */
async function generateReport() {
    const runId = prompt('Enter monitoring run ID (leave empty for latest):');

    if (runId === null) {
        return; // User canceled
    }

    const reportType = prompt('Report type (weekly/monthly/final):', 'weekly');

    if (!reportType) {
        return;
    }

    try {
        const response = await fetch('/api/report/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                run_id: runId || null,
                report_type: reportType
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Report generated successfully!');
            window.location.href = '/reports';
        } else {
            alert('Failed to generate report: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error generating report:', error);
        alert('Error generating report. Check console for details.');
    }
}

/**
 * Refresh dashboard data
 */
async function refreshDashboard(runId = null) {
    try {
        const url = runId ? `/api/dashboard?run_id=${runId}` : '/api/dashboard';
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            // Update dashboard with new data
            // This would require more complex DOM manipulation
            console.log('Dashboard data refreshed:', result.data);
        }
    } catch (error) {
        console.error('Error refreshing dashboard:', error);
    }
}

/**
 * Load topics data
 */
async function loadTopics() {
    try {
        const response = await fetch('/api/topics');
        const data = await response.json();

        if (data.success) {
            return data.topics;
        }
    } catch (error) {
        console.error('Error loading topics:', error);
    }
    return [];
}

/**
 * Load sources data
 */
async function loadSources(officialOnly = false) {
    try {
        const url = officialOnly ? '/api/sources?official=1' : '/api/sources';
        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            return data.sources;
        }
    } catch (error) {
        console.error('Error loading sources:', error);
    }
    return [];
}

/**
 * Update source metrics
 */
async function updateSourceMetrics(sourceId, metrics) {
    try {
        const response = await fetch('/api/source/metrics', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                source_id: sourceId,
                metrics: metrics
            })
        });

        const data = await response.json();
        return data.success;
    } catch (error) {
        console.error('Error updating source metrics:', error);
        return false;
    }
}

// Auto-refresh dashboard every 30 seconds if monitoring run is active
document.addEventListener('DOMContentLoaded', () => {
    const runStatus = document.querySelector('.status-running');
    if (runStatus) {
        setInterval(() => {
            console.log('Auto-refreshing dashboard...');
            window.location.reload();
        }, 30000); // 30 seconds
    }
});
