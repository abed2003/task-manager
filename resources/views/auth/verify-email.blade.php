<x-guest-layout>
    <div class="mb-7">
        <p class="eyebrow">تأكيد البريد</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">تحقق من بريدك الإلكتروني</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">أرسلنا رابط التحقق إلى بريدك. يمكنك طلب رابط جديد إذا لم يصلك.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">
            تم إرسال رابط تحقق جديد إلى بريدك الإلكتروني.
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>إرسال الرابط مرة أخرى</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-secondary">خروج</button>
        </form>
    </div>
</x-guest-layout>
