<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Models\AdmissionStatus;
use App\Models\Applicant;
use App\Models\Level;
use App\Models\StudentGroup;
use App\Services\AcademicCalendar;
use App\Services\AdmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApplicantController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Applicant::class);
        $year = $this->academicYear();

        $applicants = Applicant::query()
            ->with(['admissionStatus', 'level'])
            ->where('academic_year_id', $year->id)
            ->when($request->filled('q'), fn ($query) => $query->search($request->string('q')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('admission_status_id', $request->integer('status')))
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('admissions.index', [
            'applicants' => $applicants,
            'year' => $year,
            'statuses' => AdmissionStatus::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Applicant::class);

        return view('admissions.form', [
            'applicant' => new Applicant,
            'year' => $this->academicYear(),
            'levels' => Level::query()->orderBy('sort_order')->get(),
            'statuses' => AdmissionStatus::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, AcademicCalendar $calendar): RedirectResponse
    {
        Gate::authorize('create', Applicant::class);
        $year = $this->academicYear();
        $calendar->ensureOpen($year);

        $applicant = Applicant::query()->create([
            ...$this->validated($request),
            'academic_year_id' => $year->id,
        ]);

        return redirect()->route('applicants.show', $applicant)->with('status', 'Candidature enregistrée.');
    }

    public function show(Applicant $applicant): View
    {
        Gate::authorize('view', $applicant);
        $applicant->load(['admissionStatus', 'level', 'student', 'reviewer', 'academicYear']);

        return view('admissions.show', [
            'applicant' => $applicant,
            'statuses' => AdmissionStatus::query()->orderBy('sort_order')->get(),
            'groups' => StudentGroup::query()->where('academic_year_id', $applicant->academic_year_id)->orderBy('name')->get(),
            'levels' => Level::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function edit(Applicant $applicant): View
    {
        Gate::authorize('update', $applicant);

        return view('admissions.form', [
            'applicant' => $applicant,
            'year' => $applicant->academicYear,
            'levels' => Level::query()->orderBy('sort_order')->get(),
            'statuses' => AdmissionStatus::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Applicant $applicant, AcademicCalendar $calendar): RedirectResponse
    {
        Gate::authorize('update', $applicant);
        $calendar->ensureOpen($applicant->academicYear);
        $applicant->update($this->validated($request));

        return redirect()->route('applicants.show', $applicant)->with('status', 'Candidature mise à jour.');
    }

    public function status(Request $request, Applicant $applicant, AdmissionService $admissions): RedirectResponse
    {
        Gate::authorize('update', $applicant);
        $data = $request->validate([
            'admission_status_id' => ['required', 'integer', 'exists:admission_statuses,id'],
        ]);
        $admissions->changeStatus($applicant, AdmissionStatus::query()->findOrFail($data['admission_status_id']), $request->user());

        return back()->with('status', 'Statut de candidature mis à jour.');
    }

    public function convert(Request $request, Applicant $applicant, AdmissionService $admissions): RedirectResponse
    {
        Gate::authorize('update', $applicant);
        $data = $request->validate([
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'student_group_id' => ['nullable', 'integer', 'exists:student_groups,id'],
            'create_account' => ['sometimes', 'boolean'],
        ]);
        $student = $admissions->convert($applicant, $data, $request->user());

        return redirect()->route('students.show', $student)->with('status', 'Candidat converti en étudiant.');
    }

    public function destroy(Applicant $applicant, AcademicCalendar $calendar): RedirectResponse
    {
        Gate::authorize('delete', $applicant);
        $calendar->ensureOpen($applicant->academicYear);
        $applicant->delete();

        return redirect()->route('applicants.index')->with('status', 'Candidature supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'admission_status_id' => ['required', 'integer', 'exists:admission_statuses,id'],
            'motivation' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
