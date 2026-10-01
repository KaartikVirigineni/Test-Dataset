<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Endpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'endpoint_id',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($endpoint) {
            if (empty($endpoint->endpoint_id)) {
                $endpoint->endpoint_id = Str::random(32);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }
}