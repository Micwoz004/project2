<?php

namespace App\Products\EcoServices\Domain\Notifications\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use BelongsToClient;

    protected $table = 'eco_notification_templates';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
        ];
    }
}
