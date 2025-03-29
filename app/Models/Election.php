<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status', 'start_time', 'end_time', 'positions'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'positions' => 'array', // Ensure positions are handled as an array
    ];

    // Define relationship with candidates
    public function candidates()
    {
        return $this->hasMany(Candidate::class, 'election_id');
    }
}
