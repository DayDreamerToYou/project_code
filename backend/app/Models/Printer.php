<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    protected $table = 'tblPrinters';
    public $timestamps = true;

    protected $fillable = [
        'printer_name',
        'printer_ip',
        'printer_port',
        'printer_type',
        'is_default',
        'status',
        'description',
    ];

    protected $casts = [
        'printer_port' => 'integer',
        'is_default' => 'boolean',
        'status' => 'boolean',
    ];
}
