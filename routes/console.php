<?php

use App\Models\InspectionFeature;
use App\Models\InspectionSection;
use App\Models\Package;
use App\Models\Question;
use App\Models\RatingOption;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

Artisan::command('cerberus:create-admin', function (): int {
    $email = mb_strtolower(trim((string) $this->ask('Administrator email')));
    $name = trim((string) $this->ask('Administrator display name'));
    $password = (string) $this->secret('Administrator password (minimum 12 characters)');

    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || strlen($password) < 12) {
        $this->error('Provide a valid email, a display name, and a password of at least 12 characters.');

        return 1;
    }
    if (StaffUser::query()->where('email', $email)->exists()) {
        $this->error('A staff account already exists for that email.');

        return 1;
    }

    StaffUser::query()->create(['email' => $email, 'display_name' => $name, 'password' => $password, 'role' => 'admin', 'active' => true]);
    $this->info('Administrator account created. Keep the password in a secure password manager.');

    return 0;
})->purpose('Create the first Cerberus administrator account');

Artisan::command('cerberus:import-catalogue {path} {--dry-run}', function (string $path): int {
    $path = str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) ? $path : base_path($path);
    if (! is_file($path)) {
        $this->error('Catalogue file not found.');

        return 1;
    }
    $catalogue = json_decode((string) file_get_contents($path), true);
    if (! is_array($catalogue)) {
        $this->error('Catalogue file must contain a JSON object.');

        return 1;
    }
    foreach (['sections', 'questions', 'features', 'ratings', 'packages'] as $key) {
        if (! isset($catalogue[$key]) || ! is_array($catalogue[$key])) {
            $this->error('Catalogue must contain an array named '.$key.'.');

            return 1;
        }
    }
    $require = static function (array $row, string $key, string $context): mixed {
        if (! array_key_exists($key, $row) || $row[$key] === '') {
            throw new InvalidArgumentException($context.' requires '.$key.'.');
        }

        return $row[$key];
    };
    $sectionIds = [];
    $featureIds = [];
    $featureRecords = [];
    $questionIds = [];
    DB::beginTransaction();
    try {
        foreach ($catalogue['sections'] as $row) {
            $code = (string) $require($row, 'code', 'Section');
            $sectionIds[$code] = InspectionSection::query()->updateOrCreate(['code' => $code], [
                'title' => $require($row, 'title', 'Section '.$code), 'description' => $row['description'] ?? null,
                'sort_order' => (int) ($row['sort_order'] ?? 0), 'active' => (bool) ($row['active'] ?? false),
            ])->id;
        }
        foreach ($catalogue['questions'] as $row) {
            $code = (string) $require($row, 'code', 'Question');
            $sectionCode = (string) $require($row, 'section', 'Question '.$code);
            if (! isset($sectionIds[$sectionCode])) {
                throw new InvalidArgumentException('Question '.$code.' references unknown section '.$sectionCode.'.');
            }
            $questionIds[$code] = Question::query()->updateOrCreate(['code' => $code], [
                'section_id' => $sectionIds[$sectionCode], 'prompt' => $require($row, 'prompt', 'Question '.$code),
                'help_text' => $row['help_text'] ?? null, 'response_type' => $row['response_type'] ?? 'rating',
                'measurement_label' => $row['measurement_label'] ?? null, 'measurement_unit' => $row['measurement_unit'] ?? null,
                'required' => (bool) ($row['required'] ?? false), 'sort_order' => (int) ($row['sort_order'] ?? 0), 'active' => (bool) ($row['active'] ?? false),
            ])->id;
        }
        foreach ($catalogue['features'] as $row) {
            $code = (string) $require($row, 'code', 'Feature');
            $feature = InspectionFeature::query()->updateOrCreate(['code' => $code], [
                'name' => $require($row, 'name', 'Feature '.$code), 'description' => $row['description'] ?? null,
                'addon_price' => $row['addon_price'] ?? null, 'addon_enabled' => (bool) ($row['addon_enabled'] ?? false),
                'active' => (bool) ($row['active'] ?? false), 'sort_order' => (int) ($row['sort_order'] ?? 0),
            ]);
            $featureIds[$code] = $feature->id;
            $featureRecords[$code] = $feature;
            $questionMap = collect($row['questions'] ?? [])->map(fn (string $questionCode) => $questionIds[$questionCode] ?? throw new InvalidArgumentException('Feature '.$code.' references unknown question '.$questionCode))->values();
            $feature->questions()->sync($questionMap->mapWithKeys(fn (int $questionId, int $index) => [$questionId => ['sort_order' => $index + 1]])->all());
        }
        foreach ($catalogue['ratings'] as $index => $row) {
            $code = (string) $require($row, 'code', 'Rating');
            if (! is_string($row['color'] ?? null) || preg_match('/^#[0-9A-Fa-f]{6}$/', $row['color']) !== 1) {
                throw new InvalidArgumentException('Rating '.$code.' must use a six-digit hexadecimal colour.');
            }
            RatingOption::query()->updateOrCreate(['code' => $code], [
                'label' => $require($row, 'label', 'Rating '.$code), 'color' => $require($row, 'color', 'Rating '.$code),
                'score' => $row['score'] ?? null, 'active' => (bool) ($row['active'] ?? false), 'sort_order' => (int) ($row['sort_order'] ?? $index + 1),
            ]);
        }
        if (collect($catalogue['ratings'])->contains(fn (array $rating) => ($rating['active'] ?? false) && array_key_exists('score', $rating) && $rating['score'] !== null) === false) {
            throw new InvalidArgumentException('Catalogue must contain at least one active scored rating.');
        }
        foreach ($catalogue['packages'] as $row) {
            $code = (string) $require($row, 'code', 'Package');
            if (($row['is_demo'] ?? false) === true) {
                throw new InvalidArgumentException('Approved catalogue imports cannot contain demo packages.');
            }
            $package = Package::query()->updateOrCreate(['code' => $code], [
                'name' => $require($row, 'name', 'Package '.$code), 'description' => $row['description'] ?? null,
                'base_price' => $require($row, 'base_price', 'Package '.$code), 'currency' => strtoupper($row['currency'] ?? 'GBP'),
                'active' => (bool) ($row['active'] ?? false), 'sort_order' => (int) ($row['sort_order'] ?? 0),
                'video_included' => (bool) ($row['video_included'] ?? false), 'is_demo' => false,
            ]);
            $included = collect($row['included_features'] ?? [])->map(fn (string $featureCode) => $featureIds[$featureCode] ?? throw new InvalidArgumentException('Package '.$code.' references unknown feature '.$featureCode));
            $addons = collect($row['addon_features'] ?? [])->map(fn (string $featureCode) => $featureIds[$featureCode] ?? throw new InvalidArgumentException('Package '.$code.' references unknown feature '.$featureCode));
            if ($addons->intersect($included)->isNotEmpty()) {
                throw new InvalidArgumentException('Package '.$code.' cannot list a feature as both included and add-on.');
            }
            foreach (($row['addon_features'] ?? []) as $featureCode) {
                $feature = $featureRecords[$featureCode];
                if (! $feature->addon_enabled || $feature->addon_price === null) {
                    throw new InvalidArgumentException('Package '.$code.' references '.$featureCode.' as an add-on, but it has no active add-on price.');
                }
            }
            $map = $included->merge($addons)->unique()->values();
            if ($package->active && $included->isEmpty()) {
                throw new InvalidArgumentException('Active package '.$code.' must include at least one feature.');
            }
            $package->features()->sync($map->mapWithKeys(fn (int $featureId, int $index) => [$featureId => ['included' => $included->contains($featureId), 'sort_order' => $index + 1]])->all());
        }
        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->info('Catalogue validation passed; no changes were committed.');
        } else {
            DB::commit();
            $this->info('Approved catalogue imported successfully.');
        }

        return 0;
    } catch (Throwable $exception) {
        DB::rollBack();
        $this->error('Catalogue import failed: '.$exception->getMessage());

        return 1;
    }
})->purpose('Validate and import an approved package and checklist catalogue JSON file');

Artisan::command('cerberus:check-production', function (): int {
    $failures = [];
    $check = function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $check(config('app.env') === 'production', 'APP_ENV must be production.');
    $check((bool) config('app.key'), 'APP_KEY must be configured.');
    $check(config('app.debug') === false, 'APP_DEBUG must be false.');
    $check(config('database.default') === 'mysql', 'The production database driver must be mysql.');
    $check(! in_array(config('mail.default'), ['log', 'array'], true), 'MAIL_MAILER must be configured for delivery, not log or array.');
    $mailHost = (string) config('mail.mailers.smtp.host');
    $check($mailHost !== '' && ! in_array(strtolower($mailHost), ['localhost', '127.0.0.1'], true), 'MAIL_HOST must point to the approved SMTP provider.');
    $check(filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false, 'MAIL_FROM_ADDRESS must be a valid email address.');
    $check(parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https', 'APP_URL must use HTTPS.');
    $check(is_writable(storage_path()), 'The storage directory must be writable.');

    $dataConnection = config('database.default');
    if (Schema::connection($dataConnection)->hasTable('packages')) {
        $check(Package::on($dataConnection)->where('active', true)->where('is_demo', true)->doesntExist(), 'Active demo packages must be removed or approved before release.');
    }
    if (Schema::connection($dataConnection)->hasTable('staff_users')) {
        $check(StaffUser::on($dataConnection)->where('active', true)->where('role', 'admin')->exists(), 'At least one active administrator account is required.');
    }

    if ($failures !== []) {
        $this->error('Production readiness checks failed:');
        foreach ($failures as $failure) {
            $this->line(' - '.$failure);
        }

        return 1;
    }

    $this->info('Production readiness checks passed.');

    return 0;
})->purpose('Validate production configuration before release');

Artisan::command('cerberus:backup {--output= : Protected backup zip path}', function (): int {
    if (config('database.default') !== 'mysql') {
        $this->error('Backups require the mysql database connection.');

        return 1;
    }
    if (! class_exists(ZipArchive::class)) {
        $this->error('The PHP zip extension is required for backups.');

        return 1;
    }

    $backupDirectory = storage_path('app/backups');
    if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0750, true) && ! is_dir($backupDirectory)) {
        $this->error('Could not create the protected backup directory.');

        return 1;
    }

    $output = $this->option('output') ?: $backupDirectory.'/cerberus-'.now()->format('Ymd-His').'.zip';
    $output = str_starts_with($output, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $output)
        ? $output
        : base_path($output);
    $sqlPath = tempnam(storage_path('app'), 'cerberus-backup-');
    $db = config('database.connections.mysql');
    $dumpBinary = env('MYSQLDUMP_PATH', 'mysqldump');
    if (PHP_OS_FAMILY === 'Windows' && ! is_file($dumpBinary)) {
        $windowsBinary = 'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysqldump.exe';
        if (is_file($windowsBinary)) {
            $dumpBinary = $windowsBinary;
        }
    }

    try {
        $process = new Process([
            $dumpBinary, '--single-transaction', '--routines', '--triggers',
            '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'], $db['database'],
            '--result-file='.$sqlPath,
        ], base_path(), ['MYSQL_PWD' => (string) ($db['password'] ?? '')]);
        $process->setTimeout(300);
        $process->mustRun();

        $zip = new ZipArchive;
        if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open the backup archive for writing.');
        }
        $zip->addFile($sqlPath, 'database.sql');
        $privatePath = storage_path('app/private');
        if (is_dir($privatePath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($privatePath, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), 'private/'.str_replace('\\', '/', $iterator->getSubPathname()));
                }
            }
        }
        $zip->addFromString('manifest.json', json_encode([
            'created_at' => now()->toIso8601String(),
            'database' => $db['database'],
            'private_storage_included' => is_dir($privatePath),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $zip->close();
    } catch (Throwable $exception) {
        if (is_file($output)) {
            unlink($output);
        }
        $this->error('Backup failed: '.$exception->getMessage());

        return 1;
    } finally {
        if (is_file($sqlPath)) {
            unlink($sqlPath);
        }
    }

    $this->info('Backup created: '.$output);

    return 0;
})->purpose('Create a database and private-file backup archive');
