<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_name',
        'phone',
        'address',
        'shipping_type',
        'payment_method',
        'transaction_number',
        'invoice_number',
        'purchase_amount',
        'status',
    ];


    /*
     * you've defined the relationships between them correctly.
The Order model has a hasMany relationship with the OrderItem model, indicating that an order can have multiple order items. Conversely, the OrderItem model has a belongsTo relationship with the Order model, indicating that an order item belongs to a single order.
Your $fillable properties are also correctly defined in both models, which specifies the fields that are mass assignable, allowing you to use the create method or mass assignment to create new records.
     * */

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
