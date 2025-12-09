<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TallcmsMenu extends Model
{
    protected $table = 'tallcms_menus';
    
    protected $fillable = [
        'name',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TallcmsMenuItem::class, 'menu_id')
                    ->whereIsRoot()
                    ->defaultOrder();
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(TallcmsMenuItem::class, 'menu_id');
    }
    
    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    public static function byLocation(string $location): ?self
    {
        return static::where('location', $location)
                    ->where('is_active', true)
                    ->first();
    }
}
