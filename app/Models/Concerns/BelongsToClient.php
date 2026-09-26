<?php

namespace App\Models\Concerns;

use App\Models\Client;
use App\Support\CurrentClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Multi-tenancy for shared-database tables: add this trait to any model that has a client_id.
 * Queries are scoped to the current client automatically, and client_id is filled on create.
 */
trait BelongsToClient
{
    public static function bootBelongsToClient(): void
    {
        static::addGlobalScope('client', function (Builder $query) {
            $current = app(CurrentClient::class);
            if ($current->has()) {
                $query->where($query->getModel()->getTable() . '.client_id', $current->id());
            }
        });

        static::creating(function ($model) {
            $current = app(CurrentClient::class);
            if (! $model->client_id && $current->has()) {
                $model->client_id = $current->id();
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
