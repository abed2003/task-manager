@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <section class="page-hero">
            <div class="page-hero-inner">
                <p class="hero-eyebrow">{{ $project->name }}</p>
                <h1 class="hero-title">Sprint Management</h1>
                <p class="hero-subtitle">إدارة Sprint Backlog، ربط المهام، مراجعة السبرنت، ولوحة Drag & Drop لمراحل التنفيذ.</p>
            </div>
        </section>

        @if(session('success'))<div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100"><p class="font-black">يرجى مراجعة البيانات.</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="grid gap-6 lg:grid-cols-[400px_1fr]">
            @if(auth()->user()->role === 'admin')
                <aside class="panel">
                    <div class="panel-header"><h2 class="text-lg font-black text-slate-950">Sprint جديد</h2></div>
                    <form method="POST" action="{{ route('projects.sprints.store', $project) }}" class="panel-body space-y-4">
                        @csrf
                        <div><label class="field-label">الاسم</label><input name="name" required class="mt-2 block w-full"></div>
                        <div><label class="field-label">الهدف</label><textarea name="goal" rows="3" class="mt-2 block w-full"></textarea></div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div><label class="field-label">البداية</label><input name="starts_at" type="date" required class="mt-2 block w-full"></div>
                            <div><label class="field-label">النهاية</label><input name="ends_at" type="date" required class="mt-2 block w-full"></div>
                        </div>
                        <div><label class="field-label">الحالة</label><select name="status" class="mt-2 block w-full">@foreach($sprintStatuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                        <div>
                            <label class="field-label">Sprint Backlog</label>
                            <select name="task_ids[]" multiple class="mt-2 block h-40 w-full">
                                @foreach($tasks as $task)<option value="{{ $task->id }}">{{ $task->title }}</option>@endforeach
                            </select>
                        </div>
                        <button class="btn-primary w-full">إنشاء Sprint</button>
                    </form>
                </aside>
            @endif

            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach($sprints as $sprint)
                        <div class="stat-card">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-slate-950">{{ $sprint->name }}</p>
                                <span class="status-pill bg-sky-50 text-sky-700 ring-sky-100">{{ $sprintStatuses[$sprint->status] }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">{{ $sprint->starts_at->format('Y-m-d') }} - {{ $sprint->ends_at->format('Y-m-d') }}</p>
                            <div class="mt-4 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-slate-950" style="width: {{ $sprint->progress() }}%"></div></div>
                            <p class="mt-2 text-sm font-bold text-slate-700">{{ $sprint->progress() }}% completed</p>
                        </div>
                    @endforeach
                </div>

                @if($activeSprint)
                    <article class="panel overflow-hidden">
                        <div class="panel-header flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">{{ $activeSprint->name }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $activeSprint->goal }}</p>
                            </div>
                            @if(auth()->user()->role === 'admin')
                                <form method="POST" action="{{ route('projects.sprints.update', [$project, $activeSprint]) }}" class="grid gap-2 sm:grid-cols-[150px_1fr_auto]">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status">@foreach($sprintStatuses as $value => $label)<option value="{{ $value }}" @selected($activeSprint->status === $value)>{{ $label }}</option>@endforeach</select>
                                    <input name="review_notes" value="{{ $activeSprint->review_notes }}" placeholder="Sprint Review">
                                    @foreach($activeSprint->tasks as $task)<input type="hidden" name="task_ids[]" value="{{ $task->id }}">@endforeach
                                    <button class="btn-dark">حفظ</button>
                                </form>
                            @endif
                        </div>

                        <div id="sprint-board" class="grid gap-4 p-4 xl:grid-cols-4">
                            @foreach($taskStatuses as $status => $label)
                                <div class="rounded-2xl bg-slate-50">
                                    <div class="border-b border-slate-200 px-4 py-3"><h3 class="font-black text-slate-950">{{ $label }}</h3></div>
                                    <div class="sprint-drop-zone min-h-80 space-y-3 p-3" data-status="{{ $status }}">
                                        @foreach($activeSprint->tasks->where('status', $status) as $task)
                                            <article draggable="true" data-sprint-task data-update-url="{{ route('projects.sprints.tasks.status', [$project, $activeSprint, $task]) }}" data-status="{{ $task->status }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                                <h4 class="text-sm font-black text-slate-950">{{ $task->title }}</h4>
                                                <p class="mt-2 text-xs font-bold text-slate-500">{{ $task->user?->name }}</p>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @else
                    <div class="panel px-5 py-12 text-center text-sm font-semibold text-slate-500">لا يوجد Sprint بعد.</div>
                @endif
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            (() => {
                const board = document.getElementById('sprint-board');
                if (!board) return;
                const token = document.querySelector('meta[name="csrf-token"]').content;
                let dragged = null;
                let source = null;

                board.addEventListener('dragstart', (event) => {
                    const card = event.target.closest('[data-sprint-task]');
                    if (!card) return;
                    dragged = card;
                    source = card.parentElement;
                    event.dataTransfer.effectAllowed = 'move';
                    card.classList.add('opacity-50');
                });

                board.addEventListener('dragend', () => {
                    dragged?.classList.remove('opacity-50');
                    dragged = null;
                    source = null;
                });

                board.addEventListener('dragover', (event) => {
                    if (event.target.closest('.sprint-drop-zone')) event.preventDefault();
                });

                board.addEventListener('drop', async (event) => {
                    const zone = event.target.closest('.sprint-drop-zone');
                    if (!zone || !dragged) return;
                    event.preventDefault();
                    const oldStatus = dragged.dataset.status;
                    const newStatus = zone.dataset.status;
                    zone.appendChild(dragged);
                    dragged.dataset.status = newStatus;

                    try {
                        const response = await fetch(dragged.dataset.updateUrl, {
                            method: 'PATCH',
                            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token},
                            body: JSON.stringify({status: newStatus}),
                        });
                        if (!response.ok) throw new Error();
                    } catch (error) {
                        dragged.dataset.status = oldStatus;
                        source.appendChild(dragged);
                    }
                });
            })();
        </script>
    @endpush
@endsection
