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
    <body class="font-sans">
        <div x-data="{ open: false }" class="app-shell">
            <aside class="fixed inset-y-0 right-0 z-50 hidden w-72 border-l border-slate-200 bg-white lg:flex lg:flex-col">
                <div class="flex h-20 items-center gap-3 border-b border-slate-100 px-6">
                    <span class="brand-mark">TM</span>
                    <div>
                        <p class="text-base font-black text-slate-950">مدير الأعمال</p>
                        <p class="text-xs font-bold text-slate-500">Software Workspace</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-1 px-4 py-6">
                    <a href="{{ route('dashboard') }}" class="side-link {{ request()->routeIs('dashboard') ? 'side-link-active' : 'side-link-idle' }}">لوحة التحكم</a>
                    <a href="{{ route('projects.index') }}" class="side-link {{ request()->routeIs('projects.*') ? 'side-link-active' : 'side-link-idle' }}">المشاريع</a>
                    <a href="{{ route('tasks.index') }}" class="side-link {{ request()->routeIs('tasks.*') ? 'side-link-active' : 'side-link-idle' }}">المهام</a>
                    <a href="{{ route('meetings.index') }}" class="side-link {{ request()->routeIs('meetings.*') ? 'side-link-active' : 'side-link-idle' }}">الاجتماعات</a>
                    @if(auth()->user()?->role === 'admin')
                        <a href="{{ route('admin.users') }}" class="side-link {{ request()->routeIs('admin.*') ? 'side-link-active' : 'side-link-idle' }}">الفريق</a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="side-link {{ request()->routeIs('profile.*') ? 'side-link-active' : 'side-link-idle' }}">الملف الشخصي</a>
                </nav>

                <div class="border-t border-slate-100 p-4">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-full bg-slate-100 p-3 transition hover:bg-slate-200">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-slate-950 text-sm font-black text-white">{{ mb_substr(Auth::user()->name, 0, 1) }}</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-black text-slate-950">{{ Auth::user()->name }}</span>
                            <span class="block truncate text-xs font-semibold text-slate-500">{{ Auth::user()->email }}</span>
                        </span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-full border-2 border-slate-950 px-4 py-2.5 text-sm font-black text-slate-950 transition hover:bg-slate-950 hover:text-white">
                            تسجيل الخروج
                        </button>
                    </form>
                </div>
            </aside>

            <div class="lg:pr-72">
                <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
                    <div class="flex min-h-20 flex-col gap-3 px-4 py-3 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="open = true" class="grid h-11 w-11 place-items-center rounded-full border-2 border-slate-950 text-slate-950 lg:hidden">
                                <span class="sr-only">فتح القائمة</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75ZM2 10a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" /></svg>
                            </button>
                            <div>
                                <p class="text-sm font-black text-slate-950">مساحة العمل</p>
                                <p class="text-xs font-bold text-slate-500">{{ now()->format('Y-m-d') }}</p>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col gap-3 lg:max-w-3xl lg:flex-row lg:items-center lg:justify-end">
                            <form method="GET" action="{{ route('tasks.index') }}" class="relative flex-1 lg:max-w-md">
                                <input name="search" type="search" placeholder="بحث سريع في المهام..." class="w-full !rounded-full py-3 pr-5 pl-11 text-sm">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.473 9.764l2.631 2.631a.75.75 0 1 0 1.061-1.06l-2.631-2.632A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0A4 4 0 0 1 5 9Z" clip-rule="evenodd" /></svg>
                                </span>
                            </form>

                            <div class="flex items-center gap-2">
                                <div class="hidden rounded-full bg-slate-100 px-4 py-2.5 text-sm font-black text-slate-700 sm:block">
                                    هذا الأسبوع
                                </div>
                                <details class="relative">
                                    <summary class="grid h-11 w-11 cursor-pointer list-none place-items-center rounded-full border-2 border-slate-950 text-sm font-black text-slate-950">
                                        {{ auth()->user()->unreadNotifications()->count() }}
                                    </summary>
                                    <div class="popover absolute left-0 z-50 mt-3 w-96 max-w-[85vw] overflow-hidden">
                                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                            <h3 class="font-black text-slate-950">الإشعارات</h3>
                                            <form method="POST" action="{{ route('notifications.readAll') }}">
                                                @csrf
                                                <button class="text-xs font-black text-slate-950 underline">قراءة الكل</button>
                                            </form>
                                        </div>
                                        <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                                            @forelse(auth()->user()->notifications()->latest()->take(8)->get() as $notification)
                                                <a href="{{ route('notifications.read', $notification->id) }}" class="block px-4 py-3 transition hover:bg-slate-50">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <p class="text-sm font-black text-slate-950">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                                            <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $notification->data['body'] ?? '' }}</p>
                                                        </div>
                                                        @if(! $notification->read_at)<span class="mt-1 h-2 w-2 rounded-full bg-slate-950"></span>@endif
                                                    </div>
                                                    <p class="mt-2 text-xs font-bold text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                                                </a>
                                            @empty
                                                <div class="px-4 py-8 text-center text-sm font-semibold text-slate-500">لا توجد إشعارات.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </details>
                                <a href="{{ route('profile.edit') }}" class="grid h-11 w-11 place-items-center rounded-full bg-slate-950 text-sm font-black text-white">{{ mb_substr(Auth::user()->name, 0, 1) }}</a>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="app-container">
                    @yield('content')
                    {{ $slot ?? '' }}
                </main>
            </div>

            <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/50 lg:hidden" @click="open = false"></div>
            <aside x-show="open" x-transition class="fixed inset-y-0 right-0 z-50 w-80 bg-white shadow-2xl lg:hidden">
                <div class="flex h-16 items-center justify-between border-b border-slate-100 px-5">
                    <div class="flex items-center gap-3">
                        <span class="brand-mark h-10 w-10">TM</span>
                        <span class="font-black text-slate-950">مدير الأعمال</span>
                    </div>
                    <button type="button" @click="open = false" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">إغلاق</button>
                </div>
                <nav class="space-y-2 p-4">
                    <a href="{{ route('dashboard') }}" class="side-link {{ request()->routeIs('dashboard') ? 'side-link-active' : 'side-link-idle' }}">لوحة التحكم</a>
                    <a href="{{ route('projects.index') }}" class="side-link {{ request()->routeIs('projects.*') ? 'side-link-active' : 'side-link-idle' }}">المشاريع</a>
                    <a href="{{ route('tasks.index') }}" class="side-link {{ request()->routeIs('tasks.*') ? 'side-link-active' : 'side-link-idle' }}">المهام</a>
                    <a href="{{ route('meetings.index') }}" class="side-link {{ request()->routeIs('meetings.*') ? 'side-link-active' : 'side-link-idle' }}">الاجتماعات</a>
                    @if(auth()->user()?->role === 'admin')
                        <a href="{{ route('admin.users') }}" class="side-link {{ request()->routeIs('admin.*') ? 'side-link-active' : 'side-link-idle' }}">الفريق</a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="side-link side-link-idle">الملف الشخصي</a>
                </nav>
            </aside>
        </div>

        @stack('scripts')
    </body>
</html>
