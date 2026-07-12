<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Task Manager') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-950 antialiased">
        <main class="grid min-h-screen bg-white lg:grid-cols-[1fr_460px]">
            <section class="relative hidden overflow-hidden bg-slate-950 text-white lg:block">
                <div class="relative flex h-full flex-col justify-between p-10">
                    <a href="/" class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-sm font-black text-slate-950">TM</span>
                        <span>
                            <span class="block text-lg font-black text-white">مدير المشاريع</span>
                            <span class="block text-xs font-bold text-slate-400">Engineering Workspace</span>
                        </span>
                    </a>

                    <div class="max-w-xl">
                        <p class="text-sm font-black uppercase tracking-wide text-white/60">نظام رسمي لإدارة فرق البرمجة</p>
                        <h1 class="mt-4 text-5xl font-black leading-[1.05] tracking-tight text-white">إدارة المشاريع والمهام من مكان واحد، بوضوح وسرعة.</h1>
                        <p class="mt-5 text-base leading-7 text-slate-400">لوحة عمل مخصصة للمشرفين والمطورين، تربط المشاريع بالمهام والمراحل والمستخدمين بدون تشتيت.</p>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-3xl border border-white/15 bg-white/5 p-4">
                            <p class="text-xs font-bold text-slate-400">Workflow</p>
                            <p class="mt-1 font-black text-white">مراحل واضحة</p>
                        </div>
                        <div class="rounded-3xl border border-white/15 bg-white/5 p-4">
                            <p class="text-xs font-bold text-slate-400">Projects</p>
                            <p class="mt-1 font-black text-white">تنظيم المشاريع</p>
                        </div>
                        <div class="rounded-3xl border border-white/15 bg-white/5 p-4">
                            <p class="text-xs font-bold text-slate-400">Team</p>
                            <p class="mt-1 font-black text-white">إسناد للفريق</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="flex items-center justify-center px-4 py-10 sm:px-6">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex items-center justify-center gap-3 lg:hidden">
                        <span class="brand-mark">TM</span>
                        <span>
                            <span class="block text-lg font-black text-slate-950">مدير المشاريع</span>
                            <span class="block text-xs font-bold text-slate-500">Engineering Workspace</span>
                        </span>
                    </div>

                    <div class="auth-card">
                        {{ $slot }}
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
