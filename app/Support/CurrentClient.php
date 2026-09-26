<?php

namespace App\Support;

use App\Models\Client;

/**
 * Holds the tenant for the current request. Set by the manage middleware;
 * once set, every model using BelongsToClient is automatically scoped to it.
 */
class CurrentClient
{
    private ?Client $client = null;

    public function set(?Client $client): void
    {
        $this->client = $client;
    }

    public function get(): ?Client
    {
        return $this->client;
    }

    public function has(): bool
    {
        return $this->client !== null;
    }

    public function id(): ?int
    {
        return $this->client?->id;
    }
}
