<?php

namespace crocodicstudio\crudbooster\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class CrudboosterInstallationCommand extends Command
{
    use CBCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'crudbooster:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CRUDBooster Installation Command';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->header();

        if ($this->checkRequirements() === false) {
            return 1;
        }

        $this->info('Installing: ');

        if ($this->confirm('Do you have setting the database configuration at .env ?')) {

            if (! File::exists(base_path('.env'))) {
                $this->error('.env file not found! Please create your .env file and configure the database first.');
                $this->footer(false);

                return 1;
            }

            $vendorPath = public_path('vendor');
            if (! File::isDirectory($vendorPath)) {
                File::makeDirectory($vendorPath, 0777, true, true);
            }

            $this->info('Publishing crudbooster assets...');
            $this->call('vendor:publish', ['--provider' => 'crocodicstudio\crudbooster\CRUDBoosterServiceProvider']);

            $this->info('Dumping the autoloaded files and reloading all new files...');
            $composer = $this->findComposer();

            $process = new Process([$composer, 'dump-autoload']);
            $process->setWorkingDirectory(base_path())
                ->setTimeout(null)
                ->run(function ($type, $buffer) {
                    $this->output->write($buffer);
                });

            $this->info('Migrating database...');
            $this->call('migrate', ['--force' => true]);

            // Ask user to run the seeder automatically
            if ($this->confirm('Do you want to run CBSeeder now? (Highly recommended for default data)', true)) {

                $seederClass = 'Database\\Seeders\\CBSeeder';

                // Check if the class exists (after being published to user's directory)
                if (class_exists($seederClass) || class_exists('CBSeeder')) {
                    $this->info('Seeding database...');
                    $this->call('db:seed', ['--class' => 'CBSeeder', '--force' => true]);
                    $this->info('Database seeding completed successfully.');
                } else {
                    $this->error('CBSeeder class not found!');
                    $this->warn('Please ensure you have published the seeder or created it manually.');
                    $this->line('Command: php artisan vendor:publish --tag=cb-seeders');
                }
            } else {
                $this->warn('------------------------------------------------------------');
                $this->warn('IMPORTANT: Please run the following command manually to ');
                $this->warn('initialize the required core data (Settings, Menus, etc.):');
                $this->line('php artisan db:seed --class=CBSeeder');
                $this->warn('------------------------------------------------------------');
            }

            $this->call('config:clear');
            $this->info('Installing CRUDBooster Is Completed ! Thank You :)');
            $this->footer();

            return 0;
        }

        $this->warn('Setup Aborted !');
        $this->info('Please setting the database configuration for first !');
        $this->footer(false);

        return 1;
    }
}
