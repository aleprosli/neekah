<?php

namespace App\Support;

/**
 * How visitors reach Neekah itself: the numbers, the address and the social
 * accounts shown in the footer and used in "hubungi kami" links. Set under
 * Admin → Tetapan so nobody has to redeploy to change a phone number.
 */
class ContactSettings extends SettingGroup
{
    public function phone(): string
    {
        return $this->string('phone');
    }

    public function whatsapp(): string
    {
        return $this->string('whatsapp');
    }

    public function email(): string
    {
        return $this->string('email');
    }

    public function address(): string
    {
        return $this->string('address');
    }

    public function hours(): string
    {
        return $this->string('hours');
    }

    /**
     * The number in wa.me form: digits only, with the country code. A message
     * is pre-filled when given, so someone asking for help does not open an
     * empty chat and have to work out what to say.
     */
    public function whatsappUrl(?string $message = null): ?string
    {
        $number = PhoneNumber::normalise($this->whatsapp() ?: $this->phone());

        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }

    public function telUrl(): ?string
    {
        return $this->phone() ? 'tel:'.preg_replace('/[^0-9+]/', '', $this->phone()) : null;
    }

    /**
     * One sentence telling someone how to reach us, using whatever the admin
     * has filled in. Falls back to the site address, so an email never ends
     * without a way back to us.
     */
    public function supportSentence(): string
    {
        $channels = array_filter([$this->email() ?: null, $this->phone() ?: null, $this->whatsappUrl()]);

        return $channels === []
            ? 'Ada sebarang pertanyaan? Hubungi kami melalui '.config('app.url').'.'
            : 'Ada sebarang pertanyaan? Hubungi kami di '.implode(' · ', $channels).'.';
    }

    /**
     * Every social account that has been filled in, ready to render.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function socialLinks(): array
    {
        $labels = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'];

        return collect($labels)
            ->map(fn (string $label, string $key): array => ['label' => $label, 'url' => $this->string($key)])
            ->filter(fn (array $link): bool => $link['url'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'phone' => '',
            'whatsapp' => '',
            'email' => (string) config('mail.from.address'),
            'address' => '',
            'hours' => '',
            'facebook' => '',
            'instagram' => '',
            'tiktok' => '',
        ];
    }

    protected static function prefix(): string
    {
        return 'contact';
    }
}
