<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Structure extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'structure';

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'structure_type_id',
        'order',
    ];

    public function children()
    {
        return $this->hasMany(Structure::class, 'parent_id')->orderBy('order');
    }

    public function parent()
    {
        return $this->belongsTo(Structure::class, 'parent_id');
    }

    public function structure_type()
    {
        return $this->belongsTo(Structure_Type::class, 'structure_type_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class)->orderBy('order');
    }
}
