<?php
namespace Servit\Restsrv\Traits;

trait HasPermissions
{
    public static function findById(int $id, $guardName = null)
    {
        $guardName = $guardName ? $guardName : 'web';
        $role = static::where('id', $id)->where('guard_name', $guardName)->first();
        return $role;
    }

    public static function findByName(string $name, $guardName = null)
    {
        $guardName = $guardName ?$guardName : 'web';
        $role = static::where('name', $name)->where('guard_name', $guardName)->first();
        return $role;
    }

    public function givePermissionTo(...$permissions)
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                return $this->getStoredPermission($permission);
            })
            ->each(function ($permission) {
            })
            ->all();
        $this->permissions()->saveMany($permissions);
        return $this;
    }

    public function syncPermissions(...$permissions)
    {
        $this->permissions()->detach();

        return $this->givePermissionTo($permissions);
    }

    public function revokePermissionTo($permission)
    {
        $this->permissions()->detach($this->getStoredPermission($permission));
        return $this;
    }

    public function hasPermissionTo($permission, $guardName = null) 
    {
        if (is_string($permission)) {
            $permission = Permission::findByName(
                $permission,
                $guardName ? $guardName : $this->getDefaultGuardName()
            );
        }

        if (is_int($permission)) {
            $permission = Permission::findById($permission, $this->getDefaultGuardName());
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    protected function getStoredPermission($permissions)
    {
        if (is_numeric($permissions)) {
            return Permission::findById($permissions, $this->getDefaultGuardName());
        }

        if (is_string($permissions)) {
            return Permission::findByName($permissions, $this->getDefaultGuardName());
        }

        if (is_array($permissions)) {
            return Permission::whereIn('name', $permissions)
                ->whereIn('guard_name', $this->getGuardNames())
                ->get();
        }

        return $permissions;
    }

    protected function getDefaultGuardName() 
    {
        return 'web';
    }

    protected function getGuardNames() 
    {
        $model = $this;
        if (is_object($model)) {
            $guardName = $model->guard_name ? $model->guard_name : null;
            }

            if (!isset($guardName)) {
                $class = is_object($model) ? get_class($model) : $model;
                $gname = (new \ReflectionClass($class))->getDefaultProperties();
                
                $guardName = isset($gname['guard_name']) ? ( new \ReflectionClass($class))->getDefaultProperties()['guard_name'] : null;
            }

            if ($guardName) {
                return collect($guardName);
            }
            return collect(['web']);
    }

    public function hasDirectPermission($permission) 
    {
        if (is_string($permission)) {
            $permission = Permission::findByName($permission, $this->getDefaultGuardName());
            if (!$permission) {
                return false;
            }
        }

        if (is_int($permission)) {
            $permission = Permission::findById($permission, $this->getDefaultGuardName());
            if (!$permission) {
                return false;
            }
        }

        return $this->permissions->contains('id', $permission->id);
    }

    protected function ensureModelSharesGuard($roleOrPermission)
    {
        if (!$this->getGuardNames()->contains($roleOrPermission->guard_name)) {
            throw new Exception("Error " . $roleOrPermission->guard_name, 1);
        }
    }
}