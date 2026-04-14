/**
 * 渔业收购管理系统 - 收货录入 (layui 版本)
 * 功能：收货录入、明细管理、账单生成
 */

layui.use(['layer', 'form', 'table', 'laydate', 'jquery'], function(){
    var layer = layui.layer;
    var form = layui.form;
    var table = layui.table;
    var laydate = layui.laydate;
    var $ = layui.jquery;

    // 全局数据
    var landingDetails = [];
    var suppliers = [];
    var boats = [];
    var ports = [];
    var stocks = [];
    var bins = [];
    var currentLanding = null;
    var detailTable = null;
    var listTable = null;
    var supplierPrices = []; // 存储供应商定价数据

    // ==================== 初始化 ====================

    /**
     * 初始化
     */
    function init() {
        // 渲染表单
        form.render();

        // 初始化日期选择器
        laydate.render({
            elem: '#landing-date',
            type: 'datetime'
        });

        // 设置默认日期
        setDefaultDateTime();

        // 绑定事件
        bindEvents();

        // 加载基础数据
        loadInitialData();

        // 初始化明细表格
        initDetailTable();

        // 加载最近收货记录
        initLandingListTable();
    }

    /**
     * 设置默认日期
     */
    function setDefaultDateTime() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        $('#landing-date').val(year + '-' + month + '-' + day + ' ' + hours + ':' + minutes);
    }

    /**
     * 绑定事件
     */
    function bindEvents() {
        // 添加明细按钮
        $('#add-detail-btn').on('click', openDetailModal);

        // 操作按钮
        $('#reset-btn').on('click', resetForm);
        $('#save-btn').on('click', saveLanding);
    }

    // ==================== 加载数据 ====================

    /**
     * 加载基础数据
     */
    function loadInitialData() {
        layer.load(1);

        Promise.all([
            fetchData('tblSuppliers'),
            fetchData('tblBoat'),
            fetchData('tblPort'),
            fetchData('tblStock'),
            fetchData('tblBin'),
            fetchSupplierPrices()
        ]).then(function(results) {
            suppliers = results[0].filter(function(s) { return !s.Disc; });
            boats = results[1].filter(function(b) { return !b.Disc; });
            ports = results[2].filter(function(p) { return !p.Disc; });
            stocks = results[3];
            bins = results[4].filter(function(b) { return !b.Disc; });
            supplierPrices = results[5] || [];

            populateSelects();
            layer.closeAll('loading');
        }).catch(function(error) {
            console.error('加载数据失败:', error);
            layer.msg('加载数据失败', {icon: 2});
            layer.closeAll('loading');
        });
    }

    /**
     * 获取供应商定价数据
     */
    function fetchSupplierPrices() {
        return fetch('../api/supplier-stock-price.php?action=list')
            .then(function(response) { return response.json(); })
            .then(function(result) {
                return result.code === 0 ? result.data : [];
            })
            .catch(function(error) {
                console.error('加载供应商定价失败:', error);
                return [];
            });
    }

    /**
     * 获取表数据
     */
    function fetchData(tableName) {
        return fetch('../api/seafood.php?table=' + tableName + '&pageSize=1000')
            .then(function(response) { return response.json(); })
            .then(function(result) { return result.success ? result.data : []; });
    }

    /**
     * 填充下拉选项
     */
    function populateSelects() {
        // 供应商
        var supplierOptions = '<option value="">请选择供应商</option>';
        suppliers.forEach(function(supplier) {
            supplierOptions += '<option value="' + supplier.SupplierID + '">' + supplier.SupplierName + '</option>';
        });
        $('#supplier-select').html(supplierOptions);

        // 渔船
        var boatOptions = '<option value="">Please select boat</option>';
        boats.forEach(function(boat) {
            boatOptions += '<option value="' + boat.BoatID + '">' + (boat.BoatName || boat.BoatNo) + '</option>';
        });
        $('#boat-select').html(boatOptions);

        // 港口
        var portOptions = '<option value="">请选择港口</option>';
        ports.forEach(function(port) {
            portOptions += '<option value="' + port.PortID + '">' + port.Port + '</option>';
        });
        $('#port-select').html(portOptions);

        // 监听供应商变化
        $('#supplier-select').on('change', onSupplierChange);

        form.render('select');
    }

    /**
     * 供应商变化时更新价格显示
     */
    function onSupplierChange() {
        // 如果已经打开了明细弹窗，更新价格显示
        if ($('input[name="price"]').length > 0) {
            var stockSelect = $('#detail-stock')[0];
            if (stockSelect && stockSelect.selectedIndex > 0) {
                updatePriceBySupplierAndStock();
            }
        }
    }

    // ==================== 表格初始化 ====================

    /**
     * 初始化明细表格
     */
    function initDetailTable() {
        detailTable = table.render({
            elem: '#detail-table',
            data: [],
            cols: [[
                {field: 'stock', title: '鱼种类'},
                {field: 'binId', title: '鱼箱', templet: function(d){
                    return '箱#' + d.binId;
                }},
                {field: 'binQty', title: '桶数量', edit: 'text', templet: function(d){
                    return d.binQty || 1;
                }},
                {field: 'landedWeight', title: '带桶重量(KG)', edit: 'text', templet: function(d){
                    return d.landedWeight ? d.landedWeight.toFixed(2) : '0.00';
                }},
                {field: 'binWeight', title: '单个箱重(KG)', templet: function(d){
                    return d.binWeight.toFixed(2);
                }},
                {field: 'totalBinWeight', title: '总箱重(KG)', templet: function(d){
                    return d.totalBinWeight.toFixed(2);
                }},
                {field: 'conversion', title: '换算系数'},
                {field: 'greenWeight', title: '净重(KG)', templet: function(d){
                    return '<span style="color: #16a34a;">' + d.greenWeight.toFixed(2) + '</span>';
                }},
                {field: 'price', title: '单价($)', edit: 'text', templet: function(d){
                    return d.price ? d.price.toFixed(2) : '0.00';
                }},
                {field: 'total', title: '总价($)', templet: function(d){
                    return '<span style="color: #f59e0b;">$' + d.total.toFixed(2) + '</span>';
                }},
                {fixed: 'right', title: '操作', toolbar: '#detailOperateTpl', width: 150}
            ]],
            page: false,
            limit: 9999
        });

        // 监听单元格编辑事件
        table.on('edit(detail-table)', function(obj){
            console.log('单元格编辑事件触发');
            console.log('字段:', obj.field);
            console.log('值:', obj.value);
            console.log('数据:', obj.data);

            var field = obj.field;
            var value = obj.value;
            var index = obj.tr.attr('data-index');
            var data = landingDetails[index];

            if (field === 'landedWeight' || field === 'binQty' || field === 'price') {
                console.log('编辑了需要重新计算的字段:', field);

                // 更新数据
                if (field === 'landedWeight') {
                    data.landedWeight = parseFloat(value) || 0;
                } else if (field === 'binQty') {
                    data.binQty = parseInt(value) || 1;
                } else if (field === 'price') {
                    data.price = parseFloat(value) || 0;
                }

                // 重新计算
                var totalBinWeight = data.binWeight * data.binQty;
                var greenWeight = (data.landedWeight - totalBinWeight) * data.conversion;
                var total = greenWeight * data.price;

                // 更新数据对象
                data.totalBinWeight = totalBinWeight;
                data.greenWeight = greenWeight;
                data.total = total;

                console.log('重新计算完成 - greenWeight:', greenWeight, 'total:', total);

                // 更新汇总
                updateSummary();

                // 使用 obj.update 更新所有字段（包括 greenWeight）
                obj.update({
                    landedWeight: data.landedWeight,
                    binQty: data.binQty,
                    price: data.price,
                    totalBinWeight: totalBinWeight,
                    greenWeight: greenWeight,
                    total: total
                });

                console.log('表格更新完成 - greenWeight:', greenWeight, 'total:', total);
            }
        });

        // 监听行工具事件
        table.on('tool(detail-table)', function(obj){
            if (obj.event === 'edit') {
                editDetailModal(obj.data, obj.tr.attr('data-index'));
            } else if (obj.event === 'del') {
                landingDetails.splice(obj.tr.attr('data-index'), 1);
                reloadDetailTable();
                updateSummary();
            }
        });
    }

    /**
     * 刷新明细表格
     */
    function reloadDetailTable() {
        detailTable.reload({
            data: landingDetails
        });
    }

    /**
     * 初始化收货记录列表表格
     */
    function initLandingListTable() {
        listTable = table.render({
            elem: '#landing-list-table',
            url: '../api/purchase-landing.php?action=getRecentLandings',
            cols: [[
                {field: 'DocketID', title: 'Docket ID', width: 120},
                {field: 'PurchaseID', title: 'Purchase ID', width: 120},
                {field: 'SalesID', title: 'Sales ID', width: 100},
                {field: 'LandingDate', title: '日期', width: 160, templet: function(d){
                    return formatDateTime(d.LandingDate);
                }},
                {field: 'SupplierName', title: '供应商', width: 150},
                {field: 'Port', title: '港口', width: 100},
                {field: 'TotalGreenWeight', title: '总净重(KG)', width: 120, templet: function(d){
                    return d.TotalGreenWeight ? d.TotalGreenWeight.toFixed(2) : '0.00';
                }},
                {field: 'TotalAmount', title: '总金额($)', width: 120, templet: function(d){
                    return d.TotalAmount ? '$' + d.TotalAmount.toFixed(2) : '$0.00';
                }},
                {fixed: 'right', title: '操作', toolbar: '#listOperateTpl', width: 150}
            ]],
            page: true,
            limit: 10,
            parseData: function(res){
                return {
                    "code": res.success ? 0 : 1,
                    "msg": res.message || '',
                    "count": res.count || 0,  // 使用后端返回的总记录数
                    "data": res.data || []
                };
            }
        });

        // 监听行工具事件
        table.on('tool(landing-list-table)', function(obj){
            if (obj.event === 'bill') {
                viewBills(obj.data.DocketID);
            } else if (obj.event === 'print') {
                printBills(obj.data.DocketID);
            } else if (obj.event === 'view') {
                layer.msg('查看功能开发中', {icon: 0});
            }
        });
    }

    // ==================== 明细弹窗 ====================

    /**
     * 打开明细弹窗
     */
    function openDetailModal() {
        // 填充鱼种类和鱼箱选项
        var stockOptions = '<option value="">请选择鱼种类</option>';
        stocks.forEach(function(stock) {
            stockOptions += '<option value="' + stock.StockID + '" data-conversion="' + (stock.Conversion || 1) + '" data-price="' + (stock.Price || 0) + '">' + stock.Stock + ' - ' + stock.Description + '</option>';
        });

        var binOptions = '<option value="">请选择鱼箱</option>';
        bins.forEach(function(bin) {
            binOptions += '<option value="' + bin.BinID + '" data-weight="' + (bin.B_Weight || 0) + '">' + bin.BinName + '</option>';
        });

        layer.open({
            type: 1,
            title: '添加收货明细',
            area: ['500px', '500px'],
            content: $('#detail-modal-template').html(),
            btn: ['确定', '取消'],
            yes: function(index, layero){
                handleAddDetail(index, layero);
            },
            success: function(layero, index){
                console.log('=== success 回调被调用 ===');
                console.log('layero:', layero);
                console.log('$(layero):', $(layero));

                // 使用 layero 限定选择器范围
                var $layero = $(layero);

                console.log('开始填充选项...');

                // 填充选项
                $layero.find('#detail-stock').html(stockOptions);
                $layero.find('#detail-bin').html(binOptions);

                console.log('选项填充完成，开始渲染表单...');

                form.render('select');

                console.log('表单渲染完成');
                console.log('查找 #detail-stock:', $layero.find('#detail-stock').length);
                console.log('查找 input[name="landedWeight"]:', $layero.find('input[name="landedWeight"]').length);

                // 创建一个闭包函数来处理预览计算
                var doCalculatePreview = function() {
                    console.log('>>> doCalculatePreview 被调用');

                    var stockSelect = $layero.find('#detail-stock')[0];
                    var binSelect = $layero.find('#detail-bin')[0];
                    var landedWeight = parseFloat($layero.find('input[name="landedWeight"]').val()) || 0;
                    var binQty = parseInt($layero.find('input[name="binQty"]').val()) || 1;
                    var price = parseFloat($layero.find('input[name="price"]').val()) || 0;

                    console.log('>>> 输入值 - landedWeight:', landedWeight, 'binQty:', binQty, 'price:', price);

                    var conversion = 1;
                    var binWeight = 0;

                    // 只有当选择了有效选项时才获取值
                    if (stockSelect && stockSelect.selectedIndex > 0) {
                        var selectedStockOption = stockSelect.options[stockSelect.selectedIndex];
                        conversion = $(selectedStockOption).data('conversion') || 1;
                        console.log('>>> conversion:', conversion);
                    }

                    if (binSelect && binSelect.selectedIndex > 0) {
                        var selectedBinOption = binSelect.options[binSelect.selectedIndex];
                        binWeight = $(selectedBinOption).data('weight') || 0;
                        console.log('>>> binWeight:', binWeight);
                    }

                    var totalBinWeight = binWeight * binQty;
                    var greenWeight = (landedWeight - totalBinWeight) * conversion;
                    var total = greenWeight * price;

                    console.log('>>> 计算结果 - totalBinWeight:', totalBinWeight, 'greenWeight:', greenWeight, 'total:', total);

                    // 更新预览显示
                    $layero.find('#preview-green-weight').text(greenWeight.toFixed(2) + ' KG');
                    $layero.find('#preview-total').text('$' + total.toFixed(2));

                    console.log('>>> DOM更新完成');
                };

                console.log('开始绑定事件...');

                // 监听变化 - 使用 layero 限定范围
                var landedWeightInput = $layero.find('input[name="landedWeight"]');
                console.log('找到的 landedWeight 输入框:', landedWeightInput.length);

                landedWeightInput.on('input', function() {
                    console.log('!!! 带桶重量改变，值:', $(this).val());
                    doCalculatePreview();
                });

                $layero.find('#detail-stock').on('change', function() {
                    console.log('!!! 鱼种类改变');
                    doCalculatePreview();
                });
                $layero.find('#detail-bin').on('change', function() {
                    console.log('!!! 鱼箱改变');
                    doCalculatePreview();
                });
                $layero.find('input[name="binQty"]').on('input', function() {
                    console.log('!!! 桶数量改变，值:', $(this).val());
                    doCalculatePreview();
                });
                $layero.find('input[name="price"]').on('input', function() {
                    console.log('!!! 单价改变，值:', $(this).val());
                    doCalculatePreview();
                });

                console.log('事件绑定完成');

                // 如果已经选择了供应商，自动设置价格
                var supplierId = $('#supplier-select').val();
                if (supplierId) {
                    // 延迟执行，确保 DOM 已更新
                    setTimeout(function() {
                        // 从tblStock获取默认价格
                        var stockSelect = $layero.find('#detail-stock')[0];
                        if (stockSelect && stockSelect.selectedIndex > 0) {
                            var selectedOption = stockSelect.options[stockSelect.selectedIndex];
                            var defaultPrice = $(selectedOption).data('price') || 0;

                            // 尝试从供应商定价表获取价格
                            var stockId = stockSelect.value;
                            var supplierPrice = supplierPrices.find(function(p) {
                                return p.SupplierID === supplierId && p.StockID === stockId && p.is_del == 0;
                            });

                            if (supplierPrice && supplierPrice.UnitPrice) {
                                $layero.find('input[name="price"]').val(parseFloat(supplierPrice.UnitPrice));
                            } else {
                                $layero.find('input[name="price"]').val(defaultPrice);
                            }
                            doCalculatePreview();
                        }
                    }, 100);
                }

                console.log('=== success 回调结束 ===');
            }
        });
    }

    /**
     * 打开编辑明细弹窗
     */
    function editDetailModal(data, index) {
        // 填充鱼种类和鱼箱选项
        var stockOptions = '<option value="">请选择鱼种类</option>';
        stocks.forEach(function(stock) {
            var selected = stock.StockID == data.stockId ? ' selected' : '';
            stockOptions += '<option value="' + stock.StockID + '" data-conversion="' + (stock.Conversion || 1) + '" data-price="' + (stock.Price || 0) + '"' + selected + '>' + stock.Stock + ' - ' + stock.Description + '</option>';
        });

        var binOptions = '<option value="">请选择鱼箱</option>';
        bins.forEach(function(bin) {
            var selected = bin.BinID == data.binId ? ' selected' : '';
            binOptions += '<option value="' + bin.BinID + '" data-weight="' + (bin.B_Weight || 0) + '"' + selected + '>' + bin.BinName + '</option>';
        });

        layer.open({
            type: 1,
            title: '编辑收货明细',
            area: ['500px', '500px'],
            content: $('#detail-modal-template').html(),
            btn: ['保存', '取消'],
            yes: function(layerIndex, layero){
                handleEditDetail(layerIndex, layero, index);
            },
            success: function(layero, index){
                console.log('=== [编辑模式] success 回调被调用 ===');

                // 使用 layero 限定选择器范围
                var $layero = $(layero);

                console.log('[编辑模式] 开始填充选项...');

                // 填充选项
                $layero.find('#detail-stock').html(stockOptions);
                $layero.find('#detail-bin').html(binOptions);

                // 填充已有数据
                $layero.find('input[name="binQty"]').val(data.binQty);
                $layero.find('input[name="landedWeight"]').val(data.landedWeight);
                $layero.find('input[name="price"]').val(data.price);

                console.log('[编辑模式] 选项填充完成，开始渲染表单...');

                form.render('select');

                console.log('[编辑模式] 表单渲染完成');
                console.log('[编辑模式] 查找 #detail-stock:', $layero.find('#detail-stock').length);
                console.log('[编辑模式] 查找 input[name="landedWeight"]:', $layero.find('input[name="landedWeight"]').length);

                // 创建一个闭包函数来处理预览计算
                var doCalculatePreview = function() {
                    console.log('>>> [编辑模式] doCalculatePreview 被调用');

                    var stockSelect = $layero.find('#detail-stock')[0];
                    var binSelect = $layero.find('#detail-bin')[0];
                    var landedWeight = parseFloat($layero.find('input[name="landedWeight"]').val()) || 0;
                    var binQty = parseInt($layero.find('input[name="binQty"]').val()) || 1;
                    var price = parseFloat($layero.find('input[name="price"]').val()) || 0;

                    console.log('>>> [编辑模式] 输入值 - landedWeight:', landedWeight, 'binQty:', binQty, 'price:', price);

                    var conversion = 1;
                    var binWeight = 0;

                    // 只有当选择了有效选项时才获取值
                    if (stockSelect && stockSelect.selectedIndex > 0) {
                        var selectedStockOption = stockSelect.options[stockSelect.selectedIndex];
                        conversion = $(selectedStockOption).data('conversion') || 1;
                        console.log('>>> [编辑模式] conversion:', conversion);
                    }

                    if (binSelect && binSelect.selectedIndex > 0) {
                        var selectedBinOption = binSelect.options[binSelect.selectedIndex];
                        binWeight = $(selectedBinOption).data('weight') || 0;
                        console.log('>>> [编辑模式] binWeight:', binWeight);
                    }

                    var totalBinWeight = binWeight * binQty;
                    var greenWeight = (landedWeight - totalBinWeight) * conversion;
                    var total = greenWeight * price;

                    console.log('>>> [编辑模式] 计算结果 - totalBinWeight:', totalBinWeight, 'greenWeight:', greenWeight, 'total:', total);

                    // 更新预览显示
                    if (stockSelect && stockSelect.selectedIndex > 0) {
                        $layero.find('#preview-conversion').text(conversion);
                    } else {
                        $layero.find('#preview-conversion').text('-');
                    }

                    if (binSelect && binSelect.selectedIndex > 0) {
                        $layero.find('#preview-bin-weight').text(binWeight.toFixed(2) + ' KG');
                        $layero.find('#preview-total-bin-weight').text(totalBinWeight.toFixed(2) + ' KG');
                    } else {
                        $layero.find('#preview-bin-weight').text('-');
                        $layero.find('#preview-total-bin-weight').text('-');
                    }

                    $layero.find('#preview-green-weight').text(greenWeight.toFixed(2) + ' KG');
                    $layero.find('#preview-total').text('$' + total.toFixed(2));

                    console.log('>>> [编辑模式] DOM更新完成');
                };

                console.log('[编辑模式] 开始绑定事件...');

                // 监听变化 - 使用 layero 限定范围
                var landedWeightInput = $layero.find('input[name="landedWeight"]');
                console.log('[编辑模式] 找到的 landedWeight 输入框:', landedWeightInput.length);

                landedWeightInput.on('input', function() {
                    console.log('!!! [编辑模式] 带桶重量改变，值:', $(this).val());
                    doCalculatePreview();
                });

                $layero.find('#detail-stock').on('change', function() {
                    console.log('!!! [编辑模式] 鱼种类改变');
                    doCalculatePreview();
                });
                $layero.find('#detail-bin').on('change', function() {
                    console.log('!!! [编辑模式] 鱼箱改变');
                    doCalculatePreview();
                });
                $layero.find('input[name="binQty"]').on('input', function() {
                    console.log('!!! [编辑模式] 桶数量改变，值:', $(this).val());
                    doCalculatePreview();
                });
                $layero.find('input[name="price"]').on('input', function() {
                    console.log('!!! [编辑模式] 单价改变，值:', $(this).val());
                    doCalculatePreview();
                });

                console.log('[编辑模式] 事件绑定完成');

                // 初始计算预览
                doCalculatePreview();

                console.log('=== [编辑模式] success 回调结束 ===');
            }
        });
    }

    /**
     * 保存编辑的明细
     */
    function handleEditDetail(layerIndex, layero, dataIndex) {
        var $layero = $(layero);

        var stockId = $layero.find('#detail-stock').val();
        var binId = $layero.find('#detail-bin').val();
        var landedWeight = parseFloat($layero.find('input[name="landedWeight"]').val()) || 0;
        var binQty = parseInt($layero.find('input[name="binQty"]').val()) || 1;
        var price = parseFloat($layero.find('input[name="price"]').val()) || 0;

        if (!stockId || !binId || landedWeight <= 0) {
            layer.msg('请填写完整的必填信息', {icon: 0});
            return;
        }

        var stockSelect = $layero.find('#detail-stock')[0];
        var binSelect = $layero.find('#detail-bin')[0];
        var selectedStockOption = stockSelect.options[stockSelect.selectedIndex];
        var selectedBinOption = binSelect.options[binSelect.selectedIndex];

        var conversion = $(selectedStockOption).data('conversion') || 1;
        var binWeight = $(selectedBinOption).data('weight') || 0;
        var totalBinWeight = binWeight * binQty;
        var greenWeight = (landedWeight - totalBinWeight) * conversion;
        var total = greenWeight * price;

        // 更新数据
        landingDetails[dataIndex] = {
            stockId: stockId,
            binId: binId,
            binQty: binQty,
            landedWeight: landedWeight,
            binWeight: binWeight,
            totalBinWeight: totalBinWeight,
            conversion: conversion,
            greenWeight: greenWeight,
            price: price,
            total: total,
            stock: $(selectedStockOption).text().split(' - ')[0]
        };

        reloadDetailTable();
        updateSummary();
        layer.close(layerIndex);
    }

    /**
     * 添加明细
     */
    function handleAddDetail(layerIndex, layero) {
        var $layero = $(layero);

        var stockId = $layero.find('#detail-stock').val();
        var binId = $layero.find('#detail-bin').val();
        var landedWeight = parseFloat($layero.find('input[name="landedWeight"]').val()) || 0;
        var binQty = parseInt($layero.find('input[name="binQty"]').val()) || 1;
        var price = parseFloat($layero.find('input[name="price"]').val()) || 0;

        if (!stockId || !binId || landedWeight <= 0) {
            layer.msg('请填写完整的必填信息', {icon: 0});
            return;
        }

        var stockSelect = $layero.find('#detail-stock')[0];
        var binSelect = $layero.find('#detail-bin')[0];
        var selectedStockOption = stockSelect.options[stockSelect.selectedIndex];
        var selectedBinOption = binSelect.options[binSelect.selectedIndex];

        var conversion = $(selectedStockOption).data('conversion') || 1;
        var binWeight = $(selectedBinOption).data('weight') || 0;
        var totalBinWeight = binWeight * binQty;
        var greenWeight = (landedWeight - totalBinWeight) * conversion;
        var total = greenWeight * price;

        landingDetails.push({
            stockId: stockId,
            binId: binId,
            binQty: binQty,
            landedWeight: landedWeight,
            binWeight: binWeight,
            totalBinWeight: totalBinWeight,
            conversion: conversion,
            greenWeight: greenWeight,
            price: price,
            total: total,
            stock: $(selectedStockOption).text().split(' - ')[0]
        });

        reloadDetailTable();
        updateSummary();
        layer.close(layerIndex);
    }

    /**
     * 更新汇总
     */
    function updateSummary() {
        var totalGreenWeight = landingDetails.reduce(function(sum, d) { return sum + d.greenWeight; }, 0);
        var totalAmount = landingDetails.reduce(function(sum, d) { return sum + d.total; }, 0);

        $('#total-green-weight').text(totalGreenWeight.toFixed(2) + ' KG');
        $('#total-amount').text('$' + totalAmount.toFixed(2));
    }

    // ==================== 保存和账单 ====================

    /**
     * 重置表单
     */
    function resetForm() {
        if (landingDetails.length > 0) {
            layer.confirm('确定要重置吗？所有未保存的明细将丢失。', function(index){
                landingDetails = [];
                currentLanding = null;
                reloadDetailTable();
                updateSummary();
                setDefaultDateTime();
                form.val('landingForm', {
                    landingDate: '',
                    supplierId: '',
                    boatId: '',
                    portId: ''
                });
                layer.close(index);
            });
        } else {
            setDefaultDateTime();
            form.val('landingForm', {
                landingDate: '',
                supplierId: '',
                boatId: '',
                portId: ''
            });
        }
    }

    /**
     * 保存收货
     */
    function saveLanding() {
        var formData = form.val('landingForm');

        if (!formData.landingDate || !formData.supplierId || !formData.portId) {
            layer.msg('请填写所有必填信息', {icon: 0});
            return;
        }

        if (landingDetails.length === 0) {
            layer.msg('请至少添加一条明细', {icon: 0});
            return;
        }

        layer.load(1);

        fetch('../api/purchase-landing.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'saveLanding',
                data: {
                    landingDate: formData.landingDate,
                    supplierId: formData.supplierId,
                    portId: formData.portId,
                    boatId: formData.boatId,
                    details: landingDetails
                }
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(result){
            layer.closeAll('loading');
            if (result.success) {
                currentLanding = result.landing;
                layer.msg('保存成功！Docket ID: ' + result.landing.DocketID, {icon: 1});
                listTable.reload();
            } else {
                layer.msg(result.message || '保存失败', {icon: 2});
            }
        })
        .catch(function(error){
            layer.closeAll('loading');
            console.error('保存失败:', error);
            layer.msg('保存失败，请重试', {icon: 2});
        });
    }

    /**
     * 查看账单
     */
    function viewBills(docketId) {
        layer.load(1);

        fetch('../api/purchase-landing.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'getBills',
                docketId: docketId
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(result){
            layer.closeAll('loading');
            if (result.success) {
                currentLanding = {DocketID: docketId};
                showBillPreview(result.bills);
            } else {
                layer.msg(result.message || '获取账单失败', {icon: 2});
            }
        })
        .catch(function(error){
            layer.closeAll('loading');
            console.error('获取账单失败:', error);
            layer.msg('获取账单失败，请重试', {icon: 2});
        });
    }

    /**
     * 打印账单
     */
    function printBills(docketId) {
        layer.load(1);

        fetch('../api/purchase-landing.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'getBills',
                docketId: docketId
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(result){
            layer.closeAll('loading');
            if (result.success) {
                // 将账单数据存储到 sessionStorage
                sessionStorage.setItem('printBillsData', JSON.stringify(result.bills));
                sessionStorage.setItem('printBillsDocketId', docketId);

                // 跳转到打印页面
                window.open('print-bill.html', '_blank');
            } else {
                layer.msg(result.message || '获取账单失败', {icon: 2});
            }
        })
        .catch(function(error){
            layer.closeAll('loading');
            console.error('获取账单失败:', error);
            layer.msg('获取账单失败，请重试', {icon: 2});
        });
    }

    /**
     * 显示账单预览
     */
    function showBillPreview(bills) {
        var purchase = bills.purchase;
        var sales = bills.sales;

        layer.open({
            type: 1,
            title: '账单预览',
            area: ['800px', '600px'],
            content: $('#bill-modal-template').html(),
            btn: ['关闭'],
            success: function(){
                // 填充采购账单数据
                $('#bill-purchase-id').text(purchase.PurchaseID);
                $('#bill-date').text(formatDateTime(purchase.PurchaseDate));
                $('#bill-supplier').text(purchase.SupplierName);

                var purchaseItemsHtml = '';
                purchase.details.forEach(function(item){
                    purchaseItemsHtml += '<tr>' +
                        '<td>' + item.Stock + '</td>' +
                        '<td>' + item.LandedKG.toFixed(2) + ' ' + (item.LandedUnitSymbol || 'kg') + '</td>' +
                        '<td>' + item.GreenKG.toFixed(2) + ' ' + (item.GreenUnitSymbol || 'kg') + '</td>' +
                        '<td>$' + item.Price.toFixed(2) + '</td>' +
                        '<td>$' + item.Total.toFixed(2) + '</td>' +
                        '</tr>';
                });
                $('#bill-purchase-items').html(purchaseItemsHtml);

                // 填充销售账单数据
                $('#bill-sales-id').text(sales.SalesID);
                $('#bill-date-2').text(formatDateTime(sales.SaleDate));

                var salesItemsHtml = '';
                sales.details.forEach(function(item){
                    salesItemsHtml += '<tr>' +
                        '<td>' + item.Stock + '</td>' +
                        '<td>' + item.BinQty + '</td>' +
                        '<td>' + item.G_Weight.toFixed(2) + ' ' + (item.WeightUnitSymbol || 'kg') + '</td>' +
                        '<td>' + item.N_Weight.toFixed(2) + ' ' + (item.WeightUnitSymbol || 'kg') + '</td>' +
                        '<td>$' + item.Price.toFixed(2) + '</td>' +
                        '<td>$' + item.Amount.toFixed(2) + '</td>' +
                        '</tr>';
                });
                $('#bill-sales-items').html(salesItemsHtml);
            }
        });
    }

    // ==================== 工具函数 ====================

    /**
     * 格式化日期时间
     */
    function formatDateTime(dateStr) {
        if (!dateStr) return '-';
        var date = new Date(dateStr);
        return date.toLocaleString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // ==================== 启动 ====================
    init();
});
