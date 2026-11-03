<?php

namespace App\Http\Controllers\Panel;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('panel.users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10'],
            'role' => ['required', Rule::enum(Role::class)],
        ], ['password.min' => 'La contraseña debe tener al menos 10 caracteres.']);

        User::create($data + ['is_active' => true]);

        return back()->with('status', 'Usuario creado.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:10'],
            'role' => ['required', Rule::enum(Role::class)],
        ], ['password.min' => 'La contraseña debe tener al menos 10 caracteres.']);

        $active = $request->boolean('is_active');
        $staysAdmin = $data['role'] === Role::Admin->value && $active;
        $otherAdmins = User::where('role', Role::Admin->value)->where('is_active', true)->whereKeyNot($user->id)->exists();

        if ($user->isAdmin() && ! $staysAdmin && ! $otherAdmins) {
            return back()->withErrors(['role' => 'Debe quedar al menos un administrador activo.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data + ['is_active' => $active]);

        return back()->with('status', 'Usuario actualizado.');
    }
}
