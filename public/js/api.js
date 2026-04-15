/**
 * API 请求封装模块
 * 渔业数据管理系统
 */

layui.define(['jquery'], function(exports){
    var $ = layui.jquery;
    var API_BASE = '../api/';

    /**
     * 基础请求方法
     * @param {string} url - 请求地址
     * @param {object} options - 请求选项
     * @returns {Promise}
     */
    function request(url, options) {
        options = options || {};
        var defaultOptions = {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            }
        };

        // 合并选项
        var opts = Object.assign({}, defaultOptions, options);

        // 处理 GET 请求参数
        if (opts.method === 'GET' && opts.params) {
            var params = new URLSearchParams(opts.params);
            url += '?' + params.toString();
        }

        // 发送请求
        return fetch(url, opts)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (!data.success) {
                    throw new Error(data.message || '请求失败');
                }
                return data;
            });
    }

    /**
     * 登录
     * @param {object} data - {username, password}
     */
    function login(data) {
        return request(API_BASE + 'login.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * 登出
     */
    function logout() {
        return request(API_BASE + 'logout.php', {
            method: 'POST'
        });
    }

    /**
     * 获取列表数据(带分页)
     * @param {string} module - 模块名称 (landing/purchase/sales)
     * @param {object} params - 查询参数 {page, pageSize, search}
     */
    function getList(module, params) {
        return request(API_BASE + module + '.php', {
            method: 'GET',
            params: params
        });
    }

    /**
     * 获取单条记录
     * @param {string} module - 模块名称
     * @param {number} id - 记录ID
     */
    function getById(module, id) {
        return request(API_BASE + module + '.php?id=' + id);
    }

    /**
     * 新增记录
     * @param {string} module - 模块名称
     * @param {object} data - 记录数据
     */
    function add(module, data) {
        return request(API_BASE + module + '.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * 更新记录
     * @param {string} module - 模块名称
     * @param {object} data - 记录数据（包含id字段）
     */
    function update(module, data) {
        return request(API_BASE + module + '.php', {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * 删除记录
     * @param {string} module - 模块名称
     * @param {number} id - 记录ID
     */
    function deleteRecord(module, id) {
        // landing、purchase 和 sales 模块使用特定ID字段
        var idField = module === 'landing' ? 'LandingID' : (module === 'purchase' ? 'PurchaseID' : (module === 'sales' ? 'SalesID' : 'id'));
        var bodyData = {};
        bodyData[idField] = id;
        return request(API_BASE + module + '.php', {
            method: 'DELETE',
            body: JSON.stringify(bodyData)
        });
    }

    /**
     * 获取采购明细（批量）
     * @param {array} purchaseIds - 采购ID数组
     */
    function getPurchaseDetails(purchaseIds) {
        return request(API_BASE + 'purchase.php', {
            method: 'POST',
            body: JSON.stringify({action: 'getDetails', purchaseIds: purchaseIds})
        });
    }

    /**
     * 获取销售明细（批量）
     * @param {array} salesIds - 销售ID数组
     */
    function getSalesDetails(salesIds) {
        return request(API_BASE + 'sales.php', {
            method: 'POST',
            body: JSON.stringify({action: 'getDetails', salesIds: salesIds})
        });
    }

    /**
     * 获取到货明细（批量）
     * @param {array} landingIds - 到货ID数组
     */
    function getLandingDetails(landingIds) {
        return request(API_BASE + 'landing.php', {
            method: 'POST',
            body: JSON.stringify({action: 'getDetails', landingIds: landingIds})
        });
    }

    /**
     * 更新采购记录邮件发送状态
     * @param {number} purchaseId - 采购ID
     * @param {number} emailSent - 邮件发送状态 (0-未发送, 1-已发送)
     */
    function updatePurchaseEmailSent(purchaseId, emailSent) {
        return request(API_BASE + 'purchase.php', {
            method: 'POST',
            body: JSON.stringify({action: 'updateEmailSent', PurchaseID: purchaseId, EmailSent: emailSent})
        });
    }

    // 导出 API 模块
    exports('api', {
        request: request,
        login: login,
        logout: logout,
        getList: getList,
        getById: getById,
        add: add,
        update: update,
        delete: deleteRecord,
        getPurchaseDetails: getPurchaseDetails,
        getSalesDetails: getSalesDetails,
        getLandingDetails: getLandingDetails,
        updatePurchaseEmailSent: updatePurchaseEmailSent
    });
});
