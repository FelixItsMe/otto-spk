<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogReport extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['id'];

    /**
     * Get all of the recommendations for the LogReport
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recommendations(): HasMany
    {
        return $this->hasMany(TreatmentRecommendation::class);
    }
}
