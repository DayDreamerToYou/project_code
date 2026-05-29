<?php

namespace App\Services;

use App\Models\Bin;
use App\Models\LandingDetail;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sales;
use App\Models\SalesDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    public function getList(int $page, int $pageSize, string $search = '', string $sortField = 'PurchaseID', string $sortOrder = 'desc'): array
    {
        $query = Purchase::notDeleted()
            ->with(['supplier', 'boat', 'port']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('supplier', fn($sq) => $sq->where('SupplierName', 'like', "%{$search}%"))
                  ->orWhere('PurchaseDate', 'like', "%{$search}%");
            });
        }

        $allowedSort = [
            'PurchaseID' => 'tblPurchase.PurchaseID',
            'PurchaseDate' => 'tblPurchase.PurchaseDate',
            'SupplierName' => 'suppliers.SupplierName',
            'Subtotal' => 'tblPurchase.Subtotal',
            'GST' => 'tblPurchase.GST',
            'Total' => 'tblPurchase.Total',
        ];

        $sortCol = $allowedSort[$sortField] ?? 'tblPurchase.PurchaseID';
        $sortDir = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        $total = $query->count();
        $data = $query->leftJoin('tblSuppliers as suppliers', 'tblPurchase.SupplierID', '=', 'suppliers.SupplierID')
            ->leftJoin('tblBoat as boats', 'tblPurchase.BoatID', '=', 'boats.BoatID')
            ->leftJoin('tblPort as ports', 'tblPurchase.PortID', '=', 'ports.PortID')
            ->select('tblPurchase.*', 'suppliers.SupplierName', 'boats.BoatName', 'boats.BoatNo', 'ports.Port')
            ->orderBy($sortCol, $sortDir)
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get()
            ->map(function ($purchase) {
                $purchase->detail_count = PurchaseDetail::where('PurchaseID', $purchase->PurchaseID)->notDeleted()->count();
                $purchase->total_green_kg = PurchaseDetail::where('PurchaseID', $purchase->PurchaseID)->notDeleted()->sum('GreenKG');
                $purchase->total_landed_kg = PurchaseDetail::where('PurchaseID', $purchase->PurchaseID)->notDeleted()->sum('LandedKG');
                return $purchase;
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
        $purchase = Purchase::notDeleted()
            ->with(['supplier', 'boat', 'port'])
            ->find($id);

        if (!$purchase) {
            return null;
        }

        if ($includeDetails) {
            $purchase->load(['details.stock']);
        }

        // 扁平化关联数据，兼容前端
        $data = $purchase->toArray();
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

        // 扁平化 details 中的 stock
        if (isset($data['details'])) {
            foreach ($data['details'] as &$detail) {
                if (isset($detail['stock'])) {
                    $detail['Stock'] = $detail['stock']['Stock'] ?? null;
                    $detail['Description'] = $detail['stock']['Description'] ?? null;
                    $detail['State'] = $detail['stock']['State'] ?? null;
                    $detail['Area'] = $detail['stock']['Area'] ?? null;
                    unset($detail['stock']);
                }
            }
            unset($detail);
        }

        return $data;
    }

    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $purchase = Purchase::create([
                'PurchaseDate' => $data['PurchaseDate'],
                'SupplierID' => $data['SupplierID'],
                'PortID' => $data['PortID'] ?? null,
                'BoatID' => $data['BoatID'] ?? null,
                'Subtotal' => $data['Subtotal'] ?? 0,
                'GST' => $data['GST'] ?? 0,
                'Total' => $data['Total'] ?? 0,
            ]);

            $detailCount = 0;
            foreach ($data['details'] as $detail) {
                if (empty($detail['StockID'])) {
                    continue;
                }
                PurchaseDetail::create([
                    'PurchaseID' => $purchase->PurchaseID,
                    'StockID' => $detail['StockID'],
                    'BinQty' => $detail['BinQty'] ?? null,
                    'UnloadingDocket' => $detail['UnloadingDocket'] ?? null,
                    'ICE' => $detail['ICE'] ?? 0,
                    'GreenKG' => $detail['GreenKG'] ?? 0,
                    'LandedKG' => $detail['LandedKG'] ?? 0,
                    'LandedWeightUnitID' => $detail['LandedWeightUnitID'] ?? 1,
                    'GreenWeightUnitID' => $detail['GreenWeightUnitID'] ?? 1,
                    'Price' => $detail['Price'] ?? 0,
                    'Total' => $detail['Total'] ?? 0,
                ]);
                $detailCount++;
            }

            if ($detailCount === 0) {
                throw new \Exception('请至少添加一条明细记录');
            }

            return ['PurchaseID' => $purchase->PurchaseID];
        });
    }

    public function update(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $purchase = Purchase::notDeleted()->find($id);
            if (!$purchase) {
                throw new \Exception('记录不存在');
            }

            $purchase->update([
                'PurchaseDate' => $data['PurchaseDate'],
                'SupplierID' => $data['SupplierID'],
                'PortID' => $data['PortID'] ?? null,
                'BoatID' => $data['BoatID'] ?? null,
                'Subtotal' => $data['Subtotal'] ?? 0,
                'GST' => $data['GST'] ?? 0,
                'Total' => $data['Total'] ?? 0,
            ]);

            PurchaseDetail::where('PurchaseID', $id)->update(['is_del' => 1]);

            foreach ($data['details'] as $detail) {
                if (empty($detail['StockID'])) {
                    continue;
                }
                PurchaseDetail::create([
                    'PurchaseID' => $id,
                    'StockID' => $detail['StockID'],
                    'BinQty' => $detail['BinQty'] ?? null,
                    'UnloadingDocket' => $detail['UnloadingDocket'] ?? null,
                    'ICE' => $detail['ICE'] ?? 0,
                    'GreenKG' => $detail['GreenKG'] ?? 0,
                    'LandedKG' => $detail['LandedKG'] ?? 0,
                    'LandedWeightUnitID' => $detail['LandedWeightUnitID'] ?? 1,
                    'GreenWeightUnitID' => $detail['GreenWeightUnitID'] ?? 1,
                    'Price' => $detail['Price'] ?? 0,
                    'Total' => $detail['Total'] ?? 0,
                ]);

                if ($purchase->LandingID && isset($detail['LandedKG'])) {
                    $this->syncLandingLWeight($purchase->LandingID, $detail['StockID'], $detail['LandedKG']);
                }
            }

            return ['PurchaseID' => $id];
        });
    }

    private function syncLandingLWeight(int $landingId, string $stockId, float $landedKG): void
    {
        $detail = LandingDetail::where('LandingID', $landingId)
            ->where('StockID', $stockId)
            ->notDeleted()
            ->first();

        if (!$detail) {
            return;
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
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $salesIds = Sales::where('PurchaseID', $id)->notDeleted()->pluck('SalesID');
            if ($salesIds->isNotEmpty()) {
                SalesDetail::whereIn('SalesID', $salesIds)->update(['is_del' => 1]);
                Sales::whereIn('SalesID', $salesIds)->update(['is_del' => 1]);
            }

            PurchaseDetail::where('PurchaseID', $id)->update(['is_del' => 1]);
            Purchase::where('PurchaseID', $id)->update(['is_del' => 1]);
        });
    }

    public function getDetails(array $purchaseIds): array
    {
        return PurchaseDetail::whereIn('PurchaseID', $purchaseIds)
            ->notDeleted()
            ->with(['stock', 'purchase'])
            ->orderBy('PurchaseID')
            ->orderBy('ID')
            ->get()
            ->toArray();
    }

    public function updateEmailSent(int $purchaseId, int $emailSent = 1): void
    {
        Purchase::where('PurchaseID', $purchaseId)->update(['EmailSent' => $emailSent]);
    }

    public function generateSalesFromPurchase(int $purchaseId): array
    {
        $purchase = Purchase::notDeleted()->with(['supplier'])->find($purchaseId);
        if (!$purchase) {
            throw new \Exception('采购记录不存在');
        }

        // 检查是否已生成销售单
        $existingSales = Sales::where('PurchaseID', $purchaseId)->notDeleted()->first();
        if ($existingSales) {
            throw new \Exception('该采购记录已生成销售单，请勿重复生成');
        }

        return DB::transaction(function () use ($purchase, $purchaseId) {
            $details = PurchaseDetail::where('PurchaseID', $purchaseId)->notDeleted()->get();

            $subtotal = 0;
            $salesDetails = [];

            foreach ($details as $detail) {
                $nWeight = floatval($detail->LandedKG ?? 0);
                $weightUnitId = intval($detail->LandedWeightUnitID ?? 1);
                $price = floatval($detail->Price ?? 0);
                $binQty = intval($detail->BinQty ?? 1);

                // Get BinID and B-Weight from LandingDetail
                $binId = null;
                $bWeight = 0;
                if ($purchase->LandingID) {
                    $landingDetail = LandingDetail::where('LandingID', $purchase->LandingID)
                        ->where('StockID', $detail->StockID)
                        ->notDeleted()
                        ->first();
                    if ($landingDetail && $landingDetail->BinID) {
                        $binId = $landingDetail->BinID;
                        $bin = Bin::find($binId);
                        if ($bin) {
                            $bWeight = floatval($bin->{'B-Weight'} ?? 0);
                        }
                    }
                }

                $gWeight = $nWeight + ($binQty * $bWeight);
                $amount = $nWeight * $price;

                $salesDetails[] = [
                    'StockID' => $detail->StockID,
                    'BinID' => $binId,
                    'BinQty' => $binQty,
                    'G-Weight' => round($gWeight, 3),
                    'N-Weight' => round($nWeight, 3),
                    'WeightUnitID' => $weightUnitId,
                    'Price' => round($price, 2),
                    'Amount' => round($amount, 2),
                ];

                $subtotal += $amount;
            }

            // SalesID = PurchaseID + 20000 (per old version logic)
            $salesId = $purchaseId + 20000;

            $gst = floatval($purchase->GST ?? 0);
            $total = $subtotal + $gst;

            $sales = Sales::create([
                'SalesID' => $salesId,
                'SaleDate' => $purchase->PurchaseDate,
                'PurchaseID' => $purchaseId,
                'CustomerID' => 1,
                'Subtotal' => $subtotal,
                'GST' => $gst,
                'Total' => $total,
            ]);

            foreach ($salesDetails as $sd) {
                $sd['SalesID'] = $sales->SalesID;
                SalesDetail::create($sd);
            }

            return [
                'SalesID' => $sales->SalesID,
                'PurchaseID' => $purchaseId,
                'SaleDate' => $sales->SaleDate,
                'details_count' => count($salesDetails),
                'Subtotal' => $subtotal,
                'GST' => $gst,
                'Total' => $total,
            ];
        });
    }
}
