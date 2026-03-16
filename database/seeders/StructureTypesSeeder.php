<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StructureTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Kök Struktur'],
            ['name' => 'Şöbə'],
            ['name' => 'Sektor'],
        ];

        DB::table('structure_types')->insert($data);
    }
}
