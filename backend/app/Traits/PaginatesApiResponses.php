<?php

namespace App\Traits;

use App\Models\DirectMessage;
use App\Models\ProjectMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Estandariza la respuesta de colecciones paginadas al contrato que consume
 * el frontend (`PaginatedResponse<T>` de http.ts): { data, links, meta }.
 *
 * Evita exponer el paginador crudo de Laravel ({ current_page, data, last_page }),
 * que dejaba `response.meta` como undefined en el cliente.
 */
trait PaginatesApiResponses
{
    /**
     * @param  callable(mixed): array<string, mixed>  $transform
     * @return array{data: array<int, array<string, mixed>>, links: array<string, string|null>, meta: array<string, mixed>}
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, callable $transform): array
    {
        return [
            'data'  => collect($paginator->items())->map($transform)->values()->all(),
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'from'         => $paginator->firstItem(),
                'last_page'    => $paginator->lastPage(),
                'path'         => $paginator->path(),
                'per_page'     => $paginator->perPage(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
            ],
        ];
    }

    /**
     * Transforma un mensaje de grupo al shape plano que espera el chat.
     *
     * @return array<string, mixed>
     */
    protected function transformGroupMessage(ProjectMessage $message): array
    {
        return [
            'id'         => $message->id,
            'project_id' => $message->project_id,
            'user_id'    => $message->user_id,
            'user_name'  => $message->user?->name,
            'content'    => $message->content,
            'created_at' => optional($message->created_at)->toISOString(),
        ];
    }

    /**
     * Transforma un mensaje directo al shape plano que espera el chat.
     *
     * @return array<string, mixed>
     */
    protected function transformDirectMessage(DirectMessage $message): array
    {
        return [
            'id'              => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id'         => $message->user_id,
            'user_name'       => $message->user?->name,
            'content'         => $message->content,
            'created_at'      => optional($message->created_at)->toISOString(),
        ];
    }
}
