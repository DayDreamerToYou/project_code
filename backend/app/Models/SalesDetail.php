<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesDetail extends Model
{
    protected $table = 'tblSalesDetail';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'SalesID',
        'StockID',
        'BinQty',
        'G-Weight',
        'N-Weight',
        'WeightUnitID',
        'Price',
        'Amount',
        'BinID',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'BinQty' => 'integer',
        'G-Weight' => 'float',
        'N-Weight' => 'float',
        'Price' => 'float',
        'Amount' => 'float',
    ];

    public function sales()
    {
        return $this->belongsTo(Sales::class, 'SalesID', 'SalesID');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'StockID', 'StockID');
    }

    public function bin()
    {
        return $this->belongsTo(Bin::class, 'BinID', 'BinID');
    }

    public function weightUnit()
    {
        return $this->belongsTo(Unit::class, 'WeightUnitID', 'UnitID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
