<?php

namespace App\Support;

/**
 * Who Neekah is on its own invoices and receipts: the name, registration and
 * tax numbers, and where to reach it. Set under Admin → Tetapan → Invois &
 * resit. Anything left blank falls back to the contact details, so a receipt
 * never goes out without a way back to us.
 */
class InvoiceSettings extends SettingGroup
{
    public function __construct(private ContactSettings $contact) {}

    public function companyName(): string
    {
        return $this->string('company_name') ?: (string) config('app.name');
    }

    public function registrationNo(): string
    {
        return $this->string('registration_no');
    }

    public function taxNo(): string
    {
        return $this->string('tax_no');
    }

    public function address(): string
    {
        return $this->string('address') ?: $this->contact->address();
    }

    public function email(): string
    {
        return $this->string('email') ?: $this->contact->email();
    }

    public function phone(): string
    {
        return $this->string('phone') ?: $this->contact->phone();
    }

    /** A line at the foot of every Neekah invoice and receipt, in the reader's language. */
    public function note(): string
    {
        return $this->localised('note');
    }

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'company_name' => '',
            'registration_no' => '',
            'tax_no' => '',
            'address' => '',
            'email' => '',
            'phone' => '',
            ...array_fill_keys(array_values(self::localisedKeys('note')), ''),
        ];
    }

    protected static function prefix(): string
    {
        return 'invoice';
    }
}
