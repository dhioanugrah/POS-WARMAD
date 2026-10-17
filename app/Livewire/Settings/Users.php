<?php

namespace App\Livewire\Settings;

use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Users extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $username = '';

    public string $role = '';

    public ?int $defaultLocationId = null;

    public bool $isActive = true;

    public string $password = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'email', 'username', 'role', 'defaultLocationId', 'password']);
        $this->isActive = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = (string) $user->username;
        $this->role = $user->getRoleNames()->first() ?? '';
        $this->defaultLocationId = $user->default_location_id;
        $this->isActive = $user->is_active;
        $this->password = '';
        $this->showModal = true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($this->editingId)],
            'role' => 'required|in:Owner,Admin,Kasir,Gudang',
            'defaultLocationId' => 'nullable|exists:locations,id',
            'password' => $this->editingId ? 'nullable|string|min:8' : 'required|string|min:8',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId === auth()->id() && ! $this->isActive) {
            $this->addError('isActive', 'Anda tidak bisa menonaktifkan akun sendiri.');

            return;
        }

        $attributes = [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username ?: null,
            'default_location_id' => $this->defaultLocationId,
            'is_active' => $this->isActive,
        ];

        if ($this->password) {
            $attributes['password'] = Hash::make($this->password);
        }

        if ($this->editingId) {
            $user = User::query()->findOrFail($this->editingId);
            $user->update($attributes);
        } else {
            $user = User::query()->create($attributes);
        }

        $user->syncRoles([$this->role]);

        $this->showModal = false;
        $this->dispatch('notify', message: 'Pengguna tersimpan.', type: 'success');
    }

    public function toggleActive(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notify', message: 'Anda tidak bisa menonaktifkan akun sendiri.', type: 'error');

            return;
        }

        $user = User::query()->findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);
        $this->dispatch('notify', message: 'Status pengguna diperbarui.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.users', [
            'users' => User::query()
                ->with('roles')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(25),
            'roles' => Role::query()->pluck('name'),
            'locations' => Location::query()->where('is_active', true)->pluck('name', 'id'),
        ]);
    }
}
