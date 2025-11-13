// API Base URL
const API_BASE = '/api';

// State
let availableModels = [];
let selectedModels = [];
let isCollecting = false;

// DOM Elements
const promptInput = document.getElementById('promptInput');
const modelSelection = document.getElementById('modelSelection');
const collectBtn = document.getElementById('collectBtn');
const clearBtn = document.getElementById('clearBtn');
const refreshBtn = document.getElementById('refreshBtn');
const resultsBody = document.getElementById('resultsBody');
const progressSection = document.getElementById('progressSection');
const progressFill = document.getElementById('progressFill');
const progressText = document.getElementById('progressText');
const modelFilter = document.getElementById('modelFilter');
const limitInput = document.getElementById('limitInput');
const detailModal = document.getElementById('detailModal');
const detailContent = document.getElementById('detailContent');
const totalResponses = document.getElementById('totalResponses');
const totalModels = document.getElementById('totalModels');
const totalLanguages = document.getElementById('totalLanguages');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadModels();
    loadResponses();
    loadStatistics();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    // Sample prompts
    document.querySelectorAll('.sample-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            promptInput.value = btn.dataset.prompt;
        });
    });

    // Collect button
    collectBtn.addEventListener('click', handleCollect);

    // Clear button
    clearBtn.addEventListener('click', () => {
        promptInput.value = '';
        selectedModels = [];
        updateModelSelection();
    });

    // Refresh button
    refreshBtn.addEventListener('click', loadResponses);

    // Model filter
    modelFilter.addEventListener('change', loadResponses);

    // Limit input
    limitInput.addEventListener('change', loadResponses);

    // Modal close
    document.querySelector('.modal-close').addEventListener('click', closeModal);
    detailModal.addEventListener('click', (e) => {
        if (e.target === detailModal) closeModal();
    });
}

// Load available models
async function loadModels() {
    try {
        const response = await fetch(`${API_BASE}/models`);
        const data = await response.json();

        if (data.success) {
            availableModels = data.models;
            renderModelSelection();
            populateModelFilter();
        }
    } catch (error) {
        showToast('Ошибка загрузки моделей: ' + error.message, 'error');
    }
}

// Render model selection
function renderModelSelection() {
    modelSelection.innerHTML = '';

    availableModels.forEach(model => {
        const div = document.createElement('div');
        div.className = 'model-checkbox';
        if (selectedModels.includes(model.id)) {
            div.classList.add('selected');
        }

        div.innerHTML = `
            <input type="checkbox"
                   id="model-${model.id}"
                   ${selectedModels.includes(model.id) ? 'checked' : ''}>
            <label for="model-${model.id}">${model.name}</label>
        `;

        div.addEventListener('click', () => {
            toggleModel(model.id);
        });

        modelSelection.appendChild(div);
    });
}

// Toggle model selection
function toggleModel(modelId) {
    const index = selectedModels.indexOf(modelId);
    if (index > -1) {
        selectedModels.splice(index, 1);
    } else {
        selectedModels.push(modelId);
    }
    renderModelSelection();
}

// Update model selection
function updateModelSelection() {
    renderModelSelection();
}

// Populate model filter
function populateModelFilter() {
    modelFilter.innerHTML = '<option value="">Все модели</option>';
    availableModels.forEach(model => {
        const option = document.createElement('option');
        option.value = model.id;
        option.textContent = model.name;
        modelFilter.appendChild(option);
    });
}

// Handle collect
async function handleCollect() {
    const prompt = promptInput.value.trim();

    if (!prompt) {
        showToast('Введите промпт', 'warning');
        return;
    }

    if (selectedModels.length === 0) {
        showToast('Выберите хотя бы одну модель', 'warning');
        return;
    }

    if (isCollecting) {
        return;
    }

    isCollecting = true;
    collectBtn.disabled = true;
    showProgress(0, 'Отправка запросов...');

    try {
        const response = await fetch(`${API_BASE}/collect`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                prompt,
                models: selectedModels,
            }),
        });

        const data = await response.json();

        if (data.success) {
            showProgress(100, 'Готово!');
            showToast(
                `Успешно собрано ${data.results.successful} ответов из ${data.results.total}`,
                'success'
            );

            // Reload data
            await loadResponses();
            await loadStatistics();

            // Clear form
            promptInput.value = '';
            selectedModels = [];
            updateModelSelection();
        } else {
            throw new Error(data.error || 'Неизвестная ошибка');
        }
    } catch (error) {
        showToast('Ошибка: ' + error.message, 'error');
    } finally {
        isCollecting = false;
        collectBtn.disabled = false;
        setTimeout(() => hideProgress(), 2000);
    }
}

// Load responses
async function loadResponses() {
    try {
        const limit = limitInput.value || 20;
        const model = modelFilter.value;

        let url = `${API_BASE}/responses?limit=${limit}`;
        if (model) {
            url += `&modelName=${model}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            renderResponses(data.responses);
        }
    } catch (error) {
        showToast('Ошибка загрузки ответов: ' + error.message, 'error');
    }
}

// Render responses
function renderResponses(responses) {
    if (responses.length === 0) {
        resultsBody.innerHTML = `
            <tr>
                <td colspan="6" class="no-data">Нет данных</td>
            </tr>
        `;
        return;
    }

    resultsBody.innerHTML = responses.map(r => `
        <tr>
            <td>${formatDate(r.createdAt)}</td>
            <td><span class="model-badge">${r.model}</span></td>
            <td class="truncate" title="${escapeHtml(r.prompt)}">${escapeHtml(r.prompt)}</td>
            <td class="truncate" title="${escapeHtml(r.response)}">${escapeHtml(r.response)}</td>
            <td><span class="language-badge">${r.language || 'N/A'}</span></td>
            <td>
                <button class="view-btn" onclick="viewDetails('${r.id}')">Просмотр</button>
            </td>
        </tr>
    `).join('');
}

// View details
async function viewDetails(id) {
    try {
        const response = await fetch(`${API_BASE}/responses/${id}`);
        const data = await response.json();

        if (data.success) {
            showDetailModal(data.response);
        }
    } catch (error) {
        showToast('Ошибка загрузки деталей: ' + error.message, 'error');
    }
}

// Show detail modal
function showDetailModal(response) {
    detailContent.innerHTML = `
        <div class="detail-row">
            <div class="detail-label">Модель</div>
            <div class="detail-value"><span class="model-badge">${response.model}</span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Промпт</div>
            <div class="detail-value">${escapeHtml(response.prompt)}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Ответ</div>
            <div class="detail-value">${escapeHtml(response.response)}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Язык</div>
            <div class="detail-value"><span class="language-badge">${response.language || 'N/A'}</span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Дата создания</div>
            <div class="detail-value">${formatDate(response.createdAt)}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Метаданные</div>
            <div class="detail-value">
                <pre>${JSON.stringify(response.metadata, null, 2)}</pre>
            </div>
        </div>
    `;

    detailModal.classList.add('active');
}

// Close modal
function closeModal() {
    detailModal.classList.remove('active');
}

// Load statistics
async function loadStatistics() {
    try {
        const response = await fetch(`${API_BASE}/statistics`);
        const data = await response.json();

        if (data.success) {
            const stats = data.statistics;
            totalResponses.textContent = stats.total || 0;
            totalModels.textContent = Object.keys(stats.byModel || {}).length;
            totalLanguages.textContent = Object.keys(stats.byLanguage || {}).length;
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

// Show progress
function showProgress(percent, text) {
    progressSection.classList.remove('hidden');
    progressFill.style.width = `${percent}%`;
    progressText.textContent = text;
}

// Hide progress
function hideProgress() {
    progressSection.classList.add('hidden');
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    const container = document.getElementById('toastContainer');
    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Make viewDetails available globally
window.viewDetails = viewDetails;
