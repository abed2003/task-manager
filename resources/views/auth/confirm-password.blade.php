<x-guest-layout>
    <div class="mb-7">
        <p class="eyebrow">تأكيد أمني</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">تأكيد كلمة المرور</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">هذه منطقة محمية. أكد كلمة المرور قبل المتابعة.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="password" value="كلمة المرور" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <x-primary-button class="w-full">تأكيد</x-primary-button>
    </form>
</x-guest-layout>
