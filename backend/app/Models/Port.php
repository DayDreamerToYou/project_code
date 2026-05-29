<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    protected $table = 'tblPort';
    protected $primaryKey = 'PortID';
    public $timestamps = false;

    protected $fillable = [
        'PortID',
        'Port',
        'Disc',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'Disc' => 'float',
    ];

    public function landings()
    {
        return $this->hasMany(Landing::class, 'PortID', 'PortID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
