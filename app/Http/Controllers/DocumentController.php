<?php

namespace App\Http\Controllers;

use App\Enums\DocumentCategory;
use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Models\Document;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AcademicCalendar;
use App\Services\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request, Visibility $visibility): View
    {
        Gate::authorize('viewAny', Document::class);

        $documents = $visibility->documents($request->user())
            ->with('uploader')
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')->toString()))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.trim(str_replace(['%', '_'], '', $request->string('q')->toString())).'%';
                $query->where('title', 'like', $term);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'categories' => DocumentCategory::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Document::class);
        $student = $request->filled('student') ? Student::query()->find($request->integer('student')) : null;
        $teacher = $request->filled('teacher') ? Teacher::query()->find($request->integer('teacher')) : null;

        return view('documents.form', [
            'categories' => DocumentCategory::cases(),
            'student' => $student,
            'teacher' => $teacher,
        ]);
    }

    public function store(Request $request, AcademicCalendar $calendar, Visibility $visibility): RedirectResponse
    {
        Gate::authorize('create', Document::class);
        $year = $this->academicYear();
        $calendar->ensureOpen($year);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'file' => ['required', 'file', 'max:10240'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);

        $documentable = null;

        if (! empty($data['student_id'])) {
            $student = Student::query()->findOrFail($data['student_id']);
            $allowed = $request->user()->hasPermission('documents.manage')
                || ($request->user()->teacher && $visibility->seesStudent($request->user(), $student));
            abort_unless($allowed, 403);
            $documentable = $student;
        } elseif (! empty($data['teacher_id'])) {
            $teacher = Teacher::query()->findOrFail($data['teacher_id']);
            $allowed = $request->user()->hasPermission('documents.manage')
                || ($request->user()->teacher && $request->user()->teacher->is($teacher));
            abort_unless($allowed, 403);
            $documentable = $teacher;
        } elseif (! $request->user()->hasPermission('documents.manage')) {
            abort(403);
        }

        $file = $request->file('file');
        $path = $file->store('documents/'.now()->format('Y/m'), 'local');

        $document = new Document([
            'academic_year_id' => $year->id,
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        if ($documentable) {
            $document->documentable()->associate($documentable);
        }

        $document->save();

        return redirect()->route('documents.index')->with('status', 'Document enregistré.');
    }

    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('view', $document);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function archive(Document $document): RedirectResponse
    {
        Gate::authorize('archive', $document);
        $document->update(['archived_at' => $document->archived_at ? null : now()]);

        return back()->with('status', $document->archived_at ? 'Document archivé.' : 'Document restauré.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('status', 'Document supprimé.');
    }
}
