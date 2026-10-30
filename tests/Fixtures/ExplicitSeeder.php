<?php

namespace Tests\Fixtures;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExplicitSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('test_seed_markers')->insert(['seeded' => true]);
    }
}
