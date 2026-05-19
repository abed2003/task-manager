@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus"></i> إضافة مهمة جديدة</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('tasks.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان المهمة *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">وصف المهمة</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> حفظ المهمة
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> قائمة المهام</h5>
                    <span class="badge bg-light text-dark">{{ $tasks->total() }} مهمة</span>
                </div>
                <div class="card-body">
                    @if($tasks->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>الحالة</th>
                                    <th>المهمة</th>
                                    @if(auth()->user()->role == 'admin')
                                        <th>المستخدم</th>
                                    @endif
                                    <th>تاريخ الإضافة</th>
                                    <th>الإجراءات</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tasks as $task)
                                    <tr class="{{ $task->is_completed ? 'table-success' : '' }}">
                                        <td class="align-middle">
                                            <form action="{{ route('tasks.toggle', $task) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm {{ $task->is_completed ? 'btn-warning' : 'btn-success' }}">
                                                    <i class="fas {{ $task->is_completed ? 'fa-undo' : 'fa-check' }}"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="align-middle">
                                            <strong>{{ $task->title }}</strong>
                                            @if($task->description)
                                                <br><small class="text-muted">{{ $task->description }}</small>
                                            @endif
                                        </td>
                                        @if(auth()->user()->role == 'admin')
                                            <td class="align-middle">
                                                <span class="badge bg-info">{{ $task->user->name }}</span>
                                            </td>
                                        @endif
                                        <td class="align-middle">{{ $task->created_at->diffForHumans() }}</td>
                                        <td class="align-middle">
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $task->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه المهمة؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal تعديل المهمة -->
                                    <div class="modal fade" id="editModal{{ $task->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">تعديل المهمة</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('tasks.update', $task) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">عنوان المهمة</label>
                                                            <input type="text" name="title" class="form-control" value="{{ $task->title }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">وصف المهمة</label>
                                                            <textarea name="description" class="form-control" rows="3">{{ $task->description }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $tasks->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                            <p class="lead">لا توجد مهام حالياً</p>
                            <p class="text-muted">أضف مهمتك الأولى من خلال النموذج المجاور</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
