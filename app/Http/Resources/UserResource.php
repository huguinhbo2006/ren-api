<?php

namespace App\Http\Resources;

use App\Support\PlanHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'plan' => $this->plan ? new PlanResource($this->plan) : null,
            'plan_expires_at' => $this->plan_expires_at?->toIso8601String(),
            'is_pro' => $this->isPro(),
            'roles' => $this->roles->pluck('name'),
            'usage_summary' => PlanHelper::getUsageSummary($this->resource),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
