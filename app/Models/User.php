<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\PhoneNumber;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'google_id', 'avatar_url', 'deactivated_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Impersonate, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'deactivated_at' => 'datetime',
        ];
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    /**
     * Every wedding this user is part of, whether they created it or were invited.
     */
    public function weddings(): BelongsToMany
    {
        return $this->belongsToMany(Wedding::class, 'wedding_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function createdWeddings(): HasMany
    {
        return $this->hasMany(Wedding::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isVendor(): bool
    {
        return $this->role === UserRole::Vendor;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    /**
     * Someone who only ever signed in with Google has no password to confirm.
     */
    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * A couple's account may switch to a vendor account only while it has
     * done nothing as a couple — someone who signed up on the wrong side.
     * Anything with a wedding, booking, enquiry or review goes through admin.
     */
    public function canBecomeVendor(): bool
    {
        return $this->isCustomer()
            && ! $this->weddings()->exists()
            && ! $this->createdWeddings()->exists()
            && ! $this->bookings()->exists()
            && ! $this->enquiries()->exists()
            && ! $this->reviews()->exists();
    }

    /**
     * Only admins impersonate, so support can reproduce a complaint.
     */
    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Admins are never impersonated, so one admin cannot borrow another's access.
     * A deactivated account would be signed straight back out, so it is not offered.
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->isAdmin() && ! $this->isDeactivated();
    }

    /**
     * A wa.me link for the number this user signed up with, if they gave one.
     */
    public function whatsappUrl(): ?string
    {
        $number = PhoneNumber::normalise($this->phone);

        return $number ? 'https://wa.me/'.$number : null;
    }

    /**
     * The dashboard this user lands on after signing in.
     */
    public function homeRoute(): string
    {
        return match (true) {
            $this->isAdmin() => route('admin.dashboard'),
            $this->isVendor() => route('vendor.dashboard'),
            default => route('dashboard'),
        };
    }
}
