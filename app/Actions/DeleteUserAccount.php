<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteUserAccount
{
    public function __construct(
        private RemoveVendorProfile $removeVendorProfile,
        private StoreOptimizedImage $images,
    ) {}

    /**
     * Delete an account an admin was allowed to delete (UserPolicy::delete),
     * along with the photos it uploaded to its vendor profile or wedding cards.
     */
    public function handle(User $user, User $admin): void
    {
        DB::transaction(function () use ($user): void {
            if ($user->vendor) {
                $this->removeVendorProfile->handle($user->vendor);
            }

            $user->createdWeddings()->with('site.photos')->get()->each(function (Wedding $wedding): void {
                $this->images->delete($wedding->site?->cover_image);
                $this->images->delete($wedding->site?->gift_qr_image);
                $wedding->site?->photos->each(fn ($photo) => $this->images->delete($photo->path));
            });

            $user->delete();
        });

        Log::warning('Admin deleted an account', [
            'admin_id' => $admin->id,
            'target_id' => $user->id,
            'target_email' => $user->email,
            'target_role' => $user->role->value,
        ]);
    }
}
