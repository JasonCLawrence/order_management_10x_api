<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\User;

class AuditLog extends Model
{
    protected $table = "audit_logs";

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
