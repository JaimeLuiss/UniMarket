<?php

namespace App\Notifications;

use App\Models\EntrepreneurProfile;
use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EntrepreneurUpdateNotification extends Notification
{
    use Queueable;

    public const TYPE_NEW_LISTING = 'new_listing';

    public const TYPE_RESTOCKED = 'listing_restocked';

    public function __construct(
        private readonly EntrepreneurProfile $profile,
        private readonly Listing $listing,
        private readonly string $eventType,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->eventType,
            'entrepreneur_profile_id' => $this->profile->id,
            'entrepreneur_name' => $this->profile->business_name,
            'entrepreneur_slug' => $this->profile->slug,
            'listing_id' => $this->listing->id,
            'listing_title' => $this->listing->title,
        ];
    }
}
