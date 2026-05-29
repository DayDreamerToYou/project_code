<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'tblPurchase';
    protected $primaryKey = 'PurchaseID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'PurchaseID',
        'PurchaseDate',
        'SupplierID',
        'LandingID',
        'PortID',
        'BoatID',
        'Subtotal',
        'GST',
        'Total',
        'EmailSent',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'PurchaseDate' => 'date',
        'Subtotal' => 'float',
        'GST' => 'float',
        'Total' => 'float',
        'EmailSent' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'SupplierID', 'SupplierID');
    }

    public function landing()
    {
        return $this->belongsTo(Landing::class, 'LandingID', 'LandingID');
    }

    public function port()
    {
        return $this->belongsTo(Port::class, 'PortID', 'PortID');
    }

    public function boat()
    {
        return $this->belongsTo(Boat::class, 'BoatID', 'BoatID');
    }

    public function details()
    {
        return $this->hasMany(PurchaseDetail::class, 'PurchaseID', 'PurchaseID')->where('is_del', 0);
    }

    public function sales()
    {
        return $this->hasMany(Sales::class, 'PurchaseID', 'PurchaseID')->where('is_del', 0);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
