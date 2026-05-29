<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Landing;
use App\Models\LandingDetail;
use App\Services\LandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LandingController extends Controller
{
    protected LandingService $service;

    public function __construct(LandingService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $result = $this->service->getList(
            $request->input('page', 1),
            $request->input('pageSize', 10),
            $request->input('search', ''),
            $request->input('field', 'LandingID'),
            $request->input('order', 'desc')
        );

        return $this->paginated($result['data'], $result['pagination']);
    }

    public function show(Request $request, $id)
    {
        $includeDetails = $request->input('includeDetails', 'true') === 'true';
        $landing = $this->service->getById($id, $includeDetails);

        if (!$landing) {
            return $this->error('记录不存在', 404);
        }

        return $this->success($landing);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'LandingDate' => 'required|date',
            'SupplierID' => 'required|integer',
            'PortID' => 'required|integer',
            'BoatID' => 'required|integer',
            'details' => 'required|array|min:1',
            'details.*.StockID' => 'required',
            'details.*.BinID' => 'required|integer',
            'details.*.BinQty' => 'nullable|integer',
            'details.*.L-Weight' => 'nullable|numeric',
            'details.*.ICE' => 'nullable|integer',
            'details.*.Price' => 'nullable|numeric',
            'details.*.WeightUnitID' => 'nullable|integer',
        ]);

        try {
            $result = $this->service->create($validated);
            return $this->success($result, '添加成功', 201);
        } catch (\Exception $e) {
            Log::error('新增到货记录失败', ['error' => $e->getMessage()]);
            return $this->error('添加失败: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'LandingDate' => 'required|date',
            'SupplierID' => 'required|integer',
            'PortID' => 'required|integer',
            'BoatID' => 'required|integer',
            'details' => 'required|array|min:1',
            'details.*.StockID' => 'required',
            'details.*.BinID' => 'required|integer',
            'details.*.BinQty' => 'nullable|integer',
            'details.*.L-Weight' => 'nullable|numeric',
            'details.*.ICE' => 'nullable|integer',
            'details.*.Price' => 'nullable|numeric',
            'details.*.WeightUnitID' => 'nullable|integer',
        ]);

        try {
            $result = $this->service->update($id, $validated);
            return $this->success($result, '更新成功');
        } catch (\Exception $e) {
            Log::error('更新到货记录失败', ['error' => $e->getMessage()]);
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
            Log::error('删除到货记录失败', ['error' => $e->getMessage()]);
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    public function getDetails(Request $request)
    {
        $landingIds = $request->input('landingIds', []);
        if (!is_array($landingIds) || empty($landingIds)) {
            return $this->error('参数错误：缺少landingIds');
        }

        $details = $this->service->getDetails($landingIds);
        return $this->success($details);
    }

    public function updateLWeight(Request $request)
    {
        $validated = $request->validate([
            'LandingID' => 'required|integer',
            'StockID' => 'required|integer',
            'LandedKG' => 'required|numeric',
        ]);

        try {
            $result = $this->service->updateLWeightByStock(
                $validated['LandingID'],
                $validated['StockID'],
                $validated['LandedKG']
            );
            return $this->success($result, '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新失败: ' . $e->getMessage());
        }
    }

    public function generatePurchase(Request $request)
    {
        $validated = $request->validate([
            'LandingID' => 'required|integer',
        ]);

        try {
            $result = $this->service->generatePurchaseFromLanding($validated['LandingID']);
            return $this->success($result, '采购单生成成功');
        } catch (\Exception $e) {
            Log::error('生成采购单失败', ['error' => $e->getMessage()]);
            return $this->error('生成采购单失败: ' . $e->getMessage());
        }
    }
}
