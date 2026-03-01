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

            if (class_exists('CBSeeder')) {
                $this->info('Seeding database...');
                $this->call('db:seed', ['--class' => 'CBSeeder', '--force' => true]);
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
