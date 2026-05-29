/**
 * ============================================
 * 国际化 (i18n) 配置文件
 * 说明：定义中英文翻译
 * ============================================
 */

// 定义翻译字典
var translations = {
    zh: {
        title: '月度采购统计报告',
        backToHome: '返回首页',
        month: '统计月份',
        selectMonth: '选择月份',
        supplier: '供应商',
        allSuppliers: '全部供应商',
        stock: '鱼种',
        allStocks: '全部鱼种',
        query: '查询',
        reset: '重置',
        totalGreenWeight: '总净重 (Green Weight)',
        totalAmount: '总交易金额',
        kg: 'kg',
        currency: '元',
        tableView: '表格视图',
        chartView: '图表视图',
        // 表格列标题
        colSupplierName: '供应商名称',
        colStockName: '鱼种名称',
        colMonth: '月份',
        colTotalGreenWeight: '总净重 (kg)',
        colTransactionCount: '交易次数',
        colAvgPrice: '平均单价',
        colTotalAmount: '交易总额',
        // 图表标题
        chartBarTitle: '供应商-鱼种净重对比',
        chartPieTitle: '各供应商净重占比',
        // 消息提示
        loadingData: '正在加载数据...',
        loadSuccess: '数据加载成功',
        loadFailed: '加载数据失败',
        loadOptionsFailed: '加载选项失败',
        noData: '暂无数据'
    },
    en: {
        title: 'Monthly Purchase Statistics Report',
        backToHome: 'Back to Home',
        month: 'Month',
        selectMonth: 'Select Month',
        supplier: 'Supplier',
        allSuppliers: 'All Suppliers',
        stock: 'Stock',
        allStocks: 'All Stocks',
        query: 'Query',
        reset: 'Reset',
        totalGreenWeight: 'Total Green Weight',
        totalAmount: 'Total Amount',
        kg: 'kg',
        currency: '$',
        tableView: 'Table View',
        chartView: 'Chart View',
        // 表格列标题
        colSupplierName: 'Supplier Name',
        colStockName: 'Stock Name',
        colMonth: 'Month',
        colTotalGreenWeight: 'Total Green Weight (kg)',
        colTransactionCount: 'Transaction Count',
        colAvgPrice: 'Avg Price',
        colTotalAmount: 'Total Amount',
        // 图表标题
        chartBarTitle: 'Supplier-Stock Green Weight Comparison',
        chartPieTitle: 'Green Weight Distribution by Supplier',
        // 消息提示
        loadingData: 'Loading data...',
        loadSuccess: 'Data loaded successfully',
        loadFailed: 'Failed to load data',
        loadOptionsFailed: 'Failed to load options',
        noData: 'No data available'
    }
};

// 获取当前语言或设置默认语言
var currentLanguage = localStorage.getItem('language') || 'zh';

/**
 * 切换语言
 */
function switchLanguage(lang) {
    currentLanguage = lang;
    localStorage.setItem('language', lang);
    applyLanguage(lang);
}

/**
 * 应用语言到页面
 */
function applyLanguage(lang) {
    var texts = translations[lang] || translations.zh;

    // 更新所有带有 data-i18n 属性的元素
    $('[data-i18n]').each(function() {
        var key = $(this).attr('data-i18n');
        if (texts[key]) {
            if ($(this).children('i').length > 0) {
                // 如果包含图标，保留图标，只更新文本
                var icon = $(this).find('i').clone();
                $(this).contents().filter(function() {
                    return this.nodeType === 3 || (this.nodeType === 1 && !$(this).is('i'));
                }).remove();
                $(this).append(icon).append(' ' + texts[key]);
            } else if ($(this).is('span') || $(this).is('h2') || $(this).is('h3') || $(this).is('label')) {
                $(this).text(texts[key]);
            } else {
                $(this).html(texts[key]);
            }
        }
    });

    // 更新 placeholder
    $('[data-i18n-placeholder]').each(function() {
        var key = $(this).attr('data-i18n-placeholder');
        if (texts[key]) {
            $(this).attr('placeholder', texts[key]);
        }
    });

    // 更新语言切换按钮文本
    if (lang === 'zh') {
        $('#lang-toggle').text('English');
    } else {
        $('#lang-toggle').text('中文');
    }
}

/**
 * 获取翻译文本
 */
function t(key) {
    var texts = translations[currentLanguage] || translations.zh;
    return texts[key] || key;
}

// 页面加载完成后自动应用语言
$(document).ready(function() {
    applyLanguage(currentLanguage);
});
