<?php

namespace App\Models\Construction;

use Illuminate\Database\Eloquent\Model;

class ConstructionService extends Model
{
    protected $fillable = [
        'tenant_id',
        'service_category_id',
        'name',
        'slug',
        'description',
        'base_price',
        'currency',
        'is_active',
    ];
}
