<?php
namespace App\Traits;
use App\Role;
use App\UserRole;


trait HasRole
{
	function assignRole($role_name)
	{

		

		$role = Role::where('name', $role_name)->first();

		if($role==null)
		{
			throw new \Exception("Role $role_name does not exits");
		}

		if($this->hasRole($role_name))
		{
			throw new \Exception("Role $role_name already assigned to user");
		}

		$user_role = new UserRole;
		$user_role->user_id = $this->id;
		$user_role->role_id = $role->id;
		$user_role->save();

		return $user_role;

	}


	public function hasRole($role_name)
	{
		

		if(is_array($role_name))
		{
			$user_role = UserRole::join('roles', 'roles.id','=','user_roles.role_id')->where('user_roles.user_id', $this->id)->whereIn('roles.name', $role_name)->count();
		}
		else
		{
		$user_role = UserRole::join('roles', 'roles.id','=','user_roles.role_id')->where('user_roles.user_id', $this->id)->where('roles.name', $role_name)->count();
		}


		if($user_role>0)
		{
			return true;
		}

		return false;
	}


	public function removeRole($role_name)
	{
		$user_role = UserRole::join('roles', 'roles.id','=','user_roles.role_id')->where('user_roles.user_id', $this->id)->where('roles.name', $role_name);

		if($user_role->count()>0)
		{
			$user_role->delete();
		}

		return true;
	}


	public function roles()
	{
		return UserRole::join('roles', 'roles.id','=','user_roles.role_id')->where('user_roles.user_id', $this->id)->select('roles.id', 'roles.name')->get();
	}

	function assignRoles($role_names=[])
	{

		if(count($role_names)==0)
		{
			throw new \Exception("No role was set");
		}

		UserRole::where('user_id', $this->id)->delete();

		foreach($role_names as $role_name)
		{
		
			$role = Role::where('name', $role_name)->first();

			if($role==null)
			{
				throw new \Exception("Role $role_name does not exits");
			}

			$user_role = new UserRole;
			$user_role->user_id = $this->id;
			$user_role->role_id = $role->id;
			$user_role->save();
			
		}

		return true;

	}

}