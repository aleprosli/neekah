<?php

namespace App\Console\Commands;

use App\Actions\PlanThreadsPosts as PlanPosts;
use App\Support\ThreadsSettings;
use Illuminate\Console\Command;

class PlanThreadsPosts extends Command
{
    protected $signature = 'neekah:threads-plan';

    protected $description = 'Fill the empty Threads slots of the coming days with vendor listings, blog shares and posts from the template bank';

    public function handle(PlanPosts $plan, ThreadsSettings $settings): int
    {
        if (! $settings->isEnabled()) {
            $this->components->info('Threads auto-posting is off or not connected; nothing planned.');

            return self::SUCCESS;
        }

        $this->components->info($plan->handle().' Threads post(s) planned.');

        return self::SUCCESS;
    }
}
