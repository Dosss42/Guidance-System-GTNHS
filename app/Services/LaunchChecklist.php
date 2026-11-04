<?php

namespace App\Services;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pre-launch checks for a production installation. Each check reports
 * "pass", "warn" (should be fixed) or "fail" (must be fixed before real
 * student data is entered).
 */
class LaunchChecklist
{
    public const HEARTBEAT_KEY = 'scheduler:heartbeat';

    public function __construct(private BackupService $backups) {}

    /**
     * @return list<array{area: string, check: string, result: 'pass'|'warn'|'fail', detail: string}>
     */
    public function run(): array
    {
        return [
            $this->check('Application', 'Production environment', app()->environment('production'), 'fail', 'Set APP_ENV=production.'),
            $this->check('Application', 'Debug mode off', ! config('app.debug'), 'fail', 'Set APP_DEBUG=false. Debug pages reveal internal details.'),
            $this->check('Application', 'Application key set', strlen((string) config('app.key')) > 20, 'fail', 'Run php artisan key:generate once, then never change it.'),
            $this->check('Application', 'Site uses HTTPS', Str::startsWith((string) config('app.url'), 'https://'), 'fail', 'Serve the site over HTTPS and set APP_URL=https://….'),
            $this->check('Application', 'Frontend built', is_file(public_path('build/manifest.json')), 'fail', 'Run npm ci && npm run build.'),

            $this->check('Sessions', 'Session data encrypted', (bool) config('session.encrypt'), 'warn', 'Set SESSION_ENCRYPT=true.'),
            $this->check('Sessions', 'Cookies sent over HTTPS only', (bool) config('session.secure'), 'fail', 'Set SESSION_SECURE_COOKIE=true.'),
            $this->check('Sessions', 'Idle sign-out within 60 minutes', (int) config('session.lifetime') <= 60, 'warn', 'Set SESSION_LIFETIME=30 for a shared office.'),

            $this->check('Security', 'Content Security Policy on', (bool) config('security.csp'), 'fail', 'It is off only in local development. Set APP_ENV=production, or SECURITY_CSP=true.'),
            $this->check('Security', 'HSTS on', (bool) config('security.hsts'), 'warn', 'Set SECURITY_HSTS=true once HTTPS is permanent.'),

            $this->databaseUserCheck(),
            $this->check('Database', 'MySQL / MariaDB in use', in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true), 'warn', 'The approved design uses MySQL.'),

            $this->check('Accounts', 'An active System Administrator exists', User::active()->whereRelation('role', 'slug', RoleSlug::Administrator)->exists(), 'fail', 'Run php artisan guidance:create-admin.'),
            $this->check('Accounts', 'No sample accounts', ! User::where('email', 'like', '%@gtnhs.test')->exists(), 'fail', 'Sample @gtnhs.test accounts exist. Use a fresh database for production.'),

            $this->check('Backups', 'Backup password set', $this->backupPasswordOk(), 'fail', 'Set BACKUP_PASSWORD (16+ characters, different from APP_KEY) and store it away from the server.'),
            $this->recentBackupCheck(),
            $this->schedulerCheck(),

            $this->check('Uploads', 'PHP accepts 5 MB uploads', $this->bytes((string) ini_get('upload_max_filesize')) >= 5 * 1024 * 1024 && $this->bytes((string) ini_get('post_max_size')) >= 6 * 1024 * 1024, 'warn', 'In php.ini set upload_max_filesize=8M and post_max_size=10M, then restart the web server.'),
            $this->check('Storage', 'Storage folder writable', is_writable(storage_path('app')) && is_writable(storage_path('logs')), 'fail', 'Give the web server user write access to storage/.'),
        ];
    }

    /**
     * @param  'warn'|'fail'  $severity
     * @return array{area: string, check: string, result: 'pass'|'warn'|'fail', detail: string}
     */
    private function check(string $area, string $check, bool $passed, string $severity, string $fix): array
    {
        return ['area' => $area, 'check' => $check, 'result' => $passed ? 'pass' : $severity, 'detail' => $passed ? 'OK' : $fix];
    }

    /**
     * @return array{area: string, check: string, result: 'pass'|'warn'|'fail', detail: string}
     */
    private function databaseUserCheck(): array
    {
        $config = DB::connection()->getConfig();
        $isRoot = strtolower((string) ($config['username'] ?? '')) === 'root';
        $hasPassword = (string) ($config['password'] ?? '') !== '';

        if (DB::connection()->getDriverName() === 'sqlite') {
            return $this->check('Database', 'Dedicated database user', false, 'warn', 'SQLite is in use; production should use MySQL with its own user.');
        }

        return $this->check('Database', 'Dedicated database user', ! $isRoot && $hasPassword, 'fail', 'Connect as a dedicated user with a strong password and rights only to this database, not root.');
    }

    private function backupPasswordOk(): bool
    {
        $password = (string) config('backup.password');

        return strlen($password) >= 16 && $password !== (string) config('app.key');
    }

    /**
     * @return array{area: string, check: string, result: 'pass'|'warn'|'fail', detail: string}
     */
    private function recentBackupCheck(): array
    {
        try {
            $latest = $this->backupPasswordOk() ? $this->backups->list()->first() : null;
        } catch (Throwable) {
            $latest = null;
        }

        $recent = $latest !== null && Carbon::parse($latest['createdAt'])->greaterThan(now()->subHours(26));
        $verified = ($latest['verification']['ok'] ?? false) === true;

        return $this->check('Backups', 'Verified backup in the last 26 hours', $recent && $verified, 'fail', 'Run php artisan guidance:backup --verify, then make sure the nightly schedule runs.');
    }

    /**
     * @return array{area: string, check: string, result: 'pass'|'warn'|'fail', detail: string}
     */
    private function schedulerCheck(): array
    {
        $heartbeat = Cache::get(self::HEARTBEAT_KEY);
        $running = $heartbeat !== null && Carbon::parse($heartbeat)->greaterThan(now()->subMinutes(5));

        return $this->check('Backups', 'Scheduler running', $running, 'fail', 'Run php artisan schedule:run every minute (Windows Task Scheduler or cron).');
    }

    private function bytes(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
