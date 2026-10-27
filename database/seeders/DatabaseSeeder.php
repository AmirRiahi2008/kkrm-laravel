<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('Demo seeding is only available in the local environment.');
        }
        $this->call([SiteStructureSeeder::class, DemoSeeder::class]);
    }
}
