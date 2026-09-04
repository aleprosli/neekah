<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterVendor
{
    /**
     * Create the owner account and a pending vendor profile.
     *
     * @param  array{name: string, email: string, phone: string, password: string, business_name: string, category_id: int, city: string, state: string, tagline?: string|null}  $data
     */
    public function handle(array $data): Vendor
    {
        return DB::transaction(function () use ($data): Vendor {
            $owner = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => UserRole::Vendor,
            ]);

            return Vendor::create([
                'user_id' => $owner->id,
                'category_id' => $data['category_id'],
                'name' => $data['business_name'],
                'slug' => $this->uniqueSlug($data['business_name']),
                'tagline' => $data['tagline'] ?? null,
                'city' => $data['city'],
                'state' => $data['state'],
                'phone' => $data['phone'],
                'whatsapp' => $data['phone'],
                'status' => VendorStatus::Pending,
                'tier' => VendorTier::New,
            ]);
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Vendor::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
