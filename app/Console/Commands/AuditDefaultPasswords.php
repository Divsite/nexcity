<?php

namespace App\Console\Commands;

use App\Models\Users\User;
use App\Support\DefaultPassword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Finds accounts still on a password nobody chose (`password`, `passworrd`).
 *
 * Reporting first. `--retire-warga` replaces the default on **warga-only**
 * accounts with a random one: they sign in with a WhatsApp code, so nothing
 * is lost. Pengurus accounts are never touched — many have no phone on file,
 * and a random password would lock them out of the web entirely. They are
 * listed so the Ketua can ask each one to change it; the next web sign-in
 * forces the change anyway.
 *
 * Exit code 1 while any account is on a default, so it can sit in a deploy
 * check. Slow by design: one bcrypt check per account per default.
 *
 * See docs/architecture/system-map.md §4.
 */
class AuditDefaultPasswords extends Command
{
    protected $signature = 'users:default-passwords
                            {--retire-warga : Give warga-only accounts a random password}';

    protected $description = 'Report accounts still on a default password';

    public function handle(): int
    {
        $retire = (bool) $this->option('retire-warga');
        $warga = 0;
        $retired = 0;
        $staff = [];

        User::query()->whereNotNull('password')->orderBy('id')->chunkById(200, function ($users) use ($retire, &$warga, &$retired, &$staff) {
            foreach ($users as $user) {
                if (! $this->onDefault($user)) {
                    continue;
                }

                if (! DefaultPassword::belongsToWargaOnly($user)) {
                    $staff[] = [$user->id, $user->name, $user->email, $user->phone_normalized ?? '-'];

                    continue;
                }

                $warga++;

                if ($retire) {
                    $user->forceFill(['password' => Hash::make(Str::random(40))])->save();
                    $retired++;
                }
            }
        });

        $this->info("Warga on a default password: {$warga}" . ($retire ? " (retired: {$retired})" : ''));
        $this->info('Pengurus on a default password: ' . count($staff));

        if ($staff !== []) {
            $this->table(['id', 'name', 'email', 'phone'], $staff);
            $this->line('Each must change it on the web; their next web sign-in requires it.');
        }

        $remaining = ($warga - $retired) + count($staff);

        return $remaining === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function onDefault(User $user): bool
    {
        foreach (DefaultPassword::KNOWN as $default) {
            if (Hash::check($default, $user->password)) {
                return true;
            }
        }

        return false;
    }
}
