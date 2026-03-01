<?php

namespace crocodicstudio\crudbooster\commands;

trait CBCommandTrait
{
    private function header()
    {
        $this->info("
#     __________  __  ______  ____                   __           
#    / ____/ __ \/ / / / __ \/ __ )____  ____  _____/ /____  _____
#   / /   / /_/ / / / / / / / __  / __ \/ __ \/ ___/ __/ _ \/ ___/
#  / /___/ _, _/ /_/ / /_/ / /_/ / /_/ / /_/ (__  ) /_/  __/ /    
#  \____/_/ |_|\____/_____/_____/\____/\____/____/\__/\___/_/     
#                                                                                                                       
			");
        $this->info('--------- :===: Thanks for choosing Community Edition of CRUDBooster :==: ---------------');
        $this->info('====================================================================');
    }

    private function footer($success = true)
    {
        $this->info('--------------------------------------------------------------------');
        $this->info('Github : https://github.com/wyyr/crudbooster');
        $this->info('Documentation : https://github.com/wyyr/crudbooster/blob/3.x/docs/en/index.md');
        $this->info('====================================================================');

        if ($success) {
            $this->info('------------------- :===: Completed !! :===: ------------------------');
        } else {
            $this->info('------------------- :===: Failed !!    :===: ------------------------');
        }
    }

    private function checkRequirements()
    {
        $this->info('System Requirements Checking:');
        $system_failed = 0;
        $laravel_version = app()->version();

        $this->info('Your laravel version: '.$laravel_version);
        $this->info('Your PHP version: '.phpversion());
        $this->info('---');

        if (version_compare($laravel_version, '8.0.0', '>=')) {
            $this->info('Laravel Version (>= 8.0.*): [Good]');
        } else {
            $this->warn('Laravel Version (>= 8.0.*): [Bad]');
            $system_failed++;
        }

        if (version_compare(phpversion(), '8.0', '>=')) {
            $this->info('PHP Version (>= 8.*): [Good]');
        } else {
            $this->info('PHP Version (>= 8.*): [Bad] Yours: '.phpversion());
            $system_failed++;
        }

        $exts = ['mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'gd', 'fileinfo'];
        foreach ($exts as $ext) {
            if (extension_loaded($ext)) {
                $this->info(ucfirst($ext).' extension: [Good]');
            } else {
                $this->error(ucfirst($ext).' extension: [Bad]');
                $system_failed++;
            }
        }

        if (is_writable(public_path())) {
            $this->info('/public dir is writable: [Good]');
        } else {
            $this->error('/public dir is writable: [Bad]');
            $system_failed++;
        }

        if ($system_failed != 0) {
            $this->error('Sorry unfortunately your system is not meet with our requirements !');
            $this->footer(false);

            return false;
        }

        $this->info('--');

        return true;
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(base_path('composer.phar'))) {
            return PHP_BINARY.' composer.phar';
        }

        return 'composer';
    }
}
