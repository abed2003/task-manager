<x-guest-layout>
    <div class="mb-7">
        <p class="eyebrow">تسجيل الدخول</p>
        <h1 class="mt-2 text-2xl font-black text-slate-950">الدخول إلى مساحة العمل</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">أدخل بياناتك للوصول إلى المشاريع والمهام المسندة لك.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="البريد الإلكتروني" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="كلمة المرور" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-cyan-700 shadow-sm focus:ring-cyan-700" name="remember">
            <span>تذكرني</span>
        </label>

        <x-primary-button class="w-full">دخول</x-primary-button>

        <p class="rounded-lg bg-slate-50 px-4 py-3 text-center text-sm font-semibold text-slate-500 ring-1 ring-slate-200">
            إنشاء الحسابات واستعادة الوصول تتم من خلال المشرف فقط.
        </p>
    </form>
</x-guest-layout>
