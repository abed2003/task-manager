@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <section class="page-hero">
            <div class="page-hero-inner">
                <p class="hero-eyebrow">{{ $project->name }}</p>
                <h1 class="hero-title">Documentation Center</h1>
                <p class="hero-subtitle">وثائق المشروع، API Docs، ERD، الملاحظات التقنية، ومحاضر الاجتماعات مع مرفقات وسجل تعديلات.</p>
            </div>
        </section>

        @if(session('success'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">
                <p class="font-black">يرجى مراجعة البيانات.</p>
                <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="grid gap-6 lg:grid-cols-[380px_1fr]">
            <aside class="panel">
                <div class="panel-header">
                    <h2 class="text-lg font-black text-slate-950">مستند جديد</h2>
                    <p class="mt-1 text-sm text-slate-500">استخدم المحرر لإضافة تفاصيل تقنية منظمة.</p>
                </div>
                <form method="POST" action="{{ route('projects.documentation.store', $project) }}" enctype="multipart/form-data" class="panel-body space-y-4">
                    @csrf
                    <div><label class="field-label">العنوان</label><input name="title" value="{{ old('title') }}" required class="mt-2 block w-full"></div>
                    <div>
                        <label class="field-label">التصنيف</label>
                        <select name="category" required class="mt-2 block w-full">
                            @foreach($categories as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">المحتوى</label>
                        <textarea name="content" rows="10" class="mt-2 block w-full font-mono text-sm" placeholder="اكتب التوثيق هنا...">{{ old('content') }}</textarea>
                    </div>
                    <div><label class="field-label">مرفقات</label><input name="attachments[]" type="file" multiple class="mt-2 block w-full"></div>
                    <button class="btn-primary w-full">حفظ المستند</button>
                </form>
            </aside>

            <div class="space-y-4">
                <form method="GET" action="{{ route('projects.documentation.index', $project) }}" class="panel p-4">
                    <div class="grid gap-3 md:grid-cols-[1fr_220px_auto]">
                        <input name="search" value="{{ request('search') }}" placeholder="بحث داخل الوثائق">
                        <select name="category">
                            <option value="">كل التصنيفات</option>
                            @foreach($categories as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach
                        </select>
                        <button class="btn-dark">بحث</button>
                    </div>
                </form>

                @forelse($documents as $document)
                    <article class="panel overflow-hidden">
                        <div class="panel-header flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-black text-slate-950">{{ $document->title }}</h2>
                                    <span class="status-pill bg-sky-50 text-sky-700 ring-sky-100">{{ $document->categoryLabel() }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">آخر تحديث {{ $document->updated_at->diffForHumans() }}</p>
                            </div>
                            <details class="relative">
                                <summary class="btn-secondary list-none">تعديل</summary>
                                <div class="popover absolute left-0 z-20 mt-2 w-[28rem] max-w-[85vw] p-4">
                                    <form method="POST" action="{{ route('projects.documentation.update', [$project, $document]) }}" enctype="multipart/form-data" class="space-y-3">
                                        @csrf
                                        @method('PATCH')
                                        <input name="title" value="{{ $document->title }}" required class="block w-full">
                                        <select name="category" class="block w-full">@foreach($categories as $value => $label)<option value="{{ $value }}" @selected($document->category === $value)>{{ $label }}</option>@endforeach</select>
                                        <textarea name="content" rows="8" class="block w-full font-mono text-sm">{{ $document->content }}</textarea>
                                        <input name="change_summary" placeholder="ملخص التعديل" class="block w-full">
                                        <input name="attachments[]" type="file" multiple class="block w-full">
                                        <button class="btn-dark w-full">حفظ التعديل</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                        <div class="panel-body">
                            <div class="prose max-w-none whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ $document->content ?: 'لا يوجد محتوى بعد.' }}</div>
                            @if($document->attachments->isNotEmpty())
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($document->attachments as $attachment)
                                        <a href="{{ asset('storage/'.$attachment->path) }}" target="_blank" class="status-pill bg-slate-100 text-slate-600 ring-slate-200">{{ $attachment->original_name }}</a>
                                    @endforeach
                                </div>
                            @endif
                            <details class="mt-4">
                                <summary class="cursor-pointer text-sm font-black text-sky-700">سجل التعديلات ({{ $document->revisions->count() }})</summary>
                                <div class="mt-3 space-y-2">
                                    @foreach($document->revisions->take(5) as $revision)
                                        <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                                            {{ $revision->change_summary }} - {{ $revision->user?->name ?? 'System' }} - {{ $revision->created_at->format('Y-m-d H:i') }}
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                    </article>
                @empty
                    <div class="panel px-5 py-12 text-center text-sm font-semibold text-slate-500">لا توجد وثائق مطابقة.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
