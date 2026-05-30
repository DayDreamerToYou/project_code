<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Port;
use App\Models\Stock;
use App\Models\Bin;
use App\Models\Boat;
use App\Models\Unit;
use App\Models\FleetDetail;
use App\Models\SupplierStockPrice;
use App\Models\Customer;
use Illuminate\Http\Request;

class OptionsController extends Controller
{
    public function landingOptions(Request $request)
    {
        $supplierId = $request->input('supplierId');

        if ($supplierId) {
            $boats = Boat::whereHas('fleets', function ($q) use ($supplierId) {
                $q->where('tblFleetDetail.is_del', 0);
                $q->whereHas('suppliers', function ($sq) use ($supplierId) {
                    $sq->where('tblSuppliers.SupplierID', $supplierId)
                       ->where('tblSuppliers.is_del', 0);
                });
            })->notDeleted()->get(['BoatID', 'BoatNo', 'BoatName']);

            return $this->success($boats);
        }

        $suppliers = Supplier::notDeleted()->orderBy('SupplierName')->get(['SupplierID', 'SupplierName', 'FleetID']);
        $ports = Port::notDeleted()->orderBy('Port')->get(['PortID', 'Port']);
        $stocks = Stock::notDeleted()->orderBy('Stock')->get(['StockID', 'Stock', 'Description', 'State', 'Area', 'Price', 'Conversion']);
        $bins = Bin::notDeleted()->orderBy('BinName')->get(['BinID', 'BinName', 'B-Weight']);
        $boats = Boat::notDeleted()->orderBy('BoatName')->get(['BoatID', 'BoatNo', 'BoatName']);
        $units = Unit::notDeleted()->orderBy('SortOrder')->orderBy('UnitID')->get(['UnitID', 'UnitCode', 'UnitName', 'UnitSymbol', 'SortOrder']);

        $supplierBoatMap = $this->buildSupplierBoatMap();
        $priceMap = $this->buildPriceMap();

        return $this->success([
            'suppliers' => $suppliers,
            'ports' => $ports,
            'stocks' => $stocks,
            'bins' => $bins,
            'boats' => $boats,
            'priceMap' => $priceMap,
            'supplierBoatMap' => $supplierBoatMap,
            'units' => $units,
        ]);
    }

    private function buildSupplierBoatMap(): array
    {
        $fleetDetails = FleetDetail::notDeleted()
            ->with(['boat' => function ($q) { $q->notDeleted(); }])
            ->get();

        $supplierFleets = Supplier::notDeleted()
            ->whereNotNull('FleetID')
            ->get(['SupplierID', 'FleetID']);

        $fleetSupplierMap = $supplierFleets->groupBy('FleetID');

        $map = [];
        foreach ($fleetDetails as $fd) {
            if (!$fd->boat) continue;
            $suppliers = $fleetSupplierMap->get($fd->FleetID, collect());
            foreach ($suppliers as $supplier) {
                $map[$supplier->SupplierID][] = [
                    'BoatID' => $fd->boat->BoatID,
                    'BoatNo' => $fd->boat->BoatNo,
                    'BoatName' => $fd->boat->BoatName,
                ];
            }
        }

        return $map;
    }

    private function buildPriceMap(): array
    {
        $prices = SupplierStockPrice::notDeleted()->get();
        $map = [];
        foreach ($prices as $price) {
            $key = $price->SupplierID . '_' . $price->StockID;
            $map[$key] = (float) $price->UnitPrice;
        }
        return $map;
    }

    public function customerOptions(Request $request)
    {
        $customers = Customer::notDeleted()->orderBy('CustomerName')->get(['CustID', 'CustomerName', 'Email', 'Phone']);
        return $this->success([
            'customers' => $customers,
        ]);
    }
}
