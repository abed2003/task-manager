@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <section class="page-hero">
            <div class="page-hero-inner">
                <p class="hero-eyebrow">الفريق</p>
                <h1 class="hero-title">إدارة المستخدمين</h1>
                <p class="hero-subtitle">أضف أعضاء الفريق، حدّد الصلاحيات، وتابع حجم المهام المرتبطة بكل مستخدم.</p>
            </div>
        </section>

        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-black">يرجى مراجعة البيانات.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="stat-card"><p class="text-sm font-bold text-slate-500">إجمالي المستخدمين</p><p class="mt-3 text-3xl font-black text-slate-950">{{ $stats['total_users'] }}</p></div>
            <div class="stat-card"><p class="text-sm font-bold text-slate-500">المشرفون</p><p class="mt-3 text-3xl font-black text-red-700">{{ $stats['admins_count'] }}</p></div>
            <div class="stat-card"><p class="text-sm font-bold text-slate-500">إجمالي المهام</p><p class="mt-3 text-3xl font-black text-slate-950">{{ $stats['total_tasks'] }}</p></div>
            <div class="stat-card"><p class="text-sm font-bold text-slate-500">المهام المكتملة</p><p class="mt-3 text-3xl font-black text-emerald-700">{{ $stats['completed_tasks'] }}</p></div>
        </section>

        <section class="grid gap-6 lg:grid-cols-[380px_1fr]">
            <aside class="panel">
                <div class="panel-header">
                    <h2 class="text-lg font-black text-slate-950">إضافة مستخدم</h2>
                    <p class="mt-1 text-sm text-slate-500">الحسابات الجديدة تُنشأ بواسطة المشرف.</p>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" class="panel-body space-y-4">
                    @csrf
                    <div><label for="name" class="field-label">الاسم</label><input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-2 block w-full"></div>
                    <div><label for="email" class="field-label">البريد الإلكتروني</label><input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 block w-full"></div>
                    <div>
                        <label for="role" class="field-label">الصلاحية</label>
                        <select id="role" name="role" required class="mt-2 block w-full">
                            <option value="user" @selected(old('role') === 'user')>مستخدم</option>
                            <option value="admin" @selected(old('role') === 'admin')>مشرف</option>
                        </select>
                    </div>
                    <div><label for="password" class="field-label">كلمة المرور</label><input id="password" name="password" type="password" required class="mt-2 block w-full"></div>
                    <div><label for="password_confirmation" class="field-label">تأكيد كلمة المرور</label><input id="password_confirmation" name="password_confirmation" type="password" required class="mt-2 block w-full"></div>
                    <button type="submit" class="btn-primary w-full">إنشاء المستخدم</button>
                </form>
            </aside>

            <div class="panel overflow-hidden">
                <div class="panel-header">
                    <h2 class="text-lg font-black text-slate-950">قائمة المستخدمين</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($users as $user)
                        @php
                            $tasksCount = $user->tasks->count();
                            $completedCount = $user->tasks->where('is_completed', true)->count();
                            $rate = $tasksCount > 0 ? round(($completedCount / $tasksCount) * 100) : 0;
                            $isCurrentUser = auth()->id() === $user->id;
                        @endphp

                        <article class="p-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-11 w-11 place-items-center rounded-lg bg-slate-950 text-sm font-black text-white">{{ mb_substr($user->name, 0, 1) }}</div>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="font-black text-slate-950">{{ $user->name }}</h3>
                                                @if($isCurrentUser)<span class="status-pill bg-cyan-50 text-cyan-800 ring-cyan-100">أنت</span>@endif
                                                <span class="status-pill {{ $user->role === 'admin' ? 'bg-red-50 text-red-700 ring-red-100' : 'bg-emerald-50 text-emerald-700 ring-emerald-100' }}">{{ $user->role === 'admin' ? 'مشرف' : 'مستخدم' }}</span>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-600">{{ $user->email }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold text-slate-600">
                                        <span class="status-pill bg-slate-100 text-slate-600 ring-slate-200">المهام: {{ $tasksCount }}</span>
                                        <span class="status-pill bg-slate-100 text-slate-600 ring-slate-200">الإنجاز: {{ $rate }}%</span>
                                        <span class="status-pill bg-slate-100 text-slate-600 ring-slate-200">{{ $user->created_at ? $user->created_at->format('Y-m-d') : 'غير محدد' }}</span>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <details class="relative">
                                        <summary class="btn-secondary list-none">تعديل</summary>
                                        <div class="absolute left-0 z-20 mt-2 w-80 rounded-lg border border-slate-200 bg-white p-4 shadow-xl">
                                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3">
                                                @csrf
                                                @method('PATCH')
                                                <input name="name" type="text" value="{{ old('name', $user->name) }}" required class="block w-full">
                                                <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="block w-full">
                                                <select name="role" required class="block w-full">
                                                    <option value="user" @selected(old('role', $user->role) === 'user')>مستخدم</option>
                                                    <option value="admin" @selected(old('role', $user->role) === 'admin')>مشرف</option>
                                                </select>
                                                @if($isCurrentUser)<p class="text-xs text-slate-500">لا يمكنك إزالة صلاحية المشرف من حسابك الحالي.</p>@endif
                                                <input name="password" type="password" placeholder="كلمة مرور جديدة" class="block w-full">
                                                <input name="password_confirmation" type="password" placeholder="تأكيد كلمة المرور" class="block w-full">
                                                <button type="submit" class="btn-dark w-full">حفظ التعديل</button>
                                            </form>
                                        </div>
                                    </details>

                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('هل تريد حذف هذا المستخدم؟ سيتم حذف مهامه أيضاً.')" class="{{ $isCurrentUser ? 'hidden' : '' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger">حذف</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($users->hasPages())
                    <div class="border-t border-slate-200 px-5 py-4">{{ $users->links() }}</div>
                @endif
            </div>
        </section>
    </div>
@endsection
