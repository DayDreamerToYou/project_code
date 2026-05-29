<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bin extends Model
{
    protected $table = 'tblBin';
    protected $primaryKey = 'BinID';
    public $timestamps = false;

    protected $fillable = [
        'BinID',
        'BinName',
        'B-Weight',
        'Disc',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'B-Weight' => 'float',
        'Disc' => 'float',
    ];

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
