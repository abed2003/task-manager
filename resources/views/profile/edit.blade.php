@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="page-hero">
            <div class="page-hero-inner">
                <p class="hero-eyebrow">الحساب</p>
                <h1 class="hero-title">الملف الشخصي</h1>
                <p class="hero-subtitle">إدارة بيانات الحساب والصلاحية وكلمة المرور الخاصة بك من مكان واحد.</p>
            </div>
        </section>

        @if (session('status') === 'profile-updated')
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">
                تم تحديث الملف الشخصي بنجاح.
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h2 class="text-lg font-black text-slate-950">بيانات الحساب</h2>
                <p class="mt-1 text-sm text-slate-500">هذه البيانات تظهر داخل النظام عند إسناد المهام والتعامل مع الفريق.</p>
            </div>
            <form method="POST" action="{{ route('profile.update') }}" class="panel-body space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <label for="name" class="field-label">الاسم</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="mt-2 block w-full">
                    @error('name')
                        <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="field-label">البريد الإلكتروني</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-2 block w-full">
                    @error('email')
                        <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="field-label">نوع المستخدم</label>
                    <div class="mt-2 rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">
                        {{ $user->role === 'admin' ? 'مشرف' : 'مستخدم' }}
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary">حفظ التغييرات</button>
                    <a href="{{ route('tasks.index') }}" class="btn-secondary">رجوع للمهام</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-[28px] border border-red-100 bg-white shadow-sm">
            <div class="border-b border-red-100 px-5 py-5">
                <h2 class="text-lg font-black text-red-600">حذف الحساب</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">عند حذف الحساب سيتم حذف البيانات المرتبطة به بشكل دائم.</p>
            </div>
            <form method="POST" action="{{ route('profile.destroy') }}" class="p-5 space-y-4" onsubmit="return confirm('هل تريد حذف الحساب نهائياً؟')">
                @csrf
                @method('DELETE')

                <div>
                    <label for="delete_password" class="field-label">كلمة المرور للتأكيد</label>
                    <input id="delete_password" name="password" type="password" required class="mt-2 block w-full focus:border-red-600">
                    @error('password', 'userDeletion')
                        <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-danger">حذف الحساب</button>
            </form>
        </section>
    </div>
@endsection
