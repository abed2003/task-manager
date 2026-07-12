<x-guest-layout>
    <div class="mb-7">
        <p class="eyebrow">تسجيل الدخول</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">الدخول إلى مساحة العمل</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">أدخل بياناتك للوصول إلى المشاريع والمهام المسندة لك.</p>
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

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
            <input id="remember_me" type="checkbox" class="rounded-md border-2 border-slate-300 text-slate-950 focus:ring-slate-950" name="remember">
            <span>تذكرني</span>
        </label>

        <x-primary-button class="w-full">دخول</x-primary-button>

        <p class="rounded-2xl bg-slate-100 px-4 py-3 text-center text-sm font-bold text-slate-600">
            إنشاء الحسابات واستعادة الوصول تتم من خلال المشرف فقط.
        </p>
    </form>
</x-guest-layout>
