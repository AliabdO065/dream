<?php

namespace App\Modules\Forms;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use BelongsToClient;

    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array', 'read_at' => 'datetime'];
}
