<?php

namespace App\Support;

/**
 * The Telegram chat admins watch for new signups. Configured under
 * Admin → Tetapan; alerts are silently skipped until both the bot token and
 * the chat id are filled in, so nothing ever fails a registration.
 */
class TelegramSettings extends SettingGroup
{
    public function botToken(): string
    {
        return $this->string('bot_token');
    }

    public function chatId(): string
    {
        return $this->string('chat_id');
    }

    public function isEnabled(): bool
    {
        return (bool) $this->value('enabled') && $this->botToken() !== '' && $this->chatId() !== '';
    }

    public function sendMessageUrl(): string
    {
        return 'https://api.telegram.org/bot'.$this->botToken().'/sendMessage';
    }

    /**
     * @return array<string, string|bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => (bool) config('services.telegram.bot_token'),
            'bot_token' => (string) config('services.telegram.bot_token'),
            'chat_id' => (string) config('services.telegram.chat_id'),
        ];
    }

    protected static function prefix(): string
    {
        return 'telegram';
    }
}
