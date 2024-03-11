<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'method',
        'url',
        'headers',
        'payload',
        'response_code',
        'response_content',
    ];
}
