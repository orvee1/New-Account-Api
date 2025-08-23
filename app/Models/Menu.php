<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $table = "menus";

    public function scopeMainMenu($query)
    {
        return $query->where('parent_id', 0)->orderBy('title');
    }

    public function submenu()
    {
        return $this->hasMany(Menu::class, 'parent_id', 'id');
    }

    public function thirdmenu()
    {
        return $this->hasMany(Menu::class, 'parent_id', 'id');
    }

    public function parent_menu()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }
}
