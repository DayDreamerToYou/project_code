<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingDetail extends Model
{
    protected $table = 'tblLandingDetail';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'LandingID',
        'StockID',
        'BinID',
        'BinQty',
        'L-Weight',
        'ICE',
        'Price',
        'WeightUnitID',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'BinQty' => 'integer',
        'L-Weight' => 'float',
        'ICE' => 'integer',
        'Price' => 'float',
    ];

    public function landing()
    {
        return $this->belongsTo(Landing::class, 'LandingID', 'LandingID');
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
