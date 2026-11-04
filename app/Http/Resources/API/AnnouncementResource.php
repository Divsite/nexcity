<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Announcements\Announcement
 */
class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,

            'category' => [
                'slug' => $this->category?->slug,
                'name' => $this->category?->name,
                'icon' => $this->category?->icon,
                // The app decides how loudly to present it from this, rather
                // than keeping its own list of which categories are urgent.
                'is_urgent' => (bool) $this->category?->is_urgent,
            ],

            'organization' => [
                'id' => $this->organization?->id,
                'slug' => $this->organization?->slug,
                'name' => $this->organization?->name,
                'type' => $this->organization?->type,
            ],

            'audience' => $this->audience,
            'is_pinned' => $this->is_pinned,

            // Null for announcements that are not about a gathering. The app
            // shows a "when and where" block only when both are present.
            'event_at' => $this->event_at?->toIso8601String(),
            'event_location' => $this->event_location,

            'published_at' => $this->published_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),

            'cover_url' => $this->cover_path ? asset('storage/' . $this->cover_path) : null,

            // What this announcement is about, when it is about something the
            // app can open. Type is the short class name so the client is not
            // matching on a PHP namespace.
            'link' => $this->announceable_type ? [
                'type' => class_basename((string) $this->announceable_type),
                'id' => $this->announceable_id,
            ] : null,

            'author' => $this->author?->name,
        ];
    }
}
