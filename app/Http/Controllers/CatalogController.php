<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Room;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function levels(): View
    {
        abort_unless(request()->user()->hasPermission('groups.view'), 403);

        return view('levels.index', [
            'levels' => Level::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function storeLevel(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('groups.manage'), 403);
        Level::query()->create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:20', 'unique:levels,code'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]));

        return back()->with('status', 'Niveau ajouté.');
    }

    public function destroyLevel(Level $level): RedirectResponse
    {
        abort_unless(request()->user()->hasPermission('groups.manage'), 403);
        $level->delete();

        return back()->with('status', 'Niveau supprimé.');
    }

    public function subjects(): View
    {
        abort_unless(request()->user()->hasPermission('groups.view'), 403);

        return view('subjects.index', [
            'subjects' => Subject::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('groups.manage'), 403);
        Subject::query()->create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code'],
        ]));

        return back()->with('status', 'Matière ajoutée.');
    }

    public function destroySubject(Subject $subject): RedirectResponse
    {
        abort_unless(request()->user()->hasPermission('groups.manage'), 403);
        $subject->delete();

        return back()->with('status', 'Matière supprimée.');
    }

    public function rooms(): View
    {
        abort_unless(request()->user()->hasPermission('timetable.view'), 403);

        return view('rooms.index', [
            'rooms' => Room::query()->orderBy('code')->get(),
        ]);
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('timetable.manage'), 403);
        Room::query()->create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:20', 'unique:rooms,code'],
            'building' => ['nullable', 'string', 'max:120'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]));

        return back()->with('status', 'Salle ajoutée.');
    }

    public function destroyRoom(Room $room): RedirectResponse
    {
        abort_unless(request()->user()->hasPermission('timetable.manage'), 403);
        $room->delete();

        return back()->with('status', 'Salle supprimée.');
    }
}
