<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesProjectAccess;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectDocumentationController extends Controller
{
    use AuthorizesProjectAccess;

    public function index(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $documents = $project->documents()
            ->with(['creator', 'attachments', 'revisions.user'])
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%"));
            })
            ->latest()
            ->get();

        return view('documentation.index', [
            'project' => $project,
            'documents' => $documents,
            'categories' => ProjectDocument::categoryLabels(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(ProjectDocument::categoryLabels()))],
            'content' => ['nullable', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $document = $project->documents()->create($validated + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $document->revisions()->create($validated + [
            'user_id' => $request->user()->id,
            'change_summary' => 'Initial version',
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $document->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'path' => $file->store("projects/{$project->id}/documents", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return back()->with('success', 'تم حفظ المستند بنجاح.');
    }

    public function update(Request $request, Project $project, ProjectDocument $document): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        abort_unless($document->project_id === $project->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(ProjectDocument::categoryLabels()))],
            'content' => ['nullable', 'string'],
            'change_summary' => ['nullable', 'string', 'max:255'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $document->update($validated + ['updated_by' => $request->user()->id]);

        $document->revisions()->create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'content' => $validated['content'] ?? null,
            'change_summary' => $validated['change_summary'] ?? 'Document updated',
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $document->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'path' => $file->store("projects/{$project->id}/documents", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return back()->with('success', 'تم تحديث المستند وحفظ نسخة في سجل التعديلات.');
    }
}
