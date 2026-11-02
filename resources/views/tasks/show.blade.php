@extends('layouts.app')

@section('content')
    @php
        $isAdmin = auth()->user()->role === 'admin';
        $canManage = $task->canBeManagedBy(auth()->user());
        $statusTones = [
            'todo' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'in_progress' => 'bg-amber-50 text-amber-800 ring-amber-200',
            'review' => 'bg-sky-50 text-sky-800 ring-sky-200',
            'completed' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        ];
        $priorityTones = [
            'high' => 'bg-red-50 text-red-700 ring-red-100',
            'medium' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'low' => 'bg-slate-50 text-slate-500 ring-slate-100',
        ];
        $checklistTotal = $task->checklistItems->count();
        $checklistDone = $task->checklistItems->where('is_done', true)->count();
        $checklistProgress = $checklistTotal > 0 ? (int) round(($checklistDone / $checklistTotal) * 100) : 0;
        $loggedHours = $task->loggedHours();
        $estimatedHours = $task->estimated_hours !== null ? (float) $task->estimated_hours : null;
        $timeProgress = $estimatedHours ? min(100, (int) round(($loggedHours / $estimatedHours) * 100)) : null;
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-500">
            <a href="{{ route('tasks.index') }}" class="hover:text-slate-950">المهام</a>
            @if($task->project)
                <span>/</span>
                <a href="{{ route('tasks.index', ['project_id' => $task->project_id]) }}" class="hover:text-slate-950">{{ $task->project->name }}</a>
            @endif
            <span>/</span>
            <span class="text-slate-950">#{{ $task->id }}</span>
        </div>

        @if(session('success'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">
                <p class="font-bold">يرجى مراجعة البيانات المدخلة.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
            <div class="space-y-6">
                <section class="page-hero p-6 sm:p-7">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                        <span class="rounded-full px-3 py-1.5 ring-1 {{ $statusTones[$task->status] ?? $statusTones['todo'] }}">{{ $task->statusLabel() }}</span>
                        <span class="rounded-full px-3 py-1.5 ring-1 {{ $priorityTones[$task->priority] ?? $priorityTones['medium'] }}">أولوية {{ $task->priorityLabel() }}</span>
                        @if($task->isOverdue())
                            <span class="rounded-full bg-red-600 px-3 py-1.5 text-white">متأخرة</span>
                        @endif
                    </div>
                    <h1 class="hero-title {{ $task->is_completed ? 'text-slate-400 line-through' : '' }}">{{ $task->title }}</h1>
                    @if($task->description)
                        <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $task->description }}</p>
                    @else
                        <p class="mt-4 text-sm text-slate-400">لا يوجد وصف لهذه المهمة.</p>
                    @endif
                </section>

                <section id="checklist" class="panel p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-black text-slate-950">قائمة التحقق (المهام الفرعية)</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $checklistDone }} من {{ $checklistTotal }} منجزة</p>
                        </div>
                        <span class="text-sm font-black text-slate-950">{{ $checklistProgress }}%</span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-emerald-600" style="width: {{ $checklistProgress }}%"></div>
                    </div>

                    <ul class="mt-4 divide-y divide-slate-100">
                        @forelse($task->checklistItems as $item)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <form method="POST" action="{{ route('tasks.checklist.toggle', [$task, $item]) }}" class="flex min-w-0 items-center gap-3">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="grid h-6 w-6 shrink-0 place-items-center rounded-md border-2 {{ $item->is_done ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300 text-transparent hover:border-slate-950' }}" aria-label="تبديل حالة البند">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <span class="truncate text-sm font-semibold {{ $item->is_done ? 'text-slate-400 line-through' : 'text-slate-950' }}">{{ $item->title }}</span>
                                </form>
                                <div class="flex shrink-0 items-center gap-3">
                                    @if($item->is_done && $item->completer)
                                        <span class="hidden text-xs text-slate-400 sm:inline">{{ $item->completer->name }}</span>
                                    @endif
                                    <form method="POST" action="{{ route('tasks.checklist.destroy', [$task, $item]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-slate-400 hover:text-red-600">حذف</button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="py-4 text-sm text-slate-400">لا توجد بنود بعد. قسّم المهمة إلى خطوات صغيرة قابلة للتتبع.</li>
                        @endforelse
                    </ul>

                    <form method="POST" action="{{ route('tasks.checklist.store', $task) }}" class="mt-3 flex gap-2">
                        @csrf
                        <input name="title" type="text" placeholder="بند جديد..." required maxlength="255" class="flex-1">
                        <button type="submit" class="btn-dark">إضافة</button>
                    </form>
                </section>

                <section id="comments" class="panel p-5">
                    <h2 class="text-base font-black text-slate-950">التعليقات <span class="text-slate-400">({{ $task->comments->count() }})</span></h2>

                    <div class="mt-4 space-y-4">
                        @forelse($task->comments as $comment)
                            <article class="flex gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-950 text-xs font-black text-white">{{ mb_substr($comment->user?->name ?? '?', 0, 1) }}</span>
                                <div class="min-w-0 flex-1 rounded-2xl bg-slate-50 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-black text-slate-950">{{ $comment->user?->name ?? 'مستخدم محذوف' }}</p>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-bold text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                                            @if($isAdmin || $comment->user_id === auth()->id())
                                                <form method="POST" action="{{ route('tasks.comments.destroy', [$task, $comment]) }}" onsubmit="return confirm('حذف التعليق؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-bold text-slate-400 hover:text-red-600">حذف</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $comment->body }}</p>
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-slate-400">لا توجد تعليقات. ابدأ النقاش مع الفريق حول هذه المهمة.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('tasks.comments.store', $task) }}" class="mt-5 space-y-3">
                        @csrf
                        <textarea name="body" rows="3" required maxlength="3000" placeholder="اكتب تعليقاً..." class="block w-full">{{ old('body') }}</textarea>
                        <button type="submit" class="btn-primary">إرسال التعليق</button>
                    </form>
                </section>

                <section class="panel p-5">
                    <h2 class="text-base font-black text-slate-950">سجل النشاط</h2>
                    <ol class="mt-4 space-y-3 border-r-2 border-slate-100 pr-4">
                        @forelse($task->activities as $activity)
                            <li class="relative">
                                <span class="absolute -right-[23px] top-1.5 h-2.5 w-2.5 rounded-full bg-slate-950"></span>
                                <p class="text-sm text-slate-700"><span class="font-black text-slate-950">{{ $activity->user?->name ?? 'النظام' }}</span> {{ $activity->description }}</p>
                                <p class="text-xs font-bold text-slate-400">{{ $activity->created_at->diffForHumans() }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-slate-400">لا يوجد نشاط مسجل بعد.</li>
                        @endforelse
                    </ol>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="panel p-5">
                    <h2 class="text-base font-black text-slate-950">التفاصيل</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">المسؤول</dt><dd class="font-bold text-slate-950">{{ $task->user?->name ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">المشروع</dt><dd class="font-bold text-slate-950">{{ $task->project?->name ?? 'بدون مشروع' }}</dd></div>
                        @if($task->sprint)
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">السبرنت</dt><dd class="font-bold text-slate-950">{{ $task->sprint->name }}</dd></div>
                        @endif
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">تاريخ الاستحقاق</dt><dd class="font-bold {{ $task->isOverdue() ? 'text-red-600' : 'text-slate-950' }}">{{ $task->due_at?->format('Y-m-d') ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">أُنشئت</dt><dd class="font-bold text-slate-950">{{ $task->created_at->format('Y-m-d') }}</dd></div>
                        @if($task->bugReports->isNotEmpty())
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">مشاكل مرتبطة</dt><dd class="font-bold text-slate-950">{{ $task->bugReports->count() }}</dd></div>
                        @endif
                    </dl>

                    <form method="POST" action="{{ route('tasks.update', $task) }}" class="mt-5 space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="redirect_to" value="show">
                        @if($canManage)
                            <input type="hidden" name="title" value="{{ $task->title }}">
                            <input type="hidden" name="description" value="{{ $task->description }}">
                            <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                            <input type="hidden" name="user_id" value="{{ $task->user_id }}">
                            <input type="hidden" name="due_at" value="{{ $task->due_at?->format('Y-m-d') }}">
                            <input type="hidden" name="estimated_hours" value="{{ $task->estimated_hours }}">
                            <label class="block text-xs font-black text-slate-500">الأولوية</label>
                            <select name="priority" class="block w-full">
                                @foreach($priorityLabels as $value => $label)
                                    <option value="{{ $value }}" @selected($task->priority === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                        <label class="block text-xs font-black text-slate-500">الحالة</label>
                        <select name="status" class="block w-full">
                            @foreach($taskStatuses as $value => $label)
                                <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-dark w-full">تحديث</button>
                    </form>
                </section>

                <section id="time" class="panel p-5">
                    <h2 class="text-base font-black text-slate-950">تتبع الوقت</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-100 p-3">
                            <p class="text-xs font-bold text-slate-500">المسجل</p>
                            <p class="mt-1 text-2xl font-black text-slate-950">{{ rtrim(rtrim(number_format($loggedHours, 2), '0'), '.') }}<span class="text-sm"> س</span></p>
                        </div>
                        <div class="rounded-2xl bg-slate-100 p-3">
                            <p class="text-xs font-bold text-slate-500">التقدير</p>
                            <p class="mt-1 text-2xl font-black text-slate-950">{{ $estimatedHours !== null ? rtrim(rtrim(number_format($estimatedHours, 2), '0'), '.') : '-' }}<span class="text-sm"> س</span></p>
                        </div>
                    </div>
                    @if($timeProgress !== null)
                        <div class="mt-3 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full {{ $loggedHours > $estimatedHours ? 'bg-red-600' : 'bg-slate-950' }}" style="width: {{ $timeProgress }}%"></div>
                        </div>
                        @if($loggedHours > $estimatedHours)
                            <p class="mt-2 text-xs font-bold text-red-600">تجاوز الوقت المقدر للمهمة.</p>
                        @endif
                    @endif

                    <form method="POST" action="{{ route('tasks.time-logs.store', $task) }}" class="mt-4 grid grid-cols-2 gap-2">
                        @csrf
                        <input name="hours" type="number" step="0.25" min="0.25" max="24" placeholder="ساعات" required value="{{ old('hours') }}">
                        <input name="logged_on" type="date" required value="{{ old('logged_on', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}">
                        <input name="note" type="text" maxlength="255" placeholder="ملاحظة (اختياري)" class="col-span-2" value="{{ old('note') }}">
                        <button type="submit" class="btn-primary col-span-2">تسجيل الوقت</button>
                    </form>

                    <ul class="mt-4 divide-y divide-slate-100">
                        @foreach($task->timeLogs as $log)
                            <li class="flex items-start justify-between gap-3 py-2.5 text-sm">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-950">{{ rtrim(rtrim(number_format($log->hours(), 2), '0'), '.') }} س · {{ $log->user?->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ $log->logged_on->format('Y-m-d') }}@if($log->note) · {{ $log->note }}@endif</p>
                                </div>
                                @if($isAdmin || $log->user_id === auth()->id())
                                    <form method="POST" action="{{ route('tasks.time-logs.destroy', [$task, $log]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-slate-400 hover:text-red-600">حذف</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            </aside>
        </div>
    </div>
@endsection
