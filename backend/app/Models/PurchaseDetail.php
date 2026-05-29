<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    protected $table = 'tblPurchaseDetail';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'PurchaseID',
        'StockID',
        'BinQty',
        'UnloadingDocket',
        'ICE',
        'GreenKG',
        'LandedKG',
        'LandedWeightUnitID',
        'GreenWeightUnitID',
        'Price',
        'Total',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'BinQty' => 'integer',
        'UnloadingDocket' => 'integer',
        'ICE' => 'integer',
        'GreenKG' => 'float',
        'LandedKG' => 'float',
        'Price' => 'float',
        'Total' => 'float',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'PurchaseID', 'PurchaseID');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'StockID', 'StockID');
    }

    public function landedWeightUnit()
    {
        return $this->belongsTo(Unit::class, 'LandedWeightUnitID', 'UnitID');
    }

    public function greenWeightUnit()
    {
        return $this->belongsTo(Unit::class, 'GreenWeightUnitID', 'UnitID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
