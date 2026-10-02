<?php

namespace App\Http\Controllers;

use App\Enums\Audience;
use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use App\Models\Role;
use App\Models\StudentGroup;
use App\Models\User;
use App\Notifications\CampusNotification;
use App\Services\AcademicCalendar;
use App\Services\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request, Visibility $visibility): View
    {
        Gate::authorize('viewAny', Announcement::class);
        $manage = $request->user()->hasPermission('announcements.manage');

        $announcements = $visibility->announcements($request->user(), $manage)
            ->with('author')
            ->latest('published_at')
            ->latest('id')
            ->paginate(12);

        return view('announcements.index', compact('announcements', 'manage'));
    }

    public function create(): View
    {
        Gate::authorize('create', Announcement::class);

        return view('announcements.form', $this->formData(new Announcement));
    }

    public function store(Request $request, AcademicCalendar $calendar): RedirectResponse
    {
        Gate::authorize('create', Announcement::class);
        $year = $this->academicYear();
        $calendar->ensureOpen($year);
        $data = $this->validated($request);

        $announcement = Announcement::query()->create([
            'academic_year_id' => $year->id,
            'author_id' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'published_at' => $request->boolean('publish') ? ($data['published_at'] ?? now()) : null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $this->syncTargets($announcement, $data);
        $this->notify($announcement);

        return redirect()->route('announcements.show', $announcement)->with('status', 'Annonce enregistrée.');
    }

    public function show(Announcement $announcement): View
    {
        Gate::authorize('view', $announcement);
        $announcement->load(['author', 'targets']);

        return view('announcements.show', ['announcement' => $announcement]);
    }

    public function edit(Announcement $announcement): View
    {
        Gate::authorize('update', $announcement);
        $announcement->load('targets');

        return view('announcements.form', $this->formData($announcement));
    }

    public function update(Request $request, Announcement $announcement, AcademicCalendar $calendar): RedirectResponse
    {
        Gate::authorize('update', $announcement);
        $calendar->ensureOpen($announcement->academicYear);
        $wasPublished = $announcement->published_at !== null;
        $data = $this->validated($request);

        $announcement->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'published_at' => $request->boolean('publish') ? ($announcement->published_at ?? $data['published_at'] ?? now()) : null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
        $this->syncTargets($announcement, $data);

        if (! $wasPublished) {
            $this->notify($announcement->refresh());
        }

        return redirect()->route('announcements.show', $announcement)->with('status', 'Annonce mise à jour.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        Gate::authorize('delete', $announcement);
        $announcement->delete();

        return redirect()->route('announcements.index')->with('status', 'Annonce supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', Rule::enum(Audience::class)],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
            'publish' => ['sometimes', 'boolean'],
            'role_id' => ['required_if:audience,role', 'nullable', 'integer', 'exists:roles,id'],
            'student_group_id' => ['required_if:audience,group', 'nullable', 'integer', 'exists:student_groups,id'],
        ], [
            'role_id.required_if' => 'Choisissez le rôle destinataire.',
            'student_group_id.required_if' => 'Choisissez le groupe destinataire.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTargets(Announcement $announcement, array $data): void
    {
        $announcement->targets()->delete();

        if ($announcement->audience === Audience::Role && ! empty($data['role_id'])) {
            AnnouncementTarget::query()->create([
                'announcement_id' => $announcement->id,
                'target_type' => 'role',
                'target_id' => $data['role_id'],
            ]);
        }

        if ($announcement->audience === Audience::Group && ! empty($data['student_group_id'])) {
            AnnouncementTarget::query()->create([
                'announcement_id' => $announcement->id,
                'target_type' => 'group',
                'target_id' => $data['student_group_id'],
            ]);
        }
    }

    private function notify(Announcement $announcement): void
    {
        if (! $announcement->isPublished()) {
            return;
        }

        $announcement->load('targets');
        $query = User::query()->orderBy('id');

        if ($announcement->audience === Audience::Role) {
            $roleIds = $announcement->targets->where('target_type', 'role')->pluck('target_id');
            $query->whereHas('roles', fn ($roles) => $roles->whereIn('roles.id', $roleIds));
        }

        if ($announcement->audience === Audience::Group) {
            $groupIds = $announcement->targets->where('target_type', 'group')->pluck('target_id');
            $query->where(function ($users) use ($groupIds): void {
                $users->whereHas('student.enrollments', fn ($enrollment) => $enrollment->whereIn('student_group_id', $groupIds))
                    ->orWhereHas('guardian.students.enrollments', fn ($enrollment) => $enrollment->whereIn('student_group_id', $groupIds))
                    ->orWhereHas('teacher.assignments', fn ($assignment) => $assignment->whereIn('student_group_id', $groupIds));
            });
        }

        $query->chunkById(100, function ($users) use ($announcement): void {
            Notification::send($users, new CampusNotification(
                $announcement->title,
                route('announcements.show', $announcement),
            ));
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Announcement $announcement): array
    {
        return [
            'announcement' => $announcement,
            'audiences' => Audience::cases(),
            'roles' => Role::query()->orderBy('name')->get(),
            'groups' => StudentGroup::query()->where('academic_year_id', $this->academicYear()->id)->orderBy('name')->get(),
        ];
    }
}
