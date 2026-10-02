<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Models\Inquiry;
use App\Models\InquiryReply;
use App\Notifications\CampusNotification;
use App\Services\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InquiryController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request, Visibility $visibility): View
    {
        Gate::authorize('viewAny', Inquiry::class);

        $inquiries = $visibility->inquiries($request->user())
            ->with(['author', 'student'])
            ->latest('id')
            ->paginate(15);

        return view('inquiries.index', ['inquiries' => $inquiries]);
    }

    public function create(Visibility $visibility): View
    {
        Gate::authorize('create', Inquiry::class);
        $user = request()->user();
        $students = $visibility->students($user)->orderBy('last_name')->limit(100)->get();

        return view('inquiries.create', ['students' => $students]);
    }

    public function store(Request $request, Visibility $visibility): RedirectResponse
    {
        Gate::authorize('create', Inquiry::class);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:5000'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
        ]);

        if (! empty($data['student_id'])) {
            $student = $visibility->students($request->user())->whereKey($data['student_id'])->first();
            abort_unless($student, 403);
        }

        $inquiry = Inquiry::query()->create([
            'author_id' => $request->user()->id,
            'student_id' => $data['student_id'] ?? $request->user()->student?->id,
            'academic_year_id' => $this->academicYear()->id,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'status' => InquiryStatus::Open,
        ]);

        return redirect()->route('inquiries.show', $inquiry)->with('status', 'Message envoyé à l\'établissement.');
    }

    public function show(Inquiry $inquiry): View
    {
        Gate::authorize('view', $inquiry);
        $inquiry->load(['author', 'student', 'replies.user']);

        return view('inquiries.show', ['inquiry' => $inquiry]);
    }

    public function reply(Request $request, Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('reply', $inquiry);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        InquiryReply::query()->create([
            'inquiry_id' => $inquiry->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $staff = $request->user()->hasPermission('communication.manage');
        $inquiry->update([
            'status' => $staff ? InquiryStatus::Answered : InquiryStatus::Open,
        ]);

        $recipient = $staff ? $inquiry->author : null;

        if ($recipient && $recipient->id !== $request->user()->id) {
            $recipient->notify(new CampusNotification(
                'Réponse : '.$inquiry->subject,
                route('inquiries.show', $inquiry),
            ));
        }

        return back()->with('status', 'Réponse envoyée.');
    }

    public function close(Inquiry $inquiry): RedirectResponse
    {
        abort_unless(request()->user()->hasPermission('communication.manage'), 403);
        $inquiry->update(['status' => InquiryStatus::Closed]);

        return back()->with('status', 'Message clôturé.');
    }
}
