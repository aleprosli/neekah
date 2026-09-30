<?php

namespace App\Support;

/**
 * Whether Herepay takes payments. The keys themselves live in .env, never in
 * the database; this is only the switch an admin flips under Admin → Tetapan →
 * Gateway bayaran, and saving it on is refused while a key is missing.
 */
class HerepaySettings extends SettingGroup
{
    public function isEnabled(): bool
    {
        return (bool) $this->value('enabled');
    }

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
        ];
    }

    protected static function prefix(): string
    {
        return 'herepay';
    }
}
