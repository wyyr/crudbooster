<?php

namespace crocodicstudio\crudbooster\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class CrudboosterUpdateCommand extends Command
{
    use CBCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'crudbooster:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CRUDBooster Update Command';

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

        $this->info('Updating CRUDBooster...');

        $vendorPath = public_path('vendor');
        if (! File::isDirectory($vendorPath)) {
            File::makeDirectory($vendorPath, 0777, true, true);
        }

        $this->info('Publishing CRUDBooster assets and migrations...');
        $this->call('vendor:publish', ['--all' => true]);
        $this->call('vendor:publish', ['--tag' => 'cb_migration', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'cb_lfm', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'cb_localization', '--force' => true]);

        $this->updateLfmConfig();

        $this->info('Dumping the autoloaded files...');
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

        $this->info('Clearing Cache...');
        Cache::flush();
        $this->call('config:clear');

        $this->info('Update Completed!');
        $this->footer();

        return 0;
    }

    private function updateLfmConfig()
    {
        $this->info('Configuring Laravel File Manager...');
        $configPath = config_path('lfm.php');

        if (! File::exists($configPath)) {
            $this->warn('Warning: lfm.php not found. Skipping LFM configuration.');

            return;
        }

        $configContent = File::get($configPath);

        $replacements = [
            "['web','auth']" => "['web','\\crocodicstudio\\crudbooster\\middlewares\\CBBackend']",
            'Unisharp\Laravelfilemanager\Handlers\ConfigHandler::class' => 'function() {return Session::get("admin_id");}',
            'auth()->user()->id' => 'Session::get("admin_id")',
            "'alphanumeric_filename' => false" => "'alphanumeric_filename' => true",
            "'alphanumeric_directory' => false" => "'alphanumeric_directory' => true",
            "'base_directory' => 'public'" => "'base_directory' => 'storage/app'",
            "'images_folder_name' => 'photos'" => "'images_folder_name' => 'uploads'",
            "'files_folder_name'  => 'files'" => "'files_folder_name'  => 'uploads'",
        ];

        foreach ($replacements as $old => $new) {
            $configContent = str_replace($old, $new, $configContent);
        }

        File::put($configPath, $configContent);
    }
}
