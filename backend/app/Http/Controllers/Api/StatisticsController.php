<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function monthly(Request $request)
    {
        $month = $request->input('month', date('Y-m'));
        $supplierIds = $request->input('supplierIds', '');
        $stockNames = $request->input('stockIds', '');

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->error('月份格式不正确，应为 YYYY-MM');
        }

        $query = DB::table('tblPurchase as p')
            ->join('tblSuppliers as s', function ($join) {
                $join->on('p.SupplierID', '=', 's.SupplierID')
                    ->where('s.is_del', 0);
            })
            ->join('tblPurchaseDetail as pd', function ($join) {
                $join->on('p.PurchaseID', '=', 'pd.PurchaseID')
                    ->where('pd.is_del', 0);
            })
            ->join('tblStock as st', function ($join) {
                $join->on('pd.StockID', '=', 'st.StockID')
                    ->where('st.is_del', 0);
            })
            ->whereRaw("DATE_FORMAT(p.PurchaseDate, '%Y-%m') = ?", [$month])
            ->where('p.is_del', 0)
            ->select(
                'pd.ID',
                's.SupplierID',
                's.SupplierName',
                's.QRN',
                'st.Stock as StockName',
                'st.Description',
                'p.PurchaseDate',
                'pd.GreenKG',
                'pd.Price',
                'pd.Total',
                'p.PurchaseID'
            )
            ->orderBy('s.SupplierName')
            ->orderBy('pd.ID');

        if (!empty($supplierIds)) {
            $ids = explode(',', $supplierIds);
            $query->whereIn('p.SupplierID', $ids);
        }

        if (!empty($stockNames)) {
            $names = explode(',', $stockNames);
            $query->whereIn('st.Stock', $names);
        }

        $statistics = $query->get()->map(function ($row) {
            return [
                'ID' => $row->ID,
                'SupplierID' => $row->SupplierID,
                'SupplierName' => $row->SupplierName,
                'QRN' => $row->QRN,
                'StockName' => $row->StockName,
                'Description' => $row->Description,
                'PurchaseDate' => $row->PurchaseDate,
                'GreenKG' => (float) $row->GreenKG,
                'Price' => (float) $row->Price,
                'Total' => (float) $row->Total,
                'PurchaseID' => $row->PurchaseID,
            ];
        })->toArray();

        $totalGreenWeight = array_sum(array_column($statistics, 'GreenKG'));
        $totalAmount = array_sum(array_column($statistics, 'Total'));

        $supplierCount = DB::table('tblSuppliers')->where('is_del', 0)->distinct()->count('SupplierID');
        $stockCount = DB::table('tblStock')->where('is_del', 0)->distinct()->count('StockID');

        return $this->success([
            'statistics' => $statistics,
            'summary' => [
                'total_green_weight' => round($totalGreenWeight, 2),
                'total_amount' => round($totalAmount, 2),
            ],
            'filters' => [
                'month' => $month,
                'supplierIds' => $supplierIds,
                'stockIds' => $stockNames,
            ],
            'options' => [
                'supplier_count' => $supplierCount,
                'stock_count' => $stockCount,
            ],
        ]);
    }
}
