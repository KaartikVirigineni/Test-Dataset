<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint_id',
        'method',
        'headers',
        'body',
        'query_params',
        'ip_address',
    ];

    protected $casts = [
        'headers' => 'array',
        'body' => 'array',
        'query_params' => 'array',
    ];

    public function endpoint()
    {
        return $this->belongsTo(Endpoint::class);
    }
}