/**
 * 月底汇总报表 (layui 版本)
 * 功能：生成和导出月底供应商汇总报表
 */

layui.use(['layer', 'form', 'table', 'laydate', 'jquery'], function(){
    var layer = layui.layer;
    var form = layui.form;
    var table = layui.table;
    var laydate = layui.laydate;
    var $ = layui.jquery;

    var suppliers = [];
    var reportData = null;
    var reportTable = null;

    // ==================== 初始化 ====================

    function init() {
        // 渲染表单
        form.render();

        // 初始化月份选择器
        laydate.render({
            elem: '#report-month',
            type: 'month'
        });

        // 设置默认月份
        setDefaultMonth();

        // 绑定事件
        bindEvents();

        // 加载供应商列表
        loadSuppliers();
    }

    /**
     * 设置默认月份
     */
    function setDefaultMonth() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        $('#report-month').val(year + '-' + month);
    }

    /**
     * 绑定事件
     */
    function bindEvents() {
        $('#generate-btn').on('click', generateReport);
        $('#export-excel-btn').on('click', exportToExcel);
    }

    // ==================== 加载数据 ====================

    /**
     * 加载供应商列表
     */
    function loadSuppliers() {
        fetch('../api/seafood.php?table=tblSuppliers&pageSize=1000')
            .then(function(response) { return response.json(); })
            .then(function(result){
                if (result.success) {
                    suppliers = result.data.filter(function(s) { return !s.Disc; });

                    var options = '<option value="">全部供应商</option>';
                    suppliers.forEach(function(supplier) {
                        options += '<option value="' + supplier.SupplierID + '">' + supplier.SupplierName + '</option>';
                    });
                    $('#report-supplier').html(options);
                    form.render('select');
                } else if (result.message && result.message.indexOf('未登录') !== -1) {
                    layer.msg('请先登录', {icon: 0});
                    setTimeout(function() {
                        window.location.href = 'index.html';
                    }, 1000);
                }
            })
            .catch(function(error){
                console.error('加载供应商失败:', error);
            });
    }

    // ==================== 生成报表 ====================

    /**
     * 生成报表
     */
    function generateReport() {
        var month = $('#report-month').val();
        var supplierId = $('#report-supplier').val();

        if (!month) {
            layer.msg('请选择月份', {icon: 0});
            return;
        }

        layer.load(1);

        var params = 'action=getMonthlyReport&month=' + month;
        if (supplierId) {
            params += '&supplierId=' + supplierId;
        }

        fetch('../api/purchase-landing.php?' + params)
            .then(function(response) { return response.json(); })
            .then(function(result){
                layer.closeAll('loading');
                if (result.success) {
                    reportData = result;
                    displayReport(result);
                    $('#export-excel-btn').show();
                } else if (result.message && result.message.indexOf('未登录') !== -1) {
                    layer.msg('请先登录', {icon: 0});
                    setTimeout(function() {
                        window.location.href = 'index.html';
                    }, 1000);
                } else {
                    layer.msg(result.message || '生成报表失败', {icon: 2});
                }
            })
            .catch(function(error){
                layer.closeAll('loading');
                console.error('生成报表失败:', error);
                layer.msg('生成报表失败，请重试', {icon: 2});
            });
    }

    /**
     * 显示报表
     */
    function displayReport(result) {
        var month = result.month;
        var summary = result.summary;
        var data = result.data;

        // 调试输出
        console.log('=== 月度报表数据结构 ===');
        console.log('供应商数量:', data.length);
        data.forEach(function(supplier, idx) {
            console.log('供应商' + idx + ':', supplier.SupplierName, '记录数:', supplier.records.length);
            console.log('记录详情:', supplier.records);
        });

        // 显示汇总信息（不显示收货次数）
        $('#summary-month').text(month);

        // 检查所有记录使用的单位是否一致
        var allUnits = new Set();
        data.forEach(function(supplier) {
            supplier.records.forEach(function(record) {
                allUnits.add(record.GreenUnitSymbol || 'kg');
            });
        });
        var unitText = allUnits.size === 1 ? Array.from(allUnits)[0] : '';

        $('#summary-weight').text(summary.totalGreenWeight.toFixed(2) + (unitText ? ' ' + unitText : ''));
        $('#summary-amount').text('$' + summary.totalAmount.toFixed(2));

        $('#summary-card').show();

        // 显示详细数据（按供应商分组）
        renderGroupedReport(data);
        $('#detail-card').show();
    }

    /**
     * 渲染报表（所有记录在一个表格中，完全不合并）
     */
    function renderGroupedReport(data) {
        if (!data || data.length === 0) {
            $('#report-table').parent().html('<div style="padding: 20px; text-align: center; color: #999;">该月份暂无数据</div>');
            return;
        }

        // 将所有供应商的所有记录合并到一个数组中
        var allRecords = [];
        data.forEach(function(supplier) {
            supplier.records.forEach(function(record) {
                allRecords.push({
                    ID: record.ID,
                    SupplierName: supplier.SupplierName,
                    QRN: supplier.QRN,
                    Stock: record.Stock,
                    Description: record.Description,
                    LandingDate: record.LandingDate,
                    GreenKG: record.GreenKG,
                    GreenUnitSymbol: record.GreenUnitSymbol || 'kg',
                    Price: record.Price,
                    Total: record.Total,
                    BoatName: record.BoatName,
                    Port: record.Port
                });
            });
        });

        // 使用 layui table 渲染
        table.render({
            elem: '#report-table',
            data: allRecords,
            cols: [[
                {field: 'ID', title: 'ID', width: 60, sort: true},
                {field: 'SupplierName', title: '供应商', width: 200},
                {field: 'LandingDate', title: '日期', width: 120, templet: function(d){
                    var date = new Date(d.LandingDate);
                    return date.getFullYear() + '-' +
                           String(date.getMonth() + 1).padStart(2, '0') + '-' +
                           String(date.getDate()).padStart(2, '0');
                }},
                {field: 'Stock', title: '鱼种类', width: 120},
                {field: 'GreenKG', title: '净重', width: 100, templet: function(d){
                    return d.GreenKG.toFixed(2) + ' ' + d.GreenUnitSymbol;
                }},
                {field: 'Price', title: '单价($)', width: 80, templet: function(d){
                    return '$' + d.Price.toFixed(2);
                }},
                {field: 'Total', title: '金额($)', width: 100, templet: function(d){
                    return '$' + d.Total.toFixed(2);
                }},
                {field: 'BoatName', title: '船只', width: 120},
                {field: 'Port', title: '港口', width: 100}
            ]],
            page: true,
            limit: 20,
            limits: [10, 20, 50, 100],
            autoSort: true,
            defaultToolbar: ['filter', 'exports', 'print']
        });
    }

    // ==================== 导出 Excel ====================

    /**
     * 导出到Excel（所有记录在一个表格，完全无合并）
     */
    function exportToExcel() {
        if (!reportData || !reportData.data) {
            layer.msg('没有数据可导出', {icon: 0});
            return;
        }

        if (typeof XLSX === 'undefined') {
            layer.msg('Excel导出库未加载', {icon: 2});
            return;
        }

        try {
            var month = reportData.month;
            var summary = reportData.summary;
            var data = reportData.data;

            // 创建工作簿
            var wb = XLSX.utils.book_new();

            // 创建汇总工作表
            // 检查所有记录使用的单位是否一致
            var allUnits = new Set();
            data.forEach(function(supplier) {
                supplier.records.forEach(function(record) {
                    allUnits.add(record.GreenUnitSymbol || 'kg');
                });
            });
            var unitText = allUnits.size === 1 ? Array.from(allUnits)[0] : 'mixed';

            var summaryData = [
                ['月底汇总报表'],
                [''],
                ['月份', month],
                ['总净重 (' + unitText + ')', summary.totalGreenWeight.toFixed(2)],
                ['总金额 ($)', summary.totalAmount.toFixed(2)]
            ];
            var summaryWs = XLSX.utils.aoa_to_sheet(summaryData);
            XLSX.utils.book_append_sheet(wb, summaryWs, '汇总');

            // 创建详细数据工作表（所有记录平铺，不分组）
            var detailData = ['ID', '供应商', 'QRN', '日期', '鱼种类', '学名', '净重', '单位', '单价($)', '金额($)', '船只', '港口'];

            data.forEach(function(supplier) {
                supplier.records.forEach(function(record) {
                    var date = new Date(record.LandingDate);
                    var dateStr = date.getFullYear() + '-' +
                                 String(date.getMonth() + 1).padStart(2, '0') + '-' +
                                 String(date.getDate()).padStart(2, '0');

                    detailData.push([
                        record.ID,
                        supplier.SupplierName || '',
                        supplier.QRN || '',
                        dateStr,
                        record.Stock || '',
                        record.Description || '',
                        record.GreenKG.toFixed(2),
                        record.GreenUnitSymbol || 'kg',
                        record.Price.toFixed(2),
                        record.Total.toFixed(2),
                        record.BoatName || '',
                        record.Port || ''
                    ]);
                });
            });

            var detailWs = XLSX.utils.aoa_to_sheet(detailData);
            detailWs['!cols'] = [
                {wch: 8},
                {wch: 30},
                {wch: 15},
                {wch: 15},
                {wch: 15},
                {wch: 25},
                {wch: 12},
                {wch: 8},   // 单位列
                {wch: 10},
                {wch: 10},
                {wch: 15},
                {wch: 15}
            ];
            XLSX.utils.book_append_sheet(wb, detailWs, '详细数据');

            // 生成文件名
            var filename = '月底汇总_' + month + '.xlsx';

            // 导出下载
            XLSX.writeFile(wb, filename);

            layer.msg('导出成功！', {icon: 1});
        } catch (error) {
            console.error('导出失败:', error);
            layer.msg('导出失败，请重试', {icon: 2});
        }
    }

    // ==================== 启动 ====================
    init();
});
