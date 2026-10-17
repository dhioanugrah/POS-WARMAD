<?php

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Roles extends Component
{
    /** @var array<string, array<string, bool>> role => permission => bool */
    public array $matrix = [];

    public function mount(): void
    {
        $this->loadMatrix();
    }

    public function loadMatrix(): void
    {
        $roles = Role::query()->where('name', '!=', 'Owner')->get();
        $permissions = Permission::query()->pluck('name');

        $this->matrix = [];
        foreach ($roles as $role) {
            $granted = $role->permissions->pluck('name')->all();
            foreach ($permissions as $permission) {
                $this->matrix[$role->name][$permission] = in_array($permission, $granted, true);
            }
        }
    }

    public function toggle(string $role, string $permission): void
    {
        $this->matrix[$role][$permission] = ! ($this->matrix[$role][$permission] ?? false);
    }

    public function save(): void
    {
        foreach ($this->matrix as $roleName => $permissions) {
            $role = Role::findByName($roleName);
            $granted = array_keys(array_filter($permissions));
            $role->syncPermissions($granted);
        }

        $this->dispatch('notify', message: 'Hak akses diperbarui.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.roles', [
            'permissions' => Permission::query()->pluck('name'),
            'roles' => array_keys($this->matrix),
        ]);
    }
}
