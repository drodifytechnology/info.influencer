<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class packageOrder extends Model
{
    use HasFactory;
     protected $fillable = [
        'user_id',
        'influencer_id',
        'service_id',
        'gateway_id',
        'amount',
        'status',
        'charge',
        'discount_amount',
        'total_amount',
        'payment_status',
        'reason',
        'description',
        'meta',
    ];

    public function service() {
        return $this->belongsTo(Service::class,'service_id');
    }

    public function user() {
        return $this->belongsTo(User::class,'user_id');
    }

    public function influencer() {
        return $this->belongsTo(User::class, 'influencer_id');
    }
    public function gateway() {
        return $this->belongsTo(Gateway::class, 'gateway_id');
    }

    public function review() {
        return $this->hasOne(Review::class);
    }

    protected $casts = [
        'amount' => 'double',
        'charge' => 'double',
        'service_id' => 'integer',
        'total_amount' => 'double',
        'discount_amount' => 'double',
        'meta' => 'json',
        'user_id' => 'integer',
        'gateway_id' => 'integer',
    ];
}
