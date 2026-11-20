<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class JourneyExecutionUpdated extends Notification
{
    public const TYPE = 'journey-execution-update';

    public function __construct(public array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return self::TYPE;
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
