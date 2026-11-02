@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-500">
            <a href="{{ route('projects.index') }}" class="hover:text-slate-950">المشاريع</a>
            <span>/</span>
            <span class="text-slate-950">{{ $project->name }}</span>
            <span>/</span>
            <span class="text-slate-950">الأعضاء</span>
        </div>

        <section class="page-hero p-6 sm:p-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="hero-eyebrow">فريق المشروع</p>
                    <h1 class="hero-title">{{ $project->name }}</h1>
                    <p class="hero-subtitle">
                        أعضاء المشروع يمكنهم الوصول إلى صفحاته: المهام والوثائق والمشاكل والسبرنتات.
                        @if($canManage) يمكنك إضافة أشخاص أو إزالتهم من هنا. @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('tasks.index', ['project_id' => $project->id]) }}" class="btn-secondary">المهام</a>
                    <a href="{{ route('projects.documentation.index', $project) }}" class="btn-secondary">الوثائق</a>
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">
                <p class="font-black">يرجى مراجعة البيانات المدخلة.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 {{ $canManage ? 'xl:grid-cols-[1fr_380px]' : '' }}">
            <section class="panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-black text-slate-950">الأعضاء</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700">
                        {{ $members->count() + ($project->manager ? 1 : 0) }} شخص
                    </span>
                </div>

                <ul class="divide-y divide-slate-100">
                    @if($project->manager)
                        @php($counts = $taskCounts->get($project->manager->id))
                        <li class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-950 text-sm font-black text-white">{{ mb_substr($project->manager->name, 0, 1) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-950">{{ $project->manager->name }}</p>
                                    <p class="truncate text-xs font-semibold text-slate-500">{{ $project->manager->email }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="hidden text-xs font-bold text-slate-500 sm:inline">{{ (int) ($counts->completed ?? 0) }}/{{ (int) ($counts->total ?? 0) }} مهمة</span>
                                <span class="rounded-full bg-slate-950 px-3 py-1 text-xs font-black text-white">مدير المشروع</span>
                            </div>
                        </li>
                    @endif

                    @forelse($members as $member)
                        @php($counts = $taskCounts->get($member->id))
                        <li class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-black text-slate-950">{{ mb_substr($member->name, 0, 1) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-950">{{ $member->name }}</p>
                                    <p class="truncate text-xs font-semibold text-slate-500">{{ $member->email }} · انضم {{ $member->pivot->created_at?->format('Y-m-d') }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="hidden text-xs font-bold text-slate-500 sm:inline">{{ (int) ($counts->completed ?? 0) }}/{{ (int) ($counts->total ?? 0) }} مهمة</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">عضو</span>
                                @if($canManage)
                                    <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}" data-confirm="إزالة {{ $member->name }} من المشروع؟" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-slate-400 hover:text-red-600">إزالة</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-slate-400">لا يوجد أعضاء في هذا المشروع بعد.</li>
                    @endforelse
                </ul>
            </section>

            @if($canManage)
                <div class="space-y-6">
                <section class="panel p-5">
                    <h2 class="text-base font-black text-slate-950">إسناد مهمة لعضو</h2>
                    <p class="mt-1 text-sm text-slate-500">تُسند المهمة لأحد أعضاء هذا المشروع ويصله إشعار بها.</p>
                    <form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="mt-4 space-y-3">
                        @csrf
                        <input name="title" type="text" value="{{ old('title') }}" placeholder="عنوان المهمة" required maxlength="255" class="block w-full">
                        <textarea name="description" rows="2" placeholder="تفاصيل المهمة (اختياري)" class="block w-full">{{ old('description') }}</textarea>
                        <select name="user_id" required class="block w-full">
                            <option value="">اختر العضو المسؤول</option>
                            @foreach($assignees as $assignee)
                                <option value="{{ $assignee->id }}" @selected(old('user_id') == $assignee->id)>{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                        <div class="grid grid-cols-2 gap-2">
                            <select name="priority">
                                @foreach($priorityLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select name="status">
                                @foreach($taskStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'todo') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input name="due_at" type="date" value="{{ old('due_at') }}">
                            <input name="estimated_hours" type="number" step="0.25" min="0" value="{{ old('estimated_hours') }}" placeholder="ساعات مقدّرة">
                        </div>
                        <button type="submit" class="btn-dark w-full">إسناد المهمة</button>
                    </form>
                </section>

                <section class="panel p-5" x-data="{ q: '' }">
                    <h2 class="text-base font-black text-slate-950">إضافة أشخاص</h2>
                    <p class="mt-1 text-sm text-slate-500">اختر شخصاً أو أكثر من الفريق. سيصلهم إشعار بإضافتهم.</p>

                    @if($availableUsers->isEmpty())
                        <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">كل أعضاء الفريق مضافون لهذا المشروع.</p>
                    @else
                        <form method="POST" action="{{ route('projects.members.store', $project) }}" class="mt-4 space-y-3">
                            @csrf
                            <input type="search" x-model="q" placeholder="ابحث بالاسم أو البريد..." class="block w-full">
                            <div class="max-h-80 space-y-1 overflow-y-auto rounded-2xl border border-slate-100 p-2">
                                @foreach($availableUsers as $candidate)
                                    <label
                                        x-show="q === '' || @js(mb_strtolower($candidate->name.' '.$candidate->email)).includes(q.toLowerCase())"
                                        class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2 transition hover:bg-slate-50"
                                    >
                                        <input type="checkbox" name="user_ids[]" value="{{ $candidate->id }}" class="h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-slate-950" @checked(in_array($candidate->id, old('user_ids', [])))>
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold text-slate-950">{{ $candidate->name }}</span>
                                            <span class="block truncate text-xs text-slate-500">{{ $candidate->email }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <button type="submit" class="btn-primary w-full">إضافة إلى المشروع</button>
                        </form>
                    @endif
                </section>
                </div>
            @endif
        </div>
    </div>
@endsection
