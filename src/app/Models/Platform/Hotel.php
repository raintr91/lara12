<?php

namespace App\Models\Platform;

use App\Models\PlatformModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends PlatformModel
{
    public function chain(): BelongsTo
    {
        return $this->belongsTo(PlatformModel::class, 'chain_id');
    }

    public function managers(): HasMany
    {
        return $this->hasMany(PlatformModel::class, 'hotel_id');
    }
}
