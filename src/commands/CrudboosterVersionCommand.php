<?php

namespace crocodicstudio\crudbooster\commands;

use Illuminate\Console\Command;

class CrudboosterVersionCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'crudbooster:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CRUDBooster Version Command';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public static $version = "2.1.0";

    public function handle()
    {
        $this->info(static::$version);
    }
}
