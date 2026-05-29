<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'tblCustomer';
    protected $primaryKey = 'CustID';
    public $timestamps = false;

    protected $fillable = [
        'CustomerName',
        'Address',
        'Phone',
        'Email',
        'is_del',
    ];

    protected $casts = [
        'is_del' => 'boolean',
    ];

    public function sales()
    {
        return $this->hasMany(Sales::class, 'CustomerID', 'CustID');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where($this->getTable().'.is_del', 0);
    }
}
