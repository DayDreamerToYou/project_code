<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    protected PurchaseService $service;

    public function __construct(PurchaseService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $result = $this->service->getList(
            $request->input('page', 1),
            $request->input('pageSize', 10),
            $request->input('search', ''),
            $request->input('field', 'PurchaseID'),
            $request->input('order', 'desc')
        );

        return $this->paginated($result['data'], $result['pagination']);
    }

    public function show(Request $request, $id)
    {
        $includeDetails = $request->input('includeDetails', 'true') === 'true';
        $purchase = $this->service->getById($id, $includeDetails);

        if (!$purchase) {
            return $this->error('记录不存在', 404);
        }

        return $this->success($purchase);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'PurchaseDate' => 'required|date',
            'SupplierID' => 'required|integer',
            'PortID' => 'nullable|integer',
            'BoatID' => 'nullable|integer',
            'Subtotal' => 'nullable|numeric',
            'GST' => 'nullable|numeric',
            'Total' => 'nullable|numeric',
            'details' => 'required|array|min:1',
            'details.*.StockID' => 'required',
            'details.*.BinQty' => 'nullable|integer',
            'details.*.UnloadingDocket' => 'nullable|integer',
            'details.*.ICE' => 'nullable|integer',
            'details.*.GreenKG' => 'nullable|numeric',
            'details.*.LandedKG' => 'nullable|numeric',
            'details.*.LandedWeightUnitID' => 'nullable|integer',
            'details.*.GreenWeightUnitID' => 'nullable|integer',
            'details.*.Price' => 'nullable|numeric',
            'details.*.Total' => 'nullable|numeric',
        ]);

        try {
            $result = $this->service->create($validated);
            return $this->success($result, '添加成功', 201);
        } catch (\Exception $e) {
            Log::error('新增采购记录失败', ['error' => $e->getMessage()]);
            return $this->error('添加失败: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'PurchaseDate' => 'required|date',
            'SupplierID' => 'required|integer',
            'PortID' => 'nullable|integer',
            'BoatID' => 'nullable|integer',
            'Subtotal' => 'nullable|numeric',
            'GST' => 'nullable|numeric',
            'Total' => 'nullable|numeric',
            'details' => 'required|array|min:1',
            'details.*.StockID' => 'required',
            'details.*.BinQty' => 'nullable|integer',
            'details.*.UnloadingDocket' => 'nullable|integer',
            'details.*.ICE' => 'nullable|integer',
            'details.*.GreenKG' => 'nullable|numeric',
            'details.*.LandedKG' => 'nullable|numeric',
            'details.*.LandedWeightUnitID' => 'nullable|integer',
            'details.*.GreenWeightUnitID' => 'nullable|integer',
            'details.*.Price' => 'nullable|numeric',
            'details.*.Total' => 'nullable|numeric',
        ]);

        try {
            $result = $this->service->update($id, $validated);
            return $this->success($result, '更新成功');
        } catch (\Exception $e) {
            Log::error('更新采购记录失败', ['error' => $e->getMessage()]);
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
            return $this->success(null, '删除成功（已级联删除关联的销售记录）');
        } catch (\Exception $e) {
            Log::error('删除采购记录失败', ['error' => $e->getMessage()]);
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    public function getDetails(Request $request)
    {
        $purchaseIds = $request->input('purchaseIds', []);
        if (!is_array($purchaseIds) || empty($purchaseIds)) {
            return $this->error('参数错误：缺少purchaseIds');
        }

        $details = $this->service->getDetails($purchaseIds);
        return $this->success($details);
    }

    public function generateSales(Request $request)
    {
        $validated = $request->validate([
            'PurchaseID' => 'required|integer',
        ]);

        try {
            $result = $this->service->generateSalesFromPurchase($validated['PurchaseID']);
            return $this->success($result, '销售单生成成功');
        } catch (\Exception $e) {
            Log::error('生成销售单失败', ['error' => $e->getMessage()]);
            return $this->error('生成销售单失败: ' . $e->getMessage());
        }
    }
}
