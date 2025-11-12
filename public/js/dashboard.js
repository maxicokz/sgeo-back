// SGEO Analytics Dashboard JavaScript

/**
 * Start data collection
 */
async function startCollection() {
    if (!confirm('Start a new data collection run? This may take several minutes.')) {
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
            alert(`Data collection started! Run ID: ${data.run_id}`);

            // Monitor run progress
            monitorRunProgress(data.run_id);
        } else {
            alert('Failed to start data collection: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error starting collection:', error);
        alert('Error starting data collection. Check console for details.');
    }
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
