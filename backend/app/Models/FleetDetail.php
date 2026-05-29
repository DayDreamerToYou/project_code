<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetDetail extends Model
{
    protected $table = 'tblFleetDetail';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'FleetID',
        'BoatID',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
    ];

    public function fleet()
    {
        return $this->belongsTo(Fleet::class, 'FleetID', 'FleetID');
    }

    public function boat()
    {
        return $this->belongsTo(Boat::class, 'BoatID', 'BoatID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
