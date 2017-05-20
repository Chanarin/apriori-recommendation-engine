<?php

use Illuminate\Database\Seeder;

class OAuthClientsTableSeeder extends Seeder
{
    /**
     * @var int
     */
    const ITERATIONS = 10;
    
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i = 0; $i < self::ITERATIONS; $i++)
        {
            \DB::table('oauth_clients')->insert([
                'id'     => 'id' . $i,
                'secret' => 'secret' . $i,
                'name'   => 'name' . $i
            ]);
        }
    }
}