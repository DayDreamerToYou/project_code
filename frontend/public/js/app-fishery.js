/**
 * ============================================
 * 渔业数据管理系统 - JavaScript
 * 说明：处理登录、模块切换、数据操作等所有前端交互
 * ============================================
 */

// ==================== 全局变量 ====================
let currentModule = 'landing';  // 当前模块
let currentPage = 1;            // 当前页码
let pageSize = 10;              // 每页显示数量
let totalPages = 1;             // 总页数
let totalRecords = 0;           // 总记录数
let currentSearch = '';         // 当前搜索关键词
let deleteId = null;            // 待删除的记录ID
let deleteModule = null;        // 待删除的记录所属模块

// 各模块数据
let moduleData = {
    landing: [],
    purchase: [],
    sales: []
};

// ==================== 页面加载完成 ====================
document.addEventListener('DOMContentLoaded', function() {
    // 检查登录状态
    checkLoginStatus();

    // 绑定登录表单提交事件
    document.getElementById('login-form').addEventListener('submit', handleLogin);

    // 绑定登出按钮事件
    document.getElementById('logout-btn').addEventListener('click', handleLogout);

    // 绑定弹窗关闭按钮事件
    document.getElementById('modal-close-btn').addEventListener('click', closeEditModal);
    document.getElementById('modal-cancel-btn').addEventListener('click', closeEditModal);
    document.getElementById('confirm-close-btn').addEventListener('click', closeConfirmModal);
    document.getElementById('confirm-cancel-btn').addEventListener('click', closeConfirmModal);
    document.getElementById('confirm-delete-btn').addEventListener('click', confirmDelete);

    // 绑定编辑表单提交事件
    document.getElementById('edit-form').addEventListener('submit', handleSave);

    // 点击弹窗背景关闭弹窗
    document.getElementById('edit-modal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    document.getElementById('confirm-modal').addEventListener('click', function(e) {
        if (e.target === this) closeConfirmModal();
    });

    // 绑定各模块按钮事件
    bindModuleEvents('landing');
    bindModuleEvents('purchase');
    bindModuleEvents('sales');
});

/**
 * 绑定模块事件
 */
function bindModuleEvents(module) {
    // 新增按钮
    const addBtn = document.getElementById(`add-${module}-btn`);
    if (addBtn) {
        addBtn.addEventListener('click', () => openAddModal(module));
    }

    // 搜索按钮
    const searchBtn = document.getElementById(`search-${module}-btn`);
    if (searchBtn) {
        searchBtn.addEventListener('click', () => handleSearch(module));
    }

    // 搜索输入框回车事件
    const searchInput = document.getElementById(`search-${module}`);
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') handleSearch(module);
        });
    }

    // 刷新按钮
    const refreshBtn = document.getElementById(`refresh-${module}-btn`);
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => loadModuleData(module));
    }

    // 导出按钮
    const exportBtn = document.getElementById(`export-${module}-btn`);
    if (exportBtn) {
        exportBtn.addEventListener('click', () => exportModuleData(module));
    }

    // 全选复选框
    const selectAllCheckbox = document.getElementById(`select-all-${module}`);
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => handleSelectAll(module, e));
    }

    // 分页按钮
    document.getElementById(`${module}-first-page`).addEventListener('click', () => goToPage(module, 1));
    document.getElementById(`${module}-prev-page`).addEventListener('click', () => goToPage(module, currentPage - 1));
    document.getElementById(`${module}-next-page`).addEventListener('click', () => goToPage(module, currentPage + 1));
    document.getElementById(`${module}-last-page`).addEventListener('click', () => goToPage(module, totalPages));
}

/**
 * ==================== 登录相关 ====================
 */

/**
 * 检查登录状态
 */
function checkLoginStatus() {
    fetch('/api/landings', { method: 'GET' })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.logged_in !== false) {
            showMainSection();
            loadModuleData('landing');
        } else {
            showLoginSection();
        }
    })
    .catch(error => {
        console.error('检查登录状态失败:', error);
        showLoginSection();
    });
}

/**
 * 处理登录
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

    fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast('登录成功', 'success');
            showMainSection();
            loadModuleData('landing');
            e.target.reset();
        } else {
            showError('login-error', result.message || '用户名或密码错误');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('登录失败:', error);
        showError('login-error', '网络错误，请检查连接');
    });
}

/**
 * 处理登出
 */
function handleLogout() {
    if (!confirm('确定要退出登录吗？')) return;

    showLoading();
    fetch('/api/logout', { method: 'POST' })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast('已退出登录', 'info');
            showLoginSection();
        } else {
            showToast('退出失败，请重试', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('网络错误', 'error');
    });
}

/**
 * 显示登录界面
 */
function showLoginSection() {
    document.getElementById('login-section').style.display = 'flex';
    document.getElementById('main-section').style.display = 'none';
}

/**
 * 显示主界面
 */
function showMainSection() {
    document.getElementById('login-section').style.display = 'none';
    document.getElementById('main-section').style.display = 'flex';
}

/**
 * ==================== 模块切换 ====================
 */

/**
 * 切换模块
 */
function switchModule(module) {
    // 更新导航栏状态
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.module === module) {
            item.classList.add('active');
        }
    });

    // 更新标题
    const titles = {
        'landing': '上岸记录',
        'purchase': '采购记录',
        'sales': '销售记录',
        'other': '其他'
    };
    document.getElementById('module-title').textContent = titles[module] || '未知模块';

    // 切换内容区域
    document.querySelectorAll('.module-content').forEach(content => {
        content.classList.remove('active');
    });
    const targetModule = document.getElementById(`module-${module}`);
    if (targetModule) {
        targetModule.classList.add('active');
    }

    // 更新当前模块并加载数据
    currentModule = module;
    if (module !== 'other') {
        currentPage = 1;
        loadModuleData(module);
    }
}

/**
 * ==================== 数据加载 ====================
 */

/**
 * 加载模块数据
 */
function loadModuleData(module) {
    if (module === 'other') return;

    showLoading();
    currentModule = module;

    const apiMap = {
        'landing': '/api/landings',
        'purchase': '/api/purchases',
        'sales': '/api/sales'
    };

    const params = new URLSearchParams({
        page: currentPage,
        pageSize: pageSize,
        search: currentSearch
    });

    fetch(`${apiMap[module]}?${params}`)
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            moduleData[module] = result.data;
            renderModuleTable(module, result.data);
            renderModulePagination(module, result.pagination);
        } else {
            showToast(result.message || '加载数据失败', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('加载数据失败:', error);
        showToast('网络错误，请检查连接', 'error');
    });
}

/**
 * 渲染模块表格
 */
function renderModuleTable(module, data) {
    const tbody = document.getElementById(`${module}-table-body`);
    tbody.innerHTML = '';

    if (!data || data.length === 0) {
        const colCount = module === 'landing' ? 11 : (module === 'purchase' ? 12 : 13);
        tbody.innerHTML = `<tr class="no-data"><td colspan="${colCount}">暂无数据</td></tr>`;
        return;
    }

    data.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = buildTableRow(module, item);
        tbody.appendChild(tr);
    });
}

/**
 * 构建表格行
 */
function buildTableRow(module, item) {
    const commonStart = `
        <td><input type="checkbox" class="row-checkbox" data-module="${module}" data-id="${item.id}"></td>
        <td>${item.id}</td>
    `;

    let content = '';
    let actions = `
        <td>
            <div class="action-buttons">
                <button class="btn btn-secondary btn-sm" onclick="openEditModal('${module}', ${item.id})">编辑</button>
                <button class="btn btn-danger btn-sm" onclick="openDeleteModal('${module}', ${item.id})">删除</button>
            </div>
        </td>
    `;

    if (module === 'landing') {
        content = `
            ${commonStart}
            <td>${item.landing_date}</td>
            <td>${escapeHtml(item.fish_type)}</td>
            <td>${item.quantity}</td>
            <td>${item.unit_price}</td>
            <td>${item.total_price}</td>
            <td>${escapeHtml(item.supplier || '-')}</td>
            <td>${escapeHtml(item.boat_name || '-')}</td>
            <td>${escapeHtml(item.quality || '-')}</td>
            ${actions}
        `;
    } else if (module === 'purchase') {
        content = `
            ${commonStart}
            <td>${item.purchase_date}</td>
            <td>${escapeHtml(item.item_name)}</td>
            <td>${escapeHtml(item.category || '-')}</td>
            <td>${item.quantity}</td>
            <td>${escapeHtml(item.unit)}</td>
            <td>${item.unit_price}</td>
            <td>${item.total_price}</td>
            <td>${escapeHtml(item.supplier || '-')}</td>
            <td><span class="status-badge ${item.payment_status === '已付款' ? 'active' : 'inactive'}">${item.payment_status}</span></td>
            ${actions}
        `;
    } else if (module === 'sales') {
        content = `
            ${commonStart}
            <td>${item.sale_date}</td>
            <td>${escapeHtml(item.customer_name)}</td>
            <td>${escapeHtml(item.customer_phone || '-')}</td>
            <td>${escapeHtml(item.item_name)}</td>
            <td>${item.quantity}</td>
            <td>${escapeHtml(item.unit)}</td>
            <td>${item.unit_price}</td>
            <td>${item.total_price}</td>
            <td><span class="status-badge ${item.payment_status === '已收款' ? 'active' : 'inactive'}">${item.payment_status}</span></td>
            <td><span class="status-badge ${item.delivery_status === '已发货' ? 'active' : 'inactive'}">${item.delivery_status}</span></td>
            ${actions}
        `;
    }

    return content;
}

/**
 * 渲染分页
 */
function renderModulePagination(module, pagination) {
    currentPage = pagination.page;
    totalPages = pagination.totalPages;
    totalRecords = pagination.total;

    document.getElementById(`${module}-pagination-info`).textContent = `共 ${totalRecords} 条记录`;
    document.getElementById(`${module}-page-info`).textContent = `第 ${currentPage} / ${totalPages} 页`;

    document.getElementById(`${module}-first-page`).disabled = currentPage === 1;
    document.getElementById(`${module}-prev-page`).disabled = currentPage === 1;
    document.getElementById(`${module}-next-page`).disabled = currentPage === totalPages;
    document.getElementById(`${module}-last-page`).disabled = currentPage === totalPages;
}

/**
 * 跳转到指定页
 */
function goToPage(module, page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    currentPage = page;
    loadModuleData(module);
}

/**
 * 处理搜索
 */
function handleSearch(module) {
    currentSearch = document.getElementById(`search-${module}`).value.trim();
    currentPage = 1;
    loadModuleData(module);
}

/**
 * ==================== 编辑弹窗 ====================
 */

/**
 * 打开新增弹窗
 */
function openAddModal(module) {
    document.getElementById('edit-form').reset();
    document.getElementById('edit-id').value = '';
    document.getElementById('edit-module').value = module;
    document.getElementById('modal-title').textContent = `新增${getModuleName(module)}`;

    // 显示对应表单字段
    document.querySelectorAll('.form-fields').forEach(fields => fields.style.display = 'none');
    document.getElementById(`${module}-form-fields`).style.display = 'block';

    hideError('edit-error');
    document.getElementById('edit-modal').style.display = 'flex';
}

/**
 * 打开编辑弹窗
 */
function openEditModal(module, id) {
    showLoading();

    const apiMap = {
        'landing': '/api/landings',
        'purchase': '/api/purchases',
        'sales': '/api/sales'
    };

    fetch(`${apiMap[module]}/${id}`)
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success && result.data) {
            const item = result.data;

            document.getElementById('edit-id').value = item.id;
            document.getElementById('edit-module').value = module;
            document.getElementById('modal-title').textContent = `编辑${getModuleName(module)}`;

            // 显示对应表单字段
            document.querySelectorAll('.form-fields').forEach(fields => fields.style.display = 'none');
            document.getElementById(`${module}-form-fields`).style.display = 'block';

            // 填充表单数据
            fillFormFields(module, item);

            hideError('edit-error');
            document.getElementById('edit-modal').style.display = 'flex';
        } else {
            showToast('获取数据失败', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('网络错误', 'error');
    });
}

/**
 * 填充表单字段
 */
function fillFormFields(module, item) {
    if (module === 'landing') {
        document.getElementById('edit-landing-date').value = item.landing_date || '';
        document.getElementById('edit-fish-type').value = item.fish_type || '';
        document.getElementById('edit-landing-quantity').value = item.quantity || '';
        document.getElementById('edit-landing-unit-price').value = item.unit_price || '';
        document.getElementById('edit-landing-supplier').value = item.supplier || '';
        document.getElementById('edit-boat-name').value = item.boat_name || '';
        document.getElementById('edit-location').value = item.location || '';
        document.getElementById('edit-quality').value = item.quality || '';
        document.getElementById('edit-landing-notes').value = item.notes || '';
    } else if (module === 'purchase') {
        document.getElementById('edit-purchase-date').value = item.purchase_date || '';
        document.getElementById('edit-item-name').value = item.item_name || '';
        document.getElementById('edit-category').value = item.category || '';
        document.getElementById('edit-purchase-quantity').value = item.quantity || '';
        document.getElementById('edit-purchase-unit').value = item.unit || '';
        document.getElementById('edit-purchase-unit-price').value = item.unit_price || '';
        document.getElementById('edit-purchase-supplier').value = item.supplier || '';
        document.getElementById('edit-purchase-payment-method').value = item.payment_method || '';
        document.getElementById('edit-purchase-payment-status').value = item.payment_status || '未付款';
        document.getElementById('edit-purchase-notes').value = item.notes || '';
    } else if (module === 'sales') {
        document.getElementById('edit-sale-date').value = item.sale_date || '';
        document.getElementById('edit-customer-name').value = item.customer_name || '';
        document.getElementById('edit-customer-phone').value = item.customer_phone || '';
        document.getElementById('edit-sales-item-name').value = item.item_name || '';
        document.getElementById('edit-sales-quantity').value = item.quantity || '';
        document.getElementById('edit-sales-unit').value = item.unit || '';
        document.getElementById('edit-sales-unit-price').value = item.unit_price || '';
        document.getElementById('edit-sales-payment-method').value = item.payment_method || '';
        document.getElementById('edit-sales-payment-status').value = item.payment_status || '未收款';
        document.getElementById('edit-delivery-status').value = item.delivery_status || '未发货';
        document.getElementById('edit-sales-notes').value = item.notes || '';
    }
}

/**
 * 关闭编辑弹窗
 */
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

/**
 * 处理保存
 */
function handleSave(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const module = formData.get('module');
    const data = Object.fromEntries(formData.entries());

    hideError('edit-error');

    // 验证必填字段
    if (!validateFormData(module, data)) {
        return;
    }

    showLoading();

    const apiMap = {
        'landing': '/api/landings',
        'purchase': '/api/purchases',
        'sales': '/api/sales'
    };

    const method = data.id ? 'PUT' : 'POST';
    const url = data.id ? `${apiMap[module]}/${data.id}` : apiMap[module];

    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast(result.message || '保存成功', 'success');
            closeEditModal();
            loadModuleData(module);
        } else {
            showError('edit-error', result.message || '保存失败');
        }
    })
    .catch(error => {
        hideLoading();
        showError('edit-error', '网络错误');
    });
}

/**
 * 验证表单数据
 */
function validateFormData(module, data) {
    if (module === 'landing') {
        if (!data.landing_date || !data.fish_type || !data.quantity || !data.unit_price) {
            showError('edit-error', '请填写所有必填字段');
            return false;
        }
    } else if (module === 'purchase') {
        if (!data.purchase_date || !data.item_name || !data.quantity || !data.unit || !data.unit_price) {
            showError('edit-error', '请填写所有必填字段');
            return false;
        }
    } else if (module === 'sales') {
        if (!data.sale_date || !data.customer_name || !data.item_name || !data.quantity || !data.unit || !data.unit_price) {
            showError('edit-error', '请填写所有必填字段');
            return false;
        }
    }
    return true;
}

/**
 * ==================== 删除确认 ====================
 */

/**
 * 打开删除确认弹窗
 */
function openDeleteModal(module, id) {
    deleteId = id;
    deleteModule = module;
    document.getElementById('confirm-modal').style.display = 'flex';
}

/**
 * 关闭删除确认弹窗
 */
function closeConfirmModal() {
    deleteId = null;
    deleteModule = null;
    document.getElementById('confirm-modal').style.display = 'none';
}

/**
 * 确认删除
 */
function confirmDelete() {
    if (!deleteId || !deleteModule) return;

    showLoading();

    const apiMap = {
        'landing': '/api/landings',
        'purchase': '/api/purchases',
        'sales': '/api/sales'
    };

    fetch(`${apiMap[deleteModule]}/${deleteId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            showToast(result.message || '删除成功', 'success');
            closeConfirmModal();
            loadModuleData(deleteModule);
        } else {
            showToast(result.message || '删除失败', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('网络错误', 'error');
    });
}

/**
 * ==================== 复选框和导出功能 ====================
 */

/**
 * 处理全选/取消全选
 */
function handleSelectAll(module, e) {
    const isChecked = e.target.checked;
    const checkboxes = document.querySelectorAll(`#${module}-table-body .row-checkbox`);

    checkboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
    });
}

/**
 * 导出模块数据
 */
function exportModuleData(module) {
    const data = moduleData[module] || [];

    if (data.length === 0) {
        showToast('没有数据可导出', 'warning');
        return;
    }

    // 准备Excel数据
    const excelData = prepareExportData(module, data);

    // 使用SheetJS导出
    if (typeof XLSX === 'undefined') {
        showToast('Excel导出库未加载，请刷新页面重试', 'error');
        return;
    }

    const ws = XLSX.utils.json_to_sheet(excelData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, getModuleName(module));

    const timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    XLSX.writeFile(wb, `${getModuleName(module)}_${timestamp}.xlsx`);

    showToast('导出成功', 'success');
}

/**
 * 准备导出数据
 */
function prepareExportData(module, data) {
    return data.map(item => {
        if (module === 'landing') {
            return {
                'ID': item.id,
                '上岸日期': item.landing_date,
                '鱼种类': item.fish_type,
                '数量(斤)': item.quantity,
                '单价(元/斤)': item.unit_price,
                '总价(元)': item.total_price,
                '供应商': item.supplier || '',
                '船名': item.boat_name || '',
                '上岸地点': item.location || '',
                '品质等级': item.quality || '',
                '备注': item.notes || ''
            };
        } else if (module === 'purchase') {
            return {
                'ID': item.id,
                '采购日期': item.purchase_date,
                '物品名称': item.item_name,
                '分类': item.category || '',
                '数量': item.quantity,
                '单位': item.unit,
                '单价(元)': item.unit_price,
                '总价(元)': item.total_price,
                '供应商': item.supplier || '',
                '支付方式': item.payment_method || '',
                '付款状态': item.payment_status,
                '备注': item.notes || ''
            };
        } else if (module === 'sales') {
            return {
                'ID': item.id,
                '销售日期': item.sale_date,
                '客户名称': item.customer_name,
                '客户电话': item.customer_phone || '',
                '商品名称': item.item_name,
                '数量': item.quantity,
                '单位': item.unit,
                '单价(元)': item.unit_price,
                '总价(元)': item.total_price,
                '支付方式': item.payment_method || '',
                '收款状态': item.payment_status,
                '发货状态': item.delivery_status,
                '备注': item.notes || ''
            };
        }
    });
}

/**
 * ==================== 工具函数 ====================
 */

/**
 * 显示加载提示
 */
function showLoading() {
    document.getElementById('loading').style.display = 'flex';
}

/**
 * 隐藏加载提示
 */
function hideLoading() {
    document.getElementById('loading').style.display = 'none';
}

/**
 * 显示错误信息
 */
function showError(elementId, message) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.classList.add('show');
}

/**
 * 隐藏错误信息
 */
function hideError(elementId) {
    const element = document.getElementById(elementId);
    element.textContent = '';
    element.classList.remove('show');
}

/**
 * 显示Toast提示
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

/**
 * 转义HTML特殊字符
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * 获取模块名称
 */
function getModuleName(module) {
    const names = {
        'landing': '上岸记录',
        'purchase': '采购记录',
        'sales': '销售记录'
    };
    return names[module] || '未知';
}
