<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PrinterController extends Controller
{
    public function __construct(
        private PrinterService $printerService
    ) {}

    /**
     * 获取打印机列表
     */
    public function index(Request $request)
    {
        $page = max(1, intval($request->input('page', 1)));
        $pageSize = max(1, min(100, intval($request->input('pageSize', 10))));
        $search = trim($request->input('search', ''));

        $result = $this->printerService->getList($page, $pageSize, $search);

        return $this->paginated($result['data'], $result['pagination']);
    }

    /**
     * 获取单个打印机
     */
    public function show(int $id)
    {
        $printer = $this->printerService->getById($id);

        if (!$printer) {
            return $this->error('打印机不存在', 404);
        }

        return $this->success($printer);
    }

    /**
     * 添加打印机
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'printer_name' => 'required|string|max:100',
            'printer_ip' => 'required|ip',
            'printer_port' => 'required|integer|min:1|max:65535',
            'printer_type' => 'nullable|string|max:50',
            'is_default' => 'nullable|integer|in:0,1',
            'status' => 'nullable|integer|in:0,1',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $printer = $this->printerService->add($validated);
            return $this->success($printer, '添加成功');
        } catch (\Exception $e) {
            Log::error('添加打印机失败', ['error' => $e->getMessage()]);
            return $this->error('添加失败: ' . $e->getMessage());
        }
    }

    /**
     * 编辑打印机
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'printer_name' => 'required|string|max:100',
            'printer_ip' => 'required|ip',
            'printer_port' => 'required|integer|min:1|max:65535',
            'printer_type' => 'nullable|string|max:50',
            'is_default' => 'nullable|integer|in:0,1',
            'status' => 'nullable|integer|in:0,1',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $printer = $this->printerService->update($id, $validated);

            if (!$printer) {
                return $this->error('打印机不存在', 404);
            }

            return $this->success($printer, '更新成功');
        } catch (\Exception $e) {
            Log::error('更新打印机失败', ['error' => $e->getMessage()]);
            return $this->error('更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除打印机
     */
    public function destroy(int $id)
    {
        try {
            $deleted = $this->printerService->delete($id);

            if (!$deleted) {
                return $this->error('打印机不存在', 404);
            }

            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            Log::error('删除打印机失败', ['error' => $e->getMessage()]);
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 设置默认打印机
     */
    public function setDefault(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $result = $this->printerService->setDefault($validated['id']);

            if (!$result) {
                return $this->error('设置失败，打印机不存在');
            }

            return $this->success(null, '默认打印机设置成功');
        } catch (\Exception $e) {
            Log::error('设置默认打印机失败', ['error' => $e->getMessage()]);
            return $this->error('设置失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试打印机连接
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'printer_ip' => 'required|ip',
            'printer_port' => 'required|integer|min:1|max:65535',
        ]);

        try {
            $result = $this->printerService->testConnection($validated['printer_ip'], $validated['printer_port']);
            return $this->success($result, $result['success'] ? '测试打印已发送' : '连接失败');
        } catch (\Exception $e) {
            Log::error('测试打印机失败', ['error' => $e->getMessage()]);
            return $this->error('测试失败: ' . $e->getMessage());
        }
    }

    /**
     * 网络打印到货记录
     */
    public function printLanding(Request $request)
    {
        $validated = $request->validate([
            'landing_id' => 'required|integer',
            'printer_id' => 'nullable|integer',
        ]);

        try {
            $result = $this->printerService->printLanding($validated['landing_id'], $validated['printer_id'] ?? null);
            return $this->success($result, $result['message']);
        } catch (\Exception $e) {
            Log::error('打印到货记录失败', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage());
        }
    }

    /**
     * 网络打印采购单
     */
    public function printPurchase(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'required|integer',
            'printer_id' => 'nullable|integer',
        ]);

        try {
            $result = $this->printerService->printPurchase($validated['purchase_id'], $validated['printer_id'] ?? null);
            return $this->success($result, $result['message']);
        } catch (\Exception $e) {
            Log::error('打印采购单失败', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage());
        }
    }

    /**
     * 网络打印销售单
     */
    public function printSales(Request $request)
    {
        $validated = $request->validate([
            'sales_id' => 'required|integer',
            'printer_id' => 'nullable|integer',
        ]);

        try {
            $result = $this->printerService->printSales($validated['sales_id'], $validated['printer_id'] ?? null);
            return $this->success($result, $result['message']);
        } catch (\Exception $e) {
            Log::error('打印销售单失败', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage());
        }
    }
}
