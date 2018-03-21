<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LauditorSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $now = \Carbon\Carbon::now()->toDateTimeString();

        DB::table('active_hours')->insert(['name' => '24 hours','begin_time' => '00:00:00', 'end_time' => '23:59:59', 'created_at'=>$now,'updated_at'=>$now]);


        DB::table('groups')->insert([
            'name'=>'super administrators',
            'role'=>'SUPER_ADMINISTRATORS',
            'active_hour_id' => 1,
            'holiday_login' => '1',
            'weekend_login' => '1',
            'created_at'=>$now,
            'updated_at'=>$now]);

        //This assumes that you will be using the default config values for config('ndexondeck.lauditor.authorization_user')
        DB::table('staff')->insert([
            'group_id' => 1,
            'employee_id' => '0000',
            'fullname' => 'System',
            'active_hour_id' => 1,
            'holiday_login' => '1',
            'weekend_login' => '1',
            'email' => 'system@app.com',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        //This assumes that you will be using the default config values for config('ndexondeck.lauditor.audit_user')
        DB::table('logins')->insert([
            'username'=>'system',
            'password'=> password_hash('Password@123', PASSWORD_BCRYPT, ['cost' => 12]),
            'user_type'=>'App\Staff',
            'user_id'=>1,
            'created_at'=>$now,
            'updated_at'=>$now
        ]);


        //We want to preset default settings using index

        Model::reguard();

        echo "\nTest setup is complete\n";

    }
}
