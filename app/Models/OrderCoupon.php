<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCoupon extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'order_id',
        'service_id',
        'title',
        'code',
        'status',
        'amount'
        
    ];
    public function service() {
        return $this->belongsTo(Service::class,'service_id');
    }

    public function user() {
        return $this->belongsTo(User::class,'user_id');
    }
    public function order() {
        return $this->belongsTo(packageOrder::class,'order_id');
    }


}
