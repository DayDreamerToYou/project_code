/**
 * ============================================
 * Fisheries Data Management System - JavaScript
 * Description: Handles multi-table browsing for seafood database
 * ============================================
 */

// ==================== Multi-language Support ====================
const translations = {
    en: {
        // System Title
        systemTitle: 'Fisheries Data Management System',
        systemSubtitle: 'Seafood Business Data Query System',
        systemTitleShort: 'Fisheries Data Management System',

        // Login Form
        username: 'Username',
        password: 'Password',
        usernamePlaceholder: 'Enter username',
        passwordPlaceholder: 'Enter password',
        login: 'Login',
        testAccount: 'Test Account:',
        testUsername: 'Username: <strong>admin</strong> or <strong>testuser</strong>',
        testPassword: 'Password: <strong>123456</strong>',

        // Navigation
        welcome: 'Welcome',
        logout: 'Logout',
        switchLanguage: '中文',

        // Toolbar
        dataTable: 'Data Table:',
        exportExcel: 'Export Excel',
        exportSelected: 'Export Selected ({count})',
        exportAll: 'Export All',
        clearSelection: 'Clear Selection',
        selectAll: 'Select All',
        deselectAll: 'Deselect All',
        search: 'Search',
        searchPlaceholder: 'Search...',
        refresh: 'Refresh',
        selectTable: 'Please select a data table',
        loading: 'Loading...',
        noData: 'No data',
        noRecordsSelected: 'No records selected',

        // Pagination
        totalRecords: 'Total {count} records',
        pageOf: 'Page {current} / {total} pages',
        firstPage: 'First',
        prevPage: 'Previous',
        nextPage: 'Next',
        lastPage: 'Last',

        // Form
        addRecord: 'Add Record',
        editRecord: 'Edit Record',
        name: 'Name',
        email: 'Email',
        phone: 'Phone',
        department: 'Department',
        status: 'Status',
        enabled: 'Enabled',
        disabled: 'Disabled',
        cancel: 'Cancel',
        save: 'Save',
        namePlaceholder: 'Enter name',
        emailPlaceholder: 'Enter email',
        phonePlaceholder: 'Enter phone',
        departmentPlaceholder: 'Enter department',

        // Delete Confirmation
        confirmDelete: 'Confirm Delete',
        confirmDeleteMessage: 'Are you sure you want to delete this record? This action cannot be undone.',
        delete: 'Delete',

        // Messages
        loginSuccess: 'Login successful',
        logoutSuccess: 'Logged out successfully',
        logoutFailed: 'Logout failed, please try again',
        loginFailed: 'Login failed',
        networkError: 'Network error, please check connection',
        loadTableFailed: 'Failed to load table list',
        loadDataFailed: 'Failed to load data',
        noDataToExport: 'No data to export',
        exportSuccess: 'Exported {count} records',
        exportFailed: 'Export failed, please try again',
        confirmLogout: 'Are you sure you want to logout?',

        // Table Headers
        serialNumber: 'No.',
        pleaseSelect: 'Please select data table',
        actions: 'Actions',
        edit: 'Edit',
        delete: 'Delete',

        // Select Options
        loadingTables: 'Loading...',

        // CRUD Messages
        addSuccess: 'Record added successfully',
        editSuccess: 'Record updated successfully',
        deleteSuccess: 'Record deleted successfully',
        saveFailed: 'Failed to save record',
        addFailed: 'Failed to add record',
        editFailed: 'Failed to update record',
        deleteFailed: 'Failed to delete record',
        confirmDeleteRecord: 'Are you sure you want to delete this record? This action cannot be undone.'
    },
    zh: {
        // System Title
        systemTitle: '渔业数据管理系统',
        systemSubtitle: '海鲜业务数据查询系统',
        systemTitleShort: '渔业数据管理系统',

        // Login Form
        username: '用户名',
        password: '密码',
        usernamePlaceholder: '请输入用户名',
        passwordPlaceholder: '请输入密码',
        login: '登录',
        testAccount: '测试账号：',
        testUsername: '用户名：<strong>admin</strong> 或 <strong>testuser</strong>',
        testPassword: '密码：<strong>123456</strong>',

        // Navigation
        welcome: '欢迎访问',
        logout: '退出登录',
        switchLanguage: 'English',

        // Toolbar
        dataTable: '数据表：',
        exportExcel: '导出Excel',
        exportSelected: '导出选中 ({count})',
        exportAll: '导出全部',
        clearSelection: '清除选择',
        selectAll: '全选',
        deselectAll: '取消全选',
        search: '搜索',
        searchPlaceholder: '搜索...',
        refresh: '刷新',
        selectTable: '请选择数据表',
        loading: '加载中...',
        noData: '暂无数据',
        noRecordsSelected: '未选择任何记录',

        // Pagination
        totalRecords: '共 {count} 条记录',
        pageOf: '第 {current} / {total} 页',
        firstPage: '首页',
        prevPage: '上一页',
        nextPage: '下一页',
        lastPage: '末页',

        // Form
        addRecord: '新增记录',
        editRecord: '编辑记录',
        name: '姓名',
        email: '邮箱',
        phone: '电话',
        department: '部门',
        status: '状态',
        enabled: '启用',
        disabled: '禁用',
        cancel: '取消',
        save: '保存',
        namePlaceholder: '请输入姓名',
        emailPlaceholder: '请输入邮箱',
        phonePlaceholder: '请输入电话',
        departmentPlaceholder: '请输入部门',

        // Delete Confirmation
        confirmDelete: '确认删除',
        confirmDeleteMessage: '确定要删除这条记录吗？此操作不可恢复。',
        delete: '删除',

        // Messages
        loginSuccess: '登录成功',
        logoutSuccess: '已退出登录',
        logoutFailed: '退出失败，请重试',
        loginFailed: '登录失败',
        networkError: '网络错误，请检查连接',
        loadTableFailed: '加载表列表失败',
        loadDataFailed: '加载数据失败',
        noDataToExport: '没有数据可导出',
        exportSuccess: '已导出 {count} 条数据',
        exportFailed: '导出失败，请重试',
        confirmLogout: '确定要退出登录吗？',

        // Table Headers
        serialNumber: '序号',
        pleaseSelect: '请选择数据表',
        actions: '操作',
        edit: '编辑',
        delete: '删除',

        // Select Options
        loadingTables: '加载中...',

        // CRUD Messages
        addSuccess: '记录添加成功',
        editSuccess: '记录更新成功',
        deleteSuccess: '记录删除成功',
        saveFailed: '保存失败',
        addFailed: '添加失败',
        editFailed: '更新失败',
        deleteFailed: '删除失败',
        confirmDeleteRecord: '确定要删除这条记录吗？此操作不可恢复。'
    }
};

// Current language (default: English)
let currentLanguage = 'en';

// ==================== Global Variables ====================
let currentPage = 1;
let pageSize = 20;
let totalPages = 1;
let totalRecords = 0;
let currentSearch = '';
let currentTable = '';
let tableList = [];
let allData = [];
let selectedRecords = new Set(); // 存储选中记录的唯一标识
let isAllSelected = false; // 是否全选状态
let editingRecord = null; // 当前正在编辑的记录（null表示新增）
let tableColumns = []; // 当前表的字段信息

// ==================== Page Load Complete ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Fisheries App initialized');

    // Initialize language
    initLanguage();

    // Check login status
    checkLoginStatus();

    // Bind login form submit event
    document.getElementById('login-form').addEventListener('submit', handleLogin);

    // Bind logout button event
    document.getElementById('logout-btn').addEventListener('click', handleLogout);

    // Bind language toggle button event
    document.getElementById('lang-toggle-btn').addEventListener('click', toggleLanguage);

    // Bind add record button
    document.getElementById('add-record-btn').addEventListener('click', openAddModal);

    // Bind modal events
    document.getElementById('modal-close-btn').addEventListener('click', closeEditModal);
    document.getElementById('modal-cancel-btn').addEventListener('click', closeEditModal);
    document.getElementById('edit-form').addEventListener('submit', handleSaveRecord);
    document.getElementById('confirm-close-btn').addEventListener('click', closeConfirmModal);
    document.getElementById('confirm-cancel-btn').addEventListener('click', closeConfirmModal);
    document.getElementById('confirm-delete-btn').addEventListener('click', handleDeleteRecord);

    // Bind toolbar button events
    document.getElementById('export-all-btn').addEventListener('click', exportAllToExcel);
    document.getElementById('export-selected-btn').addEventListener('click', exportSelectedToExcel);
    document.getElementById('clear-selection-btn').addEventListener('click', clearSelection);
    document.getElementById('search-btn').addEventListener('click', handleSearch);
    document.getElementById('refresh-btn').addEventListener('click', () => loadData(currentTable));

    // Bind table selector event
    document.getElementById('table-select').addEventListener('change', function(e) {
        currentTable = e.target.value;
        if (currentTable) {
            currentPage = 1;
            currentSearch = '';
            document.getElementById('search-input').value = '';
            loadData(currentTable);
        }
    });

    // Bind search enter event
    document.getElementById('search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleSearch();
        }
    });

    // Bind pagination button events
    document.getElementById('first-page-btn').addEventListener('click', () => goToPage(1));
    document.getElementById('prev-page-btn').addEventListener('click', () => goToPage(currentPage - 1));
    document.getElementById('next-page-btn').addEventListener('click', () => goToPage(currentPage + 1));
    document.getElementById('last-page-btn').addEventListener('click', () => goToPage(totalPages));
});

/**
 * Initialize language
 */
function initLanguage() {
    // Check if language is saved in localStorage
    const savedLang = localStorage.getItem('appLanguage');
    if (savedLang && (savedLang === 'en' || savedLang === 'zh')) {
        currentLanguage = savedLang;
    }

    // Apply language to page
    applyLanguage(currentLanguage);
}

/**
 * Toggle language
 */
function toggleLanguage() {
    currentLanguage = currentLanguage === 'en' ? 'zh' : 'en';
    localStorage.setItem('appLanguage', currentLanguage);
    applyLanguage(currentLanguage);
}

/**
 * Apply language to page
 */
function applyLanguage(lang) {
    const body = document.body;
    body.className = `lang-${lang}`;
    document.documentElement.lang = lang;

    // Update page title
    document.title = translations[lang].systemTitle;

    // Update all elements with data-i18n attribute
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        const translation = translations[lang][key];
        if (translation) {
            element.innerHTML = translation;
        }
    });

    // Update all placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
        const key = element.getAttribute('data-i18n-placeholder');
        const translation = translations[lang][key];
        if (translation) {
            element.placeholder = translation;
        }
    });

    // Update dynamic content
    updateDynamicContent();
}

/**
 * Get translation by key
 */
function t(key, params = {}) {
    let translation = translations[currentLanguage][key] || translations['en'][key] || key;

    // Replace parameters
    Object.keys(params).forEach(param => {
        translation = translation.replace(`{${param}}`, params[param]);
    });

    return translation;
}

/**
 * Update dynamic content after language change
 */
function updateDynamicContent() {
    // Update pagination
    if (totalRecords > 0) {
        document.getElementById('pagination-info').textContent = t('totalRecords', { count: totalRecords });
        document.getElementById('page-info').textContent = t('pageOf', { current: currentPage, total: totalPages });
    }

    // Update table select placeholder
    const select = document.getElementById('table-select');
    if (!currentTable) {
        select.innerHTML = `<option value="">${t('pleaseSelect')}</option>`;
        tableList.forEach(table => {
            const option = document.createElement('option');
            option.value = table.name;
            option.textContent = `${table.icon} ${table.label}`;
            select.appendChild(option);
        });
    }

    // Update table no-data message
    if (!currentTable) {
        const tbody = document.getElementById('table-body');
        tbody.innerHTML = `<tr class="no-data"><td>${t('selectTable')}</td></tr>`;
    }
}

/**
 * Check login status
 */
function checkLoginStatus() {
    fetch('../api/seafood.php', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMainSection();
            loadTableList();
        } else {
            showLoginSection();
        }
    })
    .catch(error => {
        console.error('Check login status failed:', error);
        showLoginSection();
    });
}

/**
 * Handle login
 */
function handleLogin(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = {
        username: formData.get('username'),
        password: formData.get('password')
    };

    hideError('login-error');
    showLoading();

    fetch('../api/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast(t('loginSuccess'), 'success');
            showMainSection();
            loadTableList();
            e.target.reset();
        } else {
            showError('login-error', result.message || t('loginFailed'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Login failed:', error);
        showError('login-error', t('networkError'));
    });
}

/**
 * Handle logout
 */
function handleLogout() {
    if (!confirm(t('confirmLogout'))) {
        return;
    }

    showLoading();
    fetch('../api/logout.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast(t('logoutSuccess'), 'info');
            showLoginSection();
        } else {
            showToast(t('logoutFailed'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Logout failed:', error);
        showToast(t('networkError'), 'error');
    });
}

/**
 * Show login section
 */
function showLoginSection() {
    document.getElementById('login-section').style.display = 'flex';
    document.getElementById('main-section').style.display = 'none';
}

/**
 * Show main section
 */
function showMainSection() {
    document.getElementById('login-section').style.display = 'none';
    document.getElementById('main-section').style.display = 'flex';
}

/**
 * Load table list
 */
function loadTableList() {
    showLoading();

    fetch('../api/seafood.php')
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success && result.tables) {
            tableList = result.tables;
            renderTableSelect(tableList);
        } else {
            showToast(t('loadTableFailed'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Load table list failed:', error);
        showToast(t('networkError'), 'error');
    });
}

/**
 * Render table selector
 */
function renderTableSelect(tables) {
    const select = document.getElementById('table-select');
    select.innerHTML = `<option value="">${t('pleaseSelect')}</option>`;

    tables.forEach(table => {
        const option = document.createElement('option');
        option.value = table.name;
        option.textContent = `${table.icon} ${table.label}`;
        select.appendChild(option);
    });
}

/**
 * Load table data
 */
function loadData(tableName) {
    if (!tableName) {
        return;
    }

    // 切换表时清除之前的选择
    clearSelection();

    showLoading();

    const params = new URLSearchParams({
        table: tableName,
        page: currentPage,
        pageSize: pageSize,
        search: currentSearch
    });

    fetch(`../api/seafood.php?${params}`)
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            allData = result.data;
            renderTableHeader(result.data);
            renderTableData(result.data);
            renderPagination(result.pagination);
        } else {
            showToast(result.message || t('loadDataFailed'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Load data failed:', error);
        showToast(t('networkError'), 'error');
    });
}

/**
 * Render table header
 */
function renderTableHeader(data) {
    const thead = document.getElementById('table-head');
    thead.innerHTML = '';

    if (!data || data.length === 0) {
        return;
    }

    // Get all field names
    tableColumns = Object.keys(data[0]);

    // Create header row
    const tr = document.createElement('tr');

    // Add checkbox column
    const thCheckbox = document.createElement('th');
    thCheckbox.width = '5%';
    thCheckbox.style.textAlign = 'center';

    // Add select all checkbox
    const selectAllCheckbox = document.createElement('input');
    selectAllCheckbox.type = 'checkbox';
    selectAllCheckbox.id = 'select-all-checkbox';
    selectAllCheckbox.checked = isAllSelected;
    selectAllCheckbox.addEventListener('change', handleSelectAll);
    thCheckbox.appendChild(selectAllCheckbox);
    tr.appendChild(thCheckbox);

    // Add serial number column
    const thIndex = document.createElement('th');
    thIndex.textContent = t('serialNumber');
    thIndex.width = '5%';
    tr.appendChild(thIndex);

    // Add data columns
    tableColumns.forEach(column => {
        const th = document.createElement('th');
        th.textContent = column;
        th.style.whiteSpace = 'nowrap';
        tr.appendChild(th);
    });

    // Add actions column
    const thActions = document.createElement('th');
    thActions.textContent = t('actions');
    thActions.width = '10%';
    thActions.style.textAlign = 'center';
    tr.appendChild(thActions);

    thead.appendChild(tr);
}

/**
 * Render table data
 */
function renderTableData(data) {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';

    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr class="no-data"><td colspan="100%">${t('noData')}</td></tr>`;
        return;
    }

    data.forEach((item, index) => {
        const tr = document.createElement('tr');

        // 为每行创建唯一标识符（使用所有字段值的组合）
        const recordId = JSON.stringify(item);

        // Add checkbox
        const tdCheckbox = document.createElement('td');
        tdCheckbox.style.textAlign = 'center';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'row-checkbox';
        checkbox.dataset.recordId = recordId;
        checkbox.dataset.recordData = JSON.stringify(item);
        checkbox.checked = selectedRecords.has(recordId);
        checkbox.addEventListener('change', handleRowSelect);
        tdCheckbox.appendChild(checkbox);
        tr.appendChild(tdCheckbox);

        // Serial number
        const tdIndex = document.createElement('td');
        tdIndex.textContent = (currentPage - 1) * pageSize + index + 1;
        tr.appendChild(tdIndex);

        // Data columns
        Object.values(item).forEach(value => {
            const td = document.createElement('td');
            td.textContent = formatCellValue(value);
            tr.appendChild(td);
        });

        // Actions column
        const tdActions = document.createElement('td');
        tdActions.style.textAlign = 'center';

        const actionButtons = document.createElement('div');
        actionButtons.className = 'action-buttons';
        actionButtons.style.justifyContent = 'center';

        // Edit button
        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'btn btn-sm btn-primary';
        editBtn.textContent = t('edit');
        editBtn.addEventListener('click', () => openEditModal(item));
        actionButtons.appendChild(editBtn);

        // Delete button
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn btn-sm btn-danger';
        deleteBtn.textContent = t('delete');
        deleteBtn.addEventListener('click', () => openConfirmModal(item));
        actionButtons.appendChild(deleteBtn);

        tdActions.appendChild(actionButtons);
        tr.appendChild(tdActions);

        tbody.appendChild(tr);
    });
}

/**
 * Format cell value
 */
function formatCellValue(value) {
    if (value === null || value === undefined) {
        return '-';
    }
    if (typeof value === 'boolean') {
        return currentLanguage === 'zh' ? (value ? '是' : '否') : (value ? 'Yes' : 'No');
    }
    return String(value);
}

/**
 * Render pagination
 */
function renderPagination(pagination) {
    currentPage = pagination.page;
    totalPages = pagination.totalPages;
    totalRecords = pagination.total;

    document.getElementById('pagination-info').textContent = t('totalRecords', { count: totalRecords });
    document.getElementById('page-info').textContent = t('pageOf', { current: currentPage, total: totalPages });

    document.getElementById('first-page-btn').disabled = currentPage === 1;
    document.getElementById('prev-page-btn').disabled = currentPage === 1;
    document.getElementById('next-page-btn').disabled = currentPage === totalPages;
    document.getElementById('last-page-btn').disabled = currentPage === totalPages;
}

/**
 * Go to specified page
 */
function goToPage(page) {
    if (page < 1 || page > totalPages || page === currentPage) {
        return;
    }
    currentPage = page;
    loadData(currentTable);
}

/**
 * Handle search
 */
function handleSearch() {
    currentSearch = document.getElementById('search-input').value.trim();
    currentPage = 1;
    loadData(currentTable);
}

/**
 * Export Excel
 */
function exportToExcel() {
    if (typeof XLSX === 'undefined') {
        showToast('Excel export library not loaded, please refresh the page', 'error');
        return;
    }

    if (!allData || allData.length === 0) {
        showToast(t('noDataToExport'), 'warning');
        return;
    }

    try {
        // Create workbook
        const ws = XLSX.utils.json_to_sheet(allData);

        // Set column widths
        const columns = Object.keys(allData[0]);
        ws['!cols'] = columns.map(() => ({ wch: 15 }));

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, currentTable);

        // Generate filename
        const timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        const fullFilename = `${currentTable}_${timestamp}.xlsx`;

        // Export download
        XLSX.writeFile(wb, fullFilename);

        showToast(t('exportSuccess', { count: allData.length }), 'success');
    } catch (error) {
        console.error('Export failed:', error);
        showToast(t('exportFailed'), 'error');
    }
}

/**
 * Show loading indicator
 */
function showLoading() {
    document.getElementById('loading').style.display = 'flex';
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    document.getElementById('loading').style.display = 'none';
}

/**
 * Show error message
 */
function showError(elementId, message) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.classList.add('show');
}

/**
 * Hide error message
 */
function hideError(elementId) {
    const element = document.getElementById(elementId);
    element.textContent = '';
    element.classList.remove('show');
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');

    toastMessage.textContent = message;
    toast.className = 'toast ' + type;

    toast.style.display = 'block';

    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// ==================== Selection Functions ====================

/**
 * Handle row checkbox select
 */
function handleRowSelect(e) {
    const checkbox = e.target;
    const recordId = checkbox.dataset.recordId;
    const recordData = JSON.parse(checkbox.dataset.recordData);

    if (checkbox.checked) {
        selectedRecords.add(recordId);
    } else {
        selectedRecords.delete(recordId);
        // 如果取消选中某行，同时取消全选状态
        isAllSelected = false;
        const selectAllCheckbox = document.getElementById('select-all-checkbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
    }

    updateSelectionUI();
}

/**
 * Handle select all checkbox
 */
function handleSelectAll(e) {
    const isChecked = e.target.checked;
    isAllSelected = isChecked;

    // 更新当前页的所有复选框
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    rowCheckboxes.forEach(checkbox => {
        const recordId = checkbox.dataset.recordId;
        checkbox.checked = isChecked;

        if (isChecked) {
            selectedRecords.add(recordId);
        } else {
            selectedRecords.delete(recordId);
        }
    });

    updateSelectionUI();
}

/**
 * Clear all selections
 */
function clearSelection() {
    selectedRecords.clear();
    isAllSelected = false;

    // 清除当前页的所有复选框
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    rowCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });

    // 清除全选复选框
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }

    updateSelectionUI();
}

/**
 * Update selection UI (buttons and counters)
 */
function updateSelectionUI() {
    const count = selectedRecords.size;
    const exportSelectedBtn = document.getElementById('export-selected-btn');
    const clearSelectionBtn = document.getElementById('clear-selection-btn');

    // Update export selected button text
    const exportSelectedText = t('exportSelected', { count: count });
    exportSelectedBtn.querySelector('span').innerHTML = exportSelectedText;

    // Show/hide buttons based on selection
    if (count > 0) {
        exportSelectedBtn.style.display = 'inline-block';
        clearSelectionBtn.style.display = 'inline-block';
    } else {
        exportSelectedBtn.style.display = 'none';
        clearSelectionBtn.style.display = 'none';
    }
}

/**
 * Export all data to Excel
 */
function exportAllToExcel() {
    if (typeof XLSX === 'undefined') {
        showToast('Excel export library not loaded, please refresh the page', 'error');
        return;
    }

    if (!allData || allData.length === 0) {
        showToast(t('noDataToExport'), 'warning');
        return;
    }

    try {
        // Create workbook
        const ws = XLSX.utils.json_to_sheet(allData);

        // Set column widths
        const columns = Object.keys(allData[0]);
        ws['!cols'] = columns.map(() => ({ wch: 15 }));

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, currentTable);

        // Generate filename
        const timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        const fullFilename = `${currentTable}_all_${timestamp}.xlsx`;

        // Export download
        XLSX.writeFile(wb, fullFilename);

        showToast(t('exportSuccess', { count: allData.length }), 'success');
    } catch (error) {
        console.error('Export failed:', error);
        showToast(t('exportFailed'), 'error');
    }
}

/**
 * Export selected data to Excel
 */
function exportSelectedToExcel() {
    if (typeof XLSX === 'undefined') {
        showToast('Excel export library not loaded, please refresh the page', 'error');
        return;
    }

    if (selectedRecords.size === 0) {
        showToast(t('noRecordsSelected'), 'warning');
        return;
    }

    try {
        // Collect selected data
        const selectedData = [];
        const rowCheckboxes = document.querySelectorAll('.row-checkbox:checked');

        rowCheckboxes.forEach(checkbox => {
            const recordData = JSON.parse(checkbox.dataset.recordData);
            selectedData.push(recordData);
        });

        if (selectedData.length === 0) {
            showToast(t('noRecordsSelected'), 'warning');
            return;
        }

        // Create workbook
        const ws = XLSX.utils.json_to_sheet(selectedData);

        // Set column widths
        const columns = Object.keys(selectedData[0]);
        ws['!cols'] = columns.map(() => ({ wch: 15 }));

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, currentTable);

        // Generate filename
        const timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        const fullFilename = `${currentTable}_selected_${timestamp}.xlsx`;

        // Export download
        XLSX.writeFile(wb, fullFilename);

        showToast(t('exportSuccess', { count: selectedData.length }), 'success');
    } catch (error) {
        console.error('Export failed:', error);
        showToast(t('exportFailed'), 'error');
    }
}

// ==================== CRUD Functions ====================

/**
 * Open add modal
 */
function openAddModal() {
    if (!currentTable) {
        showToast(t('selectTable'), 'warning');
        return;
    }

    editingRecord = null;
    document.getElementById('modal-title').textContent = t('addRecord');
    generateFormFields(tableColumns, {});
    hideError('edit-error');
    document.getElementById('edit-modal').style.display = 'flex';
}

/**
 * Open edit modal
 */
function openEditModal(record) {
    editingRecord = record;
    document.getElementById('modal-title').textContent = t('editRecord');
    generateFormFields(tableColumns, record);
    hideError('edit-error');
    document.getElementById('edit-modal').style.display = 'flex';
}

/**
 * Generate form fields dynamically
 */
function generateFormFields(columns, record) {
    const container = document.getElementById('edit-form-fields');
    container.innerHTML = '';

    columns.forEach(column => {
        const formGroup = document.createElement('div');
        formGroup.className = 'form-group';

        const label = document.createElement('label');
        label.textContent = column;
        formGroup.appendChild(label);

        const value = record[column];

        // Check if it's a boolean field
        if (typeof value === 'boolean') {
            const select = document.createElement('select');
            select.name = column;
            select.required = false;

            const optionTrue = document.createElement('option');
            optionTrue.value = '1';
            optionTrue.textContent = currentLanguage === 'zh' ? '是' : 'Yes';
            if (value === true) optionTrue.selected = true;

            const optionFalse = document.createElement('option');
            optionFalse.value = '0';
            optionFalse.textContent = currentLanguage === 'zh' ? '否' : 'No';
            if (value === false) optionFalse.selected = true;

            select.appendChild(optionTrue);
            select.appendChild(optionFalse);
            formGroup.appendChild(select);
        }
        // Check if it's a numeric field
        else if (typeof value === 'number') {
            const input = document.createElement('input');
            input.type = 'number';
            input.name = column;
            input.value = value || '';
            input.step = 'any';
            input.className = 'form-control';
            formGroup.appendChild(input);
        }
        // Default to text input
        else {
            const input = document.createElement('input');
            input.type = 'text';
            input.name = column;
            input.value = value || '';
            input.className = 'form-control';
            formGroup.appendChild(input);
        }

        container.appendChild(formGroup);
    });
}

/**
 * Close edit modal
 */
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
    document.getElementById('edit-form').reset();
    editingRecord = null;
}

/**
 * Handle save record (add or edit)
 */
function handleSaveRecord(e) {
    e.preventDefault();

    if (!currentTable) {
        showToast(t('selectTable'), 'warning');
        return;
    }

    hideError('edit-error');
    showLoading();

    // Collect form data
    const formData = new FormData(e.target);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    // If editing, include the original record data for identification
    if (editingRecord) {
        data._original = editingRecord;
    }

    // Send request
    fetch('../api/seafood.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: editingRecord ? 'edit' : 'add',
            table: currentTable,
            data: data
        })
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast(editingRecord ? t('editSuccess') : t('addSuccess'), 'success');
            closeEditModal();
            loadData(currentTable);
        } else {
            showError('edit-error', result.message || (editingRecord ? t('editFailed') : t('addFailed')));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Save failed:', error);
        showError('edit-error', t('networkError'));
    });
}

/**
 * Open confirm delete modal
 */
function openConfirmModal(record) {
    editingRecord = record;
    const confirmModal = document.getElementById('confirm-modal');
    confirmModal.querySelector('p').textContent = t('confirmDeleteRecord');
    confirmModal.style.display = 'flex';
}

/**
 * Close confirm modal
 */
function closeConfirmModal() {
    document.getElementById('confirm-modal').style.display = 'none';
    editingRecord = null;
}

/**
 * Handle delete record
 */
function handleDeleteRecord() {
    if (!editingRecord || !currentTable) {
        showToast(t('deleteFailed'), 'error');
        return;
    }

    showLoading();

    // Send delete request
    fetch('../api/seafood.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'delete',
            table: currentTable,
            data: editingRecord
        })
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast(t('deleteSuccess'), 'success');
            closeConfirmModal();
            loadData(currentTable);
        } else {
            showToast(result.message || t('deleteFailed'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Delete failed:', error);
        showToast(t('networkError'), 'error');
    });
}
