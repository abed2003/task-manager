<x-guest-layout>
    <div class="mb-7">
        <p class="eyebrow">حساب جديد</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">إنشاء حساب</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">هذه الصفحة مخصصة للحالات الإدارية، والحسابات الاعتيادية ينشئها المشرف.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <div><x-input-label for="name" value="الاسم" /><x-text-input id="name" class="mt-2 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" /><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
        <div><x-input-label for="email" value="البريد الإلكتروني" /><x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" /><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
        <div><x-input-label for="password" value="كلمة المرور" /><x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
        <div><x-input-label for="password_confirmation" value="تأكيد كلمة المرور" /><x-text-input id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" /></div>
        <x-primary-button class="w-full">إنشاء الحساب</x-primary-button>
        <p class="text-center text-sm text-slate-600">لديك حساب؟ <a href="{{ route('login') }}" class="font-black text-slate-950 underline">تسجيل الدخول</a></p>
    </form>
</x-guest-layout>
