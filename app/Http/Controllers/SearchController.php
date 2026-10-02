<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Services\Visibility;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    use ResolvesAcademicYear;

    public function __invoke(Request $request, Visibility $visibility): View
    {
        $term = trim(str_replace(['%', '_'], '', $request->string('q')->toString()));
        $year = $this->academicYear();
        $user = $request->user();

        $students = $user->hasPermission('students.view') && strlen($term) >= 2
            ? $visibility->students($user)->search($term)->orderBy('last_name')->limit(8)->get()
            : collect();

        $teachers = $user->hasPermission('teachers.view') && strlen($term) >= 2
            ? Teacher::query()->search($term)->orderBy('last_name')->limit(8)->get()
            : collect();

        $groups = $user->hasPermission('groups.view') && strlen($term) >= 2
            ? StudentGroup::query()->where('academic_year_id', $year->id)->where(function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
            })->orderBy('name')->limit(8)->get()
            : collect();

        return view('search.index', compact('term', 'students', 'teachers', 'groups'));
    }
}
