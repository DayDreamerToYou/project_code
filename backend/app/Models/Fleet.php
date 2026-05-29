<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    protected $table = 'tblFleet';
    protected $primaryKey = 'FleetID';
    public $timestamps = false;

    protected $fillable = [
        'FleetID',
        'FleetName',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
    ];

    public function boats()
    {
        return $this->belongsToMany(Boat::class, 'tblFleetDetail', 'FleetID', 'BoatID', 'FleetID', 'BoatID')
            ->wherePivot('is_del', 0)
            ->where('tblBoat.is_del', 0);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'FleetID', 'FleetID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
