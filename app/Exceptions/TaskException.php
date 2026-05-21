<?php

namespace App\Exceptions;

use App\Enums\TaskStatus;
use Symfony\Component\HttpFoundation\Response;

class TaskException extends DomainException
{
    public static function notFound(int $id): static
    {
        return new static("Tarea #{$id} no encontrada.", Response::HTTP_NOT_FOUND);
    }

    public static function accessDenied(): static
    {
        return new static('No tienes acceso a esta tarea.', Response::HTTP_FORBIDDEN);
    }

    public static function notEditableWhenDone(): static
    {
        return new static('Una tarea completada no puede modificarse.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function invalidStatusTransition(TaskStatus $from, TaskStatus $to): static
    {
        return new static(
            "Transición de estado inválida: {$from->label()} → {$to->label()}.",
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    public static function notAssignedToUser(): static
    {
        return new static('Solo puedes modificar tareas asignadas a ti.', Response::HTTP_FORBIDDEN);
    }

    public static function doesNotBelongToProject(): static
    {
        return new static('La tarea no pertenece al proyecto indicado.', Response::HTTP_NOT_FOUND);
    }
}
