<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = collect($this->images_json ?? [])->map(function ($path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            return url(Storage::url($path));
        })->toArray();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'category' => $this->category ? new AssetCategoryResource($this->category) : null,
            'name' => $this->name,
            'description' => $this->description,
            'serial_number' => $this->serial_number,
            'daily_rate_cents' => $this->daily_rate_cents,
            'weekly_rate_cents' => $this->weekly_rate_cents,
            'monthly_rate_cents' => $this->monthly_rate_cents,
            'deposit_cents' => $this->deposit_cents,
            'initial_investment_cents' => $this->initial_investment_cents ?? 0,
            'status' => $this->status,
            'location' => $this->location,
            'notes' => $this->notes,
            'images' => $images,
            'primary_image' => $images[0] ?? null,
            'is_available' => $this->isAvailable(),
            'rentals_count' => $this->whenCounted('rentals'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
