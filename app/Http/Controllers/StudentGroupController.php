<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Services\AcademicCalendar;
use App\Services\Gradebook;
use App\Services\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentGroupController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Visibility $visibility): View
    {
        abort_unless(request()->user()->hasPermission('groups.view'), 403);
        $year = $this->academicYear();
        $user = request()->user();

        $groups = StudentGroup::query()
            ->with('level')
            ->withCount('enrollments')
            ->where('academic_year_id', $year->id)
            ->when(
                ! $user->hasAnyRole(['administration', 'pedagogical']),
                fn ($query) => $query->whereIn('id', $visibility->groupIds($user, $year)),
            )
            ->orderBy('name')
            ->paginate(12);

        return view('groups.index', compact('groups', 'year'));
    }

    public function create(): View
    {
        abort_unless(request()->user()->hasPermission('groups.manage'), 403);

        return view('groups.form', [
            'group' => new StudentGroup,
            'levels' => Level::query()->orderBy('sort_order')->get(),
            'year' => $this->academicYear(),
        ]);
    }

    public function store(Request $request, AcademicCalendar $calendar): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('groups.manage'), 403);
        $year = $this->academicYear();
        $calendar->ensureOpen($year);
        $data = $this->validateGroup($request, $year->id);

        $group = StudentGroup::query()->create([
            ...$data,
            'academic_year_id' => $year->id,
            'color' => StudentGroup::nextColor($year->id),
        ]);

        return redirect()->route('groups.show', $group)->with('status', 'Groupe créé.');
    }

    public function show(StudentGroup $studentGroup, Gradebook $gradebook, Visibility $visibility): View
    {
        $user = request()->user();
        abort_unless($user->hasPermission('groups.view'), 403);

        if (! $user->hasAnyRole(['administration', 'pedagogical'])) {
            abort_unless($visibility->groupIds($user, $studentGroup->academicYear)->contains($studentGroup->id), 404);
        }

        $studentGroup->load(['level', 'academicYear', 'assignments.teacher', 'assignments.subject']);
        $enrollments = $studentGroup->enrollments()->with('student')->where('status', 'active')->orderBy('id')->paginate(20);
        $ranking = $studentGroup->academicYear->ranking_enabled
            ? $gradebook->ranking($studentGroup->id, $studentGroup->academicYear)
            : collect();

        return view('groups.show', [
            'group' => $studentGroup,
            'enrollments' => $enrollments,
            'ranking' => $ranking,
        ]);
    }

    public function edit(StudentGroup $studentGroup): View
    {
        abort_unless(request()->user()->hasPermission('groups.manage'), 403);

        return view('groups.form', [
            'group' => $studentGroup,
            'levels' => Level::query()->orderBy('sort_order')->get(),
            'year' => $studentGroup->academicYear,
        ]);
    }

    public function update(Request $request, StudentGroup $studentGroup, AcademicCalendar $calendar): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('groups.manage'), 403);
        $calendar->ensureOpen($studentGroup->academicYear);
        $studentGroup->update($this->validateGroup($request, $studentGroup->academic_year_id, $studentGroup->id));

        return redirect()->route('groups.show', $studentGroup)->with('status', 'Groupe mis à jour.');
    }

    public function updateColor(Request $request, StudentGroup $studentGroup): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('groups.manage'), 403);

        $data = $request->validate([
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'color.regex' => 'Choisissez une couleur valide.',
        ]);

        $studentGroup->update(['color' => strtolower($data['color'])]);

        return back();
    }

    public function destroy(StudentGroup $studentGroup, AcademicCalendar $calendar): RedirectResponse
    {
        abort_unless(request()->user()->hasPermission('groups.manage'), 403);
        $calendar->ensureOpen($studentGroup->academicYear);
        $studentGroup->delete();

        return redirect()->route('groups.index')->with('status', 'Groupe supprimé.');
    }

    /**
     * @return array{name: string, code: string, level_id: int, capacity: ?int}
     */
    private function validateGroup(Request $request, int $yearId, ?int $ignore = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', Rule::unique('student_groups', 'code')->where('academic_year_id', $yearId)->ignore($ignore)],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ], [
            'code.unique' => 'Ce code de groupe est déjà utilisé pour cette année.',
        ]);
    }
}
