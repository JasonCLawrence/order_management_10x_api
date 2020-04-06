<?php

use Illuminate\Database\Seeder;
use App\Role;

class Roles extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $roles = ['administrator', 'driver'];

        foreach($roles AS $role_name)
        {
        	$role = Role::where('name', $role_name)->first();
        	if($role==null)
        	{
	        	$role = new Role;
	        	$role->name = $role_name;
	        	$role->save();        		
        	}

        }
    }
}
