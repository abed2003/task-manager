@extends('layouts.app')

@section('content')
    @php
        $stageMeta = [
            'todo' => [
                'label' => $taskStatuses['todo'] ?? 'لم تبدأ',
                'tone' => 'slate',
                'accent' => 'bg-slate-500',
                'soft' => 'bg-slate-100',
                'border' => 'border-slate-200',
                'text' => 'text-slate-700',
                'ring' => 'ring-slate-200',
                'hint' => 'جاهزة للتنفيذ',
            ],
            'in_progress' => [
                'label' => $taskStatuses['in_progress'] ?? 'قيد التنفيذ',
                'tone' => 'amber',
                'accent' => 'bg-amber-500',
                'soft' => 'bg-amber-50',
                'border' => 'border-amber-200',
                'text' => 'text-amber-800',
                'ring' => 'ring-amber-200',
                'hint' => 'يتم العمل عليها',
            ],
            'review' => [
                'label' => $taskStatuses['review'] ?? 'قيد المراجعة',
                'tone' => 'sky',
                'accent' => 'bg-sky-500',
                'soft' => 'bg-sky-50',
                'border' => 'border-sky-200',
                'text' => 'text-sky-800',
                'ring' => 'ring-sky-200',
                'hint' => 'بانتظار الفحص',
            ],
            'completed' => [
                'label' => $taskStatuses['completed'] ?? 'مكتملة',
                'tone' => 'emerald',
                'accent' => 'bg-emerald-600',
                'soft' => 'bg-emerald-50',
                'border' => 'border-emerald-200',
                'text' => 'text-emerald-800',
                'ring' => 'ring-emerald-200',
                'hint' => 'جاهزة للإغلاق',
            ],
        ];

        $priorityMeta = [
            'high' => 'bg-red-50 text-red-700 ring-red-100',
            'medium' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'low' => 'bg-slate-50 text-slate-500 ring-slate-100',
        ];

        $completedCount = $tasks->where('status', 'completed')->count();
        $progress = $tasks->count() > 0 ? round(($completedCount / $tasks->count()) * 100) : 0;
    @endphp

    <div class="space-y-6">
        <section class="page-hero">
            <div class="grid gap-6 p-6 sm:p-7 lg:grid-cols-[1fr_340px] lg:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-950 px-3 py-1.5 text-xs font-black text-white">Workflow Board</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700">{{ auth()->user()->role === 'admin' ? 'كل الفريق' : 'مهامي فقط' }}</span>
                    </div>
                    <h1 class="hero-title">لوحة تنفيذ المهام</h1>
                    <p class="hero-subtitle">
                        انقل المهمة بين المراحل بالسحب والإفلات. اللوحة مرتبة كسير عمل واحد من التخطيط إلى الإغلاق، وكل مستخدم يرى المهام المسندة له فقط.
                    </p>
                </div>

                <div class="rounded-[24px] bg-slate-100 p-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-bold text-slate-600">نسبة الإنجاز</span>
                        <span class="font-black text-slate-950">{{ $progress }}%</span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-white">
                        <div class="h-2 rounded-full bg-slate-950" style="width: {{ $progress }}%"></div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl bg-white p-3 shadow-sm">
                            <p class="text-xs font-bold text-slate-500">إجمالي المهام</p>
                            <p class="mt-1 text-2xl font-black text-slate-950">{{ $tasks->count() }}</p>
                        </div>
                        <div class="rounded-2xl bg-white p-3 shadow-sm">
                            <p class="text-xs font-bold text-slate-500">المكتملة</p>
                            <p class="mt-1 text-2xl font-black text-emerald-700">{{ $completedCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid border-t border-slate-100 md:grid-cols-4">
                @foreach($stageMeta as $status => $meta)
                    <div class="relative border-slate-100 px-5 py-4 md:border-l md:last:border-l-0">
                        @if(! $loop->last)
                            <span class="absolute left-[-10px] top-1/2 z-10 hidden h-5 w-5 -translate-y-1/2 rotate-45 border-l border-t border-slate-100 bg-white md:block"></span>
                        @endif
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full {{ $meta['accent'] }}"></span>
                            <div>
                                <h2 class="text-sm font-black text-slate-950">{{ $meta['label'] }}</h2>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $meta['hint'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel p-5">
            <form method="GET" action="{{ route('tasks.index') }}" class="space-y-4">
                <div>
                    <h2 class="text-sm font-black text-slate-950">تصفية اللوحة</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $selectedProject ? 'المعروض الآن: ' . $selectedProject->name : 'ابحث وصفِّ المهام حسب المشروع أو الأولوية أو تاريخ الاستحقاق.' }}
                    </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 {{ auth()->user()->role === 'admin' ? 'lg:grid-cols-6' : 'lg:grid-cols-5' }}">
                    <input name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="بحث في العنوان والوصف..." class="lg:col-span-2">
                    <select id="project_filter" name="project_id" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    <select name="priority" onchange="this.form.submit()">
                        <option value="">كل الأولويات</option>
                        @foreach($priorityLabels as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if(auth()->user()->role === 'admin')
                        <select name="assignee" onchange="this.form.submit()">
                            <option value="">كل أعضاء الفريق</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((int) ($filters['assignee'] ?? 0) === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="due" onchange="this.form.submit()">
                        <option value="">كل التواريخ</option>
                        <option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>المتأخرة</option>
                        <option value="today" @selected(($filters['due'] ?? '') === 'today')>مستحقة اليوم</option>
                        <option value="week" @selected(($filters['due'] ?? '') === 'week')>خلال 7 أيام</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-dark">تطبيق</button>
                    @if($selectedProjectId || array_filter($filters))
                        <a href="{{ route('tasks.index') }}" class="btn-secondary">
                            إلغاء الفلاتر
                        </a>
                    @endif
                </div>
            </form>
        </section>

        @if(session('success'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        <div id="task-board-alert" class="hidden rounded-2xl px-4 py-3 text-sm font-semibold"></div>

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

        @if(auth()->user()->role === 'admin')
            <details class="panel">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
                    <div>
                        <h2 class="text-base font-black text-slate-950">إسناد مهمة جديدة</h2>
                        <p class="mt-1 text-sm text-slate-500">أضف مهمة إلى مشروع واختر المستخدم المسؤول عنها.</p>
                    </div>
                    <span class="rounded-full bg-slate-950 px-4 py-2 text-sm font-black text-white">إضافة</span>
                </summary>
                <form method="POST" action="{{ route('tasks.store', array_filter(['project_id' => $selectedProjectId])) }}" class="grid gap-3 border-t border-slate-100 p-5 lg:grid-cols-6">
                    @csrf
                    <input name="title" type="text" value="{{ old('title') }}" placeholder="عنوان المهمة" required class="lg:col-span-2">
                    <select name="project_id">
                        <option value="">بدون مشروع</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected((int) old('project_id', $selectedProjectId) === $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    <select name="user_id" required>
                        <option value="">المستخدم</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <select name="status">
                        @foreach($taskStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'todo') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="priority">
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>متوسطة</option>
                        <option value="high" @selected(old('priority') === 'high')>عالية</option>
                        <option value="low" @selected(old('priority') === 'low')>منخفضة</option>
                    </select>
                    <textarea name="description" rows="2" placeholder="تفاصيل المهمة" class="lg:col-span-4">{{ old('description') }}</textarea>
                    <input name="due_at" type="date" value="{{ old('due_at') }}">
                    <input name="estimated_hours" type="number" step="0.25" min="0" value="{{ old('estimated_hours') }}" placeholder="الوقت المقدر (ساعات)">
                    <button type="submit" class="btn-primary lg:col-span-6">
                        حفظ المهمة
                    </button>
                </form>
            </details>
        @endif

        <section id="task-board" class="grid gap-4 xl:grid-cols-4">
            @foreach($stageMeta as $status => $meta)
                @php
                    $columnTasks = $tasks->where('status', $status);
                @endphp

                <div class="flex min-h-[560px] flex-col overflow-visible rounded-3xl border {{ $meta['border'] }} bg-white">
                    <div class="{{ $meta['soft'] }} border-b {{ $meta['border'] }} rounded-t-3xl px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span class="mt-1 h-3 w-3 rounded-full {{ $meta['accent'] }}"></span>
                                <div>
                                    <h2 class="text-sm font-black text-slate-950">{{ $meta['label'] }}</h2>
                                    <p class="mt-1 text-xs {{ $meta['text'] }}">{{ $meta['hint'] }}</p>
                                </div>
                            </div>
                            <span data-column-count="{{ $status }}" class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-700 ring-1 {{ $meta['ring'] }}">
                                {{ $columnTasks->count() }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="task-drop-zone flex-1 space-y-3 p-3 transition"
                        data-status="{{ $status }}"
                    >
                        @forelse($columnTasks as $task)
                            <article
                                draggable="true"
                                data-task-card
                                data-task-id="{{ $task->id }}"
                                data-status="{{ $task->status }}"
                                data-update-url="{{ route('tasks.status', $task) }}"
                                x-data="{ editOpen: false }"
                                class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-950 hover:shadow-md"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        @if($task->project)
                                            <p class="mb-2 truncate text-xs font-black text-slate-500">{{ $task->project->name }}</p>
                                        @endif
                                        <h3 data-task-title class="text-sm font-bold leading-6 {{ $task->is_completed ? 'text-slate-400 line-through' : 'text-slate-950' }}"><a href="{{ route('tasks.show', $task) }}" draggable="false" class="hover:underline">{{ $task->title }}</a></h3>
                                    </div>
                                    <span class="grid h-8 w-8 shrink-0 cursor-grab place-items-center rounded-xl border border-slate-200 text-slate-400 transition group-hover:border-slate-950 group-hover:text-slate-950 active:cursor-grabbing">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M7 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM16 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM7 10a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM16 10a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM7 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM16 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                                        </svg>
                                    </span>
                                </div>

                                @if($task->description)
                                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">{{ $task->description }}</p>
                                @endif

                                <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                    <span data-status-badge class="rounded-full px-2.5 py-1 ring-1 {{ $meta['soft'] }} {{ $meta['text'] }} {{ $meta['ring'] }}">{{ $task->statusLabel() }}</span>
                                    <span class="rounded-full px-2.5 py-1 ring-1 {{ $priorityMeta[$task->priority] ?? $priorityMeta['medium'] }}">{{ $task->priorityLabel() }}</span>
                                    @if(auth()->user()->role === 'admin' && $task->user)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700">{{ $task->user->name }}</span>
                                    @endif
                                    @if($task->due_at)
                                        <span class="rounded-full px-2.5 py-1 {{ $task->isOverdue() ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700' }}" @if($task->isOverdue()) title="متأخرة" @endif>{{ $task->due_at->format('Y-m-d') }}</span>
                                    @endif
                                    @if($task->checklist_items_count)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700" title="قائمة التحقق">☑ {{ $task->done_checklist_items_count }}/{{ $task->checklist_items_count }}</span>
                                    @endif
                                    @if($task->comments_count)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700" title="التعليقات">💬 {{ $task->comments_count }}</span>
                                    @endif
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                                    <a href="{{ route('tasks.show', $task) }}" draggable="false" class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-bold text-white transition hover:bg-slate-800">
                                        التفاصيل
                                    </a>
                                    <button
                                        type="button"
                                        @click="editOpen = true"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        تعديل
                                    </button>

                                    <div
                                        x-show="editOpen"
                                        x-cloak
                                        style="display: none"
                                        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                                        @keydown.escape.window="editOpen = false"
                                    >
                                        <div class="fixed inset-0 bg-slate-950/50" @click="editOpen = false"></div>
                                        <div class="popover relative z-10 w-full max-w-md p-5" @click.stop>
                                            <div class="mb-4 flex items-center justify-between">
                                                <h3 class="text-lg font-black text-slate-950">تعديل المهمة</h3>
                                                <button type="button" @click="editOpen = false" class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-950">
                                                    &times;
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('tasks.update', array_filter(['task' => $task->id, 'project_id' => $selectedProjectId])) }}" class="space-y-3">
                                                @csrf
                                                @method('PATCH')

                                                @if(auth()->user()->role === 'admin')
                                                    <input name="title" type="text" value="{{ $task->title }}" required class="block w-full">
                                                    <textarea name="description" rows="3" class="block w-full">{{ $task->description }}</textarea>
                                                    <select name="project_id" class="block w-full">
                                                        <option value="">بدون مشروع</option>
                                                        @foreach($projects as $project)
                                                            <option value="{{ $project->id }}" @selected($task->project_id === $project->id)>{{ $project->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="user_id" required class="block w-full">
                                                        @foreach($users as $user)
                                                            <option value="{{ $user->id }}" @selected($task->user_id === $user->id)>{{ $user->name }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif

                                                <select name="status" class="block w-full">
                                                    @foreach($taskStatuses as $value => $statusLabel)
                                                        <option value="{{ $value }}" @selected($task->status === $value)>{{ $statusLabel }}</option>
                                                    @endforeach
                                                </select>

                                                @if(auth()->user()->role === 'admin')
                                                    <select name="priority" class="block w-full">
                                                        <option value="medium" @selected($task->priority === 'medium')>متوسطة</option>
                                                        <option value="high" @selected($task->priority === 'high')>عالية</option>
                                                        <option value="low" @selected($task->priority === 'low')>منخفضة</option>
                                                    </select>
                                                    <input name="due_at" type="date" value="{{ $task->due_at?->format('Y-m-d') }}" class="block w-full">
                                                    <input name="estimated_hours" type="number" step="0.25" min="0" value="{{ $task->estimated_hours }}" placeholder="الوقت المقدر (ساعات)" class="block w-full">
                                                @endif

                                                <button type="submit" class="btn-dark w-full">
                                                    حفظ
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    @if(auth()->user()->role === 'admin')
                                        <form method="POST" action="{{ route('tasks.destroy', array_filter(['task' => $task->id, 'project_id' => $selectedProjectId])) }}" onsubmit="return confirm('هل تريد حذف هذه المهمة؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-bold text-red-700 transition hover:bg-red-100">
                                                حذف
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div data-empty-state class="rounded-2xl border border-dashed {{ $meta['border'] }} {{ $meta['soft'] }} px-4 py-10 text-center text-sm {{ $meta['text'] }}">
                                لا توجد مهام هنا
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </section>
    </div>

    @push('scripts')
        <script>
            (() => {
                const board = document.getElementById('task-board');
                const alertBox = document.getElementById('task-board-alert');
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const stageMeta = @json($stageMeta);
                let draggedCard = null;
                let sourceZone = null;

                const showAlert = (message, type = 'success') => {
                    alertBox.textContent = message;
                    alertBox.className = type === 'success'
                        ? 'rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-100'
                        : 'rounded-2xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 ring-1 ring-red-100';
                    window.setTimeout(() => alertBox.classList.add('hidden'), 2500);
                };

                const emptyStateClass = (status) => {
                    const meta = stageMeta[status] || stageMeta.todo;
                    return `rounded-2xl border border-dashed ${meta.border} ${meta.soft} px-4 py-10 text-center text-sm ${meta.text}`;
                };

                const refreshCounts = () => {
                    document.querySelectorAll('[data-column-count]').forEach((counter) => {
                        const status = counter.dataset.columnCount;
                        counter.textContent = document.querySelectorAll(`[data-task-card][data-status="${status}"]`).length;
                    });

                    document.querySelectorAll('.task-drop-zone').forEach((zone) => {
                        const hasCards = zone.querySelector('[data-task-card]');
                        let emptyState = zone.querySelector('[data-empty-state]');

                        if (hasCards && emptyState) {
                            emptyState.remove();
                        }

                        if (!hasCards && !emptyState) {
                            emptyState = document.createElement('div');
                            emptyState.dataset.emptyState = '';
                            emptyState.className = emptyStateClass(zone.dataset.status);
                            emptyState.textContent = 'لا توجد مهام هنا';
                            zone.appendChild(emptyState);
                        }
                    });
                };

                const updateCardStatus = (card, status) => {
                    const meta = stageMeta[status] || stageMeta.todo;
                    card.dataset.status = status;

                    const title = card.querySelector('[data-task-title]');
                    title.classList.toggle('line-through', status === 'completed');
                    title.classList.toggle('text-slate-400', status === 'completed');
                    title.classList.toggle('text-slate-950', status !== 'completed');

                    const badge = card.querySelector('[data-status-badge]');
                    badge.textContent = meta.label;
                    badge.className = `rounded-full px-2.5 py-1 ring-1 ${meta.soft} ${meta.text} ${meta.ring}`;
                };

                board.addEventListener('dragstart', (event) => {
                    const card = event.target.closest('[data-task-card]');
                    if (!card || event.target.closest('button, input, textarea, select, summary, details, a')) {
                        event.preventDefault();
                        return;
                    }

                    draggedCard = card;
                    sourceZone = card.closest('.task-drop-zone');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', card.dataset.taskId);
                    card.classList.add('opacity-50', 'ring-2', 'ring-slate-950');
                });

                board.addEventListener('dragend', () => {
                    if (draggedCard) {
                        draggedCard.classList.remove('opacity-50', 'ring-2', 'ring-slate-950');
                    }

                    document.querySelectorAll('.task-drop-zone').forEach((zone) => {
                        zone.classList.remove('bg-slate-100', 'ring-2', 'ring-slate-300');
                    });

                    draggedCard = null;
                    sourceZone = null;
                });

                board.addEventListener('dragover', (event) => {
                    const zone = event.target.closest('.task-drop-zone');
                    if (!zone || !draggedCard) {
                        return;
                    }

                    event.preventDefault();
                    zone.classList.add('bg-slate-100', 'ring-2', 'ring-slate-300');
                });

                board.addEventListener('dragleave', (event) => {
                    const zone = event.target.closest('.task-drop-zone');
                    if (zone && !zone.contains(event.relatedTarget)) {
                        zone.classList.remove('bg-slate-100', 'ring-2', 'ring-slate-300');
                    }
                });

                board.addEventListener('drop', async (event) => {
                    const zone = event.target.closest('.task-drop-zone');
                    if (!zone || !draggedCard) {
                        return;
                    }

                    event.preventDefault();
                    zone.classList.remove('bg-slate-100', 'ring-2', 'ring-slate-300');

                    const previousStatus = draggedCard.dataset.status;
                    const nextStatus = zone.dataset.status;

                    if (previousStatus === nextStatus) {
                        zone.appendChild(draggedCard);
                        refreshCounts();
                        return;
                    }

                    zone.appendChild(draggedCard);
                    updateCardStatus(draggedCard, nextStatus);
                    refreshCounts();

                    try {
                        const response = await fetch(draggedCard.dataset.updateUrl, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({ status: nextStatus }),
                        });

                        if (!response.ok) {
                            throw new Error('Request failed');
                        }

                        const payload = await response.json();
                        updateCardStatus(draggedCard, payload.status);
                        showAlert(payload.message || 'تم تحديث مرحلة المهمة.');
                    } catch (error) {
                        sourceZone.appendChild(draggedCard);
                        updateCardStatus(draggedCard, previousStatus);
                        refreshCounts();
                        showAlert('تعذر نقل المهمة. حاول مرة أخرى.', 'error');
                    }
                });
            })();
        </script>
    @endpush
@endsection
