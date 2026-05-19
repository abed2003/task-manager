@extends('layouts.app')

@section('content')
    @php
        $statusStyles = [
            'active' => 'bg-blue-50 text-blue-700 ring-blue-100',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'on_hold' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'review' => 'bg-amber-50 text-amber-700 ring-amber-100',
        ];

        $statCards = [
            ['label' => 'إجمالي المشاريع', 'value' => $stats['total_projects'], 'tone' => 'text-slate-950', 'hint' => 'كل المشاريع المسجلة'],
            ['label' => 'المشاريع قيد التنفيذ', 'value' => $stats['active_projects'], 'tone' => 'text-blue-700', 'hint' => 'مشاريع نشطة حالياً'],
            ['label' => 'المشاريع المكتملة', 'value' => $stats['completed_projects'], 'tone' => 'text-emerald-700', 'hint' => 'تم تسليمها أو إغلاقها'],
            ['label' => 'المشاريع المتوقفة', 'value' => $stats['on_hold_projects'], 'tone' => 'text-slate-700', 'hint' => 'بانتظار قرار أو متابعة'],
        ];
    @endphp

    <div class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-bold text-blue-700">إدارة المشاريع</p>
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">لوحة مشاريع شركات البرمجيات</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        متابعة المشاريع والعملاء ومدراء المشاريع ونسب الإنجاز من مكان واحد بتصميم إداري واضح ومنظم.
                    </p>
                </div>

                @if(auth()->user()->role === 'admin')
                    <details class="relative">
                        <summary class="btn-primary cursor-pointer list-none">إضافة مشروع</summary>
                        <div class="absolute left-0 z-30 mt-3 w-[min(92vw,520px)] rounded-lg border border-slate-200 bg-white p-5 shadow-xl">
                            <form method="POST" action="{{ route('projects.store') }}" class="space-y-4">
                                @csrf
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="name" class="field-label">اسم المشروع</label>
                                        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-2 block w-full">
                                    </div>
                                    <div>
                                        <label for="client_name" class="field-label">العميل</label>
                                        <input id="client_name" name="client_name" type="text" value="{{ old('client_name') }}" class="mt-2 block w-full">
                                    </div>
                                </div>
                                <div>
                                    <label for="description" class="field-label">وصف مختصر</label>
                                    <textarea id="description" name="description" rows="3" class="mt-2 block w-full">{{ old('description') }}</textarea>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label for="manager_id" class="field-label">مدير المشروع</label>
                                        <select id="manager_id" name="manager_id" class="mt-2 block w-full">
                                            <option value="">اختر المدير</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" @selected(old('manager_id') == $user->id)>{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="status" class="field-label">الحالة</label>
                                        <select id="status" name="status" class="mt-2 block w-full">
                                            @foreach($projectStatuses as $value => $label)
                                                <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="starts_at" class="field-label">تاريخ البداية</label>
                                        <input id="starts_at" name="starts_at" type="date" value="{{ old('starts_at') }}" class="mt-2 block w-full">
                                    </div>
                                </div>
                                <div>
                                    <label for="due_at" class="field-label">تاريخ الانتهاء</label>
                                    <input id="due_at" name="due_at" type="date" value="{{ old('due_at') }}" class="mt-2 block w-full">
                                </div>
                                <button type="submit" class="btn-primary w-full">حفظ المشروع</button>
                            </form>
                        </div>
                    </details>
                @endif
            </div>
        </section>

        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-black">يرجى مراجعة البيانات المدخلة.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($statCards as $card)
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-3 text-3xl font-black {{ $card['tone'] }}">{{ $card['value'] }}</p>
                        </div>
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50 text-sm font-black text-blue-700 ring-1 ring-blue-100">PM</span>
                    </div>
                    <p class="mt-3 text-xs font-semibold text-slate-400">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('projects.index') }}" class="grid gap-3 lg:grid-cols-[1.4fr_220px_180px_180px_auto] lg:items-end">
                <div>
                    <label for="search" class="field-label">البحث عن مشروع</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="اسم المشروع، العميل، أو مدير المشروع" class="mt-2 block w-full">
                </div>
                <div>
                    <label for="filter_status" class="field-label">فلترة حسب الحالة</label>
                    <select id="filter_status" name="status" class="mt-2 block w-full">
                        <option value="">كل الحالات</option>
                        @foreach($projectStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="starts_from" class="field-label">من تاريخ</label>
                    <input id="starts_from" name="starts_from" type="date" value="{{ $filters['starts_from'] ?? '' }}" class="mt-2 block w-full">
                </div>
                <div>
                    <label for="due_to" class="field-label">حتى تاريخ</label>
                    <input id="due_to" name="due_to" type="date" value="{{ $filters['due_to'] ?? '' }}" class="mt-2 block w-full">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1 lg:flex-none">تطبيق</button>
                    <a href="{{ route('projects.index') }}" class="btn-secondary">مسح</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-black text-slate-950">قائمة المشاريع</h2>
                    <p class="mt-1 text-sm text-slate-500">بيانات المشاريع مرتبطة مباشرة بقاعدة البيانات.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">
                    {{ $projects->total() }} مشروع
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-right">
                    <thead class="bg-slate-50">
                        <tr class="text-xs font-black uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3">اسم المشروع</th>
                            <th class="px-5 py-3">وصف مختصر</th>
                            <th class="px-5 py-3">العميل</th>
                            <th class="px-5 py-3">مدير المشروع</th>
                            <th class="px-5 py-3">الحالة</th>
                            <th class="px-5 py-3">نسبة الإنجاز</th>
                            <th class="px-5 py-3">تاريخ البداية</th>
                            <th class="px-5 py-3">تاريخ الانتهاء</th>
                            <th class="px-5 py-3">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($projects as $project)
                            @php
                                $completedCount = $project->tasks->where('status', 'completed')->count();
                                $progress = $project->tasks_count > 0 ? round(($completedCount / $project->tasks_count) * 100) : 0;
                                $manager = $project->manager?->name ?? $project->creator?->name ?? 'غير محدد';
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="font-black text-slate-950">{{ $project->name }}</div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400">{{ $project->tasks_count }} مهمة</div>
                                </td>
                                <td class="max-w-xs px-5 py-4">
                                    <p class="line-clamp-2 text-sm leading-6 text-slate-600">{{ $project->description ?: 'لا يوجد وصف مختصر' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-700">{{ $project->client_name ?: 'غير محدد' }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-50 text-xs font-black text-blue-700 ring-1 ring-blue-100">{{ mb_substr($manager, 0, 1) }}</span>
                                        <span class="text-sm font-bold text-slate-700">{{ $manager }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="status-pill {{ $statusStyles[$project->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $project->statusLabel() }}</span>
                                </td>
                                <td class="min-w-44 px-5 py-4">
                                    <div class="flex items-center justify-between gap-3 text-xs font-bold text-slate-500">
                                        <span>{{ $progress }}%</span>
                                        <span>{{ $completedCount }}/{{ $project->tasks_count }}</span>
                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-blue-600" style="width: {{ $progress }}%"></div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600">{{ $project->starts_at?->format('Y-m-d') ?? '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600">{{ $project->due_at?->format('Y-m-d') ?? '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('tasks.index', ['project_id' => $project->id]) }}" class="btn-secondary px-3 py-2">المهام</a>
                                        <a href="{{ route('projects.documentation.index', $project) }}" class="btn-secondary px-3 py-2">الوثائق</a>
                                        @if(auth()->user()->role === 'admin')
                                            <details class="relative">
                                                <summary class="btn-secondary cursor-pointer list-none px-3 py-2">تعديل</summary>
                                                <div class="absolute left-0 z-30 mt-2 w-96 max-w-[85vw] rounded-lg border border-slate-200 bg-white p-4 shadow-xl">
                                                    <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-3">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input name="name" type="text" value="{{ $project->name }}" required class="block w-full">
                                                        <input name="client_name" type="text" value="{{ $project->client_name }}" placeholder="العميل" class="block w-full">
                                                        <textarea name="description" rows="3" class="block w-full">{{ $project->description }}</textarea>
                                                        <select name="manager_id" class="block w-full">
                                                            <option value="">مدير المشروع</option>
                                                            @foreach($users as $user)
                                                                <option value="{{ $user->id }}" @selected($project->manager_id === $user->id)>{{ $user->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="status" class="block w-full">
                                                            @foreach($projectStatuses as $value => $label)
                                                                <option value="{{ $value }}" @selected($project->status === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="grid gap-2 sm:grid-cols-2">
                                                            <input name="starts_at" type="date" value="{{ $project->starts_at?->format('Y-m-d') }}" class="block w-full">
                                                            <input name="due_at" type="date" value="{{ $project->due_at?->format('Y-m-d') }}" class="block w-full">
                                                        </div>
                                                        <button type="submit" class="btn-dark w-full">حفظ التعديل</button>
                                                    </form>
                                                </div>
                                            </details>
                                            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('هل تريد حذف هذا المشروع؟ سيتم حذف المهام المرتبطة به أيضاً.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger px-3 py-2">حذف</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-12 text-center">
                                    <h3 class="text-base font-black text-slate-950">لا توجد مشاريع مطابقة</h3>
                                    <p class="mt-2 text-sm text-slate-500">جرّب تغيير الفلاتر أو إضافة مشروع جديد.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($projects->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $projects->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
