/**
 * 国际化支持模块 (layui 版本)
 * 提供中英文切换功能
 */

layui.define(function(exports){
    // 翻译数据
    var translations = {
        'zh': {
            // 页面标题
            'pageTitle': '渔业数据管理系统',
            'systemTitle': '渔业数据管理系统',
            'systemManagement': '渔业数据管理',
            'businessManagement': '业务管理',
            'administrator': '管理员',

            // 导航
            'landing': '到货记录',
            'purchase': '采购记录',
            'sales': '销售记录',

            // 通用
            'add': '新增',
            'edit': '编辑',
            'delete': '删除',
            'exportRow': '导出',
            'printRow': '打印',
            'netPrintRow': '网络打印',
            'generatePurchase': '生成采购单',
            'generateSales': '生成销售单',
            'save': '保存',
            'cancel': '取消',
            'confirm': '确定',
            'export': '导出',
            'refresh': '刷新',
            'search': '搜索',
            'sendEmail': '邮件',
            'logout': '退出登录',
            'loading': '加载中...',
            'noData': '暂无数据',
            'switchedToZh': '已切换到中文',

            // 登录
            'loginTitle': '渔业数据管理系统',
            'username': '用户名',
            'password': '密码',
            'loginBtn': '登录',
            'loginSuccess': '登录成功',
            'loginFailed': '登录失败',
            'logoutSuccess': '已退出登录',
            'logoutConfirm': '确定要退出登录吗？',

            // 表格
            'id': 'ID',
            'date': '日期',
            'landingDate': '到货日期',
            'purchaseDate': '采购日期',
            'saleDate': '销售日期',
            'name': '名称',
            'supplier': '供应商',
            'supplierName': '供应商名称',
            'port': '港口',
            'boatName': '船名',
            'boatNo': '船号',
            'customerName': '客户名称',
            'detailCount': '明细数量',
            'greenWeight': '绿色重量',
            'landedWeight': '上岸重量',
            'gst': '税费',
            'total': '总计',
            'status': '状态',
            'action': '操作',
            'subtotal': '小计',
            'autoCalculate': '自动计算',
            'pleaseSelect': '请选择',
            'pleaseSelectDate': '请选择日期',
            'emailSent': '邮件',
            'gstNo': 'GST No',

            // 编辑/新增标题
            'editLanding': '编辑到货记录',
            'addLandingRecord': '新增到货记录',
            'editPurchase': '编辑采购记录',
            'addPurchaseRecord': '新增采购记录',
            'editSales': '编辑销售记录',
            'addSalesRecord': '新增销售记录',

            // 按钮
            'addLanding': '新增到货记录',
            'addPurchase': '新增采购记录',
            'addSales': '新增销售记录',
            'batchDelete': '批量删除',
            'batchExport': '批量导出',

            // 表单
            'selectSupplier': '请选择供应商',
            'selectPort': '请选择港口',
            'selectBoat': '请选择船只',
            'selectCustomer': '请选择客户',
            'selectStock': '请选择库存',
            'selectState': '请选择状态',
            'enterDate': '请输入日期',

            // 明细
            'details': '明细',
            'landingDetails': '到货明细',
            'purchaseDetails': '采购明细',
            'salesDetails': '销售明细',
            'addDetail': '添加明细',
            'stock': '库存',
            'fishSpecies': '鱼种类',
            'state': '状态',
            'ice': '冰',
            'bin': '箱',
            'binQty': '箱数量',
            'grossWeight': '毛重',
            'netWeight': '净重',
            'greenWeight': '绿色重量',
            'landedWeight': '上岸重量',
            'price': '单价',
            'description': '描述',
            'unitPrice': '单价',
            'total': '总计',
            'amount': '金额',
            'area': '区域',
            'unloadingDocket': '卸货单号',
            'weight': '重量',
            'iceColumn': '冰',
            'deleteRow': '删除',
            'actionColumn': '操作',

            // 消息
            'saveSuccess': '保存成功',
            'saveFailed': '保存失败',
            'deleteSuccess': '删除成功',
            'deleteConfirm': '确定删除这条记录吗？',
            'deleteLandingConfirm': '确定要删除吗？这将删除到货记录以及所有关联的采购和销售记录！',
            'deletePurchaseConfirm': '确定要删除吗？这将删除采购记录以及所有关联的销售记录！',
            'deleteSalesConfirm': '确定要删除这条销售记录吗？',
            'warning': '警告',
            'deleteFailed': '删除失败',
            'exportSuccess': '导出成功',
            'exportFailed': '导出失败',
            'requiredField': '请填写所有必填信息',
            'noDataToExport': '没有数据可导出',
            'atLeastOneDetail': '请至少添加一条明细记录',
            'deleteDetailConfirm': '确定删除这条明细吗？',

            // 从到货记录生成采购单
            'confirmGeneratePurchase': '确定要根据此到货记录生成采购单吗？',
            'purchaseGenerated': '采购单生成成功！',
            'purchaseOrderId': '采购单ID',
            'landingRecordId': '到货记录ID',
            'purchaseDate': '采购日期',
            'detailCount': '明细数量',
            'subtotal': '小计',
            'gst': '税费(GST)',
            'total': '总计',
            'viewPurchase': '查看采购单',
            'close': '关闭',
            'purchaseFailed': '生成采购单失败',

            // 从采购记录生成销售单
            'confirmGenerateSales': '确定要根据此采购记录生成销售单吗？',
            'salesGenerated': '销售单生成成功！',
            'salesOrderId': '销售单ID',
            'purchaseRecordId': '采购记录ID',
            'saleDate': '销售日期',
            'viewSales': '查看销售单',
            'salesFailed': '生成销售单失败',

            // 错误消息
            'errorPurchaseAlreadyGenerated': '该到货记录已生成过采购单（PurchaseID: {purchaseId}）',
            'errorSalesAlreadyGenerated': '该采购记录已生成销售单，请勿重复生成',
            'errorLandingNotExist': '到货记录不存在或已删除',
            'errorLandingNoDetails': '到货记录没有明细数据，无法生成采购单',
            'errorPurchaseNotExist': '采购记录不存在',
            'errorPurchaseNoDetails': '采购明细不存在',

            // 数据管理
            'dataManagement': '数据管理',
            'statistics': '统计报告',
            'supplierManagement': '供应商管理',
            'fleetManagement': '船队管理',
            'boatManagement': '船舶管理',
            'portManagement': '港口管理',
            'stockManagement': '库存管理',
            'binManagement': '篮子管理',
            'priceManagement': '供应商定价',
            'unitManagement': '单位管理',
            'fleet': '船队',
            'fleetName': '船队名称',
            'boat': '船只',
            'boatCount': '船只数量',
            'supplierCount': '供应商数量',

            // 月度统计
            'title': '月度采购统计报告',
            'backToHome': '返回首页',
            'month': '统计月份',
            'selectMonth': '选择月份',
            'supplier': '供应商',
            'allSuppliers': '全部供应商',
            'stock': '鱼种',
            'allStocks': '全部鱼种',
            'query': '查询',
            'reset': '重置',
            'totalGreenWeight': '总净重',
            'totalAmount': '总交易金额',
            'kg': 'kg',
            'currency': '元',
            'tableView': '表格视图',
            'chartView': '图表视图',
            'colSupplierName': '供应商名称',
            'colStockName': '鱼种名称',
            'colDescription': '学名',
            'colDate': '日期',
            'colGreenWeight': '净重(KG)',
            'colPrice': '单价($)',
            'colTotal': '金额($)',
            'colPurchaseID': '采购单号',
            'colMonth': '月份',
            'colTotalGreenWeight': '总净重',
            'colTransactionCount': '交易次数',
            'colAvgPrice': '平均单价',
            'colTotalAmount': '交易总额',
            'chartBarTitle': '供应商-鱼种净重对比',
            'chartPieTitle': '各供应商净重占比',
            'loadingData': '正在加载数据...',
            'loadSuccess': '数据加载成功',
            'loadFailed': '加载数据失败',
            'loadOptionsFailed': '加载选项失败',
            'noData': '暂无数据',
            'recordCount': '记录数',
            'recordCountUnit': '条',
            'viewDetails': '查看明细',
            'noDetailData': '暂无明细数据',
            'detailRecords': '明细记录',
            'detailSupplier': '供应商',
            'detailDate': '日期',
            'detailGreenWeight': '净重 (KG)',
            'detailPrice': '单价 ($)',
            'detailTotal': '总额 ($)',
            'detailPurchaseId': 'Purchase ID',
            'detailStockName': '鱼种',
            'detailDescription': '描述',
            'detailTotalGreenWeight': '总净重',
            'detailTotalAmount': '总金额',
            'detailRecordCount': '记录数',
            'detailClose': '关闭',

            'supplierID': '供应商ID',
            'stockID': '鱼种ID',
            'fishSpecies': '鱼种',
            'origin': '产地',
            'seaArea': '海域',
            'unitPrice': '单价',
            'effectiveDate': '生效日期',
            'enabled': '启用',
            'disabled': '禁用',
            'remark': '备注',
            'supplierName': '供应商名称',
            'address': '地址',
            'contactPerson': '联系人',
            'phone': '电话',
            'portName': '港口名称',
            'boatNo': '船只编号',
            'boatName': '船只名称',
            'stockName': '库存名称',
            'description': '描述',
            'state': '州',
            'area': '区域',
            'price': '价格',
            'conversion': '转换率',
            'scientificName': '学名',
            'addSuccess': '添加成功',
            'updateSuccess': '更新成功',
            'operationSuccess': '操作成功',
            'operationFailed': '操作失败',
            'networkError': '网络错误',
            'pleaseInput': '请输入',
            'deleteTitle': '删除确认',
            'deleteRecordConfirm': '确定删除这条记录吗？',
            'backToHome': '返回主页',
            'binName': '篮子名称',
            'binWeight': '篮子重量'
        },
        'en': {
            // Page Title
            'pageTitle': 'Fishery Data Management System',
            'systemTitle': 'Fishery Data Management System',
            'systemManagement': 'Fishery Data Management',
            'businessManagement': 'Business Management',
            'administrator': 'Administrator',

            // Navigation
            'landing': 'Landing Records',
            'purchase': 'Purchase Records',
            'sales': 'Sales Records',

            // Common
            'add': 'Add',
            'edit': 'Edit',
            'delete': 'Delete',
            'exportRow': 'Export',
            'printRow': 'Print',
            'netPrintRow': 'Net Print',
            'generatePurchase': 'Generate Purchase',
            'generateSales': 'Generate Sales',
            'save': 'Save',
            'cancel': 'Cancel',
            'confirm': 'Confirm',
            'export': 'Export',
            'refresh': 'Refresh',
            'search': 'Search',
            'sendEmail': 'Email',
            'logout': 'Logout',
            'loading': 'Loading...',
            'noData': 'No Data',
            'switchedToEn': 'Switched to English',

            // Login
            'loginTitle': 'Fishery Data Management System',
            'username': 'Username',
            'password': 'Password',
            'loginBtn': 'Login',
            'loginSuccess': 'Login successful',
            'loginFailed': 'Login failed',
            'logoutSuccess': 'Logged out successfully',
            'logoutConfirm': 'Are you sure to logout?',

            // Table
            'id': 'ID',
            'date': 'Date',
            'landingDate': 'Landing Date',
            'purchaseDate': 'Purchase Date',
            'saleDate': 'Sale Date',
            'name': 'Name',
            'supplier': 'Supplier',
            'supplierName': 'Supplier Name',
            'port': 'Port',
            'boatName': 'Boat Name',
            'boatNo': 'Boat No',
            'customerName': 'Customer Name',
            'detailCount': 'Detail Count',
            'greenWeight': 'Green Weight',
            'landedWeight': 'Landed Weight',
            'subtotal': 'Subtotal',
            'gst': 'GST',
            'total': 'Total',
            'status': 'Status',
            'action': 'Action',
            'autoCalculate': 'Auto Calculate',
            'pleaseSelect': 'Please Select',
            'pleaseSelectDate': 'Please Select Date',
            'emailSent': 'Email',
            'gstNo': 'GST No',

            // Edit/Add Titles
            'editLanding': 'Edit Landing Record',
            'addLandingRecord': 'Add Landing Record',
            'editPurchase': 'Edit Purchase Record',
            'addPurchaseRecord': 'Add Purchase Record',
            'editSales': 'Edit Sales Record',
            'addSalesRecord': 'Add Sales Record',

            // Buttons
            'addLanding': 'Add Landing Record',
            'addPurchase': 'Add Purchase Record',
            'addSales': 'Add Sales Record',
            'batchDelete': 'Batch Delete',
            'batchExport': 'Batch Export',

            // Form
            'selectSupplier': 'Please select supplier',
            'selectPort': 'Please select port',
            'selectBoat': 'Please select boat',
            'selectCustomer': 'Please select customer',
            'selectStock': 'Please select stock',
            'selectState': 'Please select state',
            'enterDate': 'Please enter date',

            // Details
            'details': 'Details',
            'landingDetails': 'Landing Details',
            'purchaseDetails': 'Purchase Details',
            'salesDetails': 'Sales Details',
            'addDetail': 'Add Detail',
            'stock': 'Stock',
            'fishSpecies': 'Fish Species',
            'state': 'State',
            'ice': 'Ice',
            'bin': 'Bin',
            'binQty': 'Bin Qty',
            'grossWeight': 'Gross Weight',
            'netWeight': 'Net Weight',
            'greenWeight': 'Green Weight',
            'landedWeight': 'Landed Weight',
            'price': 'Price',
            'description': 'Description',
            'unitPrice': 'Unit Price',
            'total': 'Total',
            'amount': 'Amount',
            'area': 'Area',
            'unloadingDocket': 'Unloading Docket',
            'weight': 'Weight',
            'iceColumn': 'Ice',
            'deleteRow': 'Delete',
            'actionColumn': 'Action',

            // Messages
            'saveSuccess': 'Saved successfully',
            'saveFailed': 'Save failed',
            'deleteSuccess': 'Deleted successfully',
            'deleteConfirm': 'Are you sure to delete this record?',
            'deleteLandingConfirm': 'Are you sure? This will delete the landing record and ALL associated purchase and sales records!',
            'deletePurchaseConfirm': 'Are you sure? This will delete the purchase record and ALL associated sales records!',
            'deleteSalesConfirm': 'Are you sure to delete this sales record?',
            'warning': 'Warning',
            'deleteFailed': 'Delete failed',
            'exportSuccess': 'Export successful',
            'exportFailed': 'Export failed',
            'requiredField': 'Please fill all required fields',
            'noDataToExport': 'No data to export',
            'atLeastOneDetail': 'Please add at least one detail record',
            'deleteDetailConfirm': 'Are you sure to delete this detail?',

            // Generate Purchase from Landing
            'confirmGeneratePurchase': 'Are you sure to generate purchase from this landing record?',
            'purchaseGenerated': 'Purchase Order Generated Successfully!',
            'purchaseOrderId': 'Purchase Order ID',
            'landingRecordId': 'Landing Record ID',
            'purchaseDate': 'Purchase Date',
            'detailCount': 'Detail Count',
            'subtotal': 'Subtotal',
            'gst': 'GST',
            'total': 'Total',
            'viewPurchase': 'View Purchase Order',
            'close': 'Close',
            'purchaseFailed': 'Failed to generate purchase order',

            // Generate Sales from Purchase
            'confirmGenerateSales': 'Are you sure to generate sales from this purchase record?',
            'salesGenerated': 'Sales Order Generated Successfully!',
            'salesOrderId': 'Sales Order ID',
            'purchaseRecordId': 'Purchase Record ID',
            'saleDate': 'Sale Date',
            'viewSales': 'View Sales Order',
            'salesFailed': 'Failed to generate sales order',

            // Error Messages
            'errorPurchaseAlreadyGenerated': 'This landing record has already generated a purchase order (PurchaseID: {purchaseId})',
            'errorSalesAlreadyGenerated': 'This purchase record has already generated a sales order. Please do not regenerate.',
            'errorLandingNotExist': 'Landing record does not exist or has been deleted',
            'errorLandingNoDetails': 'Landing record has no detail data, cannot generate purchase order',
            'errorPurchaseNotExist': 'Purchase record does not exist',
            'errorPurchaseNoDetails': 'Purchase details do not exist',

            // Data Management
            'dataManagement': 'Data Management',
            'statistics': 'Statistics Report',
            'supplierManagement': 'Supplier Management',
            'fleetManagement': 'Fleet Management',
            'boatManagement': 'Boat Management',
            'portManagement': 'Port Management',
            'stockManagement': 'Stock Management',
            'binManagement': 'Bin Management',
            'priceManagement': 'Supplier Pricing',
            'unitManagement': 'Unit Management',
            'fleet': 'Fleet',
            'fleetName': 'Fleet Name',
            'boat': 'Boat',
            'boatCount': 'Boat Count',
            'supplierCount': 'Supplier Count',

            // Monthly Statistics
            'title': 'Monthly Purchase Statistics Report',
            'backToHome': 'Back to Home',
            'month': 'Month',
            'selectMonth': 'Select Month',
            'supplier': 'Supplier',
            'allSuppliers': 'All Suppliers',
            'stock': 'Stock',
            'allStocks': 'All Stocks',
            'query': 'Query',
            'reset': 'Reset',
            'totalGreenWeight': 'Total Green Weight',
            'totalAmount': 'Total Amount',
            'kg': 'kg',
            'currency': '$',
            'tableView': 'Table View',
            'chartView': 'Chart View',
            'colSupplierName': 'Supplier Name',
            'colStockName': 'Stock Name',
            'colDescription': 'Description',
            'colDate': 'Date',
            'colGreenWeight': 'Green Weight(KG)',
            'colPrice': 'Price($)',
            'colTotal': 'Amount($)',
            'colPurchaseID': 'Purchase ID',
            'colMonth': 'Month',
            'colTotalGreenWeight': 'Total Green Weight',
            'colTransactionCount': 'Transaction Count',
            'colAvgPrice': 'Avg Price',
            'colTotalAmount': 'Total Amount',
            'chartBarTitle': 'Supplier-Stock Green Weight Comparison',
            'chartPieTitle': 'Green Weight Distribution by Supplier',
            'loadingData': 'Loading data...',
            'loadSuccess': 'Data loaded successfully',
            'loadFailed': 'Failed to load data',
            'loadOptionsFailed': 'Failed to load options',
            'noData': 'No data available',
            'recordCount': 'Record Count',
            'recordCountUnit': 'records',
            'viewDetails': 'View Details',
            'noDetailData': 'No detail data available',
            'detailRecords': 'Detail Records',
            'detailSupplier': 'Supplier',
            'detailDate': 'Date',
            'detailGreenWeight': 'Green Weight (KG)',
            'detailPrice': 'Price ($)',
            'detailTotal': 'Total ($)',
            'detailPurchaseId': 'Purchase ID',
            'detailStockName': 'Stock',
            'detailDescription': 'Description',
            'detailTotalGreenWeight': 'Total Green Weight',
            'detailTotalAmount': 'Total Amount',
            'detailRecordCount': 'Record Count',
            'detailClose': 'Close',

            'supplierID': 'Supplier ID',
            'stockID': 'Stock ID',
            'fishSpecies': 'Fish Species',
            'origin': 'Origin',
            'seaArea': 'Sea Area',
            'unitPrice': 'Unit Price',
            'effectiveDate': 'Effective Date',
            'enabled': 'Enabled',
            'disabled': 'Disabled',
            'remark': 'Remark',
            'supplierName': 'Supplier Name',
            'address': 'Address',
            'contactPerson': 'Contact Person',
            'phone': 'Phone',
            'portName': 'Port Name',
            'boatNo': 'Boat No',
            'boatName': 'Boat Name',
            'stockName': 'Stock Name',
            'description': 'Description',
            'state': 'State',
            'area': 'Area',
            'price': 'Price',
            'conversion': 'Conversion',
            'scientificName': 'Scientific Name',
            'addSuccess': 'Added successfully',
            'updateSuccess': 'Updated successfully',
            'operationSuccess': 'Operation successful',
            'operationFailed': 'Operation failed',
            'networkError': 'Network error',
            'pleaseInput': 'Please input',
            'deleteTitle': 'Delete Confirmation',
            'deleteRecordConfirm': 'Are you sure to delete this record?',
            'backToHome': 'Back to Home',
            'binName': 'Bin Name',
            'binWeight': 'Bin Weight'
        }
    };

    // 当前语言 (默认为英文)
    var currentLang = localStorage.getItem('language') || 'en';

    /**
     * 获取翻译文本
     * @param {string} key - 翻译键
     * @returns {string} 翻译后的文本
     */
    function t(key) {
        return translations[currentLang][key] || translations['zh'][key] || key;
    }

    /**
     * 设置语言
     * @param {string} lang - 语言代码 ('zh' 或 'en')
     */
    function setLanguage(lang) {
        if (translations[lang]) {
            currentLang = lang;
            localStorage.setItem('language', lang);
            updatePageLanguage();
        }
    }

    /**
     * 切换语言
     */
    function toggleLanguage() {
        var newLang = currentLang === 'zh' ? 'en' : 'zh';
        setLanguage(newLang);
        return newLang;
    }

    /**
     * 获取当前语言
     */
    function getCurrentLanguage() {
        return currentLang;
    }

    /**
     * 更新页面语言
     */
    function updatePageLanguage() {
        // 更新HTML lang属性
        document.documentElement.lang = currentLang === 'zh' ? 'zh-CN' : 'en';

        // 更新页面标题
        document.title = t('pageTitle');

        // 更新语言切换按钮
        var langBtn = document.getElementById('lang-toggle');
        if (langBtn) {
            langBtn.textContent = currentLang === 'zh' ? 'English' : '中文';
        }

        // 更新 body class
        document.body.className = 'lang-' + currentLang;

        // 触发自定义事件，让其他模块知道语言已切换
        var event = new CustomEvent('languageChanged', {detail: {lang: currentLang}});
        window.dispatchEvent(event);
    }

    // 导出模块
    exports('i18n', {
        t: t,
        setLanguage: setLanguage,
        toggleLanguage: toggleLanguage,
        getCurrentLanguage: getCurrentLanguage,
        updatePageLanguage: updatePageLanguage
    });

    // 自动初始化
    updatePageLanguage();
});
