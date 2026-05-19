@extends('layouts.app')

@section('content')
    @php
        $severityStyle = ['low' => 'bg-slate-100 text-slate-700 ring-slate-200', 'medium' => 'bg-amber-50 text-amber-700 ring-amber-100', 'high' => 'bg-orange-50 text-orange-700 ring-orange-100', 'critical' => 'bg-red-50 text-red-700 ring-red-100'];
    @endphp

    <div class="space-y-6">
        <section class="page-hero">
            <div class="page-hero-inner">
                <p class="hero-eyebrow">{{ $project->name }}</p>
                <h1 class="hero-title">Bug Tracker</h1>
                <p class="hero-subtitle">تسجيل وتتبع الأخطاء البرمجية، الأولويات، المسؤولين، التعليقات والمرفقات.</p>
            </div>
        </section>

        @if(session('success'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><p class="font-black">يرجى مراجعة البيانات.</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($statusLabels as $status => $label)
                <div class="stat-card"><p class="text-sm font-bold text-slate-500">{{ $label }}</p><p class="mt-3 text-3xl font-black text-slate-950">{{ $bugs->where('status', $status)->count() }}</p></div>
            @endforeach
        </section>

        <section class="grid gap-6 lg:grid-cols-[400px_1fr]">
            <aside class="panel">
                <div class="panel-header"><h2 class="text-lg font-black text-slate-950">تسجيل Bug</h2></div>
                <form method="POST" action="{{ route('projects.bugs.store', $project) }}" enctype="multipart/form-data" class="panel-body space-y-4">
                    @csrf
                    <div><label class="field-label">العنوان</label><input name="title" required class="mt-2 block w-full"></div>
                    <div><label class="field-label">الوصف</label><textarea name="description" rows="4" class="mt-2 block w-full"></textarea></div>
                    <div><label class="field-label">خطوات إعادة المشكلة</label><textarea name="reproduction_steps" rows="4" class="mt-2 block w-full"></textarea></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><label class="field-label">الخطورة</label><select name="severity" class="mt-2 block w-full">@foreach($severityLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                        <div><label class="field-label">الحالة</label><select name="status" class="mt-2 block w-full">@foreach($statusLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                    </div>
                    <div><label class="field-label">المهمة المرتبطة</label><select name="task_id" class="mt-2 block w-full"><option value="">بدون مهمة</option>@foreach($tasks as $task)<option value="{{ $task->id }}">{{ $task->title }}</option>@endforeach</select></div>
                    <div><label class="field-label">المسؤول عن الإصلاح</label><select name="assigned_to" class="mt-2 block w-full"><option value="">غير محدد</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                    <div><label class="field-label">مرفقات وصور</label><input type="file" name="attachments[]" multiple class="mt-2 block w-full"></div>
                    <button class="btn-primary w-full">تسجيل المشكلة</button>
                </form>
            </aside>

            <div class="space-y-4">
                <form method="GET" action="{{ route('projects.bugs.index', $project) }}" class="panel p-4">
                    <div class="grid gap-3 md:grid-cols-[1fr_180px_180px_auto]">
                        <input name="search" value="{{ request('search') }}" placeholder="بحث في الأخطاء">
                        <select name="status"><option value="">كل الحالات</option>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
                        <select name="severity"><option value="">كل الخطورات</option>@foreach($severityLabels as $value => $label)<option value="{{ $value }}" @selected(request('severity') === $value)>{{ $label }}</option>@endforeach</select>
                        <button class="btn-dark">فلترة</button>
                    </div>
                </form>

                @forelse($bugs as $bug)
                    <article class="panel overflow-hidden">
                        <div class="panel-header flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-black text-slate-950">{{ $bug->title }}</h2>
                                    <span class="status-pill {{ $severityStyle[$bug->severity] ?? $severityStyle['medium'] }}">{{ $severityLabels[$bug->severity] }}</span>
                                    <span class="status-pill bg-cyan-50 text-cyan-800 ring-cyan-100">{{ $statusLabels[$bug->status] }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">Reported by {{ $bug->reporter?->name ?? 'Unknown' }} - {{ $bug->created_at->format('Y-m-d') }}</p>
                            </div>
                            <form method="POST" action="{{ route('projects.bugs.update', [$project, $bug]) }}" class="flex flex-wrap gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="status">@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($bug->status === $value)>{{ $label }}</option>@endforeach</select>
                                <select name="severity">@foreach($severityLabels as $value => $label)<option value="{{ $value }}" @selected($bug->severity === $value)>{{ $label }}</option>@endforeach</select>
                                <select name="assigned_to"><option value="">غير محدد</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($bug->assigned_to === $user->id)>{{ $user->name }}</option>@endforeach</select>
                                <button class="btn-dark">تحديث</button>
                            </form>
                        </div>
                        <div class="panel-body space-y-4">
                            <p class="text-sm leading-7 text-slate-700">{{ $bug->description }}</p>
                            @if($bug->reproduction_steps)<div class="rounded-lg bg-slate-50 p-4 text-sm leading-7 text-slate-700 ring-1 ring-slate-200"><p class="font-black text-slate-950">خطوات إعادة المشكلة</p>{{ $bug->reproduction_steps }}</div>@endif
                            <div class="flex flex-wrap gap-2 text-xs font-bold">
                                @if($bug->task)<span class="status-pill bg-slate-100 text-slate-700 ring-slate-200">Task: {{ $bug->task->title }}</span>@endif
                                @if($bug->assignee)<span class="status-pill bg-sky-50 text-sky-700 ring-sky-100">Assignee: {{ $bug->assignee->name }}</span>@endif
                            </div>
                            <form method="POST" action="{{ route('projects.bugs.comments.store', [$project, $bug]) }}" class="flex gap-2">
                                @csrf
                                <input name="body" placeholder="أضف تعليقاً" class="flex-1">
                                <button class="btn-secondary">تعليق</button>
                            </form>
                            <div class="space-y-2">
                                @foreach($bug->comments as $comment)
                                    <div class="rounded-lg bg-slate-50 p-3 text-sm ring-1 ring-slate-200"><span class="font-black">{{ $comment->user?->name }}:</span> {{ $comment->body }}</div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="panel px-5 py-12 text-center text-sm font-semibold text-slate-500">لا توجد أخطاء مطابقة.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
