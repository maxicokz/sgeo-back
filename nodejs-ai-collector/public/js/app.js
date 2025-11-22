// API Base URL
const API_BASE = '/api';

// State
let availableModels = [];
let selectedModels = [];
let isCollecting = false;
let isBatchMode = false;
let selectedResponses = new Set();

// DOM Elements
const singleModeBtn = document.getElementById('singleModeBtn');
const batchModeBtn = document.getElementById('batchModeBtn');
const singlePromptSection = document.getElementById('singlePromptSection');
const batchPromptSection = document.getElementById('batchPromptSection');
const promptInput = document.getElementById('promptInput');
const batchPromptsInput = document.getElementById('batchPromptsInput');
const modelSelection = document.getElementById('modelSelection');
const selectAllModelsBtn = document.getElementById('selectAllModelsBtn');
const collectBtn = document.getElementById('collectBtn');
const clearBtn = document.getElementById('clearBtn');
const refreshBtn = document.getElementById('refreshBtn');
const exportCsvBtn = document.getElementById('exportCsvBtn');
const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
const clearResultsBtn = document.getElementById('clearResultsBtn');
const selectAllCheckbox = document.getElementById('selectAllCheckbox');
const selectedCount = document.getElementById('selectedCount');
const resultsBody = document.getElementById('resultsBody');
const progressSection = document.getElementById('progressSection');
const progressFill = document.getElementById('progressFill');
const progressText = document.getElementById('progressText');
const modelFilter = document.getElementById('modelFilter');
const limitInput = document.getElementById('limitInput');
const detailModal = document.getElementById('detailModal');
const savePromptsModal = document.getElementById('savePromptsModal');
const detailContent = document.getElementById('detailContent');
const totalResponses = document.getElementById('totalResponses');
const totalModels = document.getElementById('totalModels');

// Saved prompts
const savePromptsBtn = document.getElementById('savePromptsBtn');
const loadPromptsBtn = document.getElementById('loadPromptsBtn');
const savedPromptsList = document.getElementById('savedPromptsList');
const deletePromptSetBtn = document.getElementById('deletePromptSetBtn');
const promptSetName = document.getElementById('promptSetName');
const confirmSavePromptsBtn = document.getElementById('confirmSavePromptsBtn');
const cancelSavePromptsBtn = document.getElementById('cancelSavePromptsBtn');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadModels();
    loadResponses();
    loadStatistics();
    loadSavedPromptsList();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    // Mode toggle
    singleModeBtn.addEventListener('click', () => switchMode('single'));
    batchModeBtn.addEventListener('click', () => switchMode('batch'));

    // Sample prompts
    document.querySelectorAll('.sample-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            promptInput.value = btn.dataset.prompt;
        });
    });

    // Model selection
    selectAllModelsBtn.addEventListener('click', selectAllModels);

    // Collect button
    collectBtn.addEventListener('click', handleCollect);

    // Clear button
    clearBtn.addEventListener('click', () => {
        promptInput.value = '';
        batchPromptsInput.value = '';
        selectedModels = [];
        updateModelSelection();
    });

    // Refresh button
    refreshBtn.addEventListener('click', loadResponses);

    // Export CSV
    exportCsvBtn.addEventListener('click', exportToCSV);

    // Clear all results
    clearResultsBtn.addEventListener('click', clearAllResults);

    // Model filter
    modelFilter.addEventListener('change', loadResponses);

    // Limit input
    limitInput.addEventListener('change', loadResponses);

    // Modal close
    document.querySelectorAll('.modal-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            closeBtn.closest('.modal').classList.remove('active');
        });
    });

    detailModal.addEventListener('click', (e) => {
        if (e.target === detailModal) closeModal();
    });

    // Saved prompts
    savePromptsBtn.addEventListener('click', () => {
        savePromptsModal.classList.add('active');
        promptSetName.value = '';
        promptSetName.focus();
    });

    confirmSavePromptsBtn.addEventListener('click', savePromptSet);
    cancelSavePromptsBtn.addEventListener('click', () => {
        savePromptsModal.classList.remove('active');
    });

    savedPromptsList.addEventListener('change', loadPromptSet);
    deletePromptSetBtn.addEventListener('click', deletePromptSet);

    // Selective deletion
    selectAllCheckbox.addEventListener('change', handleSelectAll);
    deleteSelectedBtn.addEventListener('click', deleteSelected);
}

// Switch mode
function switchMode(mode) {
    isBatchMode = mode === 'batch';

    if (isBatchMode) {
        singleModeBtn.classList.remove('active');
        batchModeBtn.classList.add('active');
        singlePromptSection.classList.add('hidden');
        batchPromptSection.classList.remove('hidden');
    } else {
        batchModeBtn.classList.remove('active');
        singleModeBtn.classList.add('active');
        batchPromptSection.classList.add('hidden');
        singlePromptSection.classList.remove('hidden');
    }
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

// Select all models
function selectAllModels() {
    if (selectedModels.length === availableModels.length) {
        // Deselect all
        selectedModels = [];
    } else {
        // Select all
        selectedModels = availableModels.map(m => m.id);
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
    if (isBatchMode) {
        await handleBatchCollect();
    } else {
        await handleSingleCollect();
    }
}

// Handle single collect
async function handleSingleCollect() {
    const prompt = promptInput.value.trim();

    if (!prompt) {
        showToast('Введите промпт', 'warning');
        return;
    }

    if (selectedModels.length === 0) {
        showToast('Выберите хотя бы одну модель', 'warning');
        return;
    }

    await collectFromModels([prompt]);
}

// Handle batch collect
async function handleBatchCollect() {
    const promptsText = batchPromptsInput.value.trim();

    if (!promptsText) {
        showToast('Введите промпты (один на строку)', 'warning');
        return;
    }

    const prompts = promptsText.split('\n')
        .map(p => p.trim())
        .filter(p => p.length > 0);

    if (prompts.length === 0) {
        showToast('Введите хотя бы один промпт', 'warning');
        return;
    }

    if (selectedModels.length === 0) {
        showToast('Выберите хотя бы одну модель', 'warning');
        return;
    }

    showToast(`Обработка ${prompts.length} промптов с ${selectedModels.length} моделями...`, 'success');
    await collectFromModels(prompts);
}

// Collect from models
async function collectFromModels(prompts) {
    if (isCollecting) {
        return;
    }

    isCollecting = true;
    collectBtn.disabled = true;

    const totalRequests = prompts.length * selectedModels.length;
    let completedRequests = 0;

    showProgress(0, `Обработка 0 из ${totalRequests} запросов...`);

    try {
        for (const prompt of prompts) {
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
                completedRequests += selectedModels.length;
                const progress = (completedRequests / totalRequests) * 100;
                showProgress(progress, `Обработка ${completedRequests} из ${totalRequests} запросов...`);
            } else {
                throw new Error(data.error || 'Неизвестная ошибка');
            }

            // Small delay between prompts
            if (prompts.indexOf(prompt) < prompts.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 500));
            }
        }

        showProgress(100, 'Готово!');
        showToast(`Успешно обработано ${completedRequests} запросов`, 'success');

        // Reload data
        await loadResponses();
        await loadStatistics();

        // Clear form
        promptInput.value = '';
        batchPromptsInput.value = '';
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
    // Clear selected items and reset checkboxes
    selectedResponses.clear();
    updateDeleteButton();
    selectAllCheckbox.checked = false;

    if (responses.length === 0) {
        resultsBody.innerHTML = `
            <tr>
                <td colspan="7" class="no-data">Нет данных</td>
            </tr>
        `;
        return;
    }

    resultsBody.innerHTML = responses.map(r => {
        const sourcesCount = r.sources && Array.isArray(r.sources) ? r.sources.length : 0;
        const sourcesDisplay = sourcesCount > 0
            ? `<span class="sources-badge" title="${sourcesCount} источников">🔗 ${sourcesCount}</span>`
            : '<span class="no-sources">—</span>';

        return `
        <tr>
            <td><input type="checkbox" class="row-checkbox" data-id="${r.id}"></td>
            <td>${formatDate(r.createdAt)}</td>
            <td><span class="model-badge">${r.model}</span></td>
            <td class="truncate" title="${escapeHtml(r.prompt)}">${escapeHtml(r.prompt)}</td>
            <td class="truncate" title="${escapeHtml(r.response)}">${escapeHtml(r.response)}</td>
            <td class="text-center">${sourcesDisplay}</td>
            <td>
                <button class="view-btn" onclick="viewDetails('${r.id}')">Просмотр</button>
            </td>
        </tr>
        `;
    }).join('');

    // Add event listeners to checkboxes
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', handleCheckboxChange);
    });
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
    // Format sources for display
    let sourcesHtml = '<p class="no-data">Нет источников</p>';
    if (response.sources && Array.isArray(response.sources) && response.sources.length > 0) {
        sourcesHtml = '<div class="sources-list">' +
            response.sources.map((source, index) => {
                const domain = extractDomain(source.url);
                return `
                    <div class="source-item">
                        <span class="source-number">[${index + 1}]</span>
                        <a href="${escapeHtml(source.url)}" target="_blank" rel="noopener noreferrer" class="source-link">
                            ${escapeHtml(source.title || source.url)}
                        </a>
                        <span class="source-domain">(${escapeHtml(domain)})</span>
                    </div>
                `;
            }).join('') +
            '</div>';
    }

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
            <div class="detail-value response-text">${escapeHtml(response.response)}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Источники</div>
            <div class="detail-value">${sourcesHtml}</div>
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

// Helper function to extract domain from URL
function extractDomain(url) {
    try {
        const urlObj = new URL(url);
        return urlObj.hostname.replace('www.', '');
    } catch (error) {
        return 'unknown';
    }
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
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

// Export to CSV
async function exportToCSV() {
    try {
        const limit = limitInput.value || 1000;
        const model = modelFilter.value;

        let url = `${API_BASE}/export/csv?limit=${limit}`;
        if (model) {
            url += `&modelName=${model}`;
        }

        window.location.href = url;
        showToast('Экспорт начат...', 'success');
    } catch (error) {
        showToast('Ошибка экспорта: ' + error.message, 'error');
    }
}

// Clear all results
async function clearAllResults() {
    if (!confirm('Вы уверены, что хотите удалить ВСЕ результаты? Это действие необратимо!')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/responses`, {
            method: 'DELETE',
        });

        const data = await response.json();

        if (data.success) {
            showToast('Все результаты удалены', 'success');
            await loadResponses();
            await loadStatistics();
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        showToast('Ошибка при удалении: ' + error.message, 'error');
    }
}

// Handle checkbox change
function handleCheckboxChange(e) {
    const id = e.target.dataset.id;
    if (e.target.checked) {
        selectedResponses.add(id);
    } else {
        selectedResponses.delete(id);
    }
    updateDeleteButton();
    updateSelectAllCheckbox();
}

// Handle select all
function handleSelectAll(e) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = e.target.checked;
        const id = cb.dataset.id;
        if (e.target.checked) {
            selectedResponses.add(id);
        } else {
            selectedResponses.delete(id);
        }
    });
    updateDeleteButton();
}

// Update delete button visibility
function updateDeleteButton() {
    selectedCount.textContent = selectedResponses.size;
    if (selectedResponses.size > 0) {
        deleteSelectedBtn.classList.remove('hidden');
    } else {
        deleteSelectedBtn.classList.add('hidden');
    }
}

// Update select all checkbox state
function updateSelectAllCheckbox() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    if (checkboxes.length === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        return;
    }

    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;

    if (checkedCount === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedCount === checkboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

// Delete selected responses
async function deleteSelected() {
    if (selectedResponses.size === 0) return;

    if (!confirm(`Вы уверены, что хотите удалить ${selectedResponses.size} выбранных записей?`)) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/responses/delete-multiple`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: Array.from(selectedResponses) }),
        });

        const data = await response.json();

        if (data.success) {
            showToast(`Удалено ${data.count} записей`, 'success');
            selectedResponses.clear();
            await loadResponses();
            await loadStatistics();
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        showToast('Ошибка при удалении: ' + error.message, 'error');
    }
}

// Saved prompts management (using API instead of localStorage)
async function loadSavedPromptsList() {
    try {
        const response = await fetch(`${API_BASE}/prompt-sets`);
        const data = await response.json();

        savedPromptsList.innerHTML = '<option value="">-- Выберите набор --</option>';

        if (data.success && data.promptSets) {
            data.promptSets.forEach(set => {
                const option = document.createElement('option');
                option.value = set.id;
                option.textContent = set.name;
                option.dataset.prompts = JSON.stringify(set.prompts);
                savedPromptsList.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading prompt sets:', error);
        showToast('Ошибка загрузки наборов промптов', 'error');
    }
}

async function savePromptSet() {
    const name = promptSetName.value.trim();
    const promptsText = batchPromptsInput.value.trim();

    if (!name) {
        showToast('Введите название набора', 'warning');
        return;
    }

    if (!promptsText) {
        showToast('Введите промпты для сохранения', 'warning');
        return;
    }

    const prompts = promptsText.split('\n').map(p => p.trim()).filter(p => p.length > 0);

    try {
        const response = await fetch(`${API_BASE}/prompt-sets`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name, prompts }),
        });

        const data = await response.json();

        if (data.success) {
            showToast(`Набор "${name}" сохранен`, 'success');
            await loadSavedPromptsList();
            savePromptsModal.classList.remove('active');
            promptSetName.value = '';
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        showToast('Ошибка: ' + error.message, 'error');
    }
}

function loadPromptSet() {
    const selectedOption = savedPromptsList.options[savedPromptsList.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;

    try {
        const prompts = JSON.parse(selectedOption.dataset.prompts || '[]');
        if (prompts && prompts.length > 0) {
            batchPromptsInput.value = prompts.join('\n');
            showToast(`Набор "${selectedOption.textContent}" загружен`, 'success');
        }
    } catch (error) {
        console.error('Error loading prompt set:', error);
        showToast('Ошибка загрузки набора', 'error');
    }
}

async function deletePromptSet() {
    const selectedOption = savedPromptsList.options[savedPromptsList.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
        showToast('Выберите набор для удаления', 'warning');
        return;
    }

    const id = selectedOption.value;
    const name = selectedOption.textContent;

    if (!confirm(`Удалить набор "${name}"?`)) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/prompt-sets/${id}`, {
            method: 'DELETE',
        });

        const data = await response.json();

        if (data.success) {
            showToast(`Набор "${name}" удален`, 'success');
            await loadSavedPromptsList();
            savedPromptsList.value = '';
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        showToast('Ошибка при удалении: ' + error.message, 'error');
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
