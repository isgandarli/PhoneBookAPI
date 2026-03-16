<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];

        $data[] = ['username' => 'admin', 'first_name' => 'Əsas', 'last_name' => 'İstifadəçi', 'password' => bcrypt('adminpanel123')];
        $data[] = ['username' => 'admin1', 'first_name' => 'Admin', 'last_name' => 'Admin', 'password' => bcrypt('adminadsea123!')];
        $data[] = ['username' => 'admin2', 'first_name' => 'Admin', 'last_name' => 'Admin', 'password' => bcrypt('adminadsea123*')];

        DB::table('users')->insert($data);
    }
}
