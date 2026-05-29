<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $table = 'tblUnits';
    protected $primaryKey = 'UnitID';
    public $timestamps = false;

    protected $fillable = [
        'UnitID',
        'UnitCode',
        'UnitName',
        'UnitSymbol',
        'SortOrder',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
        'SortOrder' => 'integer',
    ];

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
