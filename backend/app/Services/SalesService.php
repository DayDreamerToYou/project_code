<?php

namespace App\Services;

use App\Models\Sales;
use App\Models\SalesDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesService
{
    public function getList(int $page, int $pageSize, string $search = '', string $sortField = 'SalesID', string $sortOrder = 'desc'): array
    {
        $query = Sales::notDeleted()
            ->with(['customer']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn($cq) => $cq->where('CustomerName', 'like', "%{$search}%"))
                  ->orWhere('SaleDate', 'like', "%{$search}%");
            });
        }

        $allowedSort = [
            'SalesID' => 'tblSales.SalesID',
            'SaleDate' => 'tblSales.SaleDate',
            'CustomerName' => 'customers.CustomerName',
            'Subtotal' => 'tblSales.Subtotal',
            'GST' => 'tblSales.GST',
            'Total' => 'tblSales.Total',
        ];

        $sortCol = $allowedSort[$sortField] ?? 'tblSales.SalesID';
        $sortDir = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        $total = $query->count();
        $data = $query->leftJoin('tblCustomer as customers', 'tblSales.CustomerID', '=', 'customers.CustID')
            ->select('tblSales.*', 'customers.CustomerName')
            ->orderBy($sortCol, $sortDir)
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get()
            ->map(function ($sales) {
                $sales->detail_count = SalesDetail::where('SalesID', $sales->SalesID)->notDeleted()->count();
                $sales->total_g_weight = SalesDetail::where('SalesID', $sales->SalesID)->notDeleted()->sum('G-Weight');
                $sales->total_n_weight = SalesDetail::where('SalesID', $sales->SalesID)->notDeleted()->sum('N-Weight');
                return $sales;
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
        $sales = Sales::notDeleted()
            ->with(['customer'])
            ->find($id);

        if (!$sales) {
            return null;
        }

        if ($includeDetails) {
            $sales->load(['details.stock', 'details.weightUnit']);
        }

        // 扁平化关联数据，兼容前端
        $data = $sales->toArray();
        if (isset($data['customer'])) {
            $data['CustomerName'] = $data['customer']['CustomerName'] ?? null;
            unset($data['customer']);
        }

        // 扁平化 details 中的 stock/weightUnit
        if (isset($data['details'])) {
            foreach ($data['details'] as &$detail) {
                if (isset($detail['stock'])) {
                    $detail['Stock'] = $detail['stock']['Stock'] ?? null;
                    $detail['Description'] = $detail['stock']['Description'] ?? null;
                    $detail['State'] = $detail['stock']['State'] ?? null;
                    $detail['Area'] = $detail['stock']['Area'] ?? null;
                    unset($detail['stock']);
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
            $sales = Sales::create([
                'SaleDate' => $data['SaleDate'],
                'CustomerID' => $data['CustomerID'],
                'PurchaseID' => $data['PurchaseID'] ?? null,
                'Subtotal' => $data['Subtotal'] ?? 0,
                'GST' => $data['GST'] ?? 0,
                'Total' => $data['Total'] ?? 0,
            ]);

            $detailCount = 0;
            foreach ($data['details'] as $detail) {
                if (empty($detail['StockID'])) {
                    continue;
                }
                SalesDetail::create([
                    'SalesID' => $sales->SalesID,
                    'StockID' => $detail['StockID'],
                    'BinQty' => $detail['BinQty'] ?? 0,
                    'G-Weight' => $detail['G-Weight'] ?? 0,
                    'N-Weight' => $detail['N-Weight'] ?? 0,
                    'WeightUnitID' => $detail['WeightUnitID'] ?? 1,
                    'Price' => $detail['Price'] ?? 0,
                    'Amount' => $detail['Amount'] ?? 0,
                    'BinID' => $detail['BinID'] ?? null,
                ]);
                $detailCount++;
            }

            if ($detailCount === 0) {
                throw new \Exception('请至少添加一条明细记录');
            }

            return ['SalesID' => $sales->SalesID];
        });
    }

    public function update(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $sales = Sales::notDeleted()->find($id);
            if (!$sales) {
                throw new \Exception('记录不存在');
            }

            $sales->update([
                'SaleDate' => $data['SaleDate'],
                'CustomerID' => $data['CustomerID'],
                'Subtotal' => $data['Subtotal'] ?? 0,
                'GST' => $data['GST'] ?? 0,
                'Total' => $data['Total'] ?? 0,
            ]);

            SalesDetail::where('SalesID', $id)->update(['is_del' => 1]);

            foreach ($data['details'] as $detail) {
                if (empty($detail['StockID'])) {
                    continue;
                }
                SalesDetail::create([
                    'SalesID' => $id,
                    'StockID' => $detail['StockID'],
                    'BinQty' => $detail['BinQty'] ?? 0,
                    'G-Weight' => $detail['G-Weight'] ?? 0,
                    'N-Weight' => $detail['N-Weight'] ?? 0,
                    'WeightUnitID' => $detail['WeightUnitID'] ?? 1,
                    'Price' => $detail['Price'] ?? 0,
                    'Amount' => $detail['Amount'] ?? 0,
                    'BinID' => $detail['BinID'] ?? null,
                ]);
            }

            return ['SalesID' => $id];
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            SalesDetail::where('SalesID', $id)->update(['is_del' => 1]);
            Sales::where('SalesID', $id)->update(['is_del' => 1]);
        });
    }

    public function getDetails(array $salesIds): array
    {
        return SalesDetail::whereIn('SalesID', $salesIds)
            ->notDeleted()
            ->with(['stock', 'weightUnit'])
            ->orderBy('SalesID')
            ->orderBy('ID')
            ->get()
            ->toArray();
    }
}
