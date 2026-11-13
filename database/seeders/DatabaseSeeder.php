<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['General', 'Personal', 'Work'] as $name) {
            Project::firstOrCreate(['name' => $name]);
        }
    }
}
