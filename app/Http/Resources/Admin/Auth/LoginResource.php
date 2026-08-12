<?php

namespace App\Http\Resources\Admin\Auth;

use App\Models\Auth\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс ответа при входе администратора.
 *
 * @mixin Admin
 */
final class LoginResource extends JsonResource
{
    /** @var Admin */
    public $resource;

    private string $token;

    public function __construct(Admin $admin, string $token)
    {
        parent::__construct($admin);
        $this->token = $token;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'admin' => [
                'id' => $this->resource->id,
                'name' => $this->resource->name,
                'email' => $this->resource->email,
                'role' => $this->resource->role?->code,
            ],
        ];
    }
}
