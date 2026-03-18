<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];
        $data[] = ['name' => 'Dövlət Su Ehtiyatları Agentliyi', 'parent_id' => null, 'description' => null, 'order' => 1, 'structure_type_id' => 1];

        DB::table('structure')->insert($data);
    }
}
