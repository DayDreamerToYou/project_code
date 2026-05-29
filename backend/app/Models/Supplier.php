<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'tblSuppliers';
    protected $primaryKey = 'SupplierID';
    public $timestamps = false;

    protected $fillable = [
        'SupplierName',
        'Address',
        'Person',
        'Phone',
        'Email',
        'GST',
        'QRN',
        'FleetID',
        'Disc',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'Disc' => 'float',
        'GST' => 'float',
    ];

    public function fleet()
    {
        return $this->belongsTo(Fleet::class, 'FleetID', 'FleetID');
    }

    public function boats()
    {
        return $this->belongsToMany(Boat::class, 'tblFleetDetail', 'FleetID', 'BoatID', 'FleetID', 'BoatID')
            ->wherePivot('is_del', 0)
            ->where('tblBoat.is_del', 0);
    }

    public function landings()
    {
        return $this->hasMany(Landing::class, 'SupplierID', 'SupplierID');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'SupplierID', 'SupplierID');
    }

    public function stockPrices()
    {
        return $this->hasMany(SupplierStockPrice::class, 'SupplierID', 'SupplierID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
