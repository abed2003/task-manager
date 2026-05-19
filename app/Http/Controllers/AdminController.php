<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::with('tasks')->latest()->paginate(10);

        $stats = [
            'total_users' => User::count(),
            'total_tasks' => Task::count(),
            'completed_tasks' => Task::where('is_completed', true)->count(),
            'admins_count' => User::where('role', 'admin')->count(),
            'regular_users' => User::where('role', 'user')->count(),
        ];

        return view('admin.users', compact('users', 'stats'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,user'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users')->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:admin,user'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()
                ->withErrors(['role' => 'لا يمكنك إزالة صلاحية المشرف من حسابك الحالي.'])
                ->withInput();
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users')->with('success', 'تم تحديث المستخدم بنجاح.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'لا يمكنك حذف حسابك الحالي من لوحة الإدارة.']);
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'تم حذف المستخدم بنجاح.');
    }
}
