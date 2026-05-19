@extends('layouts.app')

@section('content')
    @php
        $maxWeekly = max(1, $weeklyProgress->max('count'));
        $completedDegrees = round(($stats['completed_tasks'] / max(1, $stats['total_tasks'])) * 360);
        $inProgressDegrees = round(($stats['in_progress_tasks'] / max(1, $stats['total_tasks'])) * 360);
        $reviewDegrees = round(($stats['review_tasks'] / max(1, $stats['total_tasks'])) * 360);

        $kpis = [
            ['label' => 'المشاريع النشطة', 'value' => $stats['active_projects'], 'hint' => $stats['projects_count'].' إجمالي المشاريع', 'tone' => 'text-blue-700', 'bg' => 'bg-blue-50'],
            ['label' => 'نسبة الإنجاز', 'value' => $stats['completion_percentage'].'%', 'hint' => $stats['completed_tasks'].' مهمة مكتملة', 'tone' => 'text-emerald-700', 'bg' => 'bg-emerald-50'],
            ['label' => 'قيد التنفيذ', 'value' => $stats['in_progress_tasks'], 'hint' => $stats['review_tasks'].' بانتظار المراجعة', 'tone' => 'text-indigo-700', 'bg' => 'bg-indigo-50'],
            ['label' => 'مؤشرات تحتاج متابعة', 'value' => $stats['overdue_tasks'] + $stats['open_bugs'], 'hint' => $stats['overdue_tasks'].' متأخرة / '.$stats['open_bugs'].' أخطاء', 'tone' => 'text-rose-700', 'bg' => 'bg-rose-50'],
        ];
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-white/70 bg-white/85 shadow-sm shadow-slate-200/70 backdrop-blur-xl">
            <div class="grid gap-6 p-6 xl:grid-cols-[1fr_360px] xl:items-center">
                <div>
                    <p class="text-sm font-bold text-blue-600">لوحة التحكم</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">مرحباً {{ $user->name }}</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">
                        نظرة تنفيذية على حالة المشاريع والمهام والمخاطر اليومية داخل مساحة العمل.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('tasks.index') }}" class="btn-primary">فتح لوحة المهام</a>
                        <a href="{{ route('projects.index') }}" class="btn-secondary">إدارة المشاريع</a>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-slate-500">صحة مساحة العمل</p>
                            <p class="mt-2 text-4xl font-black text-slate-950">{{ $stats['completion_percentage'] }}%</p>
                        </div>
                        <div class="grid h-24 w-24 place-items-center rounded-full" style="background: conic-gradient(#34c759 0deg {{ $completedDegrees }}deg, #007aff {{ $completedDegrees }}deg {{ $completedDegrees + $inProgressDegrees }}deg, #af52de {{ $completedDegrees + $inProgressDegrees }}deg {{ $completedDegrees + $inProgressDegrees + $reviewDegrees }}deg, #e5e7eb {{ $completedDegrees + $inProgressDegrees + $reviewDegrees }}deg 360deg)">
                            <div class="h-16 w-16 rounded-full bg-white shadow-inner"></div>
                        </div>
                    </div>
                    <div class="mt-5 grid grid-cols-3 gap-2 text-center text-xs font-bold text-slate-500">
                        <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-100">
                            <p class="text-lg font-black text-slate-950">{{ $stats['total_tasks'] }}</p>
                            <p class="mt-1">مهام</p>
                        </div>
                        <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-100">
                            <p class="text-lg font-black text-rose-700">{{ $stats['overdue_tasks'] }}</p>
                            <p class="mt-1">متأخرة</p>
                        </div>
                        <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-100">
                            <p class="text-lg font-black text-blue-700">{{ $stats['due_today_tasks'] }}</p>
                            <p class="mt-1">اليوم</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($kpis as $kpi)
                <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-sm shadow-slate-200/70 backdrop-blur-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-slate-500">{{ $kpi['label'] }}</p>
                            <p class="mt-3 text-3xl font-black {{ $kpi['tone'] }}">{{ $kpi['value'] }}</p>
                        </div>
                        <span class="h-11 w-11 rounded-2xl {{ $kpi['bg'] }} ring-1 ring-white"></span>
                    </div>
                    <p class="mt-4 text-xs font-bold text-slate-400">{{ $kpi['hint'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.4fr_0.6fr]">
            <div class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-sm shadow-slate-200/70 backdrop-blur-xl">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">الإنتاجية الأسبوعية</h2>
                        <p class="mt-1 text-sm text-slate-500">المهام المكتملة خلال آخر 7 أيام</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-600">{{ $stats['completed_tasks'] }} مكتملة</span>
                </div>

                <div class="mt-8 flex h-64 items-end gap-3">
                    @foreach($weeklyProgress as $item)
                        <div class="flex flex-1 flex-col items-center gap-3">
                            <div class="flex w-full items-end rounded-2xl bg-slate-100/80 p-1" style="height: 210px">
                                <div class="w-full rounded-2xl bg-blue-600 shadow-sm shadow-blue-500/20" style="height: {{ max(10, ($item['count'] / $maxWeekly) * 198) }}px"></div>
                            </div>
                            <span class="text-xs font-bold text-slate-500">{{ $item['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-sm shadow-slate-200/70 backdrop-blur-xl">
                <h2 class="text-lg font-black text-slate-950">توزيع المهام</h2>
                <p class="mt-1 text-sm text-slate-500">حسب مرحلة التنفيذ</p>

                <div class="mt-6 space-y-4">
                    @foreach($taskStatusChart as $slice)
                        @php $percent = round(($slice['count'] / max(1, $stats['total_tasks'])) * 100); @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full" style="background: {{ $slice['color'] }}"></span>
                                    <span class="font-bold text-slate-600">{{ $slice['label'] }}</span>
                                </div>
                                <span class="font-black text-slate-950">{{ $slice['count'] }}</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full" style="width: {{ $percent }}%; background: {{ $slice['color'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr_380px]">
            <div class="rounded-3xl border border-white/70 bg-white/85 shadow-sm shadow-slate-200/70 backdrop-blur-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">المهام ذات الأولوية</h2>
                        <p class="mt-1 text-sm text-slate-500">أقرب مواعيد التسليم والمهام التي تحتاج متابعة</p>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="text-sm font-black text-blue-700">عرض الكل</a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($stats['due_tasks'] as $task)
                        <div class="grid gap-4 px-6 py-4 md:grid-cols-[1fr_150px_130px] md:items-center">
                            <div>
                                <p class="font-black text-slate-950">{{ $task->title }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $task->project?->name ?? 'بدون مشروع' }} · {{ $task->user?->name ?? 'غير محدد' }}</p>
                            </div>
                            <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-100">{{ $task->statusLabel() }}</span>
                            <span class="text-sm font-bold {{ $task->due_at?->isPast() ? 'text-rose-700' : 'text-slate-500' }}">{{ $task->due_at?->format('Y-m-d') ?? '-' }}</span>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center text-sm font-semibold text-slate-500">لا توجد مهام مستحقة حالياً.</div>
                    @endforelse
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-sm shadow-slate-200/70 backdrop-blur-xl">
                    <h2 class="text-lg font-black text-slate-950">السبرنت النشط</h2>
                    @if($stats['active_sprint'])
                        <p class="mt-4 text-base font-black text-slate-950">{{ $stats['active_sprint']->name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $stats['active_sprint']->project?->name }}</p>
                        <div class="mt-5 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-blue-600" style="width: {{ $stats['active_sprint']->progress() }}%"></div>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-sm">
                            <span class="font-bold text-slate-500">الإنجاز</span>
                            <span class="font-black text-blue-700">{{ $stats['active_sprint']->progress() }}%</span>
                        </div>
                    @else
                        <p class="mt-4 text-sm leading-6 text-slate-500">لا يوجد Sprint نشط حالياً.</p>
                    @endif
                </div>

                <div class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-sm shadow-slate-200/70 backdrop-blur-xl">
                    <h2 class="text-lg font-black text-slate-950">الأيام القادمة</h2>
                    <div class="mt-4 grid grid-cols-7 gap-2">
                        @foreach($calendarDays as $day)
                            <div class="rounded-2xl border {{ $day['is_today'] ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-white/80' }} p-2 text-center">
                                <p class="text-[11px] font-bold text-slate-400">{{ $day['name'] }}</p>
                                <p class="mt-1 text-sm font-black text-slate-950">{{ $day['day'] }}</p>
                                @if($day['tasks_due'] > 0)
                                    <p class="mt-1 text-[11px] font-black text-blue-700">{{ $day['tasks_due'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-sm shadow-slate-200/70 backdrop-blur-xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-black text-slate-950">المشاريع الحديثة</h2>
                    <a href="{{ route('projects.index') }}" class="text-sm font-black text-blue-700">إدارة</a>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse($stats['recent_projects'] as $project)
                        <a href="{{ route('projects.index') }}" class="block rounded-2xl border border-slate-100 bg-white/70 p-4 transition hover:bg-white">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-black text-slate-950">{{ $project->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $project->client_name ?: 'بدون عميل محدد' }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600">{{ $project->tasks_count }} مهمة</span>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm font-semibold text-slate-500">لا توجد مشاريع بعد.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-sm shadow-slate-200/70 backdrop-blur-xl">
                <h2 class="text-lg font-black text-slate-950">{{ $user->role === 'admin' ? 'تحميل الفريق' : 'الإشعارات الأخيرة' }}</h2>

                @if($user->role === 'admin')
                    <div class="mt-5 space-y-4">
                        @forelse($teamWorkload as $member)
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 place-items-center rounded-2xl bg-blue-50 text-xs font-black text-blue-700 ring-1 ring-blue-100">{{ mb_substr($member->name, 0, 1) }}</span>
                                        <span class="font-black text-slate-950">{{ $member->name }}</span>
                                    </div>
                                    <span class="font-black text-slate-600">{{ $member->open_tasks_count }}</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-blue-600" style="width: {{ min(100, $member->open_tasks_count * 12) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm font-semibold text-slate-500">لا توجد بيانات فريق.</p>
                        @endforelse
                    </div>
                @else
                    <div class="mt-5 space-y-3">
                        @forelse($stats['recent_notifications'] as $notification)
                            <a href="{{ route('notifications.read', $notification->id) }}" class="block rounded-2xl border border-slate-100 bg-white/70 p-4 transition hover:bg-white">
                                <p class="text-sm font-black text-slate-950">{{ $notification->data['title'] ?? 'إشعار' }}</p>
                                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $notification->data['body'] ?? '' }}</p>
                            </a>
                        @empty
                            <p class="text-sm font-semibold text-slate-500">لا توجد إشعارات حديثة.</p>
                        @endforelse
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
