/**
 * ============================================
 * 月度统计模块 JavaScript
 * 说明：处理统计数据展示、筛选、图表渲染等功能
 * ============================================
 */

layui.use(['layer', 'form', 'table', 'laydate', 'laytpl', 'jquery', 'i18n'], function(){
    var layer = layui.layer,
        form = layui.form,
        table = layui.table,
        laydate = layui.laydate,
        $ = layui.jquery,  // 使用 LayUI 内置的 jQuery
        i18n = layui.i18n;  // 使用 LayUI i18n 模块

    // 全局变量
    var currentView = 'table';  // 当前视图：table 或 chart
    var statisticsData = [];     // 原始统计数据（详细记录）
    var aggregatedData = [];     // 聚合数据（按鱼种汇总）
    var chartBar = null;         // 柱状图实例
    var chartPie = null;         // 饼图实例

    // ==================== 初始化 ====================

    /**
     * 页面初始化
     */
    function init() {
        // 初始化日期选择器
        initDatePicker();

        // 加载筛选选项
        loadFilterOptions();

        // 绑定事件
        bindEvents();

        // 初始化表格
        initTable();

        // 初始化图表
        initCharts();

        // 加载当前月数据
        loadStatistics();

        // 应用当前语言
        applyCurrentLanguage();
    }

    /**
     * 应用当前语言
     */
    function applyCurrentLanguage() {
        // 立即应用当前存储的语言
        var currentLang = i18n.getCurrentLanguage();
        updatePageLanguage(currentLang);

        // 监听语言变化事件（由首页触发）
        $(document).on('languageChanged', function(e, lang) {
            updatePageLanguage(lang);
        });
    }

    /**
     * 更新页面语言
     */
    function updatePageLanguage(lang) {
        // 更新所有带有 data-i18n 属性的元素
        $('[data-i18n]').each(function() {
            var key = $(this).attr('data-i18n');
            var translation = i18n.t(key);
            if ($(this).children('i').length > 0) {
                // 如果包含图标，保留图标，只更新文本
                var icon = $(this).find('i').clone();
                $(this).contents().filter(function() {
                    return this.nodeType === 3 || (this.nodeType === 1 && !$(this).is('i'));
                }).remove();
                $(this).append(icon).append(' ' + translation);
            } else if ($(this).is('span') || $(this).is('h2') || $(this).is('h3') || $(this).is('label')) {
                $(this).text(translation);
            } else {
                $(this).html(translation);
            }
        });

        // 更新 placeholder
        $('[data-i18n-placeholder]').each(function() {
            var key = $(this).attr('data-i18n-placeholder');
            $(this).attr('placeholder', i18n.t(key));
        });

        // 更新下拉框的"全部"选项
        $('#supplier-select option:first').text(i18n.t('allSuppliers'));
        $('#stock-select option:first').text(i18n.t('allStocks'));

        // 重新渲染下拉框
        form.render('select');

        // 重新渲染表格以更新列标题
        if (statisticsData.length > 0) {
            table.reloadData('statistics-table', {
                data: statisticsData
            });
        }

        // 重新渲染图表以更新标题
        if (currentView === 'chart') {
            renderCharts();
        }
    }

    /**
     * 获取翻译文本的辅助函数
     */
    function t(key) {
        return i18n.t(key);
    }

    /**
     * 初始化日期选择器
     */
    function initDatePicker() {
        laydate.render({
            elem: '#month-picker',
            type: 'month',
            value: new Date(),  // 默认当前月
            done: function(value) {
                console.log('选择的月份:', value);
            }
        });
    }

    /**
     * 加载筛选选项
     */
    function loadFilterOptions() {
        layer.load(1);

        fetch('../api/statistics-options.php')
            .then(res => {
                // 检查响应状态
                if (!res.ok) {
                    throw new Error('HTTP error! status: ' + res.status);
                }
                return res.json();
            })
            .then(res => {
                layer.closeAll();

                if (res.success) {
                    // 填充供应商下拉框
                    var supplierOptions = '<option value="">' + t('allSuppliers') + '</option>';
                    res.data.suppliers.forEach(function(item) {
                        supplierOptions += '<option value="' + item.SupplierID + '">' + item.SupplierName + '</option>';
                    });
                    $('#supplier-select').html(supplierOptions);

                    // 填充鱼种下拉框 - 只显示 Stock 名称（已去重）
                    var stockOptions = '<option value="">' + t('allStocks') + '</option>';
                    res.data.stocks.forEach(function(item) {
                        // 使用 Stock 名称作为 value
                        stockOptions += '<option value="' + item.StockName + '">' + item.StockName + '</option>';
                    });
                    $('#stock-select').html(stockOptions);

                    // 重新渲染下拉框
                    form.render('select');
                } else if (res.message && res.message.indexOf('未登录') !== -1) {
                    layer.msg('请先登录', {icon: 0});
                    setTimeout(function() {
                        window.location.href = 'index.html';
                    }, 1000);
                } else {
                    layer.msg(res.message || t('loadOptionsFailed'), {icon: 2});
                }
            })
            .catch(err => {
                layer.closeAll();
                console.error('加载选项失败:', err);
                layer.msg(t('loadOptionsFailed') + ': ' + err.message, {icon: 2});
            });
    }

    /**
     * 绑定事件
     */
    function bindEvents() {
        // 返回首页按钮
        $('#back-home-btn').on('click', function() {
            window.location.href = 'index.html';
        });

        // 查询按钮
        $('#query-btn').on('click', function() {
            loadStatistics();
        });

        // 重置按钮 - 直接刷新页面
        $('#reset-btn').on('click', function() {
            window.location.reload();
        });

        // 视图切换 - 表格视图
        $('#view-table').on('click', function() {
            switchView('table');
        });

        // 视图切换 - 图表视图
        $('#view-chart').on('click', function() {
            switchView('chart');
        });
    }

    /**
     * 切换视图
     */
    function switchView(view) {
        currentView = view;

        if (view === 'table') {
            $('#table-view').show();
            $('#chart-view').hide();
            $('#chart-view-pie').hide();
            $('#view-table').addClass('layui-btn-normal').removeClass('layui-btn-primary');
            $('#view-chart').removeClass('layui-btn-normal').addClass('layui-btn-primary');
        } else {
            $('#table-view').hide();
            $('#chart-view').show();
            $('#chart-view-pie').show();
            $('#view-chart').addClass('layui-btn-normal').removeClass('layui-btn-primary');
            $('#view-table').removeClass('layui-btn-normal').addClass('layui-btn-primary');
            renderCharts();
        }
    }

    // ==================== 数据加载 ====================

    /**
     * 加载统计数据
     */
    function loadStatistics() {
        var month = $('#month-picker').val() || '';
        var supplierIds = $('#supplier-select').val() || [];
        var stockIds = $('#stock-select').val() || [];

        // 多选处理
        if (Array.isArray(supplierIds)) {
            supplierIds = supplierIds.join(',');
        }
        if (Array.isArray(stockIds)) {
            stockIds = stockIds.join(',');
        }

        var params = new URLSearchParams({
            month: month,
            supplierIds: supplierIds,
            stockIds: stockIds
        });

        layer.load(1);

        fetch('../api/monthly-statistics.php?' + params.toString())
            .then(res => {
                if (!res.ok) {
                    throw new Error('HTTP error! status: ' + res.status);
                }
                return res.json();
            })
            .then(res => {
                layer.closeAll();

                if (res.success) {
                    statisticsData = res.data.statistics;

                    // 聚合数据（按鱼种汇总）
                    aggregateDataByStock();

                    // 更新汇总卡片
                    updateSummaryCards(res.data.summary);

                    // 更新表格（使用聚合数据）
                    table.reloadData('statistics-table', {
                        data: aggregatedData
                    });

                    // 更新图表
                    if (currentView === 'chart') {
                        renderCharts();
                    }

                    layer.msg(t('loadSuccess'), {icon: 1, time: 1000});
                } else if (res.message && res.message.indexOf('未登录') !== -1) {
                    layer.msg('请先登录', {icon: 0});
                    setTimeout(function() {
                        window.location.href = 'index.html';
                    }, 1000);
                } else {
                    layer.msg(res.message || t('loadFailed'), {icon: 2});
                }
            })
            .catch(err => {
                layer.closeAll();
                console.error('加载统计数据失败:', err);
                layer.msg(t('loadFailed') + ': ' + err.message, {icon: 2});
            });
    }

    /**
     * 更新汇总卡片
     */
    function updateSummaryCards(summary) {
        $('#total-green-weight').html(summary.total_green_weight.toFixed(2) + '<span class="unit">' + t('kg') + '</span>');
        $('#total-amount').html('$' + summary.total_amount.toFixed(2) + '<span class="unit">' + t('currency') + '</span>');
    }

    /**
     * 按鱼种聚合数据
     */
    function aggregateDataByStock() {
        var stockMap = {};

        statisticsData.forEach(function(item) {
            var stockName = item.StockName;

            if (!stockMap[stockName]) {
                stockMap[stockName] = {
                    StockName: stockName,
                    Description: item.Description,
                    TotalGreenKG: 0,
                    TotalAmount: 0,
                    RecordCount: 0,
                    Details: []  // 存储明细记录
                };
            }

            stockMap[stockName].TotalGreenKG += parseFloat(item.GreenKG);
            stockMap[stockName].TotalAmount += parseFloat(item.Total);
            stockMap[stockName].RecordCount += 1;
            stockMap[stockName].Details.push(item);
        });

        // 转换为数组并排序
        aggregatedData = Object.keys(stockMap).map(function(key) {
            return stockMap[key];
        }).sort(function(a, b) {
            return b.TotalGreenKG - a.TotalGreenKG;  // 按净重降序排序
        });

        console.log('聚合数据:', aggregatedData);
    }

    // ==================== 表格 ====================

    /**
     * 初始化表格（按鱼种聚合显示）
     */
    function initTable() {
        table.render({
            elem: '#statistics-table',
            cols: [[
                {
                    field: 'StockName',
                    title: t('colStockName'),
                    width: 200,
                    fixed: 'left',
                    templet: function(d) {
                        return '<div style="cursor:pointer;color:#2563eb;font-weight:bold;" onclick="window.showStockDetails(\'' + d.StockName + '\')">' +
                               '<i class="layui-icon layui-icon-form"></i> ' + d.StockName +
                               '</div>';
                    }
                },
                {field: 'Description', title: t('colDescription'), width: 200},
                {
                    field: 'TotalGreenKG',
                    title: t('colGreenWeight') + ' (' + t('total') + ')',
                    width: 150,
                    sort: true,
                    templet: function(d) {
                        return '<span style="color:#16a34a;font-weight:bold;font-size:16px;">' + d.TotalGreenKG.toFixed(2) + '</span> ' + t('kg');
                    }
                },
                {
                    field: 'TotalAmount',
                    title: t('colTotal') + ' (' + t('total') + ')',
                    width: 150,
                    sort: true,
                    templet: function(d) {
                        return '<span style="color:#dc2626;font-weight:bold;font-size:16px;">$' + d.TotalAmount.toFixed(2) + '</span>';
                    }
                },
                {
                    field: 'RecordCount',
                    title: t('recordCount'),
                    width: 120,
                    sort: true,
                    templet: function(d) {
                        return '<span style="color:#6366f1;font-weight:bold;">' + d.RecordCount + '</span> ' + t('recordCountUnit');
                    }
                },
                {
                    field: 'Action',
                    title: t('action'),
                    width: 120,
                    fixed: 'right',
                    templet: function(d) {
                        return '<a class="layui-btn layui-btn-xs layui-btn-normal" onclick="window.showStockDetails(\'' + d.StockName + '\')">' +
                               '<i class="layui-icon layui-icon-form"></i> ' + t('viewDetails') + '</a>';
                    }
                }
            ]],
            data: [],
            page: true,
            limit: 20,
            limits: [10, 20, 50, 100],
            skin: 'line',
            even: true,
            autoSort: true
        });
    }

    // ==================== 图表 ====================

    /**
     * 初始化图表
     */
    function initCharts() {
        chartBar = echarts.init(document.getElementById('chartBar'));
        chartPie = echarts.init(document.getElementById('chartPie'));

        // 窗口大小变化时重新渲染
        window.addEventListener('resize', function() {
            chartBar.resize();
            chartPie.resize();
        });
    }

    /**
     * 渲染图表
     */
    function renderCharts() {
        if (statisticsData.length === 0) {
            chartBar.clear();
            chartPie.clear();
            return;
        }

        renderBarChart();
        renderPieChart();
    }

    /**
     * 渲染柱状图
     */
    function renderBarChart() {
        // 准备数据
        var suppliers = [];
        var stockData = {};

        statisticsData.forEach(function(item) {
            // 供应商名称 + QRN
            var supplierName = item.SupplierName || '';
            var qrn = item.QRN || '';
            var supplierDisplay = qrn ? supplierName + ' (' + qrn + ')' : supplierName;

            if (suppliers.indexOf(supplierDisplay) === -1) {
                suppliers.push(supplierDisplay);
            }

            if (!stockData[supplierDisplay]) {
                stockData[supplierDisplay] = {};
            }
            stockData[supplierDisplay][item.StockName] = item.total_green_weight;
        });

        // 获取所有鱼种
        var allStocks = [];
        statisticsData.forEach(function(item) {
            if (allStocks.indexOf(item.StockName) === -1) {
                allStocks.push(item.StockName);
            }
        });

        // 构建系列数据
        var series = [];
        allStocks.forEach(function(stockName) {
            var data = suppliers.map(function(supplierName) {
                return stockData[supplierName] && stockData[supplierName][stockName] ?
                    parseFloat(stockData[supplierName][stockName]).toFixed(2) : 0;
            });

            series.push({
                name: stockName,
                type: 'bar',
                data: data,
                label: {
                    show: true,
                    position: 'top',
                    formatter: '{c}'
                }
            });
        });

        var option = {
            title: {
                text: t('chartBarTitle'),
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                data: allStocks,
                top: 30
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                data: suppliers
            },
            yAxis: {
                type: 'value',
                name: t('kg')
            },
            series: series
        };

        chartBar.setOption(option, true);
    }

    /**
     * 渲染饼图
     */
    function renderPieChart() {
        // 按供应商汇总数据
        var supplierData = {};
        statisticsData.forEach(function(item) {
            // 供应商名称 + QRN
            var supplierName = item.SupplierName || '';
            var qrn = item.QRN || '';
            var supplierDisplay = qrn ? supplierName + ' (' + qrn + ')' : supplierName;

            if (!supplierData[supplierDisplay]) {
                supplierData[supplierDisplay] = 0;
            }
            supplierData[supplierDisplay] += parseFloat(item.total_green_weight);
        });

        var data = [];
        for (var supplierName in supplierData) {
            data.push({
                name: supplierName,
                value: supplierData[supplierName].toFixed(2)
            });
        }

        var option = {
            title: {
                text: t('chartPieTitle'),
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                formatter: '{a} <br/>{b}: {c}' + t('kg') + ' ({d}%)'
            },
            legend: {
                orient: 'vertical',
                left: 'left'
            },
            series: [
                {
                    name: t('totalGreenWeight'),
                    type: 'pie',
                    radius: '50%',
                    data: data,
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    label: {
                        formatter: '{b}: {c}' + t('kg') + ' ({d}%)'
                    }
                }
            ]
        };

        chartPie.setOption(option, true);
    }

    // ==================== 明细弹窗 ====================

    /**
     * 显示鱼种明细弹窗
     */
    window.showStockDetails = function(stockName) {
        // 从聚合数据中找到对应的鱼种
        var stockData = aggregatedData.find(function(item) {
            return item.StockName === stockName;
        });

        if (!stockData || !stockData.Details || stockData.Details.length === 0) {
            layer.msg(t('noDetailData'), {icon: 0});
            return;
        }

        var details = stockData.Details;

        // 构建表格 HTML
        var tableHtml = '<table class="layui-table" lay-skin="line">' +
            '<thead>' +
            '<tr>' +
            '<th>' + t('detailSupplier') + '</th>' +
            '<th>' + t('detailDate') + '</th>' +
            '<th>' + t('detailGreenWeight') + '</th>' +
            '<th>' + t('detailPrice') + '</th>' +
            '<th>' + t('detailTotal') + '</th>' +
            '<th>' + t('detailPurchaseId') + '</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>';

        details.forEach(function(item) {
            var supplierName = item.SupplierName || '';
            var qrn = item.QRN || '';
            var supplierDisplay = qrn ? supplierName + ' (' + qrn + ')' : supplierName;

            var date = new Date(item.PurchaseDate);
            var dateStr = date.getFullYear() + '-' +
                         String(date.getMonth() + 1).padStart(2, '0') + '-' +
                         String(date.getDate()).padStart(2, '0');

            tableHtml += '<tr>' +
                '<td>' + supplierDisplay + '</td>' +
                '<td>' + dateStr + '</td>' +
                '<td style="color:#16a34a;font-weight:bold;">' + parseFloat(item.GreenKG).toFixed(2) + '</td>' +
                '<td>' + parseFloat(item.Price).toFixed(2) + '</td>' +
                '<td style="color:#dc2626;font-weight:bold;">' + parseFloat(item.Total).toFixed(2) + '</td>' +
                '<td>' + item.PurchaseID + '</td>' +
                '</tr>';
        });

        tableHtml += '</tbody></table>';

        // 弹出层
        layer.open({
            type: 1,
            title: '<i class="layui-icon layui-icon-form"></i> ' + stockName + ' - ' + t('detailRecords') + ' (' + details.length + ' ' + t('recordCountUnit') + ')',
            area: ['90%', '80%'],
            content: '<div style="padding: 20px;">' +
                     '<div style="margin-bottom: 15px;padding: 15px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:4px;">' +
                     '<div style="margin-bottom: 5px;"><strong>' + t('detailStockName') + '：</strong>' + stockName + '</div>' +
                     '<div style="margin-bottom: 5px;"><strong>' + t('detailDescription') + '：</strong>' + (stockData.Description || '-') + '</div>' +
                     '<div style="margin-bottom: 5px;"><strong>' + t('detailTotalGreenWeight') + '：</strong><span style="color:#16a34a;font-weight:bold;">' + stockData.TotalGreenKG.toFixed(2) + ' KG</span></div>' +
                     '<div style="margin-bottom: 5px;"><strong>' + t('detailTotalAmount') + '：</strong><span style="color:#dc2626;font-weight:bold;">$' + stockData.TotalAmount.toFixed(2) + '</span></div>' +
                     '<div><strong>' + t('detailRecordCount') + '：</strong><span style="color:#6366f1;font-weight:bold;">' + stockData.RecordCount + ' ' + t('recordCountUnit') + '</span></div>' +
                     '</div>' +
                     tableHtml +
                     '</div>',
            btn: [t('detailClose')],
            yes: function(index) {
                layer.close(index);
            }
        });
    };

    // ==================== 启动 ====================

    init();
});
