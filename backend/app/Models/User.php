<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\ProjectMemberRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;

use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use Searchable;

    public function toSearchableArray(): array
    {
        return [
            'name'  => $this->name,
            'email' => $this->email,
        ];
    }

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
        'role_changed_at',
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
            'role_changed_at' => 'datetime',
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

    public function projectMembershipRole(Project $project): ?string
    {
        return $this->projectMemberships()
            ->where('project_id', $project->id)
            ->value('role');
    }

    public function hasProjectRole(Project $project, string|array $roles): bool
    {
        $membershipRole = $this->projectMembershipRole($project);

        return $membershipRole !== null
            && in_array($membershipRole, Arr::wrap($roles), true);
    }

    public function canForProject(Project $project, string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($project->owner_id === $this->id) {
            return true;
        }

        $membershipRole = $this->projectMembershipRole($project);

        if ($membershipRole === null) {
            return false;
        }

        return in_array($permission, ProjectMemberRole::permissionsFor($membershipRole), true);
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
    // Asegúrate de agregar estas líneas dentro de la clase User:

    /**
     * Obtiene todos los tokens FCM registrados del usuario.
     */
    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    /**
     * Obtiene el historial de notificaciones internas del usuario.
     */
    public function customNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * ----------------------------------------------------------------
     * CHAT RELATIONS
     * ----------------------------------------------------------------
     */

    /**
     * Group messages sent by this user.
     */
    public function projectMessages(): HasMany
    {
        return $this->hasMany(ProjectMessage::class);
    }

    /**
     * Direct messages sent by this user.
     */
    public function directMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class);
    }

    /**
     * Conversations where the user is participant (as user_one).
     */
    public function conversationsAsOne(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    /**
     * Conversations where the user is participant (as user_two).
     */
    public function conversationsAsTwo(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
    }
}
