<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status',
    ];

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class);
    }

    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }

    public function oeeReports(): HasMany
    {
        return $this->hasMany(OeeReport::class);
    }
}
