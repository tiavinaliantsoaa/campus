<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Enums\AttendanceStatus;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Applicant;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FeeInstallment;
use App\Models\Payment;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardMetrics
{
    public function __construct(private FinanceService $finance) {}

    /**
     * @return array<string, mixed>
     */
    public function campus(AcademicYear $year): array
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $attendance = $this->attendanceRate($year, $weekStart, $weekEnd);
        $finance = $this->financeTotals($year);

        return [
            'students' => Enrollment::query()->where('academic_year_id', $year->id)->where('status', 'active')->count(),
            'teachers' => Teacher::query()->where('status', 'active')->count(),
            'groups' => StudentGroup::query()->where('academic_year_id', $year->id)->count(),
            'courses_this_week' => Course::query()
                ->where('academic_year_id', $year->id)
                ->where('status', 'scheduled')
                ->whereBetween('starts_at', [$weekStart, $weekEnd])
                ->count(),
            'attendance_rate' => $attendance['rate'],
            'attendance_count' => $attendance['count'],
            'payments_received' => $finance['received'],
            'payments_pending' => $finance['pending'],
            'pending_installments' => $finance['pending_count'],
            'admissions_pending' => Applicant::query()
                ->where('academic_year_id', $year->id)
                ->whereHas('admissionStatus', fn ($query) => $query->where('is_terminal', false))
                ->count(),
        ];
    }

    /**
     * @return Collection<int, Course>
     */
    public function coursesOn(Carbon $day, AcademicYear $year, ?User $user = null): Collection
    {
        $query = Course::query()
            ->with(['subject', 'teacher', 'group', 'room'])
            ->where('academic_year_id', $year->id)
            ->where('status', 'scheduled')
            ->whereDate('starts_at', $day->toDateString())
            ->orderBy('starts_at')
            ->orderBy('id');

        if ($user && ! $user->hasAnyRole(['administration', 'pedagogical'])) {
            $query = app(Visibility::class)->courses($user, $year)
                ->with(['subject', 'teacher', 'group', 'room'])
                ->where('status', 'scheduled')
                ->whereDate('starts_at', $day->toDateString())
                ->orderBy('starts_at')
                ->orderBy('id');
        }

        return $query->get();
    }

    /**
     * @return list<array{tone: string, message: string}>
     */
    public function alerts(AcademicYear $year, array $metrics): array
    {
        $alerts = [];

        if ((int) $metrics['admissions_pending'] > 0) {
            $alerts[] = [
                'tone' => 'warning',
                'message' => $metrics['admissions_pending'].' candidature(s) en attente de décision.',
            ];
        }

        if (bccomp((string) $metrics['payments_pending'], '0', 2) > 0) {
            $alerts[] = [
                'tone' => 'danger',
                'message' => $metrics['pending_installments'].' échéance(s) restent ouvertes sur l\'année '.$year->name.'.',
            ];
        }

        if ($metrics['attendance_rate'] !== null && $metrics['attendance_rate'] < 75) {
            $alerts[] = [
                'tone' => 'danger',
                'message' => 'Le taux de présence de la semaine est de '.$metrics['attendance_rate'].' %.',
            ];
        }

        return $alerts;
    }

    /**
     * @return Collection<int, array{at: Carbon, label: string, detail: string}>
     */
    public function activity(AcademicYear $year): Collection
    {
        $payments = Payment::query()
            ->with('student')
            ->where('academic_year_id', $year->id)
            ->latest('paid_on')
            ->latest('id')
            ->limit(4)
            ->get()
            ->map(fn (Payment $payment): array => [
                'at' => $payment->created_at,
                'label' => 'Paiement enregistré',
                'detail' => $payment->student->full_name.' · '.$payment->receipt_number,
            ]);

        $applicants = Applicant::query()
            ->with('admissionStatus')
            ->where('academic_year_id', $year->id)
            ->latest('id')
            ->limit(4)
            ->get()
            ->map(fn (Applicant $applicant): array => [
                'at' => $applicant->created_at,
                'label' => 'Candidature',
                'detail' => $applicant->full_name.' · '.$applicant->admissionStatus->name,
            ]);

        $announcements = Announcement::query()
            ->where('academic_year_id', $year->id)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'at' => $announcement->published_at,
                'label' => 'Annonce',
                'detail' => $announcement->title,
            ]);

        return $payments->merge($applicants)->merge($announcements)
            ->sortByDesc(fn (array $item) => $item['at'])
            ->take(6)
            ->values();
    }

    /**
     * @return array{rate: ?int, count: int}
     */
    public function attendanceRate(AcademicYear $year, Carbon $from, Carbon $to, ?int $studentId = null): array
    {
        $query = AttendanceRecord::query()
            ->whereHas('course', fn ($course) => $course->where('academic_year_id', $year->id)->whereBetween('starts_at', [$from, $to]));

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $rows = $query->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $count = (int) $rows->sum();

        if ($count === 0) {
            return ['rate' => null, 'count' => 0];
        }

        $present = (int) ($rows[AttendanceStatus::Present->value] ?? 0) + (int) ($rows[AttendanceStatus::Late->value] ?? 0);

        return [
            'rate' => (int) round(($present / $count) * 100),
            'count' => $count,
        ];
    }

    /**
     * @return array{received: string, pending: string, pending_count: int}
     */
    public function financeTotals(AcademicYear $year): array
    {
        $received = (string) Payment::query()->where('academic_year_id', $year->id)->sum('amount');

        $paidSub = Payment::query()
            ->selectRaw('fee_installment_id, SUM(amount) as paid')
            ->whereNull('deleted_at')
            ->groupBy('fee_installment_id');

        $pending = FeeInstallment::query()
            ->where('fee_installments.academic_year_id', $year->id)
            ->leftJoinSub($paidSub, 'paid_sums', 'paid_sums.fee_installment_id', '=', 'fee_installments.id')
            ->selectRaw('COALESCE(SUM(CASE WHEN (fee_installments.amount_due - fee_installments.discount - COALESCE(paid_sums.paid, 0)) > 0 THEN (fee_installments.amount_due - fee_installments.discount - COALESCE(paid_sums.paid, 0)) ELSE 0 END), 0) as pending')
            ->value('pending');

        $pendingCount = FeeInstallment::query()
            ->where('academic_year_id', $year->id)
            ->withSum('payments', 'amount')
            ->get()
            ->filter(fn (FeeInstallment $installment): bool => bccomp($this->finance->remaining($installment), '0', 2) > 0)
            ->count();

        return [
            'received' => number_format((float) $received, 2, '.', ''),
            'pending' => number_format((float) $pending, 2, '.', ''),
            'pending_count' => $pendingCount,
        ];
    }

    public function upcomingAssessments(User $user, AcademicYear $year): Collection
    {
        return Assessment::query()
            ->with(['subject', 'group'])
            ->where('academic_year_id', $year->id)
            ->where('status', AssessmentStatus::Validated)
            ->latest('assessed_on')
            ->limit(5)
            ->get();
    }
}
