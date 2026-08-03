<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->latest();

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($sub) use ($search): void {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        return view('spk.users', [
            'pageTitle' => 'Manajemen Pengguna',
            'pageSubtitle' => 'Kelola akun supervisor, admin, dan teknisi',
            'users' => $query->paginate(20)->withQueryString(),
            'roles' => ['Supervisor', 'Admin', 'Teknisi'],
            'currentRole' => $request->get('role', 'all'),
            'currentQuery' => $request->get('q', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'role' => ['required', 'in:Supervisor,Admin,Teknisi'],
        ]);

        User::query()->create([
            ...$validated,
            'password' => Hash::make('password123'),
        ]);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan. Password default: password123');
    }

    public function destroy(User $user): RedirectResponse
    {
        User::query()->delete($user->getKey());

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
