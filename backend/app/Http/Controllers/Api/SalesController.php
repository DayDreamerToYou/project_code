<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    protected SalesService $service;

    public function __construct(SalesService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $result = $this->service->getList(
            $request->input('page', 1),
            $request->input('pageSize', 10),
            $request->input('search', ''),
            $request->input('field', 'SalesID'),
            $request->input('order', 'desc')
        );

        return $this->paginated($result['data'], $result['pagination']);
    }

    public function show(Request $request, $id)
    {
        $includeDetails = $request->input('includeDetails', 'true') === 'true';
        $sales = $this->service->getById($id, $includeDetails);

        if (!$sales) {
            return $this->error('记录不存在', 404);
        }

        return $this->success($sales);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'SaleDate' => 'required|date',
            'CustomerID' => 'required|integer',
            'PurchaseID' => 'nullable|integer',
            'Subtotal' => 'nullable|numeric',
            'GST' => 'nullable|numeric',
            'Total' => 'nullable|numeric',
            'details' => 'required|array|min:1',
            'details.*.StockID' => 'required',
            'details.*.BinQty' => 'nullable|integer',
            'details.*.G-Weight' => 'nullable|numeric',
            'details.*.N-Weight' => 'nullable|numeric',
            'details.*.WeightUnitID' => 'nullable|integer',
            'details.*.Price' => 'nullable|numeric',
            'details.*.Amount' => 'nullable|numeric',
            'details.*.BinID' => 'nullable|integer',
        ]);

        try {
            $result = $this->service->create($validated);
            return $this->success($result, '添加成功', 201);
        } catch (\Exception $e) {
            Log::error('新增销售记录失败', ['error' => $e->getMessage()]);
            return $this->error('添加失败: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'SaleDate' => 'required|date',
            'CustomerID' => 'required|integer',
            'Subtotal' => 'nullable|numeric',
            'GST' => 'nullable|numeric',
            'Total' => 'nullable|numeric',
            'details' => 'required|array|min:1',
            'details.*.StockID' => 'required',
            'details.*.BinQty' => 'nullable|integer',
            'details.*.G-Weight' => 'nullable|numeric',
            'details.*.N-Weight' => 'nullable|numeric',
            'details.*.WeightUnitID' => 'nullable|integer',
            'details.*.Price' => 'nullable|numeric',
            'details.*.Amount' => 'nullable|numeric',
            'details.*.BinID' => 'nullable|integer',
        ]);

        try {
            $result = $this->service->update($id, $validated);
            return $this->success($result, '更新成功');
        } catch (\Exception $e) {
            Log::error('更新销售记录失败', ['error' => $e->getMessage()]);
            return $this->error('更新失败: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (!$id) {
            return $this->error('记录ID不能为空');
        }

        try {
            $this->service->delete((int)$id);
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            Log::error('删除销售记录失败', ['error' => $e->getMessage()]);
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    public function getDetails(Request $request)
    {
        $salesIds = $request->input('salesIds', []);
        if (!is_array($salesIds) || empty($salesIds)) {
            return $this->error('参数错误：缺少salesIds');
        }

        $details = $this->service->getDetails($salesIds);
        return $this->success($details);
    }
}
