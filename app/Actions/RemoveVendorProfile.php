<?php

namespace App\Actions;

use App\Models\Vendor;

class RemoveVendorProfile
{
    public function __construct(private StoreOptimizedImage $images) {}

    /**
     * Delete a vendor profile and the photos it uploaded. The database cascades
     * the rows; the files on disk have to be removed here.
     */
    public function handle(Vendor $vendor): void
    {
        $this->images->delete($vendor->cover_image);
        $vendor->packages()->pluck('image')->each(fn (?string $path) => $this->images->delete($path));
        $vendor->portfolioItems()->pluck('path')->each(fn (?string $path) => $this->images->delete($path));

        $vendor->delete();
    }
}
