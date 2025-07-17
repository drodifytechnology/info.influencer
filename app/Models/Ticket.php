<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'category_id', 'order_id', 'title',
        'description', 'file_path', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }   
    public function category()
    {
        return $this->belongsTo(Category::class , 'category_id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class , 'order_id');
    }
}
