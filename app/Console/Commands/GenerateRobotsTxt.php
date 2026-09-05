<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateRobotsTxt extends Command
{
    protected $signature = 'neekah:robots';

    protected $description = 'Write public/robots.txt, stamping in this deployment\'s sitemap address';

    /**
     * robots.txt has to be a real file. The standard Laravel nginx config,
     * Herd included, answers "location = /robots.txt" itself and never passes
     * the request to PHP, so a route for it would silently never run.
     *
     * Run this on deploy, after APP_URL is set.
     */
    public function handle(): int
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Search results and token links are not content of ours.',
            'Disallow: /compare',
            'Disallow: /invitations/',
            '',
            '# Invitation cards sit on their own subdomains and are served this',
            '# same file. They stay crawlable on purpose, so the noindex tag on',
            '# each card is actually read.',
            '',
            'Sitemap: '.route('sitemap.index'),
        ];

        file_put_contents(public_path('robots.txt'), implode("\n", $lines)."\n");

        $this->components->info('robots.txt written, pointing at '.route('sitemap.index'));

        return self::SUCCESS;
    }
}
