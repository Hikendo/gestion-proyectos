<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/auth.php';
    require __DIR__ . '/api/users.php';
    require __DIR__ . '/api/roles.php';
    require __DIR__ . '/api/dashboard.php';
    require __DIR__ . '/api/projects.php';
    require __DIR__ . '/api/members.php';
    require __DIR__ . '/api/phases.php';
    require __DIR__ . '/api/criteria.php';
    require __DIR__ . '/api/plans.php';
    require __DIR__ . '/api/objectives.php';
    require __DIR__ . '/api/milestones.php';
    require __DIR__ . '/api/deliverables.php';
    require __DIR__ . '/api/tasks.php';
    require __DIR__ . '/api/tickets.php';
    require __DIR__ . '/api/risks.php';
    require __DIR__ . '/api/blockers.php';
    require __DIR__ . '/api/reports.php';
    require __DIR__ . '/api/notifications.php';
    require __DIR__ . '/api/attachments.php';
});
