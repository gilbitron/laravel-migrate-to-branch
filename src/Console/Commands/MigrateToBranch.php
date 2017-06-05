<?php

namespace Gilbitron\Laravel\Console\Commands;

use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Support\Collection;

class MigrateToBranch extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:to-branch {branch : The name of the branch}
                {--database= : The database connection to use}
                {--path= : The path of migrations files to be executed}
                {--dry-run : Output the migrations that need rolled back}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback migrations before switching to a given branch';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        // For some reason dependency injecting this class doesn't work
        $this->migrator = app('migrator');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $destBranch    = $this->argument('branch');
        $currentBranch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));

        if ($destBranch == $currentBranch) {
            $this->error('Already on branch ' . $destBranch);
            return;
        }

        $rollbackMigrations = $this->getMigrationsToRollBack($currentBranch, $destBranch);
        $ranMigrations      = $this->getRanMigrations();

        $steps = $rollbackMigrations->reject(function ($migration) use ($ranMigrations) {
            return !in_array($migration, $ranMigrations->toArray());
        });

        if ($steps->count()) {
            if ($this->option('dry-run')) {
                $this->info('Rollback required! ' . $steps->count() . ' migrations need rolled back:');

                $steps->each(function ($item) {
                    $this->line(' - ' . $item);
                });

                $this->info('Run the following command: php artisan migrate:rollback --step=' . $steps->count());
            } else {
                $this->call('migrate:rollback', [
                    '--step' => $steps->count(),
                ]);
            }
        } else {
            $this->info('No migrations need rolled back');
        }
    }

    /**
     * Get migrations to roll back
     *
     * @param string $currentBranch
     * @param string $destBranch
     * @return Collection
     */
    protected function getMigrationsToRollBack($currentBranch, $destBranch)
    {
        $command = 'cd ' . base_path() . ' && git diff --name-status ';
        $command .= $currentBranch . '..' . $destBranch;
        $command .= ' -- database/migrations';

        /*
         * Format:
         * A    database/migrations/2017_06_02_105859_example1_migration.php
         * D    database/migrations/2017_06_02_105859_example2_migration.php
         */
        $output     = trim(shell_exec($command));
        $migrations = explode("\n", $output);

        return collect($migrations)->reject(function ($migration) {
            // We only need migrations that don't exist in the dest branch
            return !starts_with($migration, 'D');
        })->map(function ($migration) {
            return basename($migration, '.php');
        });
    }

    /**
     * Get migrations that have been run
     *
     * @return Collection
     */
    protected function getRanMigrations()
    {
        $this->migrator->setConnection($this->option('database'));

        if (!$this->migrator->repositoryExists()) {
            return collect();
        }

        $ran = $this->migrator->getRepository()->getRan();

        return collect($this->getAllMigrationFiles())->reject(function ($migration) use ($ran) {
            $migrationName = $this->migrator->getMigrationName($migration);

            return !in_array($migrationName, $ran);
        })->keys();
    }

    /**
     * Get an array of all of the migration files.
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        return $this->migrator->getMigrationFiles($this->getMigrationPaths());
    }
}
