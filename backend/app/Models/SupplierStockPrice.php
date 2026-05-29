<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierStockPrice extends Model
{
    protected $table = 'tblSupplierStockPrice';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'SupplierID',
        'StockID',
        'UnitPrice',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'UnitPrice' => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'SupplierID', 'SupplierID');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'StockID', 'StockID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
