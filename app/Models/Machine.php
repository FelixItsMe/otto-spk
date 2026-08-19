<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status',
    ];
    
    /**
     * Get the average associated with the Machine
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function average(): HasOne
    {
        return $this->hasOne(MachineAverage::class);
    }
}
