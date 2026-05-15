<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * ----------------------------------------------------------------
     * TABLE
     * ----------------------------------------------------------------
     */

    protected $table = 'users';

    /**
     * ----------------------------------------------------------------
     * MASS ASSIGNABLE
     * ----------------------------------------------------------------
     */

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * ----------------------------------------------------------------
     * HIDDEN
     * ----------------------------------------------------------------
     */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * ----------------------------------------------------------------
     * CASTS
     * ----------------------------------------------------------------
     */

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * ----------------------------------------------------------------
     * PROJECTS OWNED
     * ----------------------------------------------------------------
     */

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /**
     * ----------------------------------------------------------------
     * PROJECT MEMBERSHIPS
     * ----------------------------------------------------------------
     */

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * ----------------------------------------------------------------
     * TASKS ASSIGNED
     * ----------------------------------------------------------------
     */

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * ----------------------------------------------------------------
     * TASKS CREATED
     * ----------------------------------------------------------------
     */

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * ----------------------------------------------------------------
     * TASK COMMENTS
     * ----------------------------------------------------------------
     */

    public function taskComments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * ----------------------------------------------------------------
     * TASK ATTACHMENTS
     * ----------------------------------------------------------------
     */

    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'uploaded_by');
    }

    /**
     * ----------------------------------------------------------------
     * TASK TIME LOGS
     * ----------------------------------------------------------------
     */

    public function taskTimeLogs(): HasMany
    {
        return $this->hasMany(TaskTimeLog::class);
    }

    /**
     * ----------------------------------------------------------------
     * TICKETS CREATED
     * ----------------------------------------------------------------
     */

    public function ticketsCreated(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    /**
     * ----------------------------------------------------------------
     * TICKETS ASSIGNED
     * ----------------------------------------------------------------
     */

    public function ticketsAssigned(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * ----------------------------------------------------------------
     * USER METRICS
     * ----------------------------------------------------------------
     */

    public function metrics(): HasOne
    {
        return $this->hasOne(UserMetric::class);
    }

    /**
     * ----------------------------------------------------------------
     * ACTIVITY LOGS
     * ----------------------------------------------------------------
     */

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * ----------------------------------------------------------------
     * HELPER METHODS
     * ----------------------------------------------------------------
     */

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isProjectManager(): bool
    {
        return $this->hasRole('project-manager');
    }

    public function isDeveloper(): bool
    {
        return $this->hasRole('developer');
    }

    public function isQa(): bool
    {
        return $this->hasRole('qa');
    }

    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    /**
     * ----------------------------------------------------------------
     * ACCESSORS
     * ----------------------------------------------------------------
     */

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * ----------------------------------------------------------------
     * SCOPES
     * ----------------------------------------------------------------
     */

    public function scopeDevelopers($query)
    {
        return $query->role('developer');
    }

    public function scopeManagers($query)
    {
        return $query->role('project-manager');
    }

    public function scopeClients($query)
    {
        return $query->role('client');
    }
}
