<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'tblStock';
    protected $primaryKey = 'StockID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'StockID',
        'Stock',
        'Description',
        'State',
        'Area',
        'Price',
        'Conversion',
        'ScientificName',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'Price' => 'float',
        'Conversion' => 'float',
    ];

    public function landingDetails()
    {
        return $this->hasMany(LandingDetail::class, 'StockID', 'StockID');
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class, 'StockID', 'StockID');
    }

    public function salesDetails()
    {
        return $this->hasMany(SalesDetail::class, 'StockID', 'StockID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
