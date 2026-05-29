<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landing extends Model
{
    protected $table = 'tblLanding';
    protected $primaryKey = 'LandingID';
    public $timestamps = false;

    protected $fillable = [
        'LandingDate',
        'SupplierID',
        'PortID',
        'BoatID',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'LandingDate' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'SupplierID', 'SupplierID');
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
        return $this->hasMany(LandingDetail::class, 'LandingID', 'LandingID')->where('is_del', 0);
    }

    public function purchase()
    {
        return $this->hasOne(Purchase::class, 'LandingID', 'LandingID')->where('is_del', 0);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
