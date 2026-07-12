@extends('layouts.app')

@section('content')
    @php
        $statusStyles = [
            'scheduled' => 'bg-blue-50 text-blue-700 ring-blue-100',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'cancelled' => 'bg-slate-100 text-slate-600 ring-slate-200',
        ];

        $statCards = [
            ['label' => 'كل الاجتماعات', 'value' => $stats['total'], 'tone' => 'text-slate-950'],
            ['label' => 'مجدولة', 'value' => $stats['scheduled'], 'tone' => 'text-blue-700'],
            ['label' => 'منتهية', 'value' => $stats['completed'], 'tone' => 'text-emerald-700'],
            ['label' => 'ملغية', 'value' => $stats['cancelled'], 'tone' => 'text-slate-600'],
        ];
    @endphp

    <div class="space-y-6">
        <section class="page-hero overflow-visible">
            <div class="page-hero-inner rounded-[28px]">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="hero-eyebrow">جدولة الاجتماعات</p>
                        <h1 class="hero-title">إدارة مواعيد وملخصات الاجتماعات</h1>
                        <p class="hero-subtitle">
                            جدولة اجتماعات الفريق وربطها بالمشاريع، ثم توثيق الملخص والقرارات والخطوات التالية بعد الانتهاء.
                        </p>
                    </div>

                    @if(auth()->user()->role === 'admin')
                        <details class="relative">
                            <summary class="btn-primary cursor-pointer list-none">اجتماع جديد</summary>
                            <div class="popover absolute left-0 z-40 mt-3 w-[min(92vw,620px)] p-5">
                            <form method="POST" action="{{ route('meetings.store') }}" class="space-y-4">
                                @csrf
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="title" class="field-label">عنوان الاجتماع</label>
                                        <input id="title" name="title" type="text" value="{{ old('title') }}" required class="mt-2 block w-full">
                                    </div>
                                    <div>
                                        <label for="project_id" class="field-label">المشروع</label>
                                        <select id="project_id" name="project_id" class="mt-2 block w-full">
                                            <option value="">بدون مشروع</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="agenda" class="field-label">محاور الاجتماع</label>
                                    <textarea id="agenda" name="agenda" rows="3" class="mt-2 block w-full">{{ old('agenda') }}</textarea>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label for="starts_at" class="field-label">بداية الاجتماع</label>
                                        <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" required class="mt-2 block w-full">
                                    </div>
                                    <div>
                                        <label for="ends_at" class="field-label">نهاية الاجتماع</label>
                                        <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" class="mt-2 block w-full">
                                    </div>
                                    <div>
                                        <label for="status" class="field-label">الحالة</label>
                                        <select id="status" name="status" class="mt-2 block w-full">
                                            @foreach($statuses as $value => $label)
                                                <option value="{{ $value }}" @selected(old('status', 'scheduled') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="location" class="field-label">المكان أو رابط الاجتماع</label>
                                    <input id="location" name="location" type="text" value="{{ old('location') }}" placeholder="Google Meet / Zoom / غرفة الاجتماعات" class="mt-2 block w-full">
                                </div>

                                <div>
                                    <label for="attendee_ids" class="field-label">الحضور</label>
                                    <select id="attendee_ids" name="attendee_ids[]" multiple class="mt-2 block min-h-32 w-full">
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" @selected(in_array($user->id, old('attendee_ids', [])))>{{ $user->name }} - {{ $user->email }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <button type="submit" class="btn-primary w-full">حفظ الاجتماع</button>
                            </form>
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">
                <p class="font-black">يرجى مراجعة بيانات الاجتماع.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($statCards as $card)
                <div class="stat-card">
                    <p class="text-sm font-bold text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-3 text-3xl font-black {{ $card['tone'] }}">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="panel p-5">
            <form method="GET" action="{{ route('meetings.index') }}" class="grid gap-3 lg:grid-cols-[1.3fr_220px_240px_auto] lg:items-end">
                <div>
                    <label for="search" class="field-label">البحث</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="عنوان، مشروع، محور، أو ملخص" class="mt-2 block w-full">
                </div>
                <div>
                    <label for="filter_status" class="field-label">الحالة</label>
                    <select id="filter_status" name="status" class="mt-2 block w-full">
                        <option value="">كل الحالات</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_project" class="field-label">المشروع</label>
                    <select id="filter_project" name="project_id" class="mt-2 block w-full">
                        <option value="">كل المشاريع</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected((int) ($filters['project_id'] ?? 0) === $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">تطبيق</button>
                    <a href="{{ route('meetings.index') }}" class="btn-secondary">مسح</a>
                </div>
            </form>
        </section>

        <section class="space-y-4">
            @forelse($meetings as $meeting)
                @php
                    $canManage = auth()->user()->role === 'admin' || $meeting->organizer_id === auth()->id();
                    $attendeeIds = $meeting->attendees->pluck('id')->all();
                @endphp

                <article class="panel overflow-hidden">
                    <div class="grid gap-5 p-5 xl:grid-cols-[1fr_360px]">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="status-pill {{ $statusStyles[$meeting->status] ?? $statusStyles['scheduled'] }}">{{ $meeting->statusLabel() }}</span>
                                @if($meeting->project)
                                    <span class="status-pill bg-slate-100 text-slate-600 ring-slate-200">{{ $meeting->project->name }}</span>
                                @endif
                            </div>

                            <h2 class="mt-3 text-lg font-black text-slate-950">{{ $meeting->title }}</h2>

                            @if($meeting->agenda)
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $meeting->agenda }}</p>
                            @endif

                            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-xs font-bold text-slate-400">المنظم</p>
                                    <p class="mt-1 font-bold text-slate-800">{{ $meeting->organizer->name }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-xs font-bold text-slate-400">البداية</p>
                                    <p class="mt-1 font-bold text-slate-800">{{ $meeting->starts_at->format('Y-m-d H:i') }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-xs font-bold text-slate-400">النهاية</p>
                                    <p class="mt-1 font-bold text-slate-800">{{ $meeting->ends_at?->format('Y-m-d H:i') ?? '-' }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-xs font-bold text-slate-400">المكان</p>
                                    <p class="mt-1 truncate font-bold text-slate-800">{{ $meeting->location ?: '-' }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @forelse($meeting->attendees as $attendee)
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100">{{ $attendee->name }}</span>
                                @empty
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">لا يوجد حضور محدد</span>
                                @endforelse
                            </div>

                            @if($meeting->summary || $meeting->decisions || $meeting->next_steps)
                                <div class="mt-5 rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                                    <h3 class="text-sm font-black text-emerald-900">ملخص الاجتماع</h3>
                                    @if($meeting->summary)
                                        <p class="mt-2 text-sm leading-6 text-emerald-900">{{ $meeting->summary }}</p>
                                    @endif
                                    @if($meeting->decisions)
                                        <p class="mt-3 text-sm leading-6 text-emerald-900"><span class="font-black">القرارات:</span> {{ $meeting->decisions }}</p>
                                    @endif
                                    @if($meeting->next_steps)
                                        <p class="mt-3 text-sm leading-6 text-emerald-900"><span class="font-black">الخطوات التالية:</span> {{ $meeting->next_steps }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <details class="rounded-2xl bg-slate-50">
                                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-black text-slate-800">
                                    {{ $canManage ? 'تعديل الاجتماع والملخص' : 'إضافة ملخص الاجتماع' }}
                                </summary>
                                <form method="POST" action="{{ route('meetings.update', $meeting) }}" class="space-y-3 border-t border-slate-200 p-4">
                                    @csrf
                                    @method('PATCH')

                                    @if($canManage)
                                        <input name="title" type="text" value="{{ $meeting->title }}" required class="block w-full">
                                        <select name="project_id" class="block w-full">
                                            <option value="">بدون مشروع</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}" @selected($meeting->project_id === $project->id)>{{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                        <textarea name="agenda" rows="3" class="block w-full">{{ $meeting->agenda }}</textarea>
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <input name="starts_at" type="datetime-local" value="{{ $meeting->starts_at->format('Y-m-d\TH:i') }}" required class="block w-full">
                                            <input name="ends_at" type="datetime-local" value="{{ $meeting->ends_at?->format('Y-m-d\TH:i') }}" class="block w-full">
                                        </div>
                                        <input name="location" type="text" value="{{ $meeting->location }}" class="block w-full">
                                        <select name="attendee_ids[]" multiple class="block min-h-28 w-full">
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" @selected(in_array($user->id, $attendeeIds))>{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if($canManage)
                                        <select name="status" class="block w-full">
                                            @foreach($statuses as $value => $label)
                                                <option value="{{ $value }}" @selected($meeting->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="hidden" name="status" value="completed">
                                        <div class="rounded-xl bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">
                                            سيتم حفظ الملخص كاجتماع منتهي.
                                        </div>
                                    @endif
                                    <textarea name="summary" rows="4" placeholder="ملخص الاجتماع بعد الانتهاء" class="block w-full">{{ $meeting->summary }}</textarea>
                                    <textarea name="decisions" rows="3" placeholder="القرارات المتفق عليها" class="block w-full">{{ $meeting->decisions }}</textarea>
                                    <textarea name="next_steps" rows="3" placeholder="الخطوات التالية والمسؤوليات" class="block w-full">{{ $meeting->next_steps }}</textarea>
                                    <button type="submit" class="btn-dark w-full">حفظ</button>
                                </form>
                            </details>

                            @if(auth()->user()->role === 'admin')
                                <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" onsubmit="return confirm('هل تريد حذف هذا الاجتماع؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger w-full">حذف الاجتماع</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="panel px-5 py-12 text-center">
                    <h3 class="text-lg font-black text-slate-950">لا توجد اجتماعات</h3>
                    <p class="mt-2 text-sm text-slate-500">ابدأ بجدولة اجتماع جديد للفريق أو للمشروع.</p>
                </div>
            @endforelse
        </section>

        @if($meetings->hasPages())
            <div class="panel px-5 py-4">
                {{ $meetings->links() }}
            </div>
        @endif
    </div>
@endsection
