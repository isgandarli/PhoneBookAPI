<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'first_name',
        'last_name',
        'father_name',
        'email',
        'landline_number',
        'mobile_number',
        'description',
        'order',
        'position_id',
        'structure_id',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function structure()
    {
        return $this->belongsTo(Structure::class);
    }
}
