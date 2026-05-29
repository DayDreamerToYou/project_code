<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Port;
use App\Models\Boat;
use App\Models\Stock;
use App\Models\Bin;
use App\Models\Customer;
use App\Models\Unit;
use App\Models\FleetDetail;
use App\Models\Fleet;
use App\Models\SupplierStockPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DataManagementController extends Controller
{
    protected array $tableConfig = [
        'suppliers' => [
            'model' => Supplier::class,
            'idField' => 'SupplierID',
        ],
        'port' => [
            'model' => Port::class,
            'idField' => 'PortID',
        ],
        'boat' => [
            'model' => Boat::class,
            'idField' => 'BoatID',
        ],
        'stock' => [
            'model' => Stock::class,
            'idField' => 'StockID',
        ],
        'bin' => [
            'model' => Bin::class,
            'idField' => 'BinID',
        ],
        'fleet' => [
            'model' => Fleet::class,
            'idField' => 'FleetID',
        ],
        'boat-management' => [
            'model' => Boat::class,
            'idField' => 'BoatID',
        ],
        'price' => [
            'model' => SupplierStockPrice::class,
            'idField' => 'ID',
        ],
        'units' => [
            'model' => Unit::class,
            'idField' => 'UnitID',
        ],
    ];

    public function list(Request $request, string $table)
    {
        if (!isset($this->tableConfig[$table])) {
            return $this->error('无效的表名');
        }

        $model = $this->tableConfig[$table]['model'];
        $query = $model::notDeleted();

        if ($table === 'suppliers') {
            $data = $query->with('fleet')->get();
        } elseif ($table === 'fleet') {
            $data = $query->get()->map(function ($fleet) {
                $fleet->BoatCount = FleetDetail::where('FleetID', $fleet->FleetID)->where('is_del', 0)->count();
                $fleet->SupplierCount = Supplier::where('FleetID', $fleet->FleetID)->where('is_del', 0)->count();
                return $fleet;
            });
        } elseif ($table === 'boat-management') {
            $data = $query->get()->map(function ($boat) {
                $fleetIds = FleetDetail::where('BoatID', $boat->BoatID)->where('is_del', 0)->pluck('FleetID');
                $fleetNames = Fleet::whereIn('FleetID', $fleetIds)->where('is_del', 0)->pluck('FleetName')->toArray();
                $boat->FleetNames = implode(', ', $fleetNames);
                return $boat;
            });
        } elseif ($table === 'price') {
            $data = $query->get()->map(function ($price) {
                $supplier = Supplier::where('SupplierID', $price->SupplierID)->where('is_del', 0)->first();
                $stock = Stock::where('StockID', $price->StockID)->where('is_del', 0)->first();
                $price->SupplierName = $supplier ? $supplier->SupplierName : '-';
                $price->Stock = $stock ? $stock->Stock : '-';
                return $price;
            });
        } else {
            $data = $query->get();
        }

        return $this->success($data);
    }

    public function get(Request $request, string $table, $id)
    {
        if (!isset($this->tableConfig[$table])) {
            return $this->error('无效的表名');
        }

        $model = $this->tableConfig[$table]['model'];
        $idField = $this->tableConfig[$table]['idField'];
        $record = $model::where($idField, $id)->first();

        if (!$record) {
            return $this->error('记录不存在', 404);
        }

        return $this->success($record);
    }

    public function add(Request $request, string $table)
    {
        if (!isset($this->tableConfig[$table])) {
            return $this->error('无效的表名');
        }

        $model = $this->tableConfig[$table]['model'];

        try {
            $record = $model::create($request->all());
            return $this->success($record, '添加成功', 201);
        } catch (\Exception $e) {
            Log::error("添加{$table}失败", ['error' => $e->getMessage()]);
            return $this->error('添加失败: ' . $e->getMessage());
        }
    }

    public function update(Request $request, string $table, $id)
    {
        if (!isset($this->tableConfig[$table])) {
            return $this->error('无效的表名');
        }

        $model = $this->tableConfig[$table]['model'];
        $idField = $this->tableConfig[$table]['idField'];
        $record = $model::where($idField, $id)->first();

        if (!$record) {
            return $this->error('记录不存在', 404);
        }

        try {
            $record->fill($request->all())->save();
            return $this->success($record, '更新成功');
        } catch (\Exception $e) {
            Log::error("更新{$table}失败", ['error' => $e->getMessage()]);
            return $this->error('更新失败: ' . $e->getMessage());
        }
    }

    public function delete(Request $request, string $table, $id)
    {
        if (!isset($this->tableConfig[$table])) {
            return $this->error('无效的表名');
        }

        $model = $this->tableConfig[$table]['model'];
        $idField = $this->tableConfig[$table]['idField'];
        $record = $model::where($idField, $id)->first();

        if (!$record) {
            return $this->error('记录不存在', 404);
        }

        try {
            $record->update(['is_del' => 1]);
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            Log::error("删除{$table}失败", ['error' => $e->getMessage()]);
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    public function fleetBoats(string $fleetId)
    {
        $boatIds = FleetDetail::where('FleetID', $fleetId)->pluck('BoatID');
        $boats = Boat::whereIn('BoatID', $boatIds)->where('is_del', 0)->get();
        return $this->success($boats);
    }

    public function boatFleets(string $boatId)
    {
        $fleetIds = FleetDetail::where('BoatID', $boatId)->pluck('FleetID');
        $fleets = \App\Models\Fleet::whereIn('FleetID', $fleetIds)->where('is_del', 0)->get();
        return $this->success($fleets);
    }
}
