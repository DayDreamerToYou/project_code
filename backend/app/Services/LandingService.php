<?php

namespace App\Services;

use App\Models\Bin;
use App\Models\Landing;
use App\Models\LandingDetail;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LandingService
{
    public function getList(int $page, int $pageSize, string $search = '', string $sortField = 'LandingID', string $sortOrder = 'desc'): array
    {
        $query = Landing::notDeleted()
            ->with(['supplier', 'port', 'boat']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('supplier', fn($sq) => $sq->where('SupplierName', 'like', "%{$search}%"))
                  ->orWhereHas('port', fn($pq) => $pq->where('Port', 'like', "%{$search}%"))
                  ->orWhere('LandingDate', 'like', "%{$search}%")
                  ->orWhereHas('boat', fn($bq) => $bq->where('BoatName', 'like', "%{$search}%"));
            });
        }

        $allowedSort = [
            'LandingID' => 'tblLanding.LandingID',
            'LandingDate' => 'tblLanding.LandingDate',
            'SupplierName' => 'suppliers.SupplierName',
            'Port' => 'ports.Port',
            'BoatNo' => 'boats.BoatNo',
            'BoatName' => 'boats.BoatName',
        ];

        $sortCol = $allowedSort[$sortField] ?? 'tblLanding.LandingID';
        $sortDir = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        $total = $query->count();
        $data = $query->leftJoin('tblSuppliers as suppliers', 'tblLanding.SupplierID', '=', 'suppliers.SupplierID')
            ->leftJoin('tblPort as ports', 'tblLanding.PortID', '=', 'ports.PortID')
            ->leftJoin('tblBoat as boats', 'tblLanding.BoatID', '=', 'boats.BoatID')
            ->select('tblLanding.*', 'suppliers.SupplierName', 'ports.Port', 'boats.BoatName', 'boats.BoatNo')
            ->orderBy($sortCol, $sortDir)
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get()
            ->map(function ($landing) {
                $landing->detail_count = LandingDetail::where('LandingID', $landing->LandingID)->notDeleted()->count();
                $landing->total_weight = LandingDetail::where('LandingID', $landing->LandingID)->notDeleted()->sum('L-Weight');
                return $landing;
            });

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => ceil($total / $pageSize),
            ],
        ];
    }

    public function getById(int $id, bool $includeDetails = false): ?array
    {
        $landing = Landing::notDeleted()
            ->with(['supplier', 'port', 'boat'])
            ->find($id);

        if (!$landing) {
            return null;
        }

        if ($includeDetails) {
            $landing->load(['details.stock', 'details.bin', 'details.weightUnit']);
        }

        // 扁平化关联数据，兼容前端
        $data = $landing->toArray();
        if (isset($data['supplier'])) {
            $data['SupplierName'] = $data['supplier']['SupplierName'] ?? null;
            unset($data['supplier']);
        }
        if (isset($data['port'])) {
            $data['PortName'] = $data['port']['Port'] ?? null;
            unset($data['port']);
        }
        if (isset($data['boat'])) {
            $data['BoatName'] = $data['boat']['BoatName'] ?? null;
            $data['BoatNo'] = $data['boat']['BoatNo'] ?? null;
            unset($data['boat']);
        }

        // 扁平化 details 中的 stock/bin/weightUnit
        if (isset($data['details'])) {
            foreach ($data['details'] as &$detail) {
                if (isset($detail['stock'])) {
                    $detail['Stock'] = $detail['stock']['Stock'] ?? null;
                    $detail['Description'] = $detail['stock']['Description'] ?? null;
                    $detail['State'] = $detail['stock']['State'] ?? null;
                    $detail['Area'] = $detail['stock']['Area'] ?? null;
                    unset($detail['stock']);
                }
                if (isset($detail['bin'])) {
                    $detail['BinName'] = $detail['bin']['BinName'] ?? null;
                    unset($detail['bin']);
                }
                if (isset($detail['weight_unit'])) {
                    $detail['WeightUnit'] = $detail['weight_unit']['UnitName'] ?? null;
                    $detail['WeightSymbol'] = $detail['weight_unit']['UnitSymbol'] ?? null;
                    unset($detail['weight_unit']);
                }
            }
            unset($detail);
        }

        return $data;
    }

    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $landingDate = $data['LandingDate'];
            if (strlen($landingDate) <= 10) {
                $landingDate .= ' 00:00:00';
            }

            $landing = Landing::create([
                'LandingDate' => $landingDate,
                'SupplierID' => $data['SupplierID'],
                'PortID' => $data['PortID'],
                'BoatID' => $data['BoatID'],
            ]);

            $detailCount = 0;
            foreach ($data['details'] as $detail) {
                if (empty($detail['StockID']) || empty($detail['BinID'])) {
                    continue;
                }
                LandingDetail::create([
                    'LandingID' => $landing->LandingID,
                    'StockID' => $detail['StockID'],
                    'BinID' => $detail['BinID'],
                    'BinQty' => $detail['BinQty'] ?? 1,
                    'L-Weight' => isset($detail['L-Weight']) && $detail['L-Weight'] !== '' ? $detail['L-Weight'] : null,
                    'ICE' => $detail['ICE'] ?? 0,
                    'Price' => $detail['Price'] ?? 0,
                    'WeightUnitID' => $detail['WeightUnitID'] ?? 1,
                ]);
                $detailCount++;
            }

            if ($detailCount === 0) {
                throw new \Exception('请至少添加一条明细记录');
            }

            $autoGeneratedPurchase = false;
            $purchaseData = null;
            try {
                $purchaseData = $this->generatePurchaseFromLanding($landing);
                $autoGeneratedPurchase = true;
            } catch (\Exception $e) {
                Log::warning('自动生成Purchase失败', ['error' => $e->getMessage()]);
            }

            $result = [
                'LandingID' => $landing->LandingID,
                'autoGeneratedPurchase' => $autoGeneratedPurchase,
            ];

            if ($autoGeneratedPurchase && $purchaseData) {
                $result['PurchaseID'] = $purchaseData['PurchaseID'];
                $result['PurchaseData'] = $purchaseData;
            }

            return $result;
        });
    }

    public function update(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $landing = Landing::notDeleted()->find($id);
            if (!$landing) {
                throw new \Exception('记录不存在');
            }

            $landingDate = $data['LandingDate'];
            if (strlen($landingDate) <= 10) {
                $landingDate .= ' 00:00:00';
            }

            $landing->update([
                'LandingDate' => $landingDate,
                'SupplierID' => $data['SupplierID'],
                'PortID' => $data['PortID'],
                'BoatID' => $data['BoatID'],
            ]);

            LandingDetail::where('LandingID', $id)->update(['is_del' => 1]);

            foreach ($data['details'] as $detail) {
                if (empty($detail['StockID']) || empty($detail['BinID'])) {
                    continue;
                }
                LandingDetail::create([
                    'LandingID' => $id,
                    'StockID' => $detail['StockID'],
                    'BinID' => $detail['BinID'],
                    'BinQty' => $detail['BinQty'] ?? 1,
                    'L-Weight' => isset($detail['L-Weight']) && $detail['L-Weight'] !== '' ? $detail['L-Weight'] : null,
                    'ICE' => $detail['ICE'] ?? 0,
                    'Price' => $detail['Price'] ?? 0,
                    'WeightUnitID' => $detail['WeightUnitID'] ?? 1,
                ]);
            }

            $purchaseUpdated = false;
            $purchaseData = null;
            try {
                $purchaseData = $this->updatePurchaseFromLanding($id);
                $purchaseUpdated = true;
            } catch (\Exception $e) {
                Log::warning('同步更新Purchase失败', ['error' => $e->getMessage()]);
            }

            $result = [
                'LandingID' => $id,
                'purchaseUpdated' => $purchaseUpdated,
            ];

            if ($purchaseUpdated && $purchaseData) {
                $result['PurchaseID'] = $purchaseData['PurchaseID'];
                $result['PurchaseData'] = $purchaseData;
            }

            return $result;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            LandingDetail::where('LandingID', $id)->update(['is_del' => 1]);
            Landing::where('LandingID', $id)->update(['is_del' => 1]);

            $purchaseIds = Purchase::where('LandingID', $id)->notDeleted()->pluck('PurchaseID');
            if ($purchaseIds->isNotEmpty()) {
                $salesIds = \App\Models\Sales::whereIn('PurchaseID', $purchaseIds)->notDeleted()->pluck('SalesID');
                if ($salesIds->isNotEmpty()) {
                    \App\Models\SalesDetail::whereIn('SalesID', $salesIds)->update(['is_del' => 1]);
                    \App\Models\Sales::whereIn('SalesID', $salesIds)->update(['is_del' => 1]);
                }
                PurchaseDetail::whereIn('PurchaseID', $purchaseIds)->update(['is_del' => 1]);
                Purchase::whereIn('PurchaseID', $purchaseIds)->update(['is_del' => 1]);
            }
        });
    }

    public function getDetails(array $landingIds): array
    {
        return LandingDetail::whereIn('LandingID', $landingIds)
            ->notDeleted()
            ->with(['stock', 'bin', 'weightUnit'])
            ->orderBy('ID')
            ->get()
            ->toArray();
    }

    public function updateLWeight(int $detailId, float $lWeight): void
    {
        LandingDetail::where('ID', $detailId)->update(['L-Weight' => $lWeight]);
    }

    public function updateLWeightByStock(int $landingId, string $stockId, float $landedKG): array
    {
        $detail = LandingDetail::where('LandingID', $landingId)
            ->where('StockID', $stockId)
            ->notDeleted()
            ->first();

        if (!$detail) {
            throw new \Exception('未找到对应的到货明细记录');
        }

        $binWeight = 0;
        if ($detail->BinID) {
            $bin = Bin::find($detail->BinID);
            if ($bin) {
                $binWeight = ($bin->{'B-Weight'} ?? 0) * ($detail->BinQty ?? 1);
            }
        }

        $lWeight = $landedKG + $binWeight;
        $detail->update(['L-Weight' => $lWeight]);

        return ['lWeight' => $lWeight];
    }

    public function generatePurchaseFromLanding($landingOrId): array
    {
        $landing = $landingOrId instanceof Landing ? $landingOrId : Landing::notDeleted()->findOrFail($landingOrId);

        $details = LandingDetail::where('LandingID', $landing->LandingID)
            ->notDeleted()
            ->get();

        $subtotal = 0;
        $purchaseDetails = [];

        foreach ($details as $detail) {
            $lWeight = $detail->{'L-Weight'};
            $price = $detail->Price ?? 0;
            $binQty = $detail->BinQty ?? 1;

            if (empty($lWeight) || $lWeight === '') {
                $landedKG = 0;
                $greenKG = 0;
                $total = 0;
            } else {
                $lWeight = floatval($lWeight);
                $binWeight = 0;
                if ($detail->BinID) {
                    $bin = Bin::find($detail->BinID);
                    if ($bin) {
                        $binWeight = ($bin->{'B-Weight'} ?? 0);
                    }
                }
                $totalBinWeight = $binWeight * $binQty;
                $landedKG = $lWeight - $totalBinWeight;

                $stock = Stock::find($detail->StockID);
                $conversion = $stock ? ($stock->Conversion ?? 1) : 1;
                $greenKG = $landedKG * $conversion;

                $total = $landedKG * $price;
            }

            $subtotal += $total;
            $purchaseDetails[] = [
                'StockID' => $detail->StockID,
                'BinQty' => $detail->BinQty,
                'ICE' => $detail->ICE,
                'GreenKG' => round($greenKG, 3),
                'LandedKG' => round($landedKG, 3),
                'LandedWeightUnitID' => $detail->WeightUnitID ?? 1,
                'GreenWeightUnitID' => $detail->WeightUnitID ?? 1,
                'Price' => round($price, 2),
                'Total' => round($total, 2),
            ];
        }

        $supplier = $landing->supplier;
        $gstRate = ($supplier && $supplier->GST) ? $supplier->GST / 100 : 0;
        $gst = $subtotal * $gstRate;
        $total = $subtotal + $gst;

        // PurchaseID = LandingID + 50000 (per ARCHITECTURE.md)
        $purchaseId = $landing->LandingID + 50000;

        // Check if purchase already exists (including deleted)
        $existingPurchase = Purchase::where('PurchaseID', $purchaseId)->first();
        if ($existingPurchase && $existingPurchase->is_del == 0) {
            throw new \Exception('该到货记录已生成过采购单，PurchaseID: ' . $purchaseId);
        }

        if ($existingPurchase && $existingPurchase->is_del == 1) {
            // Restore deleted purchase
            $existingPurchase->update([
                'PurchaseDate' => $landing->LandingDate,
                'SupplierID' => $landing->SupplierID,
                'LandingID' => $landing->LandingID,
                'PortID' => $landing->PortID,
                'BoatID' => $landing->BoatID,
                'Subtotal' => $subtotal,
                'GST' => $gst,
                'Total' => $total,
                'is_del' => 0,
            ]);
            PurchaseDetail::where('PurchaseID', $purchaseId)->update(['is_del' => 1]);
            foreach ($purchaseDetails as $pd) {
                $pd['PurchaseID'] = $purchaseId;
                PurchaseDetail::create($pd);
            }
            return [
                'PurchaseID' => $purchaseId,
                'LandingID' => $landing->LandingID,
                'PurchaseDate' => $existingPurchase->PurchaseDate,
                'details_count' => count($purchaseDetails),
                'Subtotal' => $subtotal,
                'GST' => $gst,
                'Total' => $total,
            ];
        }

        $purchase = Purchase::create([
            'PurchaseID' => $purchaseId,
            'PurchaseDate' => $landing->LandingDate,
            'SupplierID' => $landing->SupplierID,
            'LandingID' => $landing->LandingID,
            'PortID' => $landing->PortID,
            'BoatID' => $landing->BoatID,
            'Subtotal' => $subtotal,
            'GST' => $gst,
            'Total' => $total,
        ]);

        foreach ($purchaseDetails as $pd) {
            $pd['PurchaseID'] = $purchase->PurchaseID;
            PurchaseDetail::create($pd);
        }

        return [
            'PurchaseID' => $purchase->PurchaseID,
            'LandingID' => $landing->LandingID,
            'PurchaseDate' => $purchase->PurchaseDate,
            'details_count' => count($purchaseDetails),
            'Subtotal' => $subtotal,
            'GST' => $gst,
            'Total' => $total,
        ];
    }

    private function updatePurchaseFromLanding(int $landingId): ?array
    {
        $purchase = Purchase::where('LandingID', $landingId)->notDeleted()->first();
        if (!$purchase) {
            return null;
        }

        $landing = Landing::find($landingId);
        $details = LandingDetail::where('LandingID', $landingId)->notDeleted()->get();

        $subtotal = 0;
        PurchaseDetail::where('PurchaseID', $purchase->PurchaseID)->update(['is_del' => 1]);

        foreach ($details as $detail) {
            $lWeight = $detail->{'L-Weight'};
            $price = $detail->Price ?? 0;
            $binQty = $detail->BinQty ?? 1;

            if (empty($lWeight) || $lWeight === '') {
                $landedKG = 0;
                $greenKG = 0;
                $total = 0;
            } else {
                $lWeight = floatval($lWeight);
                $binWeight = 0;
                if ($detail->BinID) {
                    $bin = Bin::find($detail->BinID);
                    if ($bin) {
                        $binWeight = ($bin->{'B-Weight'} ?? 0);
                    }
                }
                $totalBinWeight = $binWeight * $binQty;
                $landedKG = $lWeight - $totalBinWeight;

                $stock = Stock::find($detail->StockID);
                $conversion = $stock ? ($stock->Conversion ?? 1) : 1;
                $greenKG = $landedKG * $conversion;

                $total = $landedKG * $price;
            }

            $subtotal += $total;
            PurchaseDetail::create([
                'PurchaseID' => $purchase->PurchaseID,
                'StockID' => $detail->StockID,
                'BinQty' => $detail->BinQty,
                'ICE' => $detail->ICE,
                'GreenKG' => round($greenKG, 3),
                'LandedKG' => round($landedKG, 3),
                'LandedWeightUnitID' => $detail->WeightUnitID ?? 1,
                'GreenWeightUnitID' => $detail->WeightUnitID ?? 1,
                'Price' => round($price, 2),
                'Total' => round($total, 2),
            ]);
        }

        $supplier = $landing->supplier;
        $gstRate = ($supplier && $supplier->GST) ? $supplier->GST / 100 : 0;
        $gst = $subtotal * $gstRate;
        $total = $subtotal + $gst;

        $purchase->update([
            'PurchaseDate' => $landing->LandingDate,
            'SupplierID' => $landing->SupplierID,
            'PortID' => $landing->PortID,
            'BoatID' => $landing->BoatID,
            'Subtotal' => $subtotal,
            'GST' => $gst,
            'Total' => $total,
        ]);

        return [
            'PurchaseID' => $purchase->PurchaseID,
            'Subtotal' => $subtotal,
            'GST' => $gst,
            'Total' => $total,
        ];
    }
}
