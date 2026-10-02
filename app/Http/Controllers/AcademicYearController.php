<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\AcademicYearContext;
use App\Services\AcademicYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        return view('academic-years.index', [
            'years' => AcademicYear::query()->withCount(['groups', 'enrollments'])->orderByDesc('starts_on')->get(),
        ]);
    }

    public function create(): View
    {
        return view('academic-years.form', ['year' => new AcademicYear]);
    }

    public function store(AcademicYearRequest $request, AcademicYearService $service): RedirectResponse
    {
        $year = $service->create($request->validated());

        return redirect()->route('academic-years.show', $year)->with('status', 'Année académique créée.');
    }

    public function show(AcademicYear $academicYear): View
    {
        $academicYear->load('semesters');

        return view('academic-years.show', ['year' => $academicYear]);
    }

    public function edit(AcademicYear $academicYear): View
    {
        $academicYear->load('semesters');

        return view('academic-years.form', ['year' => $academicYear]);
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear, AcademicYearService $service): RedirectResponse
    {
        $service->update($academicYear, $request->validated());

        return redirect()->route('academic-years.show', $academicYear)->with('status', 'Année académique mise à jour.');
    }

    public function activate(AcademicYear $academicYear, AcademicYearService $service, AcademicYearContext $context): RedirectResponse
    {
        $service->activate($academicYear);
        $context->select($academicYear);

        return back()->with('status', 'L\'année '.$academicYear->name.' est maintenant active.');
    }

    public function close(AcademicYear $academicYear, AcademicYearService $service): RedirectResponse
    {
        $service->close($academicYear);

        return back()->with('status', 'L\'année '.$academicYear->name.' est clôturée.');
    }

    public function archive(AcademicYear $academicYear, AcademicYearService $service): RedirectResponse
    {
        $service->archive($academicYear);

        return back()->with('status', 'L\'année '.$academicYear->name.' est archivée.');
    }

    public function select(Request $request, AcademicYearContext $context): RedirectResponse
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ]);

        $context->select(AcademicYear::query()->findOrFail($data['academic_year_id']));

        return back();
    }
}
