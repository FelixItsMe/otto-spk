<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentRecommendation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'log_report_id',
        'metric',
        'recommendation',
    ];
}
