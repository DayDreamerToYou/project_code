/**
 * 渔业数据管理系统 - layui 主应用
 * 功能：登录认证、数据表格、编辑表单、Excel导出
 */

layui.use(['layer', 'form', 'table', 'element', 'api', 'jquery', 'laydate', 'i18n'], function(){
    var layer = layui.layer;
    var form = layui.form;
    var table = layui.table;
    var element = layui.element;
    var api = layui.api;
    var $ = layui.jquery;
    var laydate = layui.laydate;
    var i18n = layui.i18n;

    // 全局状态
    var currentModule = 'landing';
    var tables = {};
    var isLoggedIn = false;

    // ==================== 登录模块 ====================

    /**
     * 检查登录状态
     */
    function checkLoginStatus() {
        var savedUser = localStorage.getItem('user');
        if (savedUser) {
            try {
                var userData = JSON.parse(savedUser);
                if (userData && userData.username) {
                    isLoggedIn = true;
                    initTables();
                    return;
                }
            } catch (e) {
                localStorage.removeItem('user');
            }
        }

        api.getList('landing', {}).then(function(res){
            if (res.logged_in === false || !res.success) {
                isLoggedIn = false;
                showLoginForm();
            } else {
                isLoggedIn = true;
                initTables();
            }
        }).catch(function(err){
            isLoggedIn = false;
            showLoginForm();
        });
    }

    /**
     * 显示登录表单
     */
    function showLoginForm() {
        // Get translations
        var loginTitle = layui.i18n ? layui.i18n.t('loginTitle') : 'Fishery Data Management System';
        var usernameLabel = layui.i18n ? layui.i18n.t('username') : 'Username';
        var passwordLabel = layui.i18n ? layui.i18n.t('password') : 'Password';
        var usernamePlaceholder = layui.i18n ? layui.i18n.t('username') : 'Username';
        var passwordPlaceholder = layui.i18n ? layui.i18n.t('password') : 'Password';
        var loginBtn = layui.i18n ? layui.i18n.t('loginBtn') : 'Login';

        var loginHtml = `
            <div style="padding: 30px;">
                <h2 class="login-title">${loginTitle}</h2>
                <form class="layui-form" lay-filter="loginForm">
                    <div class="layui-form-item">
                        <label class="layui-form-label">${usernameLabel}</label>
                        <div class="layui-input-block">
                            <input type="text" name="username" required lay-verify="required"
                                   placeholder="${usernamePlaceholder}" class="layui-input">
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">${passwordLabel}</label>
                        <div class="layui-input-block">
                            <input type="password" name="password" required lay-verify="required"
                                   placeholder="${passwordPlaceholder}" class="layui-input">
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button class="layui-btn layui-btn-fluid" lay-submit lay-filter="loginSubmit">${loginBtn}</button>
                        </div>
                    </div>
                </form>
                <div style="margin-top: 20px; color: #666; font-size: 12px;">
                    <p>Test Accounts:</p>
                    <p>Username: <strong>admin</strong> or <strong>testuser</strong></p>
                    <p>Password: <strong>123456</strong></p>
                </div>
            </div>
        `;

        // 移动端适配
        var isMobile = window.innerWidth <= 768;
        var loginArea = isMobile ? ['90%', 'auto'] : ['400px', 'auto'];

        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: loginArea,
            content: loginHtml,
            resize: isMobile,
            move: !isMobile,
            success: function(){
                form.render();

                // 监听登录提交
                form.on('submit(loginSubmit)', function(data){
                    var loadingIndex = layer.load(1);

                    api.login(data.field).then(function(res){
                        layer.close(loadingIndex);
                        layer.closeAll('page');
                        layer.msg(layui.i18n ? layui.i18n.t('loginSuccess') : 'Login successful', {icon: 1});
                        isLoggedIn = true;
                        if (res.data) {
                            localStorage.setItem('user', JSON.stringify(res.data));
                        }
                        initTables();
                    }).catch(function(err){
                        layer.close(loadingIndex);
                        layer.msg(err.message || (layui.i18n ? layui.i18n.t('loginFailed') : 'Login failed'), {icon: 2});
                    });

                    return false;
                });
            }
        });
    }

    /**
     * 登出
     */
    $('#logout-btn').on('click', function(){
        var i18n = layui.i18n;
        var confirmMsg = i18n ? i18n.t('logoutConfirm') : 'Are you sure to logout?';
        var btnConfirm = i18n ? i18n.t('confirm') : 'Confirm';
        var btnCancel = i18n ? i18n.t('cancel') : 'Cancel';

        layer.confirm(confirmMsg, {btn: [btnConfirm, btnCancel]}, function(index){
            api.logout().then(function(){
                layer.msg(i18n ? layui.i18n.t('logoutSuccess') : 'Logged out successfully', {icon: 1});
                localStorage.removeItem('user');
                location.reload();
            }).catch(function(){
                layer.msg('Logout failed', {icon: 2});
            });
            layer.close(index);
        });
    });

    // ==================== 表格模块 ====================

    /**
     * 表格列配置
     */
    var tableColumns = {
        landing: [
            {field: 'action', title: '操作', toolbar: '#landingOperateTpl', width: 200, align: 'center'},
            {field: 'LandingID', title: 'ID', width: 80, sort: true},
            {field: 'LandingDate', title: '到货日期', width: 120, templet: function(d){
                // 只显示日期部分
                if (d.LandingDate) {
                    return d.LandingDate.substring(0, 10);
                }
                return '';
            }},
            {field: 'SupplierName', title: '供应商', width: 200},
            {field: 'Port', title: '港口', width: 120},
            {field: 'BoatNo', title: '船号', width: 120},
            {field: 'BoatName', title: '船名', width: 150},
            {field: 'detail_count', title: '明细数量', width: 100, templet: function(d){
                return '<span style="color:#2563eb;font-weight:bold;">' + (d.detail_count || 0) + '</span>';
            }}
        ],
        purchase: [
            {title: '操作', toolbar: '#purchaseOperateTpl', width: 180, align: 'center'},
            {field: 'PurchaseID', title: 'ID', width: 80, sort: true},
            {field: 'PurchaseDate', title: '采购日期', width: 120, templet: function(d){
                return d.PurchaseDate ? d.PurchaseDate.substring(0, 10) : '';
            }},
            {field: 'SupplierName', title: '供应商', width: 150},
            {field: 'BoatName', title: '船名', width: 120},
            {field: 'BoatNo', title: '船号', width: 120},
            {field: 'detail_count', title: '明细数量', width: 100, templet: function(d){
                return '<span style="color:#2563eb;font-weight:bold;">' + (d.detail_count || 0) + '</span>';
            }},
            {field: 'Subtotal', title: '小计', width: 100, templet: function(d){
                return '$' + (d.Subtotal || 0);
            }},
            {field: 'GST', title: '税费', width: 100, templet: function(d){
                return '$' + (d.GST || 0);
            }},
            {field: 'Total', title: '总计', width: 100, templet: function(d){
                return '<span style="color:#16a34a;font-weight:bold;">$' + (d.Total || 0) + '</span>';
            }},
            {field: 'EmailSent', title: 'EmailSent', width: 80, templet: function(d){
                var emailSentValue = d.EmailSent || 0;
                var emailSentText = emailSentValue == 1 ? 'Yes' : 'No';
                var color = emailSentValue == 1 ? '#16a34a' : '#999';
                var fontWeight = emailSentValue == 1 ? 'bold' : 'normal';
                return '<span style="color:' + color + ';font-weight:' + fontWeight + ';">' + emailSentText + '</span>';
            }}
        ],
        sales: [
            {type: 'checkbox'},
            {field: 'SalesID', title: 'ID', width: 80, sort: true},
            {field: 'SaleDate', title: '销售日期', width: 120, templet: function(d){
                return d.SaleDate ? d.SaleDate.substring(0, 10) : '';
            }},
            {field: 'CustomerName', title: '客户名称', width: 200},
            {field: 'detail_count', title: '明细数量', width: 100, templet: function(d){
                return '<span style="color:#2563eb;font-weight:bold;">' + (d.detail_count || 0) + '</span>';
            }},
            {field: 'Subtotal', title: '小计', width: 100, templet: function(d){
                return '$' + (d.Subtotal || 0);
            }},
            {field: 'GST', title: '税费', width: 100, templet: function(d){
                return '$' + (d.GST || 0);
            }},
            {field: 'Total', title: '总计', width: 100, templet: function(d){
                return '<span style="color:#16a34a;font-weight:bold;">$' + (d.Total || 0) + '</span>';
            }},
            {title: '操作', toolbar: '#salesOperateTpl', width: 440, align: 'center'}
        ]
    };

    /**
     * 更新所有表格列标题以支持国际化
     */
    function updateColumnTitles() {
        if (!layui.i18n) return;
        var i18n = layui.i18n;

        try {
            // Purchase 表格列（优先处理）
            if (tableColumns.purchase) {
                console.log('Purchase columns before update:', tableColumns.purchase.map(function(c, i) {
                    return {index: i, field: c.field, title: c.title};
                }));

                tableColumns.purchase[0].title = i18n.t('action');
                tableColumns.purchase[2].title = i18n.t('purchaseDate');
                tableColumns.purchase[3].title = i18n.t('supplierName');
                tableColumns.purchase[4].title = i18n.t('boatName');
                tableColumns.purchase[5].title = i18n.t('boatNo');
                tableColumns.purchase[6].title = i18n.t('detailCount');
                tableColumns.purchase[7].title = i18n.t('subtotal');
                tableColumns.purchase[8].title = i18n.t('gst');
                tableColumns.purchase[9].title = i18n.t('total');
                // EmailSent 列(索引10)标题保持为 "EmailSent"，不需要翻译

                console.log('Purchase columns after update:', tableColumns.purchase.map(function(c, i) {
                    return {index: i, field: c.field, title: c.title};
                }));
            }

            // Landing 表格列
            if (tableColumns.landing) {
                tableColumns.landing[0].title = i18n.t('action');
                tableColumns.landing[2].title = i18n.t('landingDate');
                tableColumns.landing[3].title = i18n.t('supplierName');
                tableColumns.landing[4].title = i18n.t('port');
                tableColumns.landing[5].title = i18n.t('boatNo');
                tableColumns.landing[6].title = i18n.t('boatName');
                tableColumns.landing[7].title = i18n.t('detailCount');
            }

            // Sales 表格列
            if (tableColumns.sales) {
                tableColumns.sales[2].title = i18n.t('saleDate');
                tableColumns.sales[3].title = i18n.t('customerName');
                tableColumns.sales[4].title = i18n.t('detailCount');
                tableColumns.sales[5].title = i18n.t('subtotal');
                tableColumns.sales[6].title = i18n.t('gst');
                tableColumns.sales[7].title = i18n.t('total');
                tableColumns.sales[8].title = i18n.t('action');
            }
        } catch (error) {
            console.error('Error updating column titles:', error);
        }
    }

    /**
     * 初始化所有表格
     */
    function initTables() {
        // 更新所有表格列标题以支持国际化
        updateColumnTitles();

        // 检测屏幕宽度，设置操作列宽度
        var isMobile = window.innerWidth <= 768;
        var isSmallMobile = window.innerWidth <= 375;

        // 根据屏幕宽度设置操作列宽度（确保按钮和图标能完整显示）
        // 桌面端：显示完整按钮文字（按钮140px × 数量 + 间距3px × 数量）
        // 移动端：只显示图标（4个按钮×28px + 3个间距×2px + padding ≈ 125-130px）
        var actionWidths = {
            landing: isSmallMobile ? 120 : (isMobile ? 120 : 200),  // 手机端两行显示
            purchase: isSmallMobile ? 160 : (isMobile ? 160 : 200),
            sales: isSmallMobile ? 95 : (isMobile ? 100 : 440)
        };

        ['landing', 'purchase', 'sales'].forEach(function(module){
            // 动态调整操作列宽度
            var columns = tableColumns[module];
            columns.forEach(function(col){
                // 通过fixed或toolbar属性判断是否为操作列
                if (col.fixed === 'right' || (col.toolbar && col.toolbar.indexOf('OperateTpl') > 0)) {
                    col.width = actionWidths[module];
                }
            });

            tables[module] = table.render({
                elem: '#' + module + '-table',
                url: '../api/' + module + '.php',
                toolbar: '#toolbarTpl',
                defaultToolbar: ['filter'],
                cols: [columns],
                width: '100%', // 确保表格宽度为100%
                height: 'full-200', // 设置表格高度
                page: true,
                limit: 10,
                limits: [10, 20, 50, 100],
                cellMinWidth: isMobile ? 60 : 80, // 移动端单元格最小宽度
                even: true, // 开启隔行背景
                size: isMobile ? 'sm' : 'lg', // 移动端小尺寸，桌面端大尺寸
                parseData: function(res){
                    // 检查是否未登录
                    if (res.logged_in === false || !res.success) {
                        // 如果未登录，不显示错误，只是返回空数据
                        // 登录检查会在checkLoginStatus中处理
                        return {
                            "code": 0,
                            "msg": res.message || '',
                            "count": 0,
                            "data": []
                        };
                    }
                    return {
                        "code": 0,
                        "msg": res.message || '',
                        "count": res.pagination ? res.pagination.total : 0,
                        "data": res.data || []
                    };
                },
                error: function(xhr, type, errorThrown){
                    // 处理错误，但不显示登录错误（避免无限弹窗）
                    console.error('Table load error:', type, errorThrown);
                },
                request: {
                    pageName: 'page',
                    limitName: 'pageSize'
                }
            });

            // 监听工具条事件
            table.on('toolbar(' + module + '-table)', function(obj){
                if (obj.event === 'add') {
                    openEditForm(module);
                } else if (obj.event === 'export') {
                    exportData(module);
                } else if (obj.event === 'search') {
                    var searchValue = $('#search-input-' + module).val();
                    tables[module].reload({where: {search: searchValue}, page: {curr: 1}});
                } else if (obj.event === 'refresh') {
                    tables[module].reload();
                }
            });

            // 监听行工具事件
            table.on('tool(' + module + '-table)', function(obj){
                var data = obj.data;
                if (obj.event === 'edit') {
                    openEditForm(module, data);
                } else if (obj.event === 'generatePurchase') {
                    // 生成采购单
                    generatePurchaseFromLanding(data);
                } else if (obj.event === 'generateSales') {
                    // 生成销售单
                    generateSalesFromPurchase(data);
                } else if (obj.event === 'exportRow') {
                    // 导出单条记录
                    exportData(module, [data]);
                } else if (obj.event === 'printRow') {
                    // 打印单条记录（生成PDF并打开打印对话框）
                    printLandingRecord(data);
                } else if (obj.event === 'netPrintRow') {
                    // 网络打印（发送到网络热敏打印机）
                    netPrintLandingRecord(data);
                } else if (obj.event === 'sendEmail') {
                    // 发送邮件
                    sendEmailWithPDF(module, data);
                } else if (obj.event === 'del') {
                    var i18n = layui.i18n;
                    var deleteMsg = 'Are you sure to delete this record?';
                    var btnConfirm = i18n ? i18n.t('confirm') : 'Confirm';
                    var btnCancel = i18n ? i18n.t('cancel') : 'Cancel';
                    var warningTitle = i18n ? i18n.t('warning') : 'Warning';

                    // 根据模块显示不同的删除确认消息
                    if (module === 'landing') {
                        deleteMsg = i18n ? i18n.t('deleteLandingConfirm') : 'Are you sure? This will delete the landing record and ALL associated purchase and sales records!';
                    } else if (module === 'purchase') {
                        deleteMsg = i18n ? i18n.t('deletePurchaseConfirm') : 'Are you sure? This will delete the purchase record and ALL associated sales records!';
                    } else if (module === 'sales') {
                        deleteMsg = i18n ? i18n.t('deleteSalesConfirm') : 'Are you sure to delete this sales record?';
                    }

                    layer.confirm(deleteMsg, {icon: 3, title: warningTitle, btn: [btnConfirm, btnCancel]}, function(index){
                        var idField = module === 'landing' ? 'LandingID' : (module === 'purchase' ? 'PurchaseID' : (module === 'sales' ? 'SalesID' : 'id'));
                        api.delete(module, data[idField]).then(function(){
                            layer.msg(i18n ? i18n.t('deleteSuccess') : 'Deleted successfully', {icon: 1});
                            // 刷新当前表格
                            tables[module].reload();
                            // 级联刷新关联表格：删除 Landing 时刷新 Purchase 和 Sales，删除 Purchase 时刷新 Sales
                            if (module === 'landing') {
                                if (tables['purchase']) tables['purchase'].reload();
                                if (tables['sales']) tables['sales'].reload();
                            } else if (module === 'purchase') {
                                if (tables['sales']) tables['sales'].reload();
                            }
                            layer.close(index);
                        }).catch(function(err){
                            layer.msg(err.message || (i18n ? i18n.t('deleteFailed') : 'Delete failed'), {icon: 2});
                        });
                    });
                }
            });
        });
    }

    // ==================== 选项卡切换监听 ====================

    // 监听选项卡切换事件，自动刷新对应表格
    element.on('tab(main-tab)', function(data){
        var layId = data.elem.attr('lay-id');
        if (layId && tables[layId]) {
            currentModule = layId;
            // 刷新对应的表格
            tables[layId].reload();
        }
    });

    // ==================== 表单编辑模块 ====================

    /**
     * 打开编辑表单
     */
    function openEditForm(module, data) {
        var isEdit = !!data;
        var i18n = layui.i18n;

        // 使用i18n翻译标题
        var titleKey = isEdit ? 'edit' + module.charAt(0).toUpperCase() + module.slice(1) : 'add' + module.charAt(0).toUpperCase() + module.slice(1) + 'Record';
        var title = i18n ? i18n.t(titleKey) : (isEdit ? 'Edit ' + module : 'Add ' + module);

        // landing、purchase 和 sales 模块使用更大的弹窗来容纳明细表
        var area = (module === 'landing' || module === 'purchase' || module === 'sales') ? ['1000px', '700px'] : ['600px', '500px'];

        // 如果是 landing 模块的编辑模式，先获取完整数据
        if (module === 'landing' && isEdit) {
            var loadingIndex = layer.load(1);
            api.getList('landing', {id: data.LandingID, includeDetails: true}).then(function(res){
                layer.close(loadingIndex);
                if (res.success && res.data) {
                    var fullData = res.data;
                    var formHtml = generateFormHtml(module, fullData);
                    openLayerAndInit(module, title, area, formHtml, fullData);
                } else {
                    layer.msg('获取数据失败', {icon: 2});
                }
            }).catch(function(err){
                layer.close(loadingIndex);
                layer.msg(err.message || '获取数据失败', {icon: 2});
            });
        } else if (module === 'purchase' && isEdit) {
            var loadingIndex = layer.load(1);
            api.getList('purchase', {id: data.PurchaseID, includeDetails: true}).then(function(res){
                layer.close(loadingIndex);
                if (res.success && res.data) {
                    var fullData = res.data;
                    var formHtml = generateFormHtml(module, fullData);
                    openLayerAndInit(module, title, area, formHtml, fullData);
                } else {
                    layer.msg('获取数据失败', {icon: 2});
                }
            }).catch(function(err){
                layer.close(loadingIndex);
                layer.msg(err.message || '获取数据失败', {icon: 2});
            });
        } else if (module === 'sales' && isEdit) {
            var loadingIndex = layer.load(1);
            api.getList('sales', {id: data.SalesID, includeDetails: true}).then(function(res){
                layer.close(loadingIndex);
                if (res.success && res.data) {
                    var fullData = res.data;
                    var formHtml = generateFormHtml(module, fullData);
                    openLayerAndInit(module, title, area, formHtml, fullData);
                } else {
                    layer.msg('获取数据失败', {icon: 2});
                }
            }).catch(function(err){
                layer.close(loadingIndex);
                layer.msg(err.message || '获取数据失败', {icon: 2});
            });
        } else {
            var formHtml = generateFormHtml(module, data);
            openLayerAndInit(module, title, area, formHtml, data);
        }
    }

    /**
     * 打开弹窗并初始化
     */
    function openLayerAndInit(module, title, area, formHtml, data) {
        var i18n = layui.i18n;
        var saveText = i18n ? i18n.t('save') : '保存';
        var cancelText = i18n ? i18n.t('cancel') : '取消';

        // 移动端适配
        var isMobile = window.innerWidth <= 768;
        var dialogArea = isMobile ? ['95%', '90%'] : area;

        layer.open({
            type: 1,
            title: title,
            area: dialogArea,
            content: '<div style="padding: 20px;">' + formHtml + '</div>',
            btn: [saveText, cancelText],
            resize: isMobile,
            move: !isMobile,
            yes: function(index, layero){
                saveRecord(module, data, index);
            },
            success: function(){
                form.render();
                // 初始化明细表
                if (module === 'landing') {
                    initDetailTable(data);
                }
                // 如果是 purchase 模块，初始化明细表
                if (module === 'purchase') {
                    initPurchaseDetailTable(data);
                }
                // 如果是 sales 模块，初始化明细表
                if (module === 'sales') {
                    initSalesDetailTable(data);
                }
            }
        });
    }

    /**
     * 生成表单HTML
     */
    function generateFormHtml(module, data) {
        data = data || {};
        var i18n = layui.i18n;

        // 获取翻译文本
        var t = function(key) {
            return i18n ? i18n.t(key) : key;
        };

        var forms = {
            landing: `
                <form class="layui-form" lay-filter="landingForm">
                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('landingDate')}</label>
                            <div class="layui-input-inline">
                                <input type="text" name="LandingDate" value="${data.LandingDate ? data.LandingDate.substring(0, 10) : ''}" required lay-verify="required" placeholder="${t('pleaseSelectDate')}" class="layui-input" id="landing-date-input">
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('supplier')}</label>
                            <div class="layui-input-inline">
                                <select name="SupplierID" lay-verify="required" id="supplier-select" lay-filter="supplier-filter">
                                    <option value="">${t('pleaseSelect')}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('port')}</label>
                            <div class="layui-input-inline">
                                <select name="PortID" lay-verify="required" id="port-select">
                                    <option value="">${t('pleaseSelect')}</option>
                                </select>
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('boatName')}</label>
                            <div class="layui-input-inline">
                                <select name="BoatID" lay-verify="required" id="boat-select">
                                    <option value="">${t('pleaseSelect')}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="LandingID" value="${data.LandingID || ''}">
                </form>
                <fieldset class="layui-elem-field layui-field-title">
                    <legend>${t('landingDetails')}</legend>
                </fieldset>
                <div class="layui-btn-container">
                    <button type="button" class="layui-btn layui-btn-sm" id="add-detail-btn">
                        <i class="layui-icon layui-icon-add-1"></i> ${t('addDetail')}
                    </button>
                </div>
                <table id="detail-table" lay-filter="detail-table"></table>
            `,
            purchase: `
                <form class="layui-form" lay-filter="purchaseForm">
                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('purchaseDate')}</label>
                            <div class="layui-input-inline">
                                <input type="text" name="PurchaseDate" value="${data.PurchaseDate ? data.PurchaseDate.substring(0, 10) : ''}" required lay-verify="required" placeholder="${t('pleaseSelectDate')}" class="layui-input" id="purchase-date-input">
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('supplier')}</label>
                            <div class="layui-input-inline">
                                <select name="SupplierID" lay-verify="required" id="purchase-supplier-select">
                                    <option value="">${t('pleaseSelect')}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('subtotal')}</label>
                            <div class="layui-input-inline">
                                <input type="number" step="0.01" name="Subtotal" value="${data.Subtotal || ''}" placeholder="${t('autoCalculate')}" class="layui-input" readonly>
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('gst')}</label>
                            <div class="layui-input-inline">
                                <input type="number" step="0.01" name="GST" value="${data.GST || ''}" placeholder="${t('autoCalculate')}" class="layui-input" readonly>
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('total')}</label>
                            <div class="layui-input-inline">
                                <input type="number" step="0.01" name="Total" value="${data.Total || ''}" placeholder="${t('autoCalculate')}" class="layui-input" readonly>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="PurchaseID" value="${data.PurchaseID || ''}">
                </form>
                <fieldset class="layui-elem-field layui-field-title">
                    <legend>${t('purchaseDetails')}</legend>
                </fieldset>
                <div class="layui-btn-container">
                    <button type="button" class="layui-btn layui-btn-sm" id="add-purchase-detail-btn">
                        <i class="layui-icon layui-icon-add-1"></i> ${t('addDetail')}
                    </button>
                </div>
                <table id="purchase-detail-table" lay-filter="purchase-detail-table"></table>
            `,
            sales: `
                <form class="layui-form" lay-filter="salesForm">
                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('saleDate')}</label>
                            <div class="layui-input-inline">
                                <input type="text" name="SaleDate" value="${data.SaleDate ? data.SaleDate.substring(0, 10) : ''}" required lay-verify="required" placeholder="${t('pleaseSelectDate')}" class="layui-input" id="sales-date-input">
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('customerName')}</label>
                            <div class="layui-input-inline">
                                <select name="CustomerID" lay-verify="required" id="sales-customer-select">
                                    <option value="">${t('pleaseSelect')}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('subtotal')}</label>
                            <div class="layui-input-inline">
                                <input type="number" step="0.01" name="Subtotal" value="${data.Subtotal || ''}" placeholder="${t('autoCalculate')}" class="layui-input" readonly>
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('gst')}</label>
                            <div class="layui-input-inline">
                                <input type="number" step="0.01" name="GST" value="${data.GST || ''}" placeholder="${t('autoCalculate')}" class="layui-input" readonly>
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">${t('total')}</label>
                            <div class="layui-input-inline">
                                <input type="number" step="0.01" name="Total" value="${data.Total || ''}" placeholder="${t('autoCalculate')}" class="layui-input" readonly>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="SalesID" value="${data.SalesID || ''}">
                </form>
                <fieldset class="layui-elem-field layui-field-title">
                    <legend>${t('salesDetails')}</legend>
                </fieldset>
                <div class="layui-btn-container">
                    <button type="button" class="layui-btn layui-btn-sm" id="add-sales-detail-btn">
                        <i class="layui-icon layui-icon-add-1"></i> ${t('addDetail')}
                    </button>
                </div>
                <table id="sales-detail-table" lay-filter="sales-detail-table"></table>
            `
        };

        return forms[module] || '<p>Form Load Failed</p>';
    }

    /**
     * 保存记录
     */
    function saveRecord(module, data, layerIndex) {
        var loadingIndex = layer.load(1);

        // landing 模块特殊处理
        if (module === 'landing') {
            var formData = form.val('landingForm');
            var details = collectDetailData();

            console.log('saveRecord - formData:', formData);
            console.log('saveRecord - data参数:', data);
            console.log('saveRecord - details:', details);

            if (details.length === 0) {
                layer.close(loadingIndex);
                layer.msg(layui.i18n ? layui.i18n.t('atLeastOneDetail') : 'Please add at least one detail record', {icon: 2});
                return;
            }

            formData.details = details;

            // 使用 data 参数来判断是否为编辑模式，而不是依赖 formData
            var isEdit = data && data.LandingID;
            var method = isEdit ? 'update' : 'add';

            console.log('saveRecord - isEdit:', isEdit, 'method:', method);

            // 编辑模式或新增模式
            if (isEdit) {
                // 编辑模式：使用传入的 data.LandingID
                var updateData = {
                    LandingID: data.LandingID,
                    LandingDate: formData.LandingDate,
                    SupplierID: formData.SupplierID,
                    PortID: formData.PortID,
                    BoatID: formData.BoatID,
                    details: details
                };

                console.log('saveRecord - 更新数据:', updateData);

                api[method]('landing', updateData).then(function(res){
                    layer.close(loadingIndex);
                    layer.msg(res.message || (layui.i18n ? layui.i18n.t('saveSuccess') : 'Saved successfully'), {icon: 1});
                    // 保存当前的表单值供下次使用
                    saveLandingValues(formData.SupplierID, formData.PortID, formData.BoatID);
                    layer.close(layerIndex);
                    tables['landing'].reload();
                }).catch(function(err){
                    layer.close(loadingIndex);
                    layer.msg(err.message || (layui.i18n ? layui.i18n.t('saveFailed') : 'Save failed'), {icon: 2});
                });
            } else {
                // 新增模式
                console.log('saveRecord - 新增数据:', formData);

                api[method]('landing', formData).then(function(res){
                    layer.close(loadingIndex);
                    layer.msg(res.message || (layui.i18n ? layui.i18n.t('saveSuccess') : 'Saved successfully'), {icon: 1});
                    // 保存当前的表单值供下次使用
                    saveLandingValues(formData.SupplierID, formData.PortID, formData.BoatID);
                    layer.close(layerIndex);
                    tables['landing'].reload();
                    // 刷新 Purchase 表格以同步显示新生成的 Purchase 记录
                    if (tables['purchase']) {
                        tables['purchase'].reload();
                    }
                }).catch(function(err){
                    layer.close(loadingIndex);
                    layer.msg(err.message || (layui.i18n ? layui.i18n.t('saveFailed') : 'Save failed'), {icon: 2});
                });
            }
        } else if (module === 'purchase') {
            // purchase 模块特殊处理
            var formData = form.val('purchaseForm');
            var details = collectPurchaseDetailData();

            console.log('saveRecord - purchase formData:', formData);
            console.log('saveRecord - purchase data参数:', data);
            console.log('saveRecord - purchase details:', details);

            if (details.length === 0) {
                layer.close(loadingIndex);
                layer.msg(layui.i18n ? layui.i18n.t('atLeastOneDetail') : 'Please add at least one detail record', {icon: 2});
                return;
            }

            // 计算小计、GST、总计
            var subtotal = 0;
            details.forEach(function(detail){
                if (detail.Total) {
                    subtotal += parseFloat(detail.Total);
                }
            });
            var gst = subtotal * 0.15; // 假设GST税率为15%
            var total = subtotal + gst;

            formData.Subtotal = subtotal.toFixed(2);
            formData.GST = gst.toFixed(2);
            formData.Total = total.toFixed(2);
            formData.details = details;

            // 使用 data 参数来判断是否为编辑模式
            var isEdit = data && data.PurchaseID;
            var method = isEdit ? 'update' : 'add';

            console.log('saveRecord - purchase isEdit:', isEdit, 'method:', method);

            if (isEdit) {
                // 编辑模式：使用传入的 data.PurchaseID
                var updateData = {
                    PurchaseID: data.PurchaseID,
                    PurchaseDate: formData.PurchaseDate,
                    SupplierID: formData.SupplierID,
                    Subtotal: formData.Subtotal,
                    GST: formData.GST,
                    Total: formData.Total,
                    details: details
                };

                console.log('saveRecord - purchase 更新数据:', updateData);

                api[method]('purchase', updateData).then(function(res){
                    layer.close(loadingIndex);
                    layer.msg(res.message || (layui.i18n ? layui.i18n.t('saveSuccess') : 'Saved successfully'), {icon: 1});
                    layer.close(layerIndex);
                    tables['purchase'].reload();
                }).catch(function(err){
                    layer.close(loadingIndex);
                    layer.msg(err.message || (layui.i18n ? layui.i18n.t('saveFailed') : 'Save failed'), {icon: 2});
                });
            } else {
                // 新增模式
                console.log('saveRecord - purchase 新增数据:', formData);

                api[method]('purchase', formData).then(function(res){
                    layer.close(loadingIndex);
                    layer.msg(res.message || (layui.i18n ? layui.i18n.t('saveSuccess') : 'Saved successfully'), {icon: 1});
                    layer.close(layerIndex);
                    tables['purchase'].reload();
                }).catch(function(err){
                    layer.close(loadingIndex);
                    layer.msg(err.message || (layui.i18n ? layui.i18n.t('saveFailed') : 'Save failed'), {icon: 2});
                });
            }
        } else if (module === 'sales') {
            // 销售记录需要收集明细数据
            var formData = form.val('salesForm');
            var isEdit = !!formData.SalesID;
            var method = isEdit ? 'update' : 'add';

            console.log('saveRecord - sales isEdit:', isEdit, 'method:', method);

            // 收集明细数据
            var details = collectSalesDetailData();

            console.log('saveRecord - sales 收集到的明细数据:', details);
            console.log('saveRecord - sales details.length:', details.length);

            if (details.length === 0) {
                layer.close(loadingIndex);
                layer.msg('请至少添加一条明细记录', {icon: 0});
                return;
            }

            // 计算总计
            var subtotal = 0;
            details.forEach(function(d){
                subtotal += d.Amount;
            });
            var gst = subtotal * 0.15; // 15% GST
            var total = subtotal + gst;

            formData.Subtotal = subtotal.toFixed(2);
            formData.GST = gst.toFixed(2);
            formData.Total = total.toFixed(2);
            formData.details = details;

            console.log('saveRecord - sales 提交数据:', formData);

            if (isEdit) {
                // 编辑模式
                var updateData = {
                    SalesID: formData.SalesID,
                    SaleDate: formData.SaleDate,
                    CustomerID: formData.CustomerID,
                    Subtotal: formData.Subtotal,
                    GST: formData.GST,
                    Total: formData.Total,
                    details: details
                };

                console.log('saveRecord - sales 更新数据:', updateData);

                api[method]('sales', updateData).then(function(res){
                    layer.close(loadingIndex);
                    layer.msg(res.message || (layui.i18n ? layui.i18n.t('saveSuccess') : 'Saved successfully'), {icon: 1});
                    layer.close(layerIndex);
                    tables['sales'].reload();
                }).catch(function(err){
                    layer.close(loadingIndex);
                    layer.msg(err.message || (layui.i18n ? layui.i18n.t('saveFailed') : 'Save failed'), {icon: 2});
                });
            } else {
                // 新增模式
                api[method]('sales', formData).then(function(res){
                    layer.close(loadingIndex);
                    layer.msg(res.message || (layui.i18n ? layui.i18n.t('saveSuccess') : 'Saved successfully'), {icon: 1});
                    layer.close(layerIndex);
                    tables['sales'].reload();
                }).catch(function(err){
                    layer.close(loadingIndex);
                    layer.msg(err.message || (layui.i18n ? layui.i18n.t('saveFailed') : 'Save failed'), {icon: 2});
                });
            }
        } else {
            // 其他模块使用原有逻辑
            var formData = form.val(module + 'Form');
            var isEdit = !!formData.id;
            var method = isEdit ? 'update' : 'add';

            api[method](module, formData).then(function(res){
                layer.close(loadingIndex);
                layer.msg(res.message || (layui.i18n ? layui.i18n.t('saveSuccess') : 'Saved successfully'), {icon: 1});
                layer.close(layerIndex);
                tables[module].reload();
            }).catch(function(err){
                layer.close(loadingIndex);
                layer.msg(err.message || (layui.i18n ? layui.i18n.t('saveFailed') : 'Save failed'), {icon: 2});
            });
        }
    }

    // ==================== Excel 导出模块 ====================

    /**
     * 导出数据
     * @param {string} module - 模块名称
     * @param {Array} customData - 可选，自定义数据（用于单行导出）
     */
    function exportData(module, customData) {
        var data;

        // 如果传入了自定义数据，直接使用
        if (customData && customData.length > 0) {
            data = customData;
        } else {
            // 否则从表格中获取选中行或当前页数据
            var checkStatus = table.checkStatus(module + '-table');
            data = checkStatus.data;

            if (data.length === 0) {
                // 如果没有选中行，导出当前页所有数据
                data = table.cache[module + '-table'] || [];
            }
        }

        if (data.length === 0) {
            layer.msg('没有数据可导出', {icon: 0});
            return;
        }

        if (typeof XLSX === 'undefined') {
            layer.msg('Excel导出库未加载', {icon: 2});
            return;
        }

        // 采购记录需要导出明细，特殊处理
        if (module === 'purchase') {
            exportPurchaseWithDetails(data);
        } else if (module === 'sales') {
            // 销售记录也需要导出明细，特殊处理
            exportSalesWithDetails(data);
        } else if (module === 'landing') {
            // 到货记录导出明细为PDF
            exportLandingWithDetails(data);
        } else {
            var excelData = prepareExportData(module, data);
            var ws = XLSX.utils.json_to_sheet(excelData);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, getModuleName(module));

            var timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            XLSX.writeFile(wb, getModuleName(module) + '_' + timestamp + '.xlsx');

            layer.msg(layui.i18n ? layui.i18n.t('exportSuccess') : 'Export successful', {icon: 1});
        }
    }

    /**
     * 导出采购记录及明细（PDF格式）
     * 为每个采购记录生成单独命名的PDF
     */
    function exportPurchaseWithDetails(data) {
        layer.load(1);

        // 获取所有采购ID
        var purchaseIds = data.map(function(item){ return item.PurchaseID; });

        // 批量获取采购明细
        api.getPurchaseDetails(purchaseIds).then(function(res){
            layer.closeAll('loading');

            if (!res.success || !res.data) {
                layer.msg('获取采购明细失败', {icon: 2});
                return;
            }

            var detailsMap = {};
            res.data.forEach(function(detail){
                if (!detailsMap[detail.PurchaseID]) {
                    detailsMap[detail.PurchaseID] = [];
                }
                detailsMap[detail.PurchaseID].push(detail);
            });

            // 为每个采购记录单独生成PDF并打开
            data.forEach(function(purchase, index){
                var details = detailsMap[purchase.PurchaseID] || [];
                if (details.length > 0) {
                    generateSinglePurchasePDF(purchase, details, index);
                }
            });

            layer.msg(layui.i18n ? layui.i18n.t('exportSuccess') : 'Save successful', {icon: 1});
        }).catch(function(err){
            layer.closeAll('loading');
            layer.msg('获取明细失败: ' + (err.message || '未知错误'), {icon: 2});
        });
    }

    /**
     * 为单个采购记录生成PDF
     */
    function generateSinglePurchasePDF(purchase, details, index) {
        // 生成文件名: purchase_{ID}_{日期}.pdf
        var purchaseDate = purchase.PurchaseDate ? purchase.PurchaseDate.substring(0, 10).replace(/-/g, '') : '';
        var fileName = 'purchase_' + purchase.PurchaseID + '_' + purchaseDate + '.pdf';

        // 创建单个记录的HTML
        var invoiceHtml = createPurchaseInvoiceHtml(purchase, details, 1);

        // 创建临时容器并渲染HTML
        var container = document.createElement('div');
        container.style.position = 'absolute';
        container.style.left = '-9999px';
        container.style.width = '210mm';
        container.innerHTML = invoiceHtml;
        document.body.appendChild(container);

        // 等待字体加载后再生成PDF
        setTimeout(function(){
            html2canvas(container, {
                scale: 2,
                useCORS: true,
                logging: false
            }).then(function(canvas){
                document.body.removeChild(container);

                // 创建PDF
                var { jsPDF } = window.jspdf;
                var pdf = new jsPDF('p', 'mm', 'a4');

                var imgWidth = 210;
                var pageHeight = 297;
                var imgHeight = canvas.height * imgWidth / canvas.width;
                var heightLeft = imgHeight;
                var position = 0;

                // 添加第一页
                pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                // 如果内容超过一页，添加新页
                while (heightLeft > 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                // 在新窗口打开PDF（让用户选择保存或打印）
                var pdfBlob = pdf.output('blob', {filename: fileName});
                var pdfUrl = URL.createObjectURL(pdfBlob);

                // 在新窗口打开PDF
                var newWindow = window.open(pdfUrl, '_blank');
                if (!newWindow) {
                    layer.msg('请允许弹出窗口以打开PDF', {icon: 0});
                }
            }).catch(function(err){
                document.body.removeChild(container);
                console.error('PDF生成失败:', err);
                layer.msg('PDF生成失败: ' + (err.message || '未知错误'), {icon: 2});
            });
        }, 500);
    }

    /**
     * 创建采购发票HTML模板
     */
    function createPurchaseInvoiceHtml(purchase, details, invoiceNo) {
        var subtotal = 0;
        var detailsRows = '';

        details.forEach(function(detail){
            var greenWeight = detail.GreenKG || 0;
            var weight = detail.LandedKG || 0;
            var price = detail.Price || 0;
            var total = detail.Total || 0;
            subtotal += total;

            // 获取单位符号
            var greenUnit = '';
            var landedUnit = '';

            if (window.landingOptions.units && detail.GreenWeightUnitID) {
                var greenUnitItem = window.landingOptions.units.find(function(u){
                    return u.UnitID == detail.GreenWeightUnitID;
                });
                greenUnit = greenUnitItem ? greenUnitItem.UnitSymbol : 'kg';
            }

            if (window.landingOptions.units && detail.LandedWeightUnitID) {
                var landedUnitItem = window.landingOptions.units.find(function(u){
                    return u.UnitID == detail.LandedWeightUnitID;
                });
                landedUnit = landedUnitItem ? landedUnitItem.UnitSymbol : 'kg';
            }

            detailsRows += `
                <tr style="border: 1px solid #ccc;">
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: #FFFF00;">${detail.Stock || ''}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center;">${detail.State || ''}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center;">${detail.ICE || 'NO'}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center;">${detail.LandingID || ''}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center;">${greenWeight.toFixed(2)} ${greenUnit}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: #FFFF00;">${weight.toFixed(2)} ${landedUnit}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: #FFFF00;">$${price.toFixed(2)}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">$${total.toFixed(2)}</td>
                </tr>
            `;
        });

        var purchaseDate = purchase.PurchaseDate ? purchase.PurchaseDate.substring(0, 10) : '';
        var gst = purchase.GST || 0;
        var total = purchase.Total || 0;

        var html = `
            <div class="invoice-page" style="page-break-after: always; padding: 20px; font-family: Arial, sans-serif; font-size: 12px;">
                <!-- 顶部第一行：公司信息 + 发票编号 -->
                <div style="display: flex; margin-bottom: 10px;">
                    <!-- 左侧公司信息 (3/4) -->
                    <div style="flex: 0.75; padding-right: 10px;">
                        <div style="background-color: #1F4E78; color: white; font-weight: bold; padding: 10px; font-size: 16px;">
                            Seafood Direct Ltd
                        </div>
                        <div style="padding: 10px; border: 1px solid #ccc; border-top: none;">
                            698A Tay Street, Invercargill, 9810 : 404 Yarrow Street, Invercargill 9810
                        </div>
                        <div style="padding: 10px; border: 1px solid #ccc; border-top: none;">
                            Phone: 021 328-808 | GST No: 140-382-493 | LFR No: 9900844
                        </div>
                    </div>
                    <!-- 右侧发票编号 (1/4) -->
                    <div style="flex: 0.25; display: flex; flex-direction: column; justify-content: center;">
                        <div style="background-color: #FFFF00; color: #FF0000; font-weight: bold; text-align: center; padding: 10px; font-size: 14px; border: 2px solid #1F4E78;">
                            Invoice No:<br>${purchase.PurchaseID || ''}
                        </div>
                    </div>
                </div>

                <!-- IRD Approved 标题（占全长，字体放大） -->
                <div style="margin-bottom: 10px; margin-top: 10px;">
                    <div style="background-color: #1F4E78; color: white; font-weight: bold; text-align: center; padding: 15px; font-size: 20px;">
                        IRD Approved - Purchase Invoice
                    </div>
                </div>

                <div style="margin: 15px 0;"></div>

                <!-- 供应商信息 -->
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 8px; font-weight: bold; background-color: #1F4E78; color: white;">Supplier Name:</td>
                        <td style="border: 1px solid #ccc; padding: 8px;">${purchase.SupplierName || ''}</td>
                        <td style="border: 1px solid #ccc; padding: 8px; font-weight: bold; background-color: #1F4E78; color: white;">Client No:</td>
                        <td style="border: 1px solid #ccc; padding: 8px;">9900844</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 8px; font-weight: bold; background-color: #1F4E78; color: white;">Boat Name:</td>
                        <td style="border: 1px solid #ccc; padding: 8px;">${purchase.BoatName || ''}</td>
                        <td style="border: 1px solid #ccc; padding: 8px; font-weight: bold; background-color: #1F4E78; color: white;">Boat No:</td>
                        <td style="border: 1px solid #ccc; padding: 8px;">${purchase.BoatNo || ''}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 8px; font-weight: bold; background-color: #1F4E78; color: white;">Purchase Date:</td>
                        <td style="border: 1px solid #ccc; padding: 8px; background-color: white;">${purchaseDate}</td>
                    </tr>
                </table>

                <div style="margin: 15px 0;"></div>

                <!-- 数据表格 -->
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">
                    <thead>
                        <tr style="background-color: #1F4E78; color: white; font-weight: bold;">
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Fish Species</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Landed State</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Ice</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Unloading Docket</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Green Weight</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Weight</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Price</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${detailsRows}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" style="border: none; padding: 8px;"></td>
                            <td style="background-color: #1F4E78; color: white; font-weight: bold; padding: 8px; text-align: center; border: 1px solid #ccc;">Sub Total:</td>
                            <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">$${subtotal.toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="6" style="border: none; padding: 8px;"></td>
                            <td style="background-color: #1F4E78; color: white; font-weight: bold; padding: 8px; text-align: center; border: 1px solid #ccc;">GST:</td>
                            <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">$${gst.toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="6" style="border: none; padding: 8px;"></td>
                            <td style="background-color: #1F4E78; color: white; font-weight: bold; padding: 8px; text-align: center; border: 1px solid #ccc;">Total:</td>
                            <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">$${total.toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;

        return html;
    }

    /**
     * 创建采购发票工作表（保留用于Excel导出）
     */
    function createPurchaseInvoice(purchase, details, invoiceNo) {
        // 创建工作表
        var ws = {};
        var rowIndex = 1;
        var merges = []; // 合并单元格数组

        // 定义样式
        var darkBlueStyle = {
            fill: { fgColor: { rgb: "1F4E78" } },
            font: { bold: true, color: { rgb: "FFFFFF" } },
            alignment: { horizontal: "center", vertical: "center" }
        };

        var yellowRedStyle = {
            fill: { fgColor: { rgb: "FFFF00" } },
            font: { bold: true, color: { rgb: "FF0000" } },
            alignment: { horizontal: "center" }
        };

        var yellowStyle = {
            fill: { fgColor: { rgb: "FFFF00" } },
            font: { bold: false },
            alignment: { horizontal: "center" }
        };

        var headerStyle = {
            fill: { fgColor: { rgb: "1F4E78" } },
            font: { bold: true, color: { rgb: "FFFFFF" } },
            alignment: { horizontal: "center" },
            border: {
                top: { style: "thin", color: { auto: 1 } },
                bottom: { style: "thin", color: { auto: 1 } },
                left: { style: "thin", color: { auto: 1 } },
                right: { style: "thin", color: { auto: 1 } }
            }
        };

        var cellStyle = {
            alignment: { vertical: "center" },
            border: {
                top: { style: "thin", color: { auto: 1 } },
                bottom: { style: "thin", color: { auto: 1 } },
                left: { style: "thin", color: { auto: 1 } },
                right: { style: "thin", color: { auto: 1 } }
            }
        };

        // 内部函数：合并单元格
        function mergeCellsLocal(row, colStart, colEnd, value, style) {
            var cellRef = XLSX.utils.encode_cell({ r: row - 1, c: colStart - 1 });
            ws[cellRef] = { v: value, s: style };
            merges.push({
                s: { r: row - 1, c: colStart - 1 },
                e: { r: row - 1, c: colEnd - 1 }
            });
        }

        // 内部函数：设置单元格
        function setCellLocal(row, col, value, style) {
            var cellRef = XLSX.utils.encode_cell({ r: row - 1, c: col - 1 });
            ws[cellRef] = { v: value, s: style };
        }

        // ============= 第一行：公司信息 =============
        mergeCellsLocal(rowIndex, 1, 8, 'Seafood Direct Ltd', darkBlueStyle);
        rowIndex++;

        // ============= 第二行：地址信息 =============
        mergeCellsLocal(rowIndex, 1, 8, '698A Tay Street, Invercargill, 9810 : 404 Yarrow Street, Invercargill 9810', cellStyle);
        rowIndex++;

        // ============= 第三行：联系方式 =============
        mergeCellsLocal(rowIndex, 1, 8, 'Phone: 021 328-808 | GST No: 140-382-493 | LFR No: 9900844', cellStyle);
        rowIndex++;

        // ============= 第四行：空行 =============
        rowIndex++;

        // ============= 第五行：发票类型 =============
        mergeCellsLocal(rowIndex, 1, 8, 'IRD Approved - Purchase Invoice', darkBlueStyle);
        rowIndex++;

        // ============= 第六行：发票编号 =============
        mergeCellsLocal(rowIndex, 1, 8, 'Invoice No: ' + (60000 + invoiceNo), yellowRedStyle);
        rowIndex++;

        // ============= 第七行：空行 =============
        rowIndex++;

        // ============= 第八行开始：供应商信息 =============
        setCellLocal(rowIndex, 1, 'Supplier Name:', cellStyle);
        setCellLocal(rowIndex, 2, purchase.SupplierName || '', cellStyle);
        setCellLocal(rowIndex, 3, '', cellStyle);
        setCellLocal(rowIndex, 4, 'Client No:', cellStyle);
        setCellLocal(rowIndex, 5, '9900844', cellStyle);
        rowIndex++;

        setCellLocal(rowIndex, 1, 'Boat Name:', cellStyle);
        setCellLocal(rowIndex, 2, purchase.BoatName || '', cellStyle);
        setCellLocal(rowIndex, 3, '', cellStyle);
        setCellLocal(rowIndex, 4, 'Boat No:', cellStyle);
        setCellLocal(rowIndex, 5, purchase.BoatNo || '', cellStyle);
        rowIndex++;

        setCellLocal(rowIndex, 1, 'Purchase Date:', cellStyle);
        setCellLocal(rowIndex, 2, purchase.PurchaseDate ? purchase.PurchaseDate.substring(0, 10) : '', yellowStyle);
        setCellLocal(rowIndex, 3, '', cellStyle);
        rowIndex++;

        // ============= 空行 =============
        rowIndex++;

        // ============= 表头 =============
        var headers = ['Fish Species', 'Landed State', 'Ice', 'Unloading Docket', 'Green Weight', 'Weight', 'Price', 'Total'];
        var colWidths = [20, 15, 10, 18, 15, 15, 12, 15];
        headers.forEach(function(header, colIndex){
            setCellLocal(rowIndex, colIndex + 1, header, headerStyle);
        });
        rowIndex++;

        // ============= 数据行 =============
        var subtotal = 0;
        details.forEach(function(detail){
            var greenWeight = detail.GreenKG || 0;
            var weight = detail.LandedKG || 0;
            var price = detail.Price || 0;
            var total = detail.Total || 0;
            subtotal += total;

            setCellLocal(rowIndex, 1, detail.Description || '', cellStyle);
            setCellLocal(rowIndex, 2, detail.State || '', cellStyle);
            setCellLocal(rowIndex, 3, detail.ICE || 'NO', cellStyle);
            setCellLocal(rowIndex, 4, detail.ID || '', cellStyle);
            setCellLocal(rowIndex, 5, greenWeight.toFixed(2), cellStyle);
            setCellLocal(rowIndex, 6, weight.toFixed(2), yellowStyle);
            setCellLocal(rowIndex, 7, '$' + price.toFixed(2), yellowStyle);
            setCellLocal(rowIndex, 8, '$' + total.toFixed(2), yellowStyle);
            rowIndex++;
        });

        // ============= 底部汇总 =============
        rowIndex++;

        setCellLocal(rowIndex, 6, 'Sub Total:', headerStyle);
        setCellLocal(rowIndex, 7, '', headerStyle);
        setCellLocal(rowIndex, 8, '$' + subtotal.toFixed(2), yellowStyle);
        rowIndex++;

        var gst = purchase.GST || 0;
        setCellLocal(rowIndex, 6, 'GST:', headerStyle);
        setCellLocal(rowIndex, 7, '', headerStyle);
        setCellLocal(rowIndex, 8, '$' + gst.toFixed(2), yellowStyle);
        rowIndex++;

        var total = purchase.Total || 0;
        setCellLocal(rowIndex, 6, 'Total:', headerStyle);
        setCellLocal(rowIndex, 7, '', headerStyle);
        setCellLocal(rowIndex, 8, '$' + total.toFixed(2), yellowStyle);

        // 设置列宽
        ws['!cols'] = colWidths.map(function(w){ return { wch: w }; });

        // 设置合并单元格
        ws['!merges'] = merges;

        return ws;
    }

    /**
     * 合并单元格并设置值和样式
     */
    function mergeCells(ws, row, colStart, colEnd, value, style, merges) {
        var cellRef = XLSX.utils.encode_cell({ r: row - 1, c: colStart - 1 });
        ws[cellRef] = { v: value, s: style };

        // 记录合并信息（转换为0-based索引）
        merges.push({
            s: { r: row - 1, c: colStart - 1 },
            e: { r: row - 1, c: colEnd - 1 }
        });
    }

    /**
     * 设置单元格值和样式
     */
    function setCell(ws, row, col, value, style) {
        var cellRef = XLSX.utils.encode_cell({ r: row - 1, c: col - 1 });
        ws[cellRef] = { v: value, s: style };
    }

    /**
     * 准备导出数据
     */
    function prepareExportData(module, data) {
        return data.map(function(item){
            if (module === 'landing') {
                return {
                    'ID': item.LandingID,
                    '到货日期': item.LandingDate ? item.LandingDate.substring(0, 10) : '',
                    '供应商': item.SupplierName || '',
                    '港口': item.Port || '',
                    '船号': item.BoatNo || '',
                    '船名': item.BoatName || '',
                    '明细数量': item.detail_count || 0,
                    '总重量(kg)': item.total_weight || 0
                };
            } else if (module === 'purchase') {
                return {
                    'ID': item.PurchaseID,
                    '采购日期': item.PurchaseDate ? item.PurchaseDate.substring(0, 10) : '',
                    '供应商': item.SupplierName || '',
                    '船名': item.BoatName || '',
                    '船号': item.BoatNo || '',
                    '明细数量': item.detail_count || 0,
                    '小计': '$' + (item.Subtotal || 0),
                    '税费': '$' + (item.GST || 0),
                    '总计': '$' + (item.Total || 0)
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
                    '收款状态': item.payment_status,
                    '发货状态': item.delivery_status
                };
            }
        });
    }

    // ==================== 国际化 ====================

    // 使用 i18n 模块
    layui.use(['i18n'], function(){
        var i18n = layui.i18n;

        // 设置默认语言为英文
        if (!localStorage.getItem('language')) {
            localStorage.setItem('language', 'en');
        }

        // 语言切换按钮点击事件
        $('#lang-toggle').on('click', function(){
            var newLang = i18n.toggleLanguage();
            updatePageLanguage(newLang, true);  // 传入true显示提示消息
        });

        // 监听语言切换事件
        window.addEventListener('languageChanged', function(e){
            updatePageLanguage(e.detail.lang, false);  // 通过事件触发时不显示提示
        });

        /**
         * 更新表格列标题
         */
        function updateTableColumnTitles() {
            // 检查tableColumns是否存在
            if (typeof tableColumns === 'undefined') {
                console.warn('tableColumns is not defined yet');
                return;
            }

            try {
                // 更新到货记录表格列标题
                if (tableColumns.landing && tableColumns.landing[0]) {
                    tableColumns.landing[0].title = i18n.t('action');
                    tableColumns.landing[2].title = i18n.t('landingDate');
                    tableColumns.landing[3].title = i18n.t('supplierName');
                    tableColumns.landing[4].title = i18n.t('port');
                    tableColumns.landing[5].title = i18n.t('boatNo');
                    tableColumns.landing[6].title = i18n.t('boatName');
                    tableColumns.landing[7].title = i18n.t('detailCount');
                }

                // 更新采购记录表格列标题
                if (tableColumns.purchase && tableColumns.purchase[0]) {
                    tableColumns.purchase[0].title = i18n.t('action');
                    tableColumns.purchase[2].title = i18n.t('purchaseDate');
                    tableColumns.purchase[3].title = i18n.t('supplierName');
                    tableColumns.purchase[4].title = i18n.t('boatName');
                    tableColumns.purchase[5].title = i18n.t('boatNo');
                    tableColumns.purchase[6].title = i18n.t('detailCount');
                    tableColumns.purchase[7].title = i18n.t('subtotal');
                    tableColumns.purchase[8].title = i18n.t('gst');
                    tableColumns.purchase[9].title = i18n.t('total');
                    // EmailSent 列(索引10)标题保持为 "EmailSent"，不需要翻译
                }

                // 更新销售记录表格列标题
                if (tableColumns.sales && tableColumns.sales[2]) {
                    tableColumns.sales[2].title = i18n.t('saleDate');
                    tableColumns.sales[3].title = i18n.t('customerName');
                    tableColumns.sales[4].title = i18n.t('detailCount');
                    tableColumns.sales[5].title = i18n.t('subtotal');
                    tableColumns.sales[6].title = i18n.t('gst');
                    tableColumns.sales[7].title = i18n.t('total');
                    tableColumns.sales[8].title = i18n.t('action');
                }
            } catch (error) {
                console.error('Error updating table column titles:', error);
            }
        }

        /**
         * 更新页面语言
         */
        function updatePageLanguage(lang, showMessage) {
            // showMessage: 是否显示切换提示，默认false
            if (showMessage === undefined) showMessage = false;

            var isZh = lang === 'zh';

            // 更新HTML lang属性和body class
            $('html').attr('lang', isZh ? 'zh-CN' : 'en');
            $('body').removeClass('lang-zh lang-en').addClass('lang-' + lang);

            // 更新表格列标题（仅在表格已初始化时）
            if (typeof tableColumns !== 'undefined') {
                updateTableColumnTitles();
            }

            // 更新所有带有 data-i18n 属性的元素
            $('[data-i18n]').each(function(){
                var key = $(this).data('i18n');
                var translation = i18n.t(key);
                if ($(this).is('input') || $(this).is('textarea')) {
                    $(this).attr('placeholder', translation);
                } else {
                    $(this).text(translation);
                }
            });

            // 更新页面标题
            document.title = i18n.t('pageTitle');

            // 更新顶部导航
            $('span[data-i18n="systemTitle"]').text(i18n.t('systemTitle'));

            // 更新语言切换按钮文本
            $('#lang-toggle').text(isZh ? 'English' : '中文');

            // 刷新表格以显示新语言
            if (tables.landing) tables.landing.reload();
            if (tables.purchase) tables.purchase.reload();
            if (tables.sales) tables.sales.reload();

            // 只有在showMessage为true时才显示提示
            if (showMessage) {
                layer.msg(isZh ? '已切换到中文' : 'Switched to English', {icon: 1});
            }
        }

        // 初始化时更新页面语言
        var currentLang = i18n.getCurrentLanguage();
        updatePageLanguage(currentLang);
    });

    // ==================== 辅助函数 ====================

    /**
     * 获取模块名称
     */
    function getModuleName(module) {
        var i18n = layui.i18n;
        if (i18n) {
            var names = {
                'landing': i18n.t('landing'),
                'purchase': i18n.t('purchase'),
                'sales': i18n.t('sales')
            };
            return names[module] || 'Unknown';
        } else {
            var names = {
                'landing': 'Landing Records',
                'purchase': 'Purchase Records',
                'sales': 'Sales Records'
            };
            return names[module] || 'Unknown';
        }
    }

    /**
     * 从到货记录生成采购单
     */
    function generatePurchaseFromLanding(landingData) {
        var i18n = layui.i18n;
        var confirmMsg = i18n ? i18n.t('confirmGeneratePurchase') : 'Are you sure to generate purchase from this landing record?';
        var btnConfirm = i18n ? i18n.t('confirm') : 'Confirm';
        var btnCancel = i18n ? i18n.t('cancel') : 'Cancel';

        // 翻译错误消息的函数
        function translateErrorMessage(msg) {
            if (!msg) return '';
            if (msg.indexOf('该到货记录已生成过采购单') !== -1) {
                // 提取 PurchaseID
                var match = msg.match(/PurchaseID[:\s]*(\d+)/);
                var purchaseId = match ? match[1] : '';
                var template = i18n ? i18n.t('errorPurchaseAlreadyGenerated') : 'This landing record has already generated a purchase order (PurchaseID: {purchaseId})';
                return template.replace('{purchaseId}', purchaseId);
            } else if (msg.indexOf('到货记录不存在或已删除') !== -1) {
                return i18n ? i18n.t('errorLandingNotExist') : 'Landing record does not exist or has been deleted';
            } else if (msg.indexOf('到货记录没有明细数据') !== -1) {
                return i18n ? i18n.t('errorLandingNoDetails') : 'Landing record has no detail data, cannot generate purchase order';
            }
            return msg;
        }

        layer.confirm(confirmMsg, {btn: [btnConfirm, btnCancel]}, function(index){
            var loadingIndex = layer.load(1);

            // 调用后端API
            $.ajax({
                url: '../api/landing-to-purchase.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    LandingID: landingData.LandingID
                }),
                success: function(res){
                    layer.close(loadingIndex);
                    layer.close(index);

                    if (res.success) {
                        // 显示生成结果详情
                        var t = i18n ? i18n.t : function(key) { return key; };
                        var resultHtml = '<div style="padding: 20px;">';
                        resultHtml += '<p><strong>' + t('purchaseGenerated') + '</strong></p>';
                        resultHtml += '<p>' + t('purchaseOrderId') + ': <span style="color:#16a34a;font-weight:bold;">' + res.data.PurchaseID + '</span></p>';
                        resultHtml += '<p>' + t('landingRecordId') + ': ' + res.data.LandingID + '</p>';
                        resultHtml += '<p>' + t('purchaseDate') + ': ' + res.data.PurchaseDate + '</p>';
                        resultHtml += '<p>' + t('detailCount') + ': <span style="color:#2563eb;font-weight:bold;">' + res.data.details_count + '</span></p>';
                        resultHtml += '<hr style="margin: 15px 0;">';
                        resultHtml += '<p>' + t('subtotal') + ': $' + res.data.Subtotal + '</p>';
                        resultHtml += '<p>' + t('gst') + ': $' + res.data.GST + '</p>';
                        resultHtml += '<p>' + t('total') + ': <span style="color:#dc2626;font-weight:bold;font-size:18px;">$' + res.data.Total + '</span></p>';
                        resultHtml += '</div>';

                        // 移动端适配
                        var isMobile = window.innerWidth <= 768;
                        var resultArea = isMobile ? ['90%', 'auto'] : ['500px', 'auto'];

                        layer.open({
                            type: 1,
                            title: t('purchaseGenerated'),
                            area: resultArea,
                            resize: isMobile,
                            move: !isMobile,
                            content: resultHtml,
                            btn: [t('viewPurchase'), t('close')],
                            yes: function(idx){
                                layer.close(idx);
                                // 切换到采购记录选项卡
                                element.tabChange('main-tab', 'purchase');
                                // 刷新采购记录列表
                                tables['purchase'].reload();
                            },
                            btn2: function(idx){
                                layer.close(idx);
                                // 刷新采购记录列表
                                tables['purchase'].reload();
                            }
                        });

                        // 同时刷新到货记录列表
                        tables['landing'].reload();
                    } else {
                        var translatedMsg = translateErrorMessage(res.message);
                        layer.msg(translatedMsg || (i18n ? i18n.t('purchaseFailed') : 'Failed to generate purchase order'), {icon: 2});
                    }
                },
                error: function(xhr, status, error){
                    layer.close(loadingIndex);
                    layer.close(index);
                    layer.msg('Network error, please try again later', {icon: 2});
                }
            });
        });
    }

    /**
     * 从采购记录生成销售单
     */
    function generateSalesFromPurchase(purchaseData) {
        var i18n = layui.i18n;
        var confirmMsg = i18n ? i18n.t('confirmGenerateSales') : 'Are you sure to generate sales from this purchase record?';
        var btnConfirm = i18n ? i18n.t('confirm') : 'Confirm';
        var btnCancel = i18n ? i18n.t('cancel') : 'Cancel';

        // 翻译错误消息的函数
        function translateErrorMessage(msg) {
            if (!msg) return '';
            if (msg.indexOf('该采购记录已生成销售单') !== -1) {
                return i18n ? i18n.t('errorSalesAlreadyGenerated') : 'This purchase record has already generated a sales order. Please do not regenerate.';
            } else if (msg.indexOf('采购记录不存在') !== -1) {
                return i18n ? i18n.t('errorPurchaseNotExist') : 'Purchase record does not exist';
            } else if (msg.indexOf('采购明细不存在') !== -1) {
                return i18n ? i18n.t('errorPurchaseNoDetails') : 'Purchase details do not exist';
            }
            return msg;
        }

        layer.confirm(confirmMsg, {btn: [btnConfirm, btnCancel]}, function(index){
            var loadingIndex = layer.load(1);

            // 调用后端API
            $.ajax({
                url: '/api/purchase-to-sales.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    PurchaseID: purchaseData.PurchaseID
                }),
                success: function(res){
                    layer.close(loadingIndex);
                    layer.close(index);

                    if (res.success) {
                        // 显示生成结果详情
                        var t = i18n ? i18n.t : function(key) { return key; };
                        var resultHtml = '<div style="padding: 20px;">';
                        resultHtml += '<p><strong>' + t('salesGenerated') + '</strong></p>';
                        resultHtml += '<p>' + t('salesOrderId') + ': <span style="color:#16a34a;font-weight:bold;">' + res.data.SalesID + '</span></p>';
                        resultHtml += '<p>' + t('purchaseRecordId') + ': ' + res.data.PurchaseID + '</p>';
                        resultHtml += '<p>' + t('saleDate') + ': ' + res.data.SaleDate + '</p>';
                        resultHtml += '<p>' + t('detailCount') + ': <span style="color:#2563eb;font-weight:bold;">' + res.data.details_count + '</span></p>';
                        resultHtml += '<hr style="margin: 15px 0;">';
                        resultHtml += '<p>' + t('subtotal') + ': $' + res.data.Subtotal + '</p>';
                        resultHtml += '<p>' + t('gst') + ': $' + res.data.GST + '</p>';
                        resultHtml += '<p>' + t('total') + ': <span style="color:#dc2626;font-weight:bold;font-size:18px;">$' + res.data.Total + '</span></p>';
                        resultHtml += '</div>';

                        // 移动端适配
                        var isMobile = window.innerWidth <= 768;
                        var resultArea = isMobile ? ['90%', 'auto'] : ['500px', 'auto'];

                        layer.open({
                            type: 1,
                            title: t('salesGenerated'),
                            area: resultArea,
                            resize: isMobile,
                            move: !isMobile,
                            content: resultHtml,
                            btn: [t('viewSales'), t('close')],
                            yes: function(idx){
                                layer.close(idx);
                                // 切换到销售记录选项卡
                                element.tabChange('main-tab', 'sales');
                                // 刷新销售记录列表
                                tables['sales'].reload();
                            },
                            btn2: function(idx){
                                layer.close(idx);
                                // 刷新销售记录列表
                                tables['sales'].reload();
                            }
                        });

                        // 同时刷新采购记录列表
                        tables['purchase'].reload();
                    } else {
                        var translatedMsg = translateErrorMessage(res.message);
                        layer.msg(translatedMsg || (i18n ? i18n.t('salesFailed') : 'Failed to generate sales order'), {icon: 2});
                    }
                },
                error: function(xhr, status, error){
                    layer.close(loadingIndex);
                    layer.close(index);
                    layer.msg('Network error, please try again later', {icon: 2});
                }
            });
        });
    }

    // ==================== 到货记录专用函数 ====================

    /**
     * 格式化显示重量（带单位）
     * @param {number} value - 重量值
     * @param {number} unitId - 单位ID
     * @returns {string} 格式化后的字符串
     */
    function formatWeightWithUnit(value, unitId) {
        if (!window.landingOptions.units || window.landingOptions.units.length === 0) {
            return (value || 0) + ' kg';
        }

        var unit = window.landingOptions.units.find(function(u){
            return u.UnitID == unitId;
        });

        var symbol = unit ? unit.UnitSymbol : 'kg';
        return (value || 0) + ' ' + symbol;
    }

    /**
     * 获取价格（优先使用供应商特定价格）
     * @param {string} supplierId - 供应商ID
     * @param {string} stockId - 鱼种ID
     * @returns {number} 价格
     */
    function getLandingPrice(supplierId, stockId) {
        // 先查找供应商特定价格
        if (supplierId && stockId && window.landingOptions && window.landingOptions.priceMap) {
            var key = supplierId + '_' + stockId;
            var supplierPrice = window.landingOptions.priceMap[key];
            if (supplierPrice !== undefined && supplierPrice !== null) {
                return parseFloat(supplierPrice);
            }
        }
        // 如果没有供应商特定价格，使用默认价格
        if (stockId && window.landingOptions && window.landingOptions.stocks) {
            var stock = window.landingOptions.stocks.find(function(item){
                return item.StockID === stockId;
            });
            if (stock && stock.Price) {
                return parseFloat(stock.Price);
            }
        }
        return 0;
    }

    /**
     * 全局变量：存储选项数据和明细数据
     */
    window.landingOptions = {
        suppliers: [],
        ports: [],
        stocks: [],
        stockList: [],  // Stock 列表（去重）
        stockStateMap: {},  // Stock -> State 映射
        bins: [],
        boats: [],
        details: []
    };

    /**
     * 加载选项数据
     */
    function loadLandingOptions() {
        return $.ajax({
            url: '../api/landing-options.php',
            method: 'GET',
            dataType: 'json'
        }).then(function(res){
            if (res.success) {
                window.landingOptions.suppliers = res.data.suppliers || [];
                window.landingOptions.ports = res.data.ports || [];
                window.landingOptions.stocks = res.data.stocks || [];
                window.landingOptions.bins = res.data.bins || [];
                window.landingOptions.boats = res.data.boats || [];
                window.landingOptions.priceMap = res.data.priceMap || {};
                window.landingOptions.supplierBoatMap = res.data.supplierBoatMap || {};
                window.landingOptions.units = res.data.units || [];  // 加载单位列表

                // 构建 Stock 列表和 Stock->State 映射
                buildStockData();
                console.log('供应商-船只映射已加载:', window.landingOptions.supplierBoatMap);
                console.log('单位列表已加载:', window.landingOptions.units);
            }
            return res;
        });
    }

    /**
     * 构建 Stock 列表和 Stock->State 映射
     */
    function buildStockData() {
        var stocks = window.landingOptions.stocks || [];
        var stockSet = new Set();
        var stockStateMap = {};

        // 收集所有唯一的 Stock
        stocks.forEach(function(item) {
            if (item.Stock) {
                stockSet.add(item.Stock);
            }
        });

        // 构建 Stock 列表（排序）
        window.landingOptions.stockList = Array.from(stockSet).sort();

        // 构建 Stock -> State 列表映射
        window.landingOptions.stockList.forEach(function(stock) {
            var states = stocks
                .filter(function(item) { return item.Stock === stock; })
                .map(function(item) { return item.State; })
                .filter(function(state) { return state; })
                .sort();
            stockStateMap[stock] = states;
        });

        window.landingOptions.stockStateMap = stockStateMap;

        console.log('Stock列表:', window.landingOptions.stockList);
        console.log('Stock->State映射:', stockStateMap);
    }

    /**
     * 根据 Stock 和 State 查找 StockID
     */
    function findStockID(stock, state) {
        var stocks = window.landingOptions.stocks || [];
        var found = stocks.find(function(item) {
            return item.Stock === stock && item.State === state;
        });
        return found ? found.StockID : '';
    }

    /**
     * 根据 StockID 查找 Stock 和 State
     */
    function findStockState(stockID) {
        var stocks = window.landingOptions.stocks || [];
        var found = stocks.find(function(item) {
            return item.StockID === stockID;
        });
        return found ? { Stock: found.Stock, State: found.State } : { Stock: '', State: '' };
    }

    /**
     * 获取上一次保存的 landing 值
     */
    function getLastLandingValues() {
        try {
            var lastValues = localStorage.getItem('lastLandingValues');
            if (lastValues) {
                return JSON.parse(lastValues);
            }
        } catch (e) {
            console.error('读取上次值失败:', e);
        }
        return {
            supplierId: '',
            portId: '',
            boatId: ''
        };
    }

    /**
     * 获取默认日期（今天）
     */
    function getLastLandingDate() {
        // 获取今天的日期，格式为 YYYY-MM-DD
        var today = new Date();
        var year = today.getFullYear();
        var month = String(today.getMonth() + 1).padStart(2, '0');
        var day = String(today.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    /**
     * 保存当前 landing 表单的值
     */
    function saveLandingValues(supplierId, portId, boatId) {
        try {
            var values = {
                supplierId: supplierId,
                portId: portId,
                boatId: boatId,
                savedAt: new Date().toISOString()
            };
            localStorage.setItem('lastLandingValues', JSON.stringify(values));
        } catch (e) {
            console.error('保存值失败:', e);
        }
    }

    /**
     * 初始化明细表
     */
    function initDetailTable(data) {
        var details = data && data.details ? data.details : [];
        window.landingOptions.details = details;

        // 判断是否为新增模式
        var isNewRecord = !data || !data.LandingID;

        // Get i18n translations
        var i18n = layui.i18n;
        var t = i18n ? i18n.t : function(key) { return key; };
        var pleaseSelect = t('pleaseSelect');

        // 加载选项数据
        loadLandingOptions().then(function(){
            // 如果是新增模式，尝试从 localStorage 读取上次的值
            var lastValues = isNewRecord ? getLastLandingValues() : {};

            // 填充供应商下拉框
            var supplierOptions = '<option value="">' + pleaseSelect + '</option>';
            window.landingOptions.suppliers.forEach(function(item){
                var selected = false;
                if (data && data.SupplierID == item.SupplierID) {
                    selected = true;
                } else if (isNewRecord && lastValues.supplierId == item.SupplierID) {
                    selected = true;
                }
                supplierOptions += '<option value="' + item.SupplierID + '" ' + (selected ? 'selected' : '') + '>' + item.SupplierName + '</option>';
            });
            $('#supplier-select').html(supplierOptions);

            // 填充港口下拉框
            var portOptions = '<option value="">' + pleaseSelect + '</option>';
            window.landingOptions.ports.forEach(function(item){
                var selected = false;
                if (data && data.PortID == item.PortID) {
                    selected = true;
                } else if (isNewRecord && lastValues.portId == item.PortID) {
                    selected = true;
                }
                portOptions += '<option value="' + item.PortID + '" ' + (selected ? 'selected' : '') + '>' + item.Port + '</option>';
            });
            $('#port-select').html(portOptions);

            // 获取当前选中的供应商ID和船只ID
            var currentSupplierId = null;
            var currentBoatId = null;
            if (data && data.SupplierID) {
                currentSupplierId = data.SupplierID;
                currentBoatId = data.BoatID;
            } else if (isNewRecord && lastValues.supplierId) {
                currentSupplierId = lastValues.supplierId;
                currentBoatId = lastValues.boatId;
            }

            // 填充船只下拉框（根据供应商的船队过滤）- 使用缓存数据，无延迟
            var loadBoatOptions = function(supplierId, boatId) {
                if (!supplierId) {
                    // 如果没有选择供应商，不显示船只
                    var boatOptions = '<option value="">' + pleaseSelect + '</option>';
                    $('#boat-select').html(boatOptions);
                    form.render('select');
                } else {
                    // 从缓存中获取该供应商的船只（无需 AJAX 请求，即时响应）
                    var boats = window.landingOptions.supplierBoatMap && window.landingOptions.supplierBoatMap[supplierId];
                    var boatOptions = '<option value="">' + pleaseSelect + '</option>';

                    if (!boats || boats.length === 0) {
                        boatOptions = '<option value="">' + pleaseSelect + ' (No available boats)</option>';
                    } else {
                        boats.forEach(function(item){
                            var selected = (boatId == item.BoatID);
                            boatOptions += '<option value="' + item.BoatID + '" ' + (selected ? 'selected' : '') + '>' + item.BoatName + ' (' + item.BoatNo + ')</option>';
                        });
                    }
                    $('#boat-select').html(boatOptions);
                    form.render('select');
                }
            };

            // 初始加载船只选项
            loadBoatOptions(currentSupplierId, currentBoatId);

            form.render('select');

            // 初始化日期选择器
            var defaultDate = isNewRecord ? getLastLandingDate() : (data.LandingDate || '');
            laydate.render({
                elem: '#landing-date-input',
                type: 'date',
                format: 'yyyy-MM-dd',
                value: defaultDate
            });

            // 如果是新增模式且设置了默认日期，更新输入框显示
            if (isNewRecord && defaultDate) {
                $('#landing-date-input').val(defaultDate);
            }

            // 渲染明细表
            renderDetailTable();

            // 监听供应商选择变化，更新船只选项和明细行价格
            // 使用 LayUI 的 form.on() 方法，因为 LayUI 渲染后原始 select 被替换
            form.on('select(supplier-filter)', function(data){
                var supplierId = data.value || '';

                // 更新船只选项（根据供应商的船队过滤）- 使用缓存数据，无延迟
                var pleaseSelect = t('pleaseSelect');
                if (!supplierId) {
                    // 如果没有选择供应商，不显示船只
                    var boatOptions = '<option value="">' + pleaseSelect + '</option>';
                    $('#boat-select').html(boatOptions);
                    form.render('select');
                } else {
                    // 从缓存中获取该供应商的船只（无需 AJAX 请求，即时响应）
                    var boats = window.landingOptions.supplierBoatMap && window.landingOptions.supplierBoatMap[supplierId];
                    var boatOptions = '<option value="">' + pleaseSelect + '</option>';

                    if (!boats || boats.length === 0) {
                        boatOptions = '<option value="">' + pleaseSelect + ' (No available boats)</option>';
                    } else {
                        boats.forEach(function(item){
                            boatOptions += '<option value="' + item.BoatID + '">' + item.BoatName + ' (' + item.BoatNo + ')</option>';
                        });
                    }
                    $('#boat-select').html(boatOptions);
                    form.render('select');
                }

                // 遍历所有明细行，更新价格输入框为空的行
                $('.detail-stock-select').each(function(){
                    var $stockSelect = $(this);
                    var stockId = $stockSelect.val();
                    var $priceInput = $stockSelect.closest('tr').find('.detail-price-input');

                    // 只有当价格输入框为空且已选择鱼种时才更新价格
                    if (stockId && $priceInput.length > 0 && !$priceInput.val()) {
                        var price = getLandingPrice(supplierId, stockId);
                        if (price > 0) {
                            $priceInput.val(price);
                        }
                    }
                });
            });

            // 监听添加明细按钮
            $('#add-detail-btn').on('click', function(){
                addDetailRow();
            });
        });
    }

    /**
     * 渲染明细表
     */
    function renderDetailTable() {
        console.log('渲染明细表，数据条数:', window.landingOptions.details.length);
        console.log('明细数据:', window.landingOptions.details);

        // Get i18n translations
        var i18n = layui.i18n;
        var t = i18n ? function(key) { return i18n.t(key); } : function(key) { return key; };

        table.render({
            elem: '#detail-table',
            data: window.landingOptions.details,
            cols: [[
                {field: 'Stock', title: t('stock'), width: 150, templet: function(d){
                    // 从StockID中解析出Stock和State
                    var stockState = d.StockID ? findStockState(d.StockID) : { Stock: '', State: '' };
                    var selectedStock = stockState.Stock;

                    var options = '<select class="layui-input detail-stock-select" lay-ignore>';
                    options += '<option value="">' + t('selectStock') + '</option>';
                    if (window.landingOptions.stockList && window.landingOptions.stockList.length > 0) {
                        window.landingOptions.stockList.forEach(function(stock){
                            var selected = selectedStock === stock ? 'selected' : '';
                            options += '<option value="' + stock + '" ' + selected + '>' + stock + '</option>';
                        });
                    }
                    options += '</select>';
                    return options;
                }},
                {field: 'State', title: t('state'), width: 150, templet: function(d){
                    // 从StockID中解析出Stock和State
                    var stockState = d.StockID ? findStockState(d.StockID) : { Stock: '', State: '' };
                    var selectedStock = stockState.Stock;
                    var selectedState = stockState.State;

                    var options = '<select class="layui-input detail-state-select" lay-ignore>';
                    options += '<option value="">' + t('selectState') + '</option>';

                    // 如果已选择Stock，显示对应的State选项
                    if (selectedStock && window.landingOptions.stockStateMap && window.landingOptions.stockStateMap[selectedStock]) {
                        window.landingOptions.stockStateMap[selectedStock].forEach(function(state){
                            var selected = selectedState === state ? 'selected' : '';
                            options += '<option value="' + state + '" ' + selected + '>' + state + '</option>';
                        });
                    }

                    options += '</select>';
                    return options;
                }},
                {field: 'Description', title: t('description') || 'Description', width: 200, templet: function(d){
                    // 查找对应的Description值
                    var description = '';
                    if (window.landingOptions.stocks && window.landingOptions.stocks.length > 0) {
                        var stock = window.landingOptions.stocks.find(function(item){
                            return item.StockID === d.StockID;
                        });
                        if (stock) {
                            description = stock.Description || '';
                        }
                    }
                    var color = description ? '#059669' : '#999';
                    var fontWeight = description ? 'normal' : 'normal';
                    return '<span class="detail-description-display" style="color:' + color + ';font-weight:' + fontWeight + ';font-size:12px;">' + (description || '-') + '</span>';
                }},
                {field: 'Price', title: t('price'), width: 120, templet: function(d){
                    // 使用新逻辑获取默认价格（优先使用供应商特定价格）
                    var supplierId = $('#supplier-select').val() || '';
                    var stockId = d.StockID;
                    var price = d.Price || 0;

                    // 如果Price为空，尝试获取默认价格
                    if (!price || price === 0) {
                        // 先尝试获取供应商特定价格
                        if (supplierId && stockId && window.landingOptions.priceMap) {
                            var key = supplierId + '_' + stockId;
                            var supplierPrice = window.landingOptions.priceMap[key];
                            if (supplierPrice !== undefined && supplierPrice !== null) {
                                price = parseFloat(supplierPrice);
                            }
                        }

                        // 如果没有供应商特定价格，使用默认价格
                        if (price === 0 && window.landingOptions.stocks && window.landingOptions.stocks.length > 0) {
                            var stock = window.landingOptions.stocks.find(function(item){
                                return item.StockID === stockId;
                            });
                            if (stock && stock.Price) {
                                price = stock.Price;
                            }
                        }
                    }

                    return '<input type="number" step="0.01" class="layui-input detail-price-input" value="' + (price || '') + '" placeholder="' + t('price') + '">';
                }},
                {field: 'Area', title: t('area') || 'Area', width: 100, templet: function(d){
                    // 查找对应的Area值
                    var area = '';
                    if (window.landingOptions.stocks && window.landingOptions.stocks.length > 0) {
                        var stock = window.landingOptions.stocks.find(function(item){
                            return item.StockID === d.StockID;
                        });
                        if (stock) {
                            area = stock.Area || '';
                        }
                    }
                    var color = area ? '#2563eb' : '#999';
                    var fontWeight = area ? 'bold' : 'normal';
                    return '<span class="detail-area-display" style="color:' + color + ';font-weight:' + fontWeight + ';">' + (area || '-') + '</span>';
                }},
                {field: 'BinID', title: t('bin'), width: 180, templet: function(d){
                    var options = '<select class="layui-input detail-bin-select" lay-ignore>';
                    options += '<option value="">' + t('selectStock') + '</option>';
                    if (window.landingOptions.bins && window.landingOptions.bins.length > 0) {
                        window.landingOptions.bins.forEach(function(item){
                            var selected = d.BinID == item.BinID ? 'selected' : '';
                            options += '<option value="' + item.BinID + '" ' + selected + '>' + item.BinName + ' (' + item['B-Weight'] + 'kg)</option>';
                        });
                    }
                    options += '</select>';
                    return options;
                }},
                {field: 'BinQty', title: t('binQty'), width: 100, templet: function(d){
                    return '<input type="number" step="1" class="layui-input detail-binqty-input" value="' + (d.BinQty || 1) + '" placeholder="1">';
                }},
                {field: 'ICE', title: t('ice'), width: 100, templet: function(d){
                    var iceValue = d.ICE || 0;
                    var options = '<select class="layui-input detail-ice-select" lay-ignore>';
                    options += '<option value="0" ' + (iceValue == 0 ? 'selected' : '') + '>No</option>';
                    options += '<option value="1" ' + (iceValue == 1 ? 'selected' : '') + '>Yes</option>';
                    options += '</select>';
                    return options;
                }},
                {field: 'L-Weight', title: t('landedWeight') + '(kg)', width: 150, templet: function(d){
                    return '<input type="number" step="0.1" class="layui-input detail-weight-input" value="' + (d['L-Weight'] || '') + '" placeholder="' + t('enterDate') + '">';
                }},
                {field: 'WeightUnitID', title: t('unit') || 'Unit', width: 120, templet: function(d){
                    var unitId = d.WeightUnitID || 1;  // 默认为 1 (KG)
                    var options = '<select class="layui-input detail-unit-select" lay-ignore>';
                    if (window.landingOptions.units && window.landingOptions.units.length > 0) {
                        window.landingOptions.units.forEach(function(unit){
                            var selected = parseInt(unitId) == unit.UnitID ? 'selected' : '';
                            options += '<option value="' + unit.UnitID + '" ' + selected + '>' + unit.UnitCode + '</option>';
                        });
                    }
                    options += '</select>';
                    return options;
                }},
                {field: 'action', title: t('action'), width: 100, fixed: 'right', templet: function(d){
                    return '<a class="layui-btn layui-btn-xs layui-btn-danger" onclick="window.deleteDetailRow(' + d.LAY_TABLE_INDEX + ')">' + t('delete') + '</a>';
                }}
            ]],
            limit: 100,
            height: 300,
            page: false,
            done: function(res){
                console.log('表格渲染完成，数据条数:', res.data.length);
                // 延迟绑定事件，确保DOM完全加载
                setTimeout(function(){
                    bindStockChangeEvent();
                }, 100);
            }
        });
    }

    /**
     * 绑定库存下拉框的change事件
     */
    function bindStockChangeEvent() {
        // 获取 i18n 翻译函数
        var i18n = layui.i18n;
        var t = i18n ? function(key) { return i18n.t(key); } : function(key) { return key; };

        // 获取当前选中的供应商ID
        function getCurrentSupplierId() {
            var supplierSelect = $('#supplier-select');
            return supplierSelect.val() || '';
        }

        // Stock 下拉框变化事件
        $(document).off('change', '.detail-stock-select').on('change', '.detail-stock-select', function(e){
            e.preventDefault();
            var $select = $(this);
            var stock = $select.val();

            // 获取当前行
            var $tr = $select.closest('tr');
            var $stateSelect = $tr.find('.detail-state-select');

            console.log('=== Stock选择事件触发 ===');
            console.log('选中的Stock:', stock);

            // 如果选择的是空值，清空State下拉框
            if (!stock) {
                $stateSelect.html('<option value="">' + t('selectState') + '</option>');
                $tr.find('.detail-price-input').val('');
                $tr.find('.detail-area-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.detail-description-display').text('-').css('color', '#999').css('font-weight', 'normal');
                console.log('清空Stock选择');
                return;
            }

            // 更新State下拉框的选项
            var stateOptions = '<option value="">' + t('selectState') + '</option>';
            if (window.landingOptions.stockStateMap && window.landingOptions.stockStateMap[stock]) {
                window.landingOptions.stockStateMap[stock].forEach(function(state){
                    stateOptions += '<option value="' + state + '">' + state + '</option>';
                });
            }
            $stateSelect.html(stateOptions);
        });

        // State 下拉框变化事件
        $(document).off('change', '.detail-state-select').on('change', '.detail-state-select', function(e){
            e.preventDefault();
            var $stateSelect = $(this);
            var state = $stateSelect.val();

            // 获取当前行
            var $tr = $stateSelect.closest('tr');
            var $stockSelect = $tr.find('.detail-stock-select');
            var stock = $stockSelect.val();

            console.log('=== State选择事件触发 ===');
            console.log('选中的Stock:', stock, 'State:', state);

            // 如果选择的是空值，清空显示
            if (!stock || !state) {
                $tr.find('.detail-price-input').val('');
                $tr.find('.detail-area-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.detail-description-display').text('-').css('color', '#999').css('font-weight', 'normal');
                console.log('清空State选择');
                return;
            }

            // 根据 Stock + State 查找 StockID
            var stockId = findStockID(stock, state);
            console.log('查找到的StockID:', stockId);

            // 查找对应的其他属性
            var stockData = window.landingOptions.stocks.find(function(item){
                return item.StockID === stockId;
            });

            if (stockData) {
                var area = stockData.Area || '';
                var description = stockData.Description || '';

                // 使用全局函数获取价格（优先使用供应商特定价格）
                var supplierId = getCurrentSupplierId();
                var price = getLandingPrice(supplierId, stockId);

                console.log('Stock数据 - Area:', area, 'Description:', description, 'SupplierID:', supplierId, 'Price:', price);

                // 更新显示
                var $areaSpan = $tr.find('.detail-area-display');
                var $descriptionSpan = $tr.find('.detail-description-display');
                var $priceInput = $tr.find('.detail-price-input');

                // 每次重新选择State时都更新价格
                if ($priceInput.length > 0) {
                    $priceInput.val(price > 0 ? price : '');
                    console.log('Price已更新为:', price);
                }

                if ($areaSpan.length > 0) {
                    var areaColor = area ? '#2563eb' : '#999';
                    var areaFontWeight = area ? 'bold' : 'normal';
                    $areaSpan.text(area || '-').css('color', areaColor).css('font-weight', areaFontWeight);
                    console.log('Area已更新为:', area || '-');
                }

                if ($descriptionSpan.length > 0) {
                    var descColor = description ? '#059669' : '#999';
                    $descriptionSpan.text(description || '-').css('color', descColor).css('font-weight', 'normal');
                    console.log('Description已更新为:', description || '-');
                }
            }
        });
    }

    /**
     * 添加明细行
     */
    window.addDetailRow = function() {
        // 先收集当前表格中已输入的数据（包括未填完整的）
        var currentData = collectCurrentDetailData();

        // 添加新行（包含默认单位）
        currentData.push({
            StockID: '',
            BinID: '',
            'L-Weight': '',
            WeightUnitID: 1,  // 默认为 1 (KG)
            ICE: 0,
            Price: 0
        });

        // 更新数据并重新渲染
        window.landingOptions.details = currentData;
        renderDetailTable();
    };

    /**
     * 删除明细行
     */
    window.deleteDetailRow = function(index) {
        var i18n = layui.i18n;
        var confirmMsg = i18n ? i18n.t('deleteDetailConfirm') : 'Are you sure to delete this detail?';
        var btnConfirm = i18n ? i18n.t('confirm') : 'Confirm';
        var btnCancel = i18n ? i18n.t('cancel') : 'Cancel';

        layer.confirm(confirmMsg, {btn: [btnConfirm, btnCancel]}, function(i){
            // 先收集当前表格中已输入的数据
            var currentData = collectCurrentDetailData();
            // 删除指定行
            currentData.splice(index, 1);
            // 更新数据并重新渲染
            window.landingOptions.details = currentData;
            renderDetailTable();
            layer.close(i);
        });
    };

    /**
     * 收集当前表格中的明细数据（包括未填完整的）
     */
    function collectCurrentDetailData() {
        var details = [];
        var stockSelects = $('.detail-stock-select');
        var stateSelects = $('.detail-state-select');
        var binSelects = $('.detail-bin-select');
        var binQtyInputs = $('.detail-binqty-input');
        var iceSelects = $('.detail-ice-select');
        var weightInputs = $('.detail-weight-input');
        var unitSelects = $('.detail-unit-select');  // 添加单位选择器
        var priceInputs = $('.detail-price-input');

        // 获取当前选中的供应商ID
        var supplierId = $('#supplier-select').val() || '';

        for (var i = 0; i < stockSelects.length; i++) {
            var stock = $(stockSelects[i]).val();
            var state = $(stateSelects[i]).val();
            var binID = $(binSelects[i]).val();
            var binQty = $(binQtyInputs[i]).val();
            var ice = $(iceSelects[i]).val();
            var weight = $(weightInputs[i]).val();
            var weightUnitId = $(unitSelects[i]).val();  // 获取单位ID
            var price = $(priceInputs[i]).val();

            // 根据 Stock + State 组合查找 StockID
            var stockID = '';
            if (stock && state) {
                stockID = findStockID(stock, state);
            }

            // 如果价格输入框为空，尝试获取默认价格
            if (!price && stockID) {
                price = getLandingPrice(supplierId, stockID);
            }

            // 收集所有数据，包括未填完整的
            details.push({
                StockID: stockID || '',
                BinID: binID || '',
                BinQty: binQty ? parseInt(binQty) : 1,
                ICE: ice ? parseInt(ice) : 0,
                'L-Weight': weight ? parseFloat(weight) : '',
                WeightUnitID: weightUnitId ? parseInt(weightUnitId) : 1,  // 默认为 1 (KG)
                'Price': price ? parseFloat(price) : 0
            });
        }

        return details;
    }

    /**
     * 收集明细数据（仅收集完整的）
     */
    function collectDetailData() {
        var details = [];
        var stockSelects = $('.detail-stock-select');
        var stateSelects = $('.detail-state-select');
        var binSelects = $('.detail-bin-select');
        var binQtyInputs = $('.detail-binqty-input');
        var iceSelects = $('.detail-ice-select');
        var weightInputs = $('.detail-weight-input');
        var unitSelects = $('.detail-unit-select');  // 单位选择器
        var priceInputs = $('.detail-price-input');

        // 获取当前选中的供应商ID
        var supplierId = $('#supplier-select').val() || '';

        console.log('collectDetailData - stockSelects.length:', stockSelects.length);

        for (var i = 0; i < stockSelects.length; i++) {
            var stock = $(stockSelects[i]).val();
            var state = $(stateSelects[i]).val();
            var binID = $(binSelects[i]).val();
            var binQty = $(binQtyInputs[i]).val();
            var ice = $(iceSelects[i]).val();
            var weight = $(weightInputs[i]).val();
            var weightUnitId = $(unitSelects[i]).val();  // 获取单位ID
            var price = $(priceInputs[i]).val();

            // 根据 Stock + State 组合查找 StockID
            var stockID = '';
            if (stock && state) {
                stockID = findStockID(stock, state);
            }

            console.log('行', i, ': stock=', stock, 'state=', state, 'stockID=', stockID, 'binID=', binID, 'binQty=', binQty, 'ice=', ice, 'weight=', weight, 'weightUnitId=', weightUnitId, 'price=', price);

            // 只收集完整的数据（L-Weight 可以为空）
            if (stockID && binID) {
                // 如果价格输入框为空，尝试获取默认价格
                if (!price) {
                    price = getLandingPrice(supplierId, stockID);
                }

                details.push({
                    StockID: stockID,
                    BinID: binID,
                    BinQty: binQty ? parseInt(binQty) : 1,
                    ICE: ice ? parseInt(ice) : 0,
                    'L-Weight': weight ? parseFloat(weight) : '',
                    WeightUnitID: weightUnitId ? parseInt(weightUnitId) : 1,  // 默认为 1 (KG)
                    'Price': price ? parseFloat(price) : 0
                });
            }
        }

        console.log('收集到的明细数据:', details);
        return details;
    }

    // ==================== 采购记录专用函数 ====================

    /**
     * 全局变量：存储采购选项数据和明细数据
     */
    window.purchaseOptions = {
        suppliers: [],
        stocks: [],
        details: []
    };

    /**
     * 初始化采购明细表
     */
    function initPurchaseDetailTable(data) {
        var details = data && data.details ? data.details : [];
        window.purchaseOptions.details = details;

        // Get i18n translations
        var i18n = layui.i18n;
        var t = i18n ? i18n.t : function(key) { return key; };
        var pleaseSelect = t('pleaseSelect');

        // 加载选项数据
        loadLandingOptions().then(function(){
            // 填充供应商下拉框
            var supplierOptions = '<option value="">' + pleaseSelect + '</option>';
            window.landingOptions.suppliers.forEach(function(item){
                var selected = data && data.SupplierID == item.SupplierID ? 'selected' : '';
                supplierOptions += '<option value="' + item.SupplierID + '" ' + selected + '>' + item.SupplierName + '</option>';
            });
            $('#purchase-supplier-select').html(supplierOptions);

            form.render('select');

            // 初始化日期选择器
            laydate.render({
                elem: '#purchase-date-input',
                type: 'date',
                format: 'yyyy-MM-dd'
            });

            // 渲染采购明细表
            renderPurchaseDetailTable();
        });

        // 监听添加明细按钮
        $('#add-purchase-detail-btn').on('click', function(){
            addPurchaseDetailRow();
        });
    }

    /**
     * 渲染采购明细表
     */
    function renderPurchaseDetailTable() {
        console.log('渲染采购明细表，数据条数:', window.purchaseOptions.details.length);
        console.log('采购明细数据:', window.purchaseOptions.details);

        // Get i18n translations
        var i18n = layui.i18n;
        var t = i18n ? function(key) { return i18n.t(key); } : function(key) { return key; };

        table.render({
            elem: '#purchase-detail-table',
            data: window.purchaseOptions.details,
            cols: [[
                {field: 'Stock', title: t('stock'), width: 150, templet: function(d){
                    // 从StockID中解析出Stock和State
                    var stockState = d.StockID ? findStockState(d.StockID) : { Stock: '', State: '' };
                    var selectedStock = stockState.Stock;

                    var options = '<select class="layui-input purchase-detail-stock-select" lay-ignore>';
                    options += '<option value="">' + t('selectStock') + '</option>';
                    if (window.landingOptions.stockList && window.landingOptions.stockList.length > 0) {
                        window.landingOptions.stockList.forEach(function(stock){
                            var selected = selectedStock === stock ? 'selected' : '';
                            options += '<option value="' + stock + '" ' + selected + '>' + stock + '</option>';
                        });
                    }
                    options += '</select>';
                    return options;
                }},
                {field: 'State', title: t('state'), width: 150, templet: function(d){
                    // 从StockID中解析出Stock和State
                    var stockState = d.StockID ? findStockState(d.StockID) : { Stock: '', State: '' };
                    var selectedStock = stockState.Stock;
                    var selectedState = stockState.State;

                    var options = '<select class="layui-input purchase-detail-state-select" lay-ignore>';
                    options += '<option value="">' + t('selectState') + '</option>';

                    // 如果已选择Stock，显示对应的State选项
                    if (selectedStock && window.landingOptions.stockStateMap && window.landingOptions.stockStateMap[selectedStock]) {
                        window.landingOptions.stockStateMap[selectedStock].forEach(function(state){
                            var selected = selectedState === state ? 'selected' : '';
                            options += '<option value="' + state + '" ' + selected + '>' + state + '</option>';
                        });
                    }

                    options += '</select>';
                    return options;
                }},
                {field: 'StockDisplay', title: t('fishSpecies'), width: 150, templet: function(d){
                    // 查找对应的Stock值（用于显示）
                    var stock = '';
                    if (window.landingOptions.stocks && window.landingOptions.stocks.length > 0) {
                        var stockItem = window.landingOptions.stocks.find(function(item){
                            return item.StockID === d.StockID;
                        });
                        if (stockItem) {
                            stock = stockItem.Stock || '';
                        }
                    }
                    var color = stock ? '#2563eb' : '#999';
                    var fontWeight = stock ? 'bold' : 'normal';
                    return '<span class="purchase-detail-stock-display" style="color:' + color + ';font-weight:' + fontWeight + ';">' + (stock || '-') + '</span>';
                }},
                {field: 'StateDisplay', title: t('state') + ' (' + t('display') + ')', width: 100, templet: function(d){
                    // 查找对应的State值（用于显示）
                    var state = '';
                    if (window.landingOptions.stocks && window.landingOptions.stocks.length > 0) {
                        var stock = window.landingOptions.stocks.find(function(item){
                            return item.StockID === d.StockID;
                        });
                        if (stock) {
                            state = stock.State || '';
                        }
                    }
                    var color = state ? '#16a34a' : '#999';
                    var fontWeight = state ? 'bold' : 'normal';
                    return '<span class="purchase-detail-state-display" style="color:' + color + ';font-weight:' + fontWeight + ';">' + (state || '-') + '</span>';
                }},
                {field: 'Area', title: t('area'), width: 100, templet: function(d){
                    // 查找对应的Area值
                    var area = '';
                    if (window.landingOptions.stocks && window.landingOptions.stocks.length > 0) {
                        var stock = window.landingOptions.stocks.find(function(item){
                            return item.StockID === d.StockID;
                        });
                        if (stock) {
                            area = stock.Area || '';
                        }
                    }
                    var color = area ? '#2563eb' : '#999';
                    var fontWeight = area ? 'bold' : 'normal';
                    return '<span class="purchase-detail-area-display" style="color:' + color + ';font-weight:' + fontWeight + ';">' + (area || '-') + '</span>';
                }},
                {field: 'BinQty', title: t('binQty'), width: 80, templet: function(d){
                    var binQty = d.BinQty || 0;
                    var color = binQty > 0 ? '#2563eb' : '#999';
                    var fontWeight = binQty > 0 ? 'bold' : 'normal';
                    return '<span style="color:' + color + ';font-weight:' + fontWeight + ';">' + binQty + '</span>';
                }},
                {field: 'ICE', title: t('ice'), width: 80, templet: function(d){
                    var iceValue = d.ICE || 0;
                    var iceText = iceValue == 1 ? 'Yes' : 'No';
                    var color = iceValue == 1 ? '#16a34a' : '#999';
                    var fontWeight = iceValue == 1 ? 'bold' : 'normal';
                    return '<span style="color:' + color + ';font-weight:' + fontWeight + ';">' + iceText + '</span>';
                }},
                {field: 'GreenKG', title: t('greenWeight'), width: 120, templet: function(d){
                    var unit = '';
                    if (window.landingOptions.units && d.GreenWeightUnitID) {
                        var unitItem = window.landingOptions.units.find(function(u){
                            return u.UnitID == d.GreenWeightUnitID;
                        });
                        unit = unitItem ? unitItem.UnitSymbol : '';
                    }
                    return '<div style="display:flex;align-items:center;gap:5px;">' +
                           '<input type="number" step="0.1" class="layui-input purchase-detail-green-input" value="' + (d.GreenKG || '') + '" placeholder="' + t('greenWeight') + '" style="flex:1;" data-index="' + d.LAY_TABLE_INDEX + '">' +
                           '<span style="color:#2563eb;font-weight:bold;min-width:30px;">' + unit + '</span>' +
                           '</div>';
                }},
                {field: 'LandedKG', title: t('landedWeight'), width: 120, templet: function(d){
                    var unit = '';
                    if (window.landingOptions.units && d.LandedWeightUnitID) {
                        var unitItem = window.landingOptions.units.find(function(u){
                            return u.UnitID == d.LandedWeightUnitID;
                        });
                        unit = unitItem ? unitItem.UnitSymbol : '';
                    }
                    return '<div style="display:flex;align-items:center;gap:5px;">' +
                           '<input type="number" step="0.1" class="layui-input purchase-detail-landed-input" value="' + (d.LandedKG || '') + '" placeholder="' + t('landedWeight') + '" style="flex:1;">' +
                           '<span style="color:#2563eb;font-weight:bold;min-width:30px;">' + unit + '</span>' +
                           '</div>';
                }},
                {field: 'Price', title: t('price'), width: 120, templet: function(d){
                    return '<input type="number" step="0.01" class="layui-input purchase-detail-price-input" value="' + (d.Price || '') + '" placeholder="' + t('price') + '">';
                }},
                {field: 'Total', title: t('total'), width: 120, templet: function(d){
                    return '<input type="number" step="0.01" class="layui-input purchase-detail-total-input" value="' + (d.Total || '') + '" placeholder="' + t('total') + '" readonly>';
                }},
                {field: '', title: t('actionColumn'), width: 100, templet: function(d){
                    return '<a class="layui-btn layui-btn-xs layui-btn-danger" onclick="window.deletePurchaseDetailRow(' + d.LAY_TABLE_INDEX + ')">' + t('deleteRow') + '</a>';
                }}
            ]],
            limit: 100,
            height: 300,
            page: false,
            done: function(res){
                console.log('采购明细表渲染完成，数据条数:', res.data.length);
                // 延迟绑定事件，确保DOM完全加载
                setTimeout(function(){
                    bindPurchaseDetailEvents();
                }, 100);
            }
        });
    }

    /**
     * 绑定采购明细表的事件
     */
    function bindPurchaseDetailEvents() {
        // 获取 i18n 翻译函数
        var i18n = layui.i18n;
        var t = i18n ? function(key) { return i18n.t(key); } : function(key) { return key; };

        // Stock 下拉框变化事件
        $(document).off('change', '.purchase-detail-stock-select').on('change', '.purchase-detail-stock-select', function(e){
            e.preventDefault();
            var $select = $(this);
            var stock = $select.val();

            // 获取当前行
            var $tr = $select.closest('tr');
            var $stateSelect = $tr.find('.purchase-detail-state-select');

            console.log('=== Purchase Stock选择事件触发 ===');
            console.log('选中的Stock:', stock);

            // 如果选择的是空值，清空State下拉框
            if (!stock) {
                $stateSelect.html('<option value="">' + t('selectState') + '</option>');
                $tr.find('.purchase-detail-stock-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.purchase-detail-state-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.purchase-detail-area-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.purchase-detail-description-display').text('-').css('color', '#999').css('font-weight', 'normal');
                return;
            }

            // 更新State下拉框的选项
            var stateOptions = '<option value="">' + t('selectState') + '</option>';
            if (window.landingOptions.stockStateMap && window.landingOptions.stockStateMap[stock]) {
                window.landingOptions.stockStateMap[stock].forEach(function(state){
                    stateOptions += '<option value="' + state + '">' + state + '</option>';
                });
            }
            $stateSelect.html(stateOptions);
        });

        // State 下拉框变化事件
        $(document).off('change', '.purchase-detail-state-select').on('change', '.purchase-detail-state-select', function(e){
            e.preventDefault();
            var $stateSelect = $(this);
            var state = $stateSelect.val();

            // 获取当前行
            var $tr = $stateSelect.closest('tr');
            var $stockSelect = $tr.find('.purchase-detail-stock-select');
            var stock = $stockSelect.val();

            console.log('=== Purchase State选择事件触发 ===');
            console.log('选中的Stock:', stock, 'State:', state);

            // 如果选择的是空值，清空显示
            if (!stock || !state) {
                $tr.find('.purchase-detail-stock-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.purchase-detail-state-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.purchase-detail-area-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.purchase-detail-description-display').text('-').css('color', '#999').css('font-weight', 'normal');
                return;
            }

            // 根据 Stock + State 查找 StockID
            var stockId = findStockID(stock, state);
            console.log('查找到的StockID:', stockId);

            // 查找对应的其他属性
            var stockData = window.landingOptions.stocks.find(function(item){
                return item.StockID === stockId;
            });

            if (stockData) {
                var area = stockData.Area || '';
                var description = stockData.Description || '';

                // 更新 Stock (Fish Species) 显示
                var $stockSpan = $tr.find('.purchase-detail-stock-display');
                if ($stockSpan.length > 0) {
                    var stockColor = stock ? '#2563eb' : '#999';
                    var stockFontWeight = stock ? 'bold' : 'normal';
                    $stockSpan.text(stock || '-').css('color', stockColor).css('font-weight', stockFontWeight);
                }

                // 更新 State 显示
                var $stateSpan = $tr.find('.purchase-detail-state-display');
                if ($stateSpan.length > 0) {
                    var stateColor = state ? '#16a34a' : '#999';
                    var stateFontWeight = state ? 'bold' : 'normal';
                    $stateSpan.text(state || '-').css('color', stateColor).css('font-weight', stateFontWeight);
                }

                // 更新 Area 显示
                var $areaSpan = $tr.find('.purchase-detail-area-display');
                if ($areaSpan.length > 0) {
                    var areaColor = area ? '#2563eb' : '#999';
                    var areaFontWeight = area ? 'bold' : 'normal';
                    $areaSpan.text(area || '-').css('color', areaColor).css('font-weight', areaFontWeight);
                }

                // 更新 Description 显示
                var $descriptionSpan = $tr.find('.purchase-detail-description-display');
                if ($descriptionSpan.length > 0) {
                    var descColor = description ? '#059669' : '#999';
                    $descriptionSpan.text(description || '-').css('color', descColor).css('font-weight', 'normal');
                }

                // Stock 变化后重新计算净重（因为 conversion 可能变化）
                var $landedInput = $tr.find('.purchase-detail-landed-input');
                if ($landedInput.length > 0 && $landedInput.val()) {
                    calculatePurchaseDetailTotal($landedInput);
                }
            }
        });

        // 计算总计（上岸重量 * 单价）
        $(document).off('change', '.purchase-detail-landed-input').on('change', '.purchase-detail-landed-input', function(){
            calculatePurchaseDetailTotal($(this));
        });
        $(document).off('change', '.purchase-detail-price-input').on('change', '.purchase-detail-price-input', function(){
            calculatePurchaseDetailTotal($(this));
        });

        // Green Weight 输入后倒计算 Landed Weight
        $(document).off('change', '.purchase-detail-green-input').on('change', '.purchase-detail-green-input', function(){
            calculateLandedFromGreen($(this));
        });
    }

    /**
     * 根据Green Weight倒计算Landed Weight
     * 公式: LandedKG = GreenKG / Conversion
     */
    function calculateLandedFromGreen($input) {
        var $tr = $input.closest('tr');
        var greenKG = parseFloat($input.val()) || 0;

        if (greenKG <= 0) {
            return;
        }

        // 获取 Conversion
        var $stockSelect = $tr.find('.purchase-detail-stock-select');
        var $stateSelect = $tr.find('.purchase-detail-state-select');
        var stock = $stockSelect.val();
        var state = $stateSelect.val();
        var conversion = 1; // 默认值

        if (stock && state && window.landingOptions && window.landingOptions.stocks) {
            var stockData = window.landingOptions.stocks.find(function(item){
                return item.Stock === stock && item.State === state;
            });
            if (stockData && stockData.Conversion) {
                conversion = stockData.Conversion;
            }
        }

        // 倒计算 LandedKG = GreenKG / Conversion
        var landedKG = greenKG / conversion;
        $tr.find('.purchase-detail-landed-input').val(landedKG.toFixed(2));

        // 计算总价
        var price = parseFloat($tr.find('.purchase-detail-price-input').val()) || 0;
        var total = landedKG * price;
        $tr.find('.purchase-detail-total-input').val(total.toFixed(2));

        console.log('Green Weight倒计算 - GreenKG:', greenKG, 'Conversion:', conversion, 'LandedKG:', landedKG);

        // 如果有关联的Landing记录，尝试更新Landing的L-Weight
        updateLandingLWeight($tr, stock, state, landedKG);
    }

    /**
     * 更新Landing记录的L-Weight
     * 根据Purchase的LandedWeight倒计算Landing的L-Weight
     */
    function updateLandingLWeight($tr, stock, state, landedKG) {
        // 获取当前行的原始数据
        var table = layui.table;
        var index = $tr.attr('data-index');
        var data = table.cache['purchase-detail-table'][index];

        if (!data || !data.UnloadingDocket) {
            console.log('没有关联的Landing记录，跳过更新L-Weight');
            return;
        }

        var landingID = data.UnloadingDocket;

        // 查找对应的Landing Detail
        // 这里需要调用API来查找并更新Landing Detail
        $.ajax({
            url: '../api/landing.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'findLandingDetailByStock',
                landingID: landingID,
                stockID: data.StockID
            }),
            success: function(res){
                if (res.success && res.data && res.data.ID) {
                    // 调用更新L-Weight的API
                    $.ajax({
                        url: '../api/landing.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            action: 'updateLWeight',
                            landingDetailId: res.data.ID,
                            landedKG: landedKG
                        }),
                        success: function(updateRes){
                            if (updateRes.success) {
                                console.log('Landing L-Weight已更新:', updateRes.lWeight);
                                layer.msg('已同步更新Landing的L-Weight: ' + updateRes.lWeight.toFixed(2), {icon: 1, time: 2000});
                            } else {
                                console.warn('更新Landing L-Weight失败:', updateRes.message);
                            }
                        },
                        error: function(xhr){
                            console.error('更新Landing L-Weight请求失败:', xhr.status, xhr.statusText);
                        }
                    });
                }
            },
            error: function(xhr){
                console.log('查找Landing Detail失败，跳过更新L-Weight');
            }
        });
    }

    /**
     * 计算采购明细总计和净重
     */
    function calculatePurchaseDetailTotal($input) {
        var $tr = $input.closest('tr');
        var landed = parseFloat($tr.find('.purchase-detail-landed-input').val()) || 0;
        var price = parseFloat($tr.find('.purchase-detail-price-input').val()) || 0;
        var total = landed * price;
        $tr.find('.purchase-detail-total-input').val(total.toFixed(2));

        // 计算净重 (GreenKG = LandedKG * Conversion)
        var $stockSelect = $tr.find('.purchase-detail-stock-select');
        var $stateSelect = $tr.find('.purchase-detail-state-select');
        var stock = $stockSelect.val();
        var state = $stateSelect.val();
        var conversion = 1; // 默认值

        // 根据 Stock 和 State 查找 StockID 和 Conversion
        if (stock && state && window.landingOptions && window.landingOptions.stocks) {
            var stockData = window.landingOptions.stocks.find(function(item){
                return item.Stock === stock && item.State === state;
            });
            if (stockData && stockData.Conversion) {
                conversion = stockData.Conversion;
            }
        }

        var greenKG = landed * conversion;
        $tr.find('.purchase-detail-green-input').val(greenKG.toFixed(2));

        // 如果修改的是 LandedKG 输入框，则更新对应 Landing 的 L-Weight
        if ($input.hasClass('purchase-detail-landed-input') && landed > 0) {
            updateLandingLWeight($tr, stock, state, landed);
        }
    }

    /**
     * 添加采购明细行
     */
    window.addPurchaseDetailRow = function() {
        // 先收集当前表格中已输入的数据（包括未填完整的）
        var currentData = collectCurrentPurchaseDetailData();

        // 添加新行
        currentData.push({
            StockID: '',
            BinQty: null,
            UnloadingDocket: null,
            ICE: 0,
            GreenKG: '',
            LandedKG: '',
            Price: '',
            Total: ''
        });

        // 更新数据并重新渲染
        window.purchaseOptions.details = currentData;
        renderPurchaseDetailTable();
    };

    /**
     * 删除采购明细行
     */
    window.deletePurchaseDetailRow = function(index) {
        var i18n = layui.i18n;
        var confirmMsg = i18n ? i18n.t('deleteDetailConfirm') : 'Are you sure to delete this detail?';
        var btnConfirm = i18n ? i18n.t('confirm') : 'Confirm';
        var btnCancel = i18n ? i18n.t('cancel') : 'Cancel';

        layer.confirm(confirmMsg, {btn: [btnConfirm, btnCancel]}, function(i){
            // 先收集当前表格中已输入的数据
            var currentData = collectCurrentPurchaseDetailData();
            // 删除指定行
            currentData.splice(index, 1);
            // 更新数据并重新渲染
            window.purchaseOptions.details = currentData;
            renderPurchaseDetailTable();
            layer.close(i);
        });
    };

    /**
     * 收集当前采购明细表格中的数据（包括未填完整的）
     */
    function collectCurrentPurchaseDetailData() {
        var details = [];
        var stockSelects = $('.purchase-detail-stock-select');
        var stateSelects = $('.purchase-detail-state-select');
        var greenInputs = $('.purchase-detail-green-input');
        var landedInputs = $('.purchase-detail-landed-input');
        var priceInputs = $('.purchase-detail-price-input');
        var totalInputs = $('.purchase-detail-total-input');

        for (var i = 0; i < stockSelects.length; i++) {
            var stock = $(stockSelects[i]).val();
            var state = $(stateSelects[i]).val();

            // 根据 Stock + State 组合查找 StockID
            var stockID = '';
            if (stock && state) {
                stockID = findStockID(stock, state);
            }

            var originalData = window.purchaseOptions.details[i] || {};

            details.push({
                StockID: stockID || '',
                BinQty: originalData.BinQty || null,
                UnloadingDocket: originalData.UnloadingDocket || null,
                ICE: originalData.ICE || 0,
                GreenKG: $(greenInputs[i]).val() || '',
                LandedKG: $(landedInputs[i]).val() || '',
                Price: $(priceInputs[i]).val() || '',
                Total: $(totalInputs[i]).val() || ''
            });
        }

        return details;
    }

    /**
     * 收集采购明细数据（仅收集完整的）
     */
    function collectPurchaseDetailData() {
        var details = [];
        var stockSelects = $('.purchase-detail-stock-select');
        var stateSelects = $('.purchase-detail-state-select');
        var greenInputs = $('.purchase-detail-green-input');
        var landedInputs = $('.purchase-detail-landed-input');
        var priceInputs = $('.purchase-detail-price-input');
        var totalInputs = $('.purchase-detail-total-input');

        console.log('collectPurchaseDetailData - stockSelects.length:', stockSelects.length);

        for (var i = 0; i < stockSelects.length; i++) {
            var stock = $(stockSelects[i]).val();
            var state = $(stateSelects[i]).val();

            // 根据 Stock + State 组合查找 StockID
            var stockID = '';
            if (stock && state) {
                stockID = findStockID(stock, state);
            }

            var greenKG = $(greenInputs[i]).val();
            var landedKG = $(landedInputs[i]).val();
            var price = $(priceInputs[i]).val();
            var total = $(totalInputs[i]).val();
            var originalData = window.purchaseOptions.details[i] || {};

            console.log('采购行', i, ': stock=', stock, 'state=', state, 'stockID=', stockID, 'landedKG=', landedKG, 'price=', price);

            // 只收集完整的数据（至少需要库存和上岸重量）
            if (stockID) {
                details.push({
                    StockID: stockID,
                    BinQty: originalData.BinQty || null,
                    UnloadingDocket: originalData.UnloadingDocket || null,
                    ICE: originalData.ICE || 0,
                    GreenKG: greenKG ? parseFloat(greenKG) : 0,
                    LandedKG: landedKG ? parseFloat(landedKG) : 0,
                    LandedWeightUnitID: originalData.LandedWeightUnitID || 1,
                    GreenWeightUnitID: originalData.GreenWeightUnitID || 1,
                    Price: price ? parseFloat(price) : 0,
                    Total: total ? parseFloat(total) : 0
                });
            }
        }

        console.log('收集到的采购明细数据:', details);
        return details;
    }

    // ==================== 销售记录专用函数 ====================

    /**
     * 全局变量：存储销售选项数据和明细数据
     */
    window.salesOptions = {
        customers: [],
        stocks: [],
        bins: [],
        details: []
    };

    /**
     * 导出销售记录及明细（PDF格式）
     */
    function exportSalesWithDetails(data) {
        layer.load(1);

        // 获取所有销售ID
        var salesIds = data.map(function(item){ return item.SalesID; });

        // 批量获取销售明细
        api.getSalesDetails(salesIds).then(function(res){
            layer.closeAll('loading');

            if (!res.success || !res.data) {
                layer.msg('获取销售明细失败', {icon: 2});
                return;
            }

            var detailsMap = {};
            res.data.forEach(function(detail){
                if (!detailsMap[detail.SalesID]) {
                    detailsMap[detail.SalesID] = [];
                }
                detailsMap[detail.SalesID].push(detail);
            });

            // 为每个销售记录单独生成PDF并打开
            data.forEach(function(sales, index){
                var details = detailsMap[sales.SalesID] || [];
                if (details.length > 0) {
                    generateSingleSalesPDF(sales, details, index);
                }
            });

            layer.msg(layui.i18n ? layui.i18n.t('exportSuccess') : 'Save successful', {icon: 1});
        }).catch(function(err){
            layer.closeAll('loading');
            layer.msg('获取明细失败: ' + (err.message || '未知错误'), {icon: 2});
        });
    }

    /**
     * 为单个销售记录生成PDF
     */
    function generateSingleSalesPDF(sales, details, index) {
        // 生成文件名: sales_{ID}_{日期}.pdf
        var salesDate = sales.SalesDate ? sales.SalesDate.substring(0, 10).replace(/-/g, '') : '';
        var fileName = 'sales_' + sales.SalesID + '_' + salesDate + '.pdf';

        // 创建单个记录的HTML
        var invoiceHtml = createSalesInvoiceHtml(sales, details, 1);

        // 创建临时容器并渲染HTML
        var container = document.createElement('div');
        container.style.position = 'absolute';
        container.style.left = '-9999px';
        container.style.width = '210mm';
        container.innerHTML = invoiceHtml;
        document.body.appendChild(container);

        // 等待字体加载后再生成PDF
        setTimeout(function(){
            html2canvas(container, {
                scale: 2,
                useCORS: true,
                logging: false
            }).then(function(canvas){
                document.body.removeChild(container);

                // 创建PDF
                var { jsPDF } = window.jspdf;
                var pdf = new jsPDF('p', 'mm', 'a4');

                var imgWidth = 210;
                var pageHeight = 297;
                var imgHeight = canvas.height * imgWidth / canvas.width;
                var heightLeft = imgHeight;
                var position = 0;

                // 添加第一页
                pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                // 如果内容超过一页，添加新页
                while (heightLeft > 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                // 在新窗口打开PDF（让用户选择保存或打印）
                var pdfBlob = pdf.output('blob', {filename: fileName});
                var pdfUrl = URL.createObjectURL(pdfBlob);

                // 在新窗口打开PDF
                var newWindow = window.open(pdfUrl, '_blank');
                if (!newWindow) {
                    layer.msg('请允许弹出窗口以打开PDF', {icon: 0});
                }
            }).catch(function(err){
                document.body.removeChild(container);
                console.error('PDF生成失败:', err);
                layer.msg('PDF生成失败: ' + (err.message || '未知错误'), {icon: 2});
            });
        }, 500);
    }

    /**
     * 创建销售发票HTML模板
     */
    function createSalesInvoiceHtml(sales, details, invoiceNo) {
        var subtotal = 0;
        var detailsRows = '';

        details.forEach(function(detail){
            var binQty = detail.BinQty || 0;
            var gWeight = detail['G-Weight'] || 0;
            var nWeight = detail['N-Weight'] || 0;
            var price = detail.Price || 0;
            var amount = detail.Amount || 0;
            var weightUnit = detail.WeightUnitSymbol || 'kg';  // 获取单位符号
            subtotal += amount;

            detailsRows += `
                <tr style="border: 1px solid #ccc;">
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center;">${detail.Stock || ''}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center;">${detail.State || ''}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center;">${binQty}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center;">${gWeight.toFixed(2)} ${weightUnit}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">${nWeight.toFixed(2)} ${weightUnit}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">$${price.toFixed(2)}</td>
                    <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">$${amount.toFixed(2)}</td>
                </tr>
            `;
        });

        var salesDate = sales.SaleDate ? sales.SaleDate.substring(0, 10) : '';
        var gst = sales.GST || 0;
        var total = sales.Total || 0;

        var html = `
            <div class="invoice-page" style="page-break-after: always; padding: 20px; font-family: Arial, sans-serif; font-size: 12px;">
                <!-- 顶部第一行：公司信息 + 发票编号 -->
                <div style="display: flex; margin-bottom: 10px;">
                    <!-- 左侧公司信息 (3/4) -->
                    <div style="flex: 0.75; padding-right: 10px;">
                        <div style="background-color: #1F4E78; color: white; font-weight: bold; padding: 10px; font-size: 16px;">
                            Seafood Direct Ltd
                        </div>
                        <div style="padding: 10px; border: 1px solid #ccc; border-top: none;">
                            698A Tay Street, Invercargill, 9810 : 404 Yarrow Street, Invercargill 9810
                        </div>
                        <div style="padding: 10px; border: 1px solid #ccc; border-top: none;">
                            Phone: 021 328-808 | GST No: 140-382-493 | LFR No: 9900844
                        </div>
                    </div>
                    <!-- 右侧发票编号 (1/4) -->
                    <div style="flex: 0.25; display: flex; flex-direction: column; justify-content: center;">
                        <div style="background-color: #FFFF00; color: #FF0000; font-weight: bold; text-align: center; padding: 10px; font-size: 14px; border: 2px solid #1F4E78;">
                            Invoice No:<br>${sales.SalesID || ''}
                        </div>
                    </div>
                </div>

                <!-- IRD Approved 标题（占全长，字体放大） -->
                <div style="margin-bottom: 10px; margin-top: 10px;">
                    <div style="background-color: #1F4E78; color: white; font-weight: bold; text-align: center; padding: 15px; font-size: 20px;">
                        Sales Invoice
                    </div>
                </div>

                <div style="margin: 15px 0;"></div>

                <!-- 客户信息 - Customer Name 占一整行 -->
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 8px; font-weight: bold; background-color: #1F4E78; color: white; width: 15%;">Customer Name:</td>
                        <td colspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc; border-bottom: 1px solid #ccc; border-left: none; padding: 8px;">${sales.CustomerName || ''}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 8px; font-weight: bold; background-color: #1F4E78; color: white;">Address:</td>
                        <td style="border: 1px solid #ccc; padding: 8px;">${(sales.Address || '').replace(/\n/g, '<br>')}</td>
                        <td style="border: 1px solid #ccc; padding: 8px; font-weight: bold; background-color: #1F4E78; color: white;">Sales Date:</td>
                        <td style="border: 1px solid #ccc; padding: 8px; background-color: white;">${salesDate}</td>
                    </tr>
                </table>

                <div style="margin: 15px 0;"></div>

                <!-- 数据表格 -->
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">
                    <thead>
                        <tr style="background-color: #1F4E78; color: white; font-weight: bold;">
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Fish Species</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">State</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Bins</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">gross-weight</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">net-weight</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Unit Price</th>
                            <th style="border: 1px solid #ccc; padding: 8px; text-align: center;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${detailsRows}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="border: none; padding: 8px;"></td>
                            <td style="background-color: #1F4E78; color: white; font-weight: bold; padding: 8px; text-align: center; border: 1px solid #ccc;">Sub Total:</td>
                            <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">$${subtotal.toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="border: none; padding: 8px;"></td>
                            <td style="background-color: #1F4E78; color: white; font-weight: bold; padding: 8px; text-align: center; border: 1px solid #ccc;">GST:</td>
                            <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">$${gst.toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="border: none; padding: 8px;"></td>
                            <td style="background-color: #1F4E78; color: white; font-weight: bold; padding: 8px; text-align: center; border: 1px solid #ccc;">Total:</td>
                            <td style="border: 1px solid #ccc; padding: 8px; text-align: center; background-color: white;">$${total.toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;

        return html;
    }

    /**
     * 初始化销售明细表
     */
    function initSalesDetailTable(data) {
        var details = data && data.details ? data.details : [];
        window.salesOptions.details = details;

        // Get i18n translations
        var i18n = layui.i18n;
        var t = i18n ? i18n.t : function(key) { return key; };
        var pleaseSelect = t('pleaseSelect');

        // 加载选项数据
        $.when(
            $.ajax({
                url: '/api/customer-options.php',
                method: 'GET',
                dataType: 'json'
            }),
            loadLandingOptions()
        ).then(function(customerRes, landingRes){
            // 保存客户选项
            if (customerRes[0].success) {
                window.salesOptions.customers = customerRes[0].data.customers || [];
            }

            // 填充客户下拉框
            var customerOptions = '<option value="">' + pleaseSelect + '</option>';
            window.salesOptions.customers.forEach(function(item){
                var selected = data && data.CustomerID == item.CustID ? 'selected' : '';
                customerOptions += '<option value="' + item.CustID + '" ' + selected + '>' + item.CustomerName + '</option>';
            });
            $('#sales-customer-select').html(customerOptions);

            form.render('select');

            // 初始化日期选择器
            laydate.render({
                elem: '#sales-date-input',
                type: 'date',
                format: 'yyyy-MM-dd'
            });

            // 渲染销售明细表
            renderSalesDetailTable();
        });

        // 监听添加明细按钮
        $('#add-sales-detail-btn').on('click', function(){
            addSalesDetailRow();
        });
    }

    /**
     * 渲染销售明细表
     */
    function renderSalesDetailTable() {
        console.log('渲染销售明细表，数据条数:', window.salesOptions.details.length);
        console.log('销售明细数据:', window.salesOptions.details);

        // Get i18n translations
        var i18n = layui.i18n;
        var t = i18n ? function(key) { return i18n.t(key); } : function(key) { return key; };

        table.render({
            elem: '#sales-detail-table',
            data: window.salesOptions.details,
            cols: [[
                {field: 'Stock', title: t('stock'), width: 150, templet: function(d){
                    // 从StockID中解析出Stock和State
                    var stockState = d.StockID ? findStockState(d.StockID) : { Stock: '', State: '' };
                    var selectedStock = stockState.Stock;

                    var options = '<select class="layui-input sales-detail-stock-select" lay-ignore>';
                    options += '<option value="">' + t('selectStock') + '</option>';
                    if (window.landingOptions.stockList && window.landingOptions.stockList.length > 0) {
                        window.landingOptions.stockList.forEach(function(stock){
                            var selected = selectedStock === stock ? 'selected' : '';
                            options += '<option value="' + stock + '" ' + selected + '>' + stock + '</option>';
                        });
                    }
                    options += '</select>';
                    return options;
                }},
                {field: 'State', title: t('state'), width: 150, templet: function(d){
                    // 从StockID中解析出Stock和State
                    var stockState = d.StockID ? findStockState(d.StockID) : { Stock: '', State: '' };
                    var selectedStock = stockState.Stock;
                    var selectedState = stockState.State;

                    var options = '<select class="layui-input sales-detail-state-select" lay-ignore>';
                    options += '<option value="">' + t('selectState') + '</option>';

                    // 如果已选择Stock，显示对应的State选项
                    if (selectedStock && window.landingOptions.stockStateMap && window.landingOptions.stockStateMap[selectedStock]) {
                        window.landingOptions.stockStateMap[selectedStock].forEach(function(state){
                            var selected = selectedState === state ? 'selected' : '';
                            options += '<option value="' + state + '" ' + selected + '>' + state + '</option>';
                        });
                    }

                    options += '</select>';
                    return options;
                }},
                {field: 'StockDisplay', title: t('fishSpecies'), width: 150, templet: function(d){
                    var stock = '';
                    if (window.landingOptions.stocks && window.landingOptions.stocks.length > 0) {
                        var stockItem = window.landingOptions.stocks.find(function(item){
                            return item.StockID === d.StockID;
                        });
                        if (stockItem) {
                            stock = stockItem.Stock || '';
                        }
                    }
                    var color = stock ? '#2563eb' : '#999';
                    var fontWeight = stock ? 'bold' : 'normal';
                    return '<span class="sales-detail-stock-display" style="color:' + color + ';font-weight:' + fontWeight + ';">' + (stock || '-') + '</span>';
                }},
                {field: 'StateDisplay', title: t('state') + ' (' + t('display') + ')', width: 100, templet: function(d){
                    var state = '';
                    if (window.landingOptions.stocks && window.landingOptions.stocks.length > 0) {
                        var stockItem = window.landingOptions.stocks.find(function(item){
                            return item.StockID === d.StockID;
                        });
                        if (stockItem) {
                            state = stockItem.State || '';
                        }
                    }
                    var color = state ? '#16a34a' : '#999';
                    var fontWeight = state ? 'bold' : 'normal';
                    return '<span class="sales-detail-state-display" style="color:' + color + ';font-weight:' + fontWeight + ';">' + (state || '-') + '</span>';
                }},
                {field: 'BinID', title: t('bin'), width: 180, templet: function(d){
                    var options = '<select class="layui-input sales-detail-bin-select" lay-ignore>';
                    options += '<option value="">' + t('selectStock') + '</option>';
                    if (window.landingOptions.bins && window.landingOptions.bins.length > 0) {
                        window.landingOptions.bins.forEach(function(item){
                            var selected = d.BinID == item.BinID ? 'selected' : '';
                            options += '<option value="' + item.BinID + '" ' + selected + '>' + item.BinName + ' (' + item['B-Weight'] + 'kg)</option>';
                        });
                    }
                    options += '</select>';
                    return options;
                }},
                {field: 'BinQty', title: t('binQty'), width: 120, templet: function(d){
                    return '<input type="number" step="1" class="layui-input sales-detail-binqty-input" value="' + (d.BinQty || '') + '" placeholder="' + t('binQty') + '">';
                }},
                {field: 'G-Weight', title: t('grossWeight') + '(kg)', width: 150, templet: function(d){
                    return '<input type="number" step="0.1" class="layui-input sales-detail-gweight-input" value="' + (d['G-Weight'] || '') + '" placeholder="' + t('grossWeight') + '">';
                }},
                {field: 'N-Weight', title: t('netWeight'), width: 150, templet: function(d){
                    return '<input type="number" step="0.1" class="layui-input sales-detail-nweight-input" value="' + (d['N-Weight'] || '') + '" placeholder="' + t('netWeight') + '">';
                }},
                {field: 'WeightUnitID', title: t('unit'), width: 120, templet: function(d){
                    if (!window.landingOptions || !window.landingOptions.units) {
                        return '<input type="number" class="layui-input sales-detail-unit-input" value="' + (d.WeightUnitID || 1) + '">';
                    }
                    var options = '<select class="layui-input sales-detail-unit-select" lay-ignore>';
                    window.landingOptions.units.forEach(function(unit){
                        var selected = (d.WeightUnitID || 1) == unit.UnitID ? 'selected' : '';
                        options += '<option value="' + unit.UnitID + '" ' + selected + '>' + unit.UnitCode + '</option>';
                    });
                    options += '</select>';
                    return options;
                }},
                {field: 'Price', title: t('unitPrice'), width: 120, templet: function(d){
                    return '<input type="number" step="0.01" class="layui-input sales-detail-price-input" value="' + (d.Price || '') + '" placeholder="' + t('unitPrice') + '">';
                }},
                {field: 'Amount', title: t('total'), width: 120, templet: function(d){
                    return '<input type="number" step="0.01" class="layui-input sales-detail-amount-input" value="' + (d.Amount || '') + '" placeholder="' + t('total') + '" readonly>';
                }},
                {field: '', title: t('actionColumn'), width: 100, templet: function(d){
                    return '<a class="layui-btn layui-btn-xs layui-btn-danger" onclick="window.deleteSalesDetailRow(' + d.LAY_TABLE_INDEX + ')">' + t('deleteRow') + '</a>';
                }}
            ]],
            limit: 100,
            height: 300,
            page: false,
            done: function(res){
                console.log('销售明细表渲染完成，数据条数:', res.data.length);
                // 延迟绑定事件，确保DOM完全加载
                setTimeout(function(){
                    bindSalesDetailEvents();
                }, 100);
            }
        });
    }

    /**
     * 绑定销售明细表的事件
     */
    function bindSalesDetailEvents() {
        // 获取 i18n 翻译函数
        var i18n = layui.i18n;
        var t = i18n ? function(key) { return i18n.t(key); } : function(key) { return key; };

        // Stock 下拉框变化事件
        $(document).off('change', '.sales-detail-stock-select').on('change', '.sales-detail-stock-select', function(e){
            e.preventDefault();
            var $select = $(this);
            var stock = $select.val();

            // 获取当前行
            var $tr = $select.closest('tr');
            var $stateSelect = $tr.find('.sales-detail-state-select');

            console.log('=== Sales Stock选择事件触发 ===');
            console.log('选中的Stock:', stock);

            // 如果选择的是空值，清空State下拉框
            if (!stock) {
                $stateSelect.html('<option value="">' + t('selectState') + '</option>');
                $tr.find('.sales-detail-stock-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.sales-detail-state-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.sales-detail-description-display').text('-').css('color', '#999').css('font-weight', 'normal');
                return;
            }

            // 更新State下拉框的选项
            var stateOptions = '<option value="">' + t('selectState') + '</option>';
            if (window.landingOptions.stockStateMap && window.landingOptions.stockStateMap[stock]) {
                window.landingOptions.stockStateMap[stock].forEach(function(state){
                    stateOptions += '<option value="' + state + '">' + state + '</option>';
                });
            }
            $stateSelect.html(stateOptions);
        });

        // State 下拉框变化事件
        $(document).off('change', '.sales-detail-state-select').on('change', '.sales-detail-state-select', function(e){
            e.preventDefault();
            var $stateSelect = $(this);
            var state = $stateSelect.val();

            // 获取当前行
            var $tr = $stateSelect.closest('tr');
            var $stockSelect = $tr.find('.sales-detail-stock-select');
            var stock = $stockSelect.val();

            console.log('=== Sales State选择事件触发 ===');
            console.log('选中的Stock:', stock, 'State:', state);

            // 如果选择的是空值，清空显示
            if (!stock || !state) {
                $tr.find('.sales-detail-stock-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.sales-detail-state-display').text('-').css('color', '#999').css('font-weight', 'normal');
                $tr.find('.sales-detail-description-display').text('-').css('color', '#999').css('font-weight', 'normal');
                return;
            }

            // 根据 Stock + State 查找 StockID
            var stockId = findStockID(stock, state);
            console.log('查找到的StockID:', stockId);

            // 查找对应的其他属性
            var stockData = window.landingOptions.stocks.find(function(item){
                return item.StockID === stockId;
            });

            if (stockData) {
                var description = stockData.Description || '';

                // 更新 Stock (Fish Species) 显示
                var $stockSpan = $tr.find('.sales-detail-stock-display');
                if ($stockSpan.length > 0) {
                    var stockColor = stock ? '#2563eb' : '#999';
                    var stockFontWeight = stock ? 'bold' : 'normal';
                    $stockSpan.text(stock || '-').css('color', stockColor).css('font-weight', stockFontWeight);
                }

                // 更新 State 显示
                var $stateSpan = $tr.find('.sales-detail-state-display');
                if ($stateSpan.length > 0) {
                    var stateColor = state ? '#16a34a' : '#999';
                    var stateFontWeight = state ? 'bold' : 'normal';
                    $stateSpan.text(state || '-').css('color', stateColor).css('font-weight', stateFontWeight);
                }

                // 更新 Description 显示
                var $descriptionSpan = $tr.find('.sales-detail-description-display');
                if ($descriptionSpan.length > 0) {
                    var descColor = description ? '#059669' : '#999';
                    $descriptionSpan.text(description || '-').css('color', descColor).css('font-weight', 'normal');
                }
            }
        });

        // 计算总计（净重量 * 单价）
        $(document).off('change', '.sales-detail-nweight-input').on('change', '.sales-detail-nweight-input', function(){
            calculateSalesDetailTotal($(this));
        });
        $(document).off('change', '.sales-detail-price-input').on('change', '.sales-detail-price-input', function(){
            calculateSalesDetailTotal($(this));
        });
    }

    /**
     * 计算销售明细总计
     */
    function calculateSalesDetailTotal($input) {
        var $tr = $input.closest('tr');
        var nWeight = parseFloat($tr.find('.sales-detail-nweight-input').val()) || 0;
        var price = parseFloat($tr.find('.sales-detail-price-input').val()) || 0;
        var total = nWeight * price;
        $tr.find('.sales-detail-amount-input').val(total.toFixed(2));
    }

    /**
     * 添加销售明细行
     */
    window.addSalesDetailRow = function() {
        // 先收集当前表格中已输入的数据（包括未填完整的）
        var currentData = collectCurrentSalesDetailData();

        // 添加新行
        currentData.push({
            StockID: '',
            BinID: '',
            BinQty: '',
            'G-Weight': '',
            'N-Weight': '',
            WeightUnitID: 1,  // 默认单位为 1 (KG)
            Price: '',
            Amount: ''
        });

        // 更新数据并重新渲染
        window.salesOptions.details = currentData;
        renderSalesDetailTable();
    };

    /**
     * 删除销售明细行
     */
    window.deleteSalesDetailRow = function(index) {
        var i18n = layui.i18n;
        var confirmMsg = i18n ? i18n.t('deleteDetailConfirm') : 'Are you sure to delete this detail?';
        var btnConfirm = i18n ? i18n.t('confirm') : 'Confirm';
        var btnCancel = i18n ? i18n.t('cancel') : 'Cancel';

        layer.confirm(confirmMsg, {btn: [btnConfirm, btnCancel]}, function(i){
            // 先收集当前表格中已输入的数据
            var currentData = collectCurrentSalesDetailData();
            // 删除指定行
            currentData.splice(index, 1);
            // 更新数据并重新渲染
            window.salesOptions.details = currentData;
            renderSalesDetailTable();
            layer.close(i);
        });
    };

    /**
     * 收集当前销售明细表格中的数据（包括未填完整的）
     */
    function collectCurrentSalesDetailData() {
        var details = [];
        var stockSelects = $('.sales-detail-stock-select');
        var stateSelects = $('.sales-detail-state-select');
        var binSelects = $('.sales-detail-bin-select');
        var binQtyInputs = $('.sales-detail-binqty-input');
        var gWeightInputs = $('.sales-detail-gweight-input');
        var nWeightInputs = $('.sales-detail-nweight-input');
        var unitSelects = $('.sales-detail-unit-select');
        var priceInputs = $('.sales-detail-price-input');
        var amountInputs = $('.sales-detail-amount-input');

        for (var i = 0; i < stockSelects.length; i++) {
            var stock = $(stockSelects[i]).val();
            var state = $(stateSelects[i]).val();

            // 根据 Stock + State 组合查找 StockID
            var stockID = '';
            if (stock && state) {
                stockID = findStockID(stock, state);
            }

            details.push({
                StockID: stockID || '',
                BinID: $(binSelects[i]).val() || '',
                BinQty: $(binQtyInputs[i]).val() || '',
                'G-Weight': $(gWeightInputs[i]).val() || '',
                'N-Weight': $(nWeightInputs[i]).val() || '',
                WeightUnitID: unitSelects.length > i ? $(unitSelects[i]).val() : 1,
                Price: $(priceInputs[i]).val() || '',
                Amount: $(amountInputs[i]).val() || ''
            });
        }

        return details;
    }

    /**
     * 收集销售明细数据（仅收集完整的）
     */
    function collectSalesDetailData() {
        var details = [];
        var stockSelects = $('.sales-detail-stock-select');
        var stateSelects = $('.sales-detail-state-select');
        var binSelects = $('.sales-detail-bin-select');
        var binQtyInputs = $('.sales-detail-binqty-input');
        var gWeightInputs = $('.sales-detail-gweight-input');
        var nWeightInputs = $('.sales-detail-nweight-input');
        var unitSelects = $('.sales-detail-unit-select');
        var priceInputs = $('.sales-detail-price-input');
        var amountInputs = $('.sales-detail-amount-input');

        console.log('collectSalesDetailData - stockSelects.length:', stockSelects.length);

        for (var i = 0; i < stockSelects.length; i++) {
            var stock = $(stockSelects[i]).val();
            var state = $(stateSelects[i]).val();

            // 根据 Stock + State 组合查找 StockID
            var stockID = '';
            if (stock && state) {
                stockID = findStockID(stock, state);
            }

            var binID = $(binSelects[i]).val();
            var binQty = $(binQtyInputs[i]).val();
            var gWeight = $(gWeightInputs[i]).val();
            var nWeight = $(nWeightInputs[i]).val();
            var weightUnitId = unitSelects.length > i ? $(unitSelects[i]).val() : 1;
            var price = $(priceInputs[i]).val();
            var amount = $(amountInputs[i]).val();

            console.log('销售行', i, ': stock=', stock, 'state=', state, 'stockID=', stockID, 'nWeight=', nWeight, 'price=', price);

            // 只收集完整的数据（至少需要库存）
            if (stockID) {
                details.push({
                    StockID: stockID,
                    BinID: binID || null,
                    BinQty: binQty ? parseInt(binQty) : 0,
                    'G-Weight': gWeight ? parseFloat(gWeight) : 0,
                    'N-Weight': nWeight ? parseFloat(nWeight) : 0,
                    WeightUnitID: weightUnitId ? parseInt(weightUnitId) : 1,
                    Price: price ? parseFloat(price) : 0,
                    Amount: amount ? parseFloat(amount) : 0
                });
            }
        }

        console.log('收集到的销售明细数据:', details);
        return details;
    }

    /**
     * 导出到货记录及明细（PDF格式）
     * 为每个到货记录生成单独命名的PDF
     */
    function exportLandingWithDetails(data) {
        layer.load(1);

        // 获取所有到货ID
        var landingIds = data.map(function(item){ return item.LandingID; });

        // 批量获取到货明细
        api.getLandingDetails(landingIds).then(function(res){
            layer.closeAll('loading');

            if (!res.success || !res.data) {
                layer.msg('获取到货明细失败', {icon: 2});
                return;
            }

            var detailsMap = {};
            res.data.forEach(function(detail){
                if (!detailsMap[detail.LandingID]) {
                    detailsMap[detail.LandingID] = [];
                }
                detailsMap[detail.LandingID].push(detail);
            });

            // 为每个到货记录单独生成PDF并打开
            data.forEach(function(landing, index){
                var details = detailsMap[landing.LandingID] || [];
                if (details.length > 0) {
                    generateSingleLandingPDF(landing, details, index);
                }
            });

            layer.msg(layui.i18n ? layui.i18n.t('exportSuccess') : 'Save successful', {icon: 1});
        }).catch(function(err){
            layer.closeAll('loading');
            layer.msg('获取明细失败: ' + (err.message || '未知错误'), {icon: 2});
        });
    }

    /**
     * 为单个到货记录生成PDF
     */
    function generateSingleLandingPDF(landing, details, index) {
        // 生成文件名: landing_{ID}_{日期}.pdf
        var landingDate = landing.LandingDate ? landing.LandingDate.substring(0, 10).replace(/-/g, '') : '';
        var fileName = 'landing_' + landing.LandingID + '_' + landingDate + '.pdf';

        // 创建单个记录的HTML
        var invoiceHtml = createLandingInvoiceHtml(landing, details, 1);

        // 创建临时容器并渲染HTML
        var container = document.createElement('div');
        container.style.position = 'absolute';
        container.style.left = '-9999px';
        container.style.width = '210mm';
        container.innerHTML = invoiceHtml;
        document.body.appendChild(container);

        // 等待字体加载后再生成PDF
        setTimeout(function(){
            html2canvas(container, {
                scale: 2,
                useCORS: true,
                logging: false
            }).then(function(canvas){
                document.body.removeChild(container);

                // 创建PDF
                var { jsPDF } = window.jspdf;
                var pdf = new jsPDF('p', 'mm', 'a4');

                var imgWidth = 210;
                var pageHeight = 297;
                var imgHeight = canvas.height * imgWidth / canvas.width;
                var heightLeft = imgHeight;
                var position = 0;

                // 添加第一页
                pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                // 如果内容超过一页，添加新页
                while (heightLeft > 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                // 在新窗口打开PDF（让用户选择保存或打印）
                var pdfBlob = pdf.output('blob', {filename: fileName});
                var pdfUrl = URL.createObjectURL(pdfBlob);

                // 在新窗口打开PDF
                var newWindow = window.open(pdfUrl, '_blank');
                if (!newWindow) {
                    // 如果新窗口被阻止，显示提示
                    layer.msg('请允许弹出窗口以打开PDF', {icon: 0});
                }
            }).catch(function(err){
                document.body.removeChild(container);
                console.error('PDF生成失败:', err);
                layer.msg('PDF生成失败: ' + (err.message || '未知错误'), {icon: 2});
            });
        }, 500);
    }

    /**
     * 创建到货记录HTML模板
     */
    function createLandingInvoiceHtml(landing, details, invoiceNo) {
        var totalWeight = 0;
        var detailsRows = '';

        details.forEach(function(detail){
            var weight = detail['L-Weight'] || 0;
            var binQty = detail.BinQty || 0;
            totalWeight += weight;

            // 当 weight 为 0 或未定义时，显示为空白；否则显示两位小数
            var weightDisplay = (weight > 0) ? weight.toFixed(2) : '';

            detailsRows += '<tr>' +
                '<td style="border: 1px solid #999; padding: 10px 8px;">' + (detail.Stock || '') + '</td>' +
                '<td style="border: 1px solid #999; padding: 10px 8px;">' + (detail.Area || '') + '</td>' +
                '<td style="border: 1px solid #999; padding: 10px 8px;">' + (detail.State || '') + '</td>' +
                '<td style="border: 1px solid #999; padding: 10px 8px; text-align: center;">' + binQty + '</td>' +
                '<td style="border: 1px solid #999; padding: 10px 8px; text-align: center;">' + weightDisplay + '</td>' +
                '</tr>';
        });

        var landingDate = landing.LandingDate ? landing.LandingDate.substring(0, 10) : '';

        var html = '<div style="page-break-after: always; padding: 20px; font-family: Arial, sans-serif; font-size: 12px; color: #000;">' +

            // 顶部公司信息区域
            '<div style="margin-bottom: 15px;">' +
            '<div style="display: flex;">' +
            // 左侧公司信息
            '<div style="flex: 1; padding-right: 15px;">' +
            '<div style="font-size: 28px; font-weight: bold; color: #1F4E78; margin-bottom: 10px;">SEAFOOD DIRECT LTD</div>' +
            '<div style="font-size: 11px; line-height: 1.6;">' +
            '<div>698A Tay Street, Invercargill, 9810; 404 Yarrow Street, Invercargill 9810</div>' +
            '<div>Phone: 021 328-808; Email: bruce@ascofoodgroup.com; LFR No: 9900844</div>' +
            '</div>' +
            '</div>' +
            // 右侧Docket模块
            '<div style="display: flex; flex-direction: column;">' +
            '<div style="background-color: #1F4E78; color: white; padding: 8px 15px; text-align: center; font-weight: bold; font-size: 14px; border: 1px solid #1F4E78;">Docket</div>' +
            '<div style="background-color: #FFFF00; color: #FF0000; padding: 8px 15px; text-align: center; font-weight: bold; font-size: 16px; border: 1px solid #1F4E78; border-top: none;">' + (landing.LandingID || '') + '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +

            // 标题区块
            '<div style="background-color: #1F4E78; color: white; padding: 12px; text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 15px;">' +
            'FISH LANDING DOCKET' +
            '</div>' +

            // 基础信息栏（三行）
            '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">' +
            '<tr>' +
            '<td style="background-color: #1F4E78; color: white; padding: 8px 12px; font-weight: bold; width: 15%; border: 1px solid #1F4E78;">Supplier Name:</td>' +
            '<td style="padding: 8px 12px; border: 1px solid #1F4E78; background-color: white;">' + (landing.SupplierName || '') + '</td>' +
            '<td style="background-color: #1F4E78; color: white; padding: 8px 12px; font-weight: bold; width: 15%; border: 1px solid #1F4E78;">Client No:</td>' +
            '<td style="padding: 8px 12px; border: 1px solid #1F4E78; background-color: white;">' + (landing.QRN || '') + '</td>' +
            '</tr>' +
            '<tr>' +
            '<td style="background-color: #1F4E78; color: white; padding: 8px 12px; font-weight: bold; border: 1px solid #1F4E78;">Boat Name:</td>' +
            '<td style="padding: 8px 12px; border: 1px solid #1F4E78; background-color: white;">' + (landing.BoatName || '') + '</td>' +
            '<td style="background-color: #1F4E78; color: white; padding: 8px 12px; font-weight: bold; border: 1px solid #1F4E78;">Boat No:</td>' +
            '<td style="padding: 8px 12px; border: 1px solid #1F4E78; background-color: white;">' + (landing.BoatNo || '') + '</td>' +
            '</tr>' +
            '<tr>' +
            '<td style="background-color: #1F4E78; color: white; padding: 8px 12px; font-weight: bold; border: 1px solid #1F4E78;">Date Landed:</td>' +
            '<td style="padding: 8px 12px; border: 1px solid #1F4E78; background-color: white;">' + landingDate + '</td>' +
            '<td style="background-color: #1F4E78; color: white; padding: 8px 12px; font-weight: bold; border: 1px solid #1F4E78;">Port:</td>' +
            '<td style="padding: 8px 12px; border: 1px solid #1F4E78; background-color: white;">' + (landing.Port || '') + '</td>' +
            '</tr>' +
            '</table>' +

            // 鱼类信息表格
            '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">' +
            '<thead>' +
            '<tr style="background-color: #E0E0E0;">' +
            '<th style="border: 1px solid #999; padding: 10px 8px; text-align: left; font-weight: bold;">Fish Species</th>' +
            '<th style="border: 1px solid #999; padding: 10px 8px; text-align: left; font-weight: bold;">Area</th>' +
            '<th style="border: 1px solid #999; padding: 10px 8px; text-align: left; font-weight: bold;">State</th>' +
            '<th style="border: 1px solid #999; padding: 10px 8px; text-align: center; font-weight: bold;">Bin</th>' +
            '<th style="border: 1px solid #999; padding: 10px 8px; text-align: center; font-weight: bold;">Landed Weight</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>' + detailsRows + '</tbody>' +
            '</table>' +

            '</div>';

        return html;
    }

    /**
     * 发送邮件（带PDF附件）
     * 支持 purchase 和 landing 模块
     */
    function sendEmailWithPDF(module, data) {
        // 只处理 purchase 和 landing 模块
        if (module !== 'purchase' && module !== 'landing') {
            layer.msg('该记录不支持邮件发送功能', {icon: 0});
            return;
        }

        layer.load(1);

        if (module === 'purchase') {
            // 处理采购记录
            api.getList('purchase', {id: data.PurchaseID, includeDetails: true}).then(function(res){
                layer.closeAll('loading');

                if (!res.success || !res.data) {
                    layer.msg('获取采购明细失败', {icon: 2});
                    return;
                }

                var purchase = res.data;
                var details = purchase.details || [];

                if (details.length === 0) {
                    layer.msg('该采购记录没有明细，无法发送邮件', {icon: 0});
                    return;
                }

                // 生成PDF
                var invoiceHtml = createPurchaseInvoiceHtml(purchase, details, 1);

                // 创建临时容器并渲染HTML
                var container = document.createElement('div');
                container.style.position = 'absolute';
                container.style.left = '-9999px';
                container.style.width = '210mm';
                container.innerHTML = invoiceHtml;
                document.body.appendChild(container);

                // 等待字体加载后再生成PDF
                setTimeout(function(){
                    html2canvas(container, {
                        scale: 2,
                        useCORS: true,
                        logging: false
                    }).then(function(canvas){
                        document.body.removeChild(container);

                        // 创建PDF
                        var { jsPDF } = window.jspdf;
                        var pdf = new jsPDF('p', 'mm', 'a4');

                        var imgWidth = 210;
                        var pageHeight = 297;
                        var imgHeight = canvas.height * imgWidth / canvas.width;
                        var heightLeft = imgHeight;
                        var position = 0;

                        pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;

                        while (heightLeft > 0) {
                            position = heightLeft - imgHeight;
                            pdf.addPage();
                            pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                            heightLeft -= pageHeight;
                        }

                        // 获取PDF的Base64数据
                        var pdfData = pdf.output('datauristring').split(',')[1];

                        // 询问收件人邮箱 - 预填充供应商 Email
                        var supplierEmail = purchase.Email || '';
                        layer.prompt({
                            title: '请输入收件人邮箱',
                            formType: 0,
                            value: supplierEmail,
                            area: ['400px', '150px']
                        }, function(email, index){
                            layer.close(index);

                            if (!email) {
                                layer.msg('请输入邮箱地址', {icon: 0});
                                return;
                            }

                            // 发送邮件
                            layer.load(1);

                            fetch('../api/send-email.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    to: email,
                                    subject: 'Purchase Record - Invoice No: ' + purchase.PurchaseID,
                                    body: 'Please find the attached purchase record PDF file.\n\nPurchase ID: ' + purchase.PurchaseID + '\nSupplier: ' + (purchase.SupplierName || '') + '\nPurchase Date: ' + (purchase.PurchaseDate ? purchase.PurchaseDate.substring(0, 10) : ''),
                                    pdfData: pdfData
                                })
                            }).then(function(response){
                                return response.json();
                            }).then(function(result){
                                layer.closeAll('loading');
                                if (result.success) {
                                    layer.msg('Email sent successfully!', {icon: 1, time: 2000});
                                    // 更新邮件发送状态
                                    api.updatePurchaseEmailSent(purchase.PurchaseID, 1).then(function(){
                                        // 刷新表格显示最新状态
                                        tables['purchase'].reload();
                                    }).catch(function(err){
                                        console.error('更新邮件状态失败:', err);
                                    });
                                } else {
                                    layer.msg('邮件发送失败: ' + (result.message || '未知错误'), {icon: 2});
                                }
                            }).catch(function(err){
                                layer.closeAll('loading');
                                console.error('发送邮件错误:', err);
                                layer.msg('邮件发送失败: ' + (err.message || '网络错误'), {icon: 2});
                            });
                        });
                    }).catch(function(err){
                        document.body.removeChild(container);
                        console.error('PDF生成失败:', err);
                        layer.msg('PDF生成失败: ' + (err.message || '未知错误'), {icon: 2});
                    });
                }, 500);
            }).catch(function(err){
                layer.closeAll('loading');
                layer.msg('获取明细失败: ' + (err.message || '未知错误'), {icon: 2});
            });
        } else if (module === 'landing') {
            // 处理到货记录
            api.getLandingDetails([data.LandingID]).then(function(res){
                layer.closeAll('loading');

                if (!res.success || !res.data) {
                    layer.msg('获取到货明细失败', {icon: 2});
                    return;
                }

                var landing = data;
                var details = res.data || [];

                if (details.length === 0) {
                    layer.msg('该到货记录没有明细，无法发送邮件', {icon: 0});
                    return;
                }

                // 生成PDF
                var invoiceHtml = createLandingInvoiceHtml(landing, details, 1);

                // 创建临时容器并渲染HTML
                var container = document.createElement('div');
                container.style.position = 'absolute';
                container.style.left = '-9999px';
                container.style.width = '210mm';
                container.innerHTML = invoiceHtml;
                document.body.appendChild(container);

                // 等待字体加载后再生成PDF
                setTimeout(function(){
                    html2canvas(container, {
                        scale: 2,
                        useCORS: true,
                        logging: false
                    }).then(function(canvas){
                        document.body.removeChild(container);

                        // 创建PDF
                        var { jsPDF } = window.jspdf;
                        var pdf = new jsPDF('p', 'mm', 'a4');

                        var imgWidth = 210;
                        var pageHeight = 297;
                        var imgHeight = canvas.height * imgWidth / canvas.width;
                        var heightLeft = imgHeight;
                        var position = 0;

                        pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;

                        while (heightLeft > 0) {
                            position = heightLeft - imgHeight;
                            pdf.addPage();
                            pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                            heightLeft -= pageHeight;
                        }

                        // 获取PDF的Base64数据
                        var pdfData = pdf.output('datauristring').split(',')[1];

                        var landingDate = landing.LandingDate ? landing.LandingDate.substring(0, 10) : '';

                        // 询问收件人邮箱 - 预填充供应商 Email
                        var supplierEmail = landing.Email || '';
                        layer.prompt({
                            title: '请输入收件人邮箱',
                            formType: 0,
                            value: supplierEmail,
                            area: ['400px', '150px']
                        }, function(email, index){
                            layer.close(index);

                            if (!email) {
                                layer.msg('请输入邮箱地址', {icon: 0});
                                return;
                            }

                            // 发送邮件
                            layer.load(1);

                            fetch('../api/send-email.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    to: email,
                                    subject: 'Landing Record - Docket No: ' + landing.LandingID,
                                    body: 'Please find the attached landing record PDF file.\n\nLanding ID: ' + landing.LandingID + '\nSupplier: ' + (landing.SupplierName || '') + '\nLanding Date: ' + landingDate,
                                    pdfData: pdfData
                                })
                            }).then(function(response){
                                return response.json();
                            }).then(function(result){
                                layer.closeAll('loading');
                                if (result.success) {
                                    layer.msg('Email sent successfully!', {icon: 1, time: 2000});
                                } else {
                                    layer.msg('邮件发送失败: ' + (result.message || '未知错误'), {icon: 2});
                                }
                            }).catch(function(err){
                                layer.closeAll('loading');
                                console.error('发送邮件错误:', err);
                                layer.msg('邮件发送失败: ' + (err.message || '网络错误'), {icon: 2});
                            });
                        });
                    }).catch(function(err){
                        document.body.removeChild(container);
                        console.error('PDF生成失败:', err);
                        layer.msg('PDF生成失败: ' + (err.message || '未知错误'), {icon: 2});
                    });
                }, 500);
            }).catch(function(err){
                layer.closeAll('loading');
                layer.msg('获取明细失败: ' + (err.message || '未知错误'), {icon: 2});
            });
        }
    }

    /**
     * 创建热敏纸格式的到货记录HTML（80mm宽度）
     * @param {Object} landing - 到货记录数据
     * @param {Array} details - 明细数据
     */
    function createThermalPaperLandingHtml(landing, details) {
        var totalWeight = 0;
        var detailsRows = '';

        details.forEach(function(detail){
            var weight = detail['L-Weight'] || 0;
            var binQty = detail.BinQty || 0;
            totalWeight += weight;

            detailsRows += '<tr>' +
                '<td style="padding: 4px 1px; font-size: 10px;">' + (detail.Stock || '-') + '</td>' +
                '<td style="padding: 4px 1px; font-size: 10px;">' + (detail.Area || '-') + '</td>' +
                '<td style="padding: 4px 1px; font-size: 10px;">' + (detail.State || '-') + '</td>' +
                '<td style="padding: 4px 1px; font-size: 10px; text-align: center;">' + binQty + '</td>' +
                '<td style="padding: 4px 1px; font-size: 10px; text-align: center;">' + weight.toFixed(2) + '</td>' +
                '</tr>';
        });

        var landingDate = landing.LandingDate ? landing.LandingDate.substring(0, 10) : '';

        var html = '<div style="padding: 5px; font-family: Arial, sans-serif; font-size: 12px; color: #000; width: 80mm;">' +

            // 标题
            '<div style="text-align: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #000;">' +
            '<div style="font-size: 14px; font-weight: bold; margin-bottom: 4px;">FISH LANDING DOCKET</div>' +
            '<div style="font-size: 16px; font-weight: bold; color: #FF0000;">#' + (landing.LandingID || '') + '</div>' +
            '</div>' +

            // 供应商信息
            '<div style="margin-bottom: 8px; font-size: 11px;">' +
            '<div><strong>Supplier:</strong> ' + (landing.SupplierName || '') + '</div>' +
            '<div><strong>Client No:</strong> ' + (landing.QRN || '') + '</div>' +
            '</div>' +

            // 船只信息
            '<div style="margin-bottom: 8px; font-size: 11px;">' +
            '<div><strong>Boat:</strong> ' + (landing.BoatName || '') + ' (' + (landing.BoatNo || '') + ')</div>' +
            '<div><strong>Date:</strong> ' + landingDate + ' | <strong>Port:</strong> ' + (landing.Port || '') + '</div>' +
            '</div>' +

            // 分隔线
            '<div style="border-top: 1px dashed #000; margin: 8px 0;"></div>' +

            // 表头
            '<table style="width: 100%; border-collapse: collapse; font-size: 10px;">' +
            '<thead>' +
            '<tr style="border-bottom: 1px solid #000;">' +
            '<th style="padding: 4px 1px; text-align: left; font-weight: bold; width: 24%;">Fish Species</th>' +
            '<th style="padding: 4px 1px; text-align: left; font-weight: bold; width: 16%;">Area</th>' +
            '<th style="padding: 4px 1px; text-align: left; font-weight: bold; width: 14%;">State</th>' +
            '<th style="padding: 4px 1px; text-align: center; font-weight: bold; width: 10%;">Bin</th>' +
            '<th style="padding: 4px 1px; text-align: center; font-weight: bold; width: 36%;">Landed Weight</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>' + detailsRows + '</tbody>' +
            '</table>' +

            // 分隔线
            '<div style="border-top: 1px dashed #000; margin: 8px 0;"></div>' +

            // 汇总信息
            '<div style="text-align: left; font-size: 12px; margin-bottom: 8px;">' +
            '<div><strong>Total Weight:</strong> ' + totalWeight.toFixed(2) + ' KG</div>' +
            '</div>' +

            // 页脚
            '<div style="text-align: center; font-size: 10px; color: #666; margin-top: 10px; padding-top: 8px; border-top: 1px dashed #000;">' +
            '<div>SEAFOOD DIRECT LTD</div>' +
            '<div>LFR No: 9900844</div>' +
            '</div>' +

            '</div>';

        return html;
    }

    /**
     * 打印到货记录（生成PDF并直接打开打印对话框）
     * 使用热敏纸格式（80mm宽度）
     * @param {Object} landing - 到货记录数据
     */
    function printLandingRecord(landing) {
        layer.load(1);

        // 获取到货明细
        api.getLandingDetails([landing.LandingID]).then(function(res){
            layer.closeAll('loading');

            if (!res.success || !res.data) {
                layer.msg('获取到货明细失败', {icon: 2});
                return;
            }

            var details = res.data || [];

            if (details.length === 0) {
                layer.msg('该记录没有明细数据', {icon: 0});
                return;
            }

            // 创建热敏纸格式HTML
            var invoiceHtml = createThermalPaperLandingHtml(landing, details);

            // 创建临时容器并渲染HTML
            var container = document.createElement('div');
            container.style.position = 'absolute';
            container.style.left = '-9999px';
            container.style.width = '80mm';
            container.innerHTML = invoiceHtml;
            document.body.appendChild(container);

            // 等待字体加载后再生成PDF
            setTimeout(function(){
                html2canvas(container, {
                    scale: 2,
                    useCORS: true,
                    logging: false
                }).then(function(canvas){
                    document.body.removeChild(container);

                    // 创建PDF - 使用热敏纸尺寸（80mm宽度，高度根据内容）
                    var { jsPDF } = window.jspdf;
                    var pdfWidth = 80; // 80mm
                    var imgHeight = canvas.height * pdfWidth / canvas.width;
                    var pdf = new jsPDF('p', 'mm', [pdfWidth, Math.max(imgHeight, 297)]);

                    // 添加内容
                    pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, 0, pdfWidth, imgHeight);

                    // 设置自动打印
                    pdf.autoPrint();

                    // 在新窗口中打开PDF，触发打印对话框
                    window.open(pdf.output('bloburl'), '_blank');

                    layer.msg(layui.i18n ? layui.i18n.t('exportSuccess') : 'Opening print dialog...', {icon: 1});
                }).catch(function(err){
                    document.body.removeChild(container);
                    console.error('PDF生成失败:', err);
                    layer.msg('PDF生成失败: ' + (err.message || '未知错误'), {icon: 2});
                });
            }, 500);
        }).catch(function(err){
            layer.closeAll('loading');
            layer.msg('获取明细失败: ' + (err.message || '未知错误'), {icon: 2});
        });
    }

    /**
     * 打印到货记录页面（生成A4格式PDF并打开打印对话框）
     * @param {Object} landing - 到货记录数据
     */
    function printLandingPage(landing) {
        layer.load(1);

        // 获取到货明细
        api.getLandingDetails([landing.LandingID]).then(function(res){
            layer.closeAll('loading');

            if (!res.success || !res.data) {
                layer.msg('获取到货明细失败', {icon: 2});
                return;
            }

            var details = res.data || [];

            if (details.length === 0) {
                layer.msg('该记录没有明细数据', {icon: 0});
                return;
            }

            // 创建A4格式HTML（与Export相同的格式）
            var invoiceHtml = createLandingInvoiceHtml(landing, details, 1);

            // 创建临时容器并渲染HTML
            var container = document.createElement('div');
            container.style.position = 'absolute';
            container.style.left = '-9999px';
            container.style.width = '210mm';
            container.innerHTML = invoiceHtml;
            document.body.appendChild(container);

            // 等待字体加载后再生成PDF
            setTimeout(function(){
                html2canvas(container, {
                    scale: 2,
                    useCORS: true,
                    logging: false
                }).then(function(canvas){
                    document.body.removeChild(container);

                    // 创建PDF - 使用A4尺寸
                    var { jsPDF } = window.jspdf;
                    var pdf = new jsPDF('p', 'mm', 'a4');

                    var imgWidth = 210;
                    var pageHeight = 297;
                    var imgHeight = canvas.height * imgWidth / canvas.width;
                    var heightLeft = imgHeight;
                    var position = 0;

                    // 添加第一页
                    pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;

                    // 如果内容超过一页，添加新页
                    while (heightLeft > 0) {
                        position = heightLeft - imgHeight;
                        pdf.addPage();
                        pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }

                    // 设置自动打印
                    pdf.autoPrint();

                    // 在新窗口中打开PDF，触发打印对话框
                    window.open(pdf.output('bloburl'), '_blank');

                    layer.msg(layui.i18n ? layui.i18n.t('exportSuccess') : 'Opening print dialog...', {icon: 1});
                }).catch(function(err){
                    document.body.removeChild(container);
                    console.error('PDF生成失败:', err);
                    layer.msg('PDF生成失败: ' + (err.message || '未知错误'), {icon: 2});
                });
            }, 500);
        }).catch(function(err){
            layer.closeAll('loading');
            layer.msg('获取明细失败: ' + (err.message || '未知错误'), {icon: 2});
        });
    }

    /**
     * 网络打印到货记录（发送到网络热敏打印机）
     * @param {Object} landing - 到货记录数据
     */
    function netPrintLandingRecord(landing) {
        var i18n = layui.i18n;
        layer.load(1);

        // 首先获取可用的打印机列表
        fetch('../api/printer.php?action=list&pageSize=100', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(result) {
            layer.closeAll('loading');

            if (!result.success || !result.data || result.data.length === 0) {
                layer.msg(i18n ? i18n.t('noPrinterConfigured') || '没有可用的打印机，请先在数据管理中配置打印机' : '没有可用的打印机，请先在数据管理中配置打印机', {
                    icon: 0,
                    time: 3000
                });
                return;
            }

            var printers = result.data;
            var defaultPrinter = printers.find(function(p) { return p.is_default == 1; }) || printers[0];

            // 如果只有一台打印机或只有默认打印机，直接打印
            if (printers.length === 1 || defaultPrinter) {
                sendPrintRequest(landing.LandingID, defaultPrinter ? defaultPrinter.id : null);
            } else {
                // 多台打印机，弹出选择对话框
                showPrinterSelectDialog(printers, landing.LandingID);
            }
        })
        .catch(function(err) {
            layer.closeAll('loading');
            console.error('获取打印机列表失败:', err);
            layer.msg('获取打印机列表失败: ' + (err.message || '未知错误'), {icon: 2});
        });
    }

    /**
     * 显示打印机选择对话框
     */
    function showPrinterSelectDialog(printers, landingId) {
        var i18n = layui.i18n;
        var title = i18n ? i18n.t('selectPrinter') || '选择打印机' : '选择打印机';

        // 创建选择内容
        var content = '<div style="padding: 20px;">';
        content += '<div class="layui-form">';
        content += '<select name="printer_select" lay-search="">';
        content += '<option value="">' + (i18n ? i18n.t('pleaseSelect') || '请选择' : '请选择') + '</option>';

        printers.forEach(function(p) {
            var selected = p.is_default == 1 ? ' selected' : '';
            var statusText = p.status == 1 ? '' : ' (禁用)';
            content += '<option value="' + p.id + '"' + selected + '>' + p.printer_name + ' - ' + p.printer_ip + ':' + p.printer_port + statusText + '</option>';
        });

        content += '</select>';
        content += '</div></div>';

        layer.open({
            type: 1,
            title: title,
            area: ['500px', '250px'],
            content: content,
            btn: [i18n ? i18n.t('confirm') || '确定' : '确定', i18n ? i18n.t('cancel') || '取消' : '取消'],
            yes: function(index, layero) {
                var select = layero.find('select[name="printer_select"]');
                var printerId = select.val();

                if (!printerId) {
                    layer.msg(i18n ? i18n.t('pleaseSelectPrinter') || '请选择打印机' : '请选择打印机', {icon: 0});
                    return;
                }

                layer.close(index);
                sendPrintRequest(landingId, printerId);
            },
            success: function(layero, index) {
                // 重新渲染select
                layui.form.render('select');
            }
        });
    }

    /**
     * 发送打印请求到后端
     */
    function sendPrintRequest(landingId, printerId) {
        var i18n = layui.i18n;
        layer.load(1);

        var payload = {
            action: 'printLanding',
            landing_id: landingId
        };

        if (printerId) {
            payload.printer_id = printerId;
        }

        fetch('../api/printer.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(function(res) { return res.json(); })
        .then(function(result) {
            layer.closeAll('loading');

            if (result.success) {
                var printerName = result.printer || '';
                layer.msg(result.message || (i18n ? i18n.t('printSuccess') || '打印成功' : '打印成功'), {
                    icon: 1,
                    time: 2000
                });
            } else {
                layer.msg(result.message || (i18n ? i18n.t('printFailed') || '打印失败' : '打印失败'), {
                    icon: 2,
                    time: 3000
                });
            }
        })
        .catch(function(err) {
            layer.closeAll('loading');
            console.error('网络打印失败:', err);
            layer.msg('网络打印失败: ' + (err.message || '未知错误'), {icon: 2});
        });
    }

    // ==================== 初始化 ====================

    checkLoginStatus();
});
