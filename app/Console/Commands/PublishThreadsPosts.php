<?php

namespace App\Console\Commands;

use App\Actions\PublishThreadsPost;
use App\Enums\ThreadsPostStatus;
use App\Models\ThreadsPost;
use App\Support\ThreadsSettings;
use Illuminate\Console\Command;

class PublishThreadsPosts extends Command
{
    protected $signature = 'neekah:threads-publish';

    protected $description = 'Send the Threads posts whose time has come, one step a minute';

    /**
     * A post this late is not sent. After the server was down for a morning,
     * four posts going out in four minutes would look like spam; the slot is
     * marked missed and the admin can send it again by hand.
     */
    public const MISSED_AFTER_MINUTES = 120;

    public function handle(PublishThreadsPost $publish, ThreadsSettings $settings): int
    {
        if (! $settings->isEnabled()) {
            return self::SUCCESS;
        }

        $missed = ThreadsPost::query()
            ->scheduled()
            ->where('scheduled_at', '<', now()->subMinutes(self::MISSED_AFTER_MINUTES))
            ->update(['status' => ThreadsPostStatus::Missed, 'updated_at' => now()]);

        // Containers waiting on Threads first, then one new post: one a
        // minute, so posts due together are not dumped on the feed at once.
        ThreadsPost::query()
            ->where('status', ThreadsPostStatus::Processing)
            ->orderBy('scheduled_at')
            ->get()
            ->each(fn (ThreadsPost $post) => $publish->handle($post));

        $due = ThreadsPost::query()->scheduled()->where('scheduled_at', '<=', now())->orderBy('scheduled_at')->first();

        if ($due) {
            $publish->handle($due);
        }

        if ($missed || $due) {
            $this->components->info(($due ? "Post {$due->id} moved on. " : '')."{$missed} post(s) missed.");
        }

        return self::SUCCESS;
    }
}
