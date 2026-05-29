<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    protected $table = 'tblSales';
    protected $primaryKey = 'SalesID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'SalesID',
        'SaleDate',
        'CustomerID',
        'PurchaseID',
        'Subtotal',
        'GST',
        'Total',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'SaleDate' => 'date',
        'Subtotal' => 'float',
        'GST' => 'float',
        'Total' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'CustomerID', 'CustID');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'PurchaseID', 'PurchaseID');
    }

    public function details()
    {
        return $this->hasMany(SalesDetail::class, 'SalesID', 'SalesID')->where('is_del', 0);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
