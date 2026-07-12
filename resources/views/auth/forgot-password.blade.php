<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-3xl font-black text-slate-950">نسيت كلمة المرور؟</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            أدخل بريدك الإلكتروني، وسنرسل لك رابطاً للتحقق وإعادة تعيين كلمة المرور.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="البريد الإلكتروني" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            إرسال رابط إعادة التعيين
        </x-primary-button>

        <p class="text-center text-sm text-slate-600">
            تذكرت كلمة المرور؟
            <a href="{{ route('login') }}" class="font-black text-slate-950 underline">تسجيل الدخول</a>
        </p>
    </form>
</x-guest-layout>
