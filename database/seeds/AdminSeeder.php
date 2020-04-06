<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //seed admin
        $admin = User::where("email","admin@oms.com")->first();
        if ($admin == null) {
            $admin = new User();
            $admin->first_name = "Admin";
            $admin->last_name = "Admin";
            $admin->email = "admin@oms.com";
            $admin->email_verified_at = now();
            $admin->password = Hash::make('admin');
            $admin->remember_token = str_random(10);
            $admin->save();

            //assign role
            $admin->assignRole("administrator");


        }

        //seed driver
        $driver = User::where("email","driver@oms.com")->first();
        if ($driver == null) {
            $driver = new User();
            $driver->first_name = "Driver";
            $driver->last_name = "Driver";
            $driver->email = "driver@oms.com";
            $driver->email_verified_at = now();
            $driver->password = Hash::make('driver');
            $driver->remember_token = str_random(10);
            $driver->save();

            //assign role 
            $driver->assignRole("driver");
        }



    }
}
