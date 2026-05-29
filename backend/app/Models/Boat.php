<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boat extends Model
{
    protected $table = 'tblBoat';
    protected $primaryKey = 'BoatID';
    public $timestamps = false;

    protected $fillable = [
        'BoatID',
        'BoatNo',
        'BoatName',
        'Person',
        'Disc',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'Disc' => 'float',
    ];

    public function fleets()
    {
        return $this->belongsToMany(Fleet::class, 'tblFleetDetail', 'BoatID', 'FleetID', 'BoatID', 'FleetID')
            ->wherePivot('is_del', 0);
    }

    public function landings()
    {
        return $this->hasMany(Landing::class, 'BoatID', 'BoatID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
