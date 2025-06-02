<?php

namespace App\Http\Resources\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'users_id'   => $this->users_id,
            'image_url'  => $this->image_url,
            'content'    => $this->content,
            'caption'    => $this->caption,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user'       => new UserResource($this->whenLoaded('user')),
        ];
    }
}
