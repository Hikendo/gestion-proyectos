<?php

// app/Models/Risk.php

namespace App\Models;

use App\Enums\RiskImpact;
use App\Enums\RiskProbability;
use App\Enums\RiskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Risk extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'impact',
        'probability',
        'mitigation_plan',
        'status',
    ];
    protected $casts = [
        'impact'      => RiskImpact::class,
        'probability' => RiskProbability::class,
        'status'      => RiskStatus::class,
    ];

    public function criticality(): int
    {
        return $this->probability->criticality($this->impact);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

