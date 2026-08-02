<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Generate a Sanctum API token for the scraper (super_admin).
 *
 * Exists because shared hosting disables shell_exec(), so `php artisan tinker`
 * cannot run there. This command does NOT need shell_exec.
 *
 * Usage:
 *   php artisan scraper:token asapanganbangsa@gmail.com
 *
 * Intended to be run once, then optionally deleted. It is admin-gated by
 * requiring CLI access (you cannot call console commands over HTTP).
 */
class GenerateScraperToken extends Command
{
    protected $signature = 'scraper:token {email} {--name=scraper : Token name}';

    protected $description = 'Issue a Sanctum API token (super_admin) for the Chrome scraper extension.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $tokenName = $this->option('name');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("User not found: {$email}");
            return self::FAILURE;
        }

        // Ensure the user can actually use the scrape endpoint (gated on super_admin).
        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
            $this->info('Assigned role: super_admin');
        }

        $plain = $user->createToken($tokenName)->plainTextToken;

        $this->info('========================================================');
        $this->info(' SANCTUM TOKEN (copy the WHOLE line incl. the "|"):');
        $this->info('========================================================');
        $this->line($plain);
        $this->info('========================================================');
        $this->warn(' This token is shown ONCE. Store it safely.');
        $this->info('========================================================');

        return self::SUCCESS;
    }
}
