<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'prd_title',
        'quantity',
        'price',
        'discount_price',
        'type_product',
        'prdSizeList',
        'single_image',
        'multiple_images',
    ];
}
