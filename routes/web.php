<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentGroupController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/annee/selection', [AcademicYearController::class, 'select'])->name('academic-years.select');
    Route::get('/recherche', SearchController::class)->name('search');
    Route::get('/compte', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/compte', [AccountController::class, 'update'])->name('account.update');
    Route::post('/famille/etudiant', [AccountController::class, 'selectChild'])->name('family.select');
    Route::post('/notifications/{notification}/lire', [AccountController::class, 'readNotification'])->name('notifications.read');

    Route::middleware('permission:academic-years.manage')->group(function (): void {
        Route::resource('annees-academiques', AcademicYearController::class)
            ->parameters(['annees-academiques' => 'academicYear'])
            ->names('academic-years')
            ->except(['destroy']);
        Route::post('/annees-academiques/{academicYear}/activer', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
        Route::post('/annees-academiques/{academicYear}/cloturer', [AcademicYearController::class, 'close'])->name('academic-years.close');
        Route::post('/annees-academiques/{academicYear}/archiver', [AcademicYearController::class, 'archive'])->name('academic-years.archive');
    });

    Route::middleware('permission:administration.manage')->group(function (): void {
        Route::resource('utilisateurs', UserController::class)->names('users')->except(['show', 'destroy']);
    });

    Route::get('/etudiants', [StudentController::class, 'index'])->name('students.index')->middleware('permission:students.view');
    Route::get('/etudiants/nouveau', [StudentController::class, 'create'])->name('students.create')->middleware('permission:students.manage');
    Route::post('/etudiants', [StudentController::class, 'store'])->name('students.store')->middleware('permission:students.manage');
    Route::get('/etudiants/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('/etudiants/{student}/modifier', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/etudiants/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::post('/etudiants/{student}/archiver', [StudentController::class, 'archive'])->name('students.archive');
    Route::delete('/etudiants/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::get('/etudiants/{student}/photo', [StudentController::class, 'photo'])->name('students.photo');

    Route::get('/enseignants/nouveau', [TeacherController::class, 'create'])->name('teachers.create')->middleware('permission:teachers.manage');
    Route::post('/enseignants', [TeacherController::class, 'store'])->name('teachers.store')->middleware('permission:teachers.manage');
    Route::middleware('permission:teachers.view')->group(function (): void {
        Route::get('/enseignants', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('/enseignants/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');
        Route::get('/enseignants/{teacher}/photo', [TeacherController::class, 'photo'])->name('teachers.photo');
    });
    Route::middleware('permission:teachers.manage')->group(function (): void {
        Route::get('/enseignants/{teacher}/modifier', [TeacherController::class, 'edit'])->name('teachers.edit');
        Route::put('/enseignants/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('/enseignants/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
    });

    Route::get('/groupes/nouveau', [StudentGroupController::class, 'create'])->name('groups.create')->middleware('permission:groups.manage');
    Route::middleware('permission:groups.view')->group(function (): void {
        Route::get('/groupes', [StudentGroupController::class, 'index'])->name('groups.index');
        Route::get('/groupes/{studentGroup}', [StudentGroupController::class, 'show'])->name('groups.show');
        Route::get('/niveaux', [CatalogController::class, 'levels'])->name('levels.index');
        Route::get('/matieres', [CatalogController::class, 'subjects'])->name('subjects.index');
    });
    Route::middleware('permission:groups.manage')->group(function (): void {
        Route::post('/groupes', [StudentGroupController::class, 'store'])->name('groups.store');
        Route::get('/groupes/{studentGroup}/modifier', [StudentGroupController::class, 'edit'])->name('groups.edit');
        Route::put('/groupes/{studentGroup}', [StudentGroupController::class, 'update'])->name('groups.update');
        Route::patch('/groupes/{studentGroup}/couleur', [StudentGroupController::class, 'updateColor'])->name('groups.color');
        Route::delete('/groupes/{studentGroup}', [StudentGroupController::class, 'destroy'])->name('groups.destroy');
        Route::post('/niveaux', [CatalogController::class, 'storeLevel'])->name('levels.store');
        Route::delete('/niveaux/{level}', [CatalogController::class, 'destroyLevel'])->name('levels.destroy');
        Route::post('/matieres', [CatalogController::class, 'storeSubject'])->name('subjects.store');
        Route::delete('/matieres/{subject}', [CatalogController::class, 'destroySubject'])->name('subjects.destroy');
    });

    Route::middleware('permission:timetable.view')->group(function (): void {
        Route::get('/emploi-du-temps', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/salles', [CatalogController::class, 'rooms'])->name('rooms.index');
    });
    Route::middleware('permission:timetable.manage')->group(function (): void {
        Route::get('/emploi-du-temps/nouveau', [CourseController::class, 'create'])->name('courses.create');
        Route::post('/emploi-du-temps', [CourseController::class, 'store'])->name('courses.store');
        Route::get('/emploi-du-temps/{course}/modifier', [CourseController::class, 'edit'])->name('courses.edit');
        Route::put('/emploi-du-temps/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/emploi-du-temps/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('/salles', [CatalogController::class, 'storeRoom'])->name('rooms.store');
        Route::delete('/salles/{room}', [CatalogController::class, 'destroyRoom'])->name('rooms.destroy');
    });

    Route::middleware('permission:attendance.view')->group(function (): void {
        Route::get('/assiduite', [AttendanceController::class, 'index'])->name('attendance.index');
    });
    Route::get('/assiduite/{course}', [AttendanceController::class, 'roll'])->name('attendance.roll');
    Route::post('/assiduite/{course}', [AttendanceController::class, 'store'])->name('attendance.store');

    Route::get('/notes/nouvelle', [AssessmentController::class, 'create'])->name('assessments.create')->middleware('permission:grades.enter');
    Route::middleware('permission:grades.view')->group(function (): void {
        Route::get('/notes', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/notes/{assessment}', [AssessmentController::class, 'entry'])->name('assessments.entry');
    });
    Route::middleware('permission:grades.enter')->group(function (): void {
        Route::post('/notes', [AssessmentController::class, 'store'])->name('assessments.store');
        Route::post('/notes/{assessment}/saisie', [AssessmentController::class, 'save'])->name('assessments.save');
        Route::post('/notes/{assessment}/soumettre', [AssessmentController::class, 'submit'])->name('assessments.submit');
    });
    Route::middleware('permission:grades.validate')->group(function (): void {
        Route::post('/notes/{assessment}/valider', [AssessmentController::class, 'validateAssessment'])->name('assessments.validate');
        Route::post('/notes/{assessment}/rouvrir', [AssessmentController::class, 'reopen'])->name('assessments.reopen');
    });

    Route::middleware('permission:finance.view')->group(function (): void {
        Route::get('/scolarite', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('/scolarite/etudiants/{student}', [FinanceController::class, 'statement'])->name('finance.statement');
        Route::get('/scolarite/recus/{payment}', [FinanceController::class, 'receipt'])->name('finance.receipt');
    });
    Route::middleware('permission:finance.manage')->group(function (): void {
        Route::post('/scolarite/tarifs', [FinanceController::class, 'storeTariff'])->name('finance.tariffs.store');
        Route::post('/scolarite/tarifs/{feeTariff}/affecter', [FinanceController::class, 'assignTariff'])->name('finance.tariffs.assign');
        Route::get('/scolarite/echeances/nouvelle', [FinanceController::class, 'createInstallment'])->name('finance.installments.create');
        Route::post('/scolarite/echeances', [FinanceController::class, 'storeInstallment'])->name('finance.installments.store');
        Route::post('/scolarite/paiements', [FinanceController::class, 'storePayment'])->name('finance.payments.store');
        Route::delete('/scolarite/paiements/{payment}', [FinanceController::class, 'destroyPayment'])->name('finance.payments.destroy');
    });

    Route::get('/admissions/nouvelle', [ApplicantController::class, 'create'])->name('applicants.create')->middleware('permission:admissions.manage');
    Route::middleware('permission:admissions.view')->group(function (): void {
        Route::get('/admissions', [ApplicantController::class, 'index'])->name('applicants.index');
        Route::get('/admissions/{applicant}', [ApplicantController::class, 'show'])->name('applicants.show');
    });
    Route::middleware('permission:admissions.manage')->group(function (): void {
        Route::post('/admissions', [ApplicantController::class, 'store'])->name('applicants.store');
        Route::get('/admissions/{applicant}/modifier', [ApplicantController::class, 'edit'])->name('applicants.edit');
        Route::put('/admissions/{applicant}', [ApplicantController::class, 'update'])->name('applicants.update');
        Route::post('/admissions/{applicant}/statut', [ApplicantController::class, 'status'])->name('applicants.status');
        Route::post('/admissions/{applicant}/convertir', [ApplicantController::class, 'convert'])->name('applicants.convert');
        Route::delete('/admissions/{applicant}', [ApplicantController::class, 'destroy'])->name('applicants.destroy');
    });

    Route::middleware('permission:documents.view')->group(function (): void {
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/nouveau', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}/telecharger', [DocumentController::class, 'download'])->name('documents.download');
    });
    Route::post('/documents/{document}/archiver', [DocumentController::class, 'archive'])->name('documents.archive');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/annonces/nouvelle', [AnnouncementController::class, 'create'])->name('announcements.create')->middleware('permission:announcements.manage');
    Route::middleware('permission:announcements.view')->group(function (): void {
        Route::get('/annonces', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/annonces/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
    });
    Route::middleware('permission:announcements.manage')->group(function (): void {
        Route::post('/annonces', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('/annonces/{announcement}/modifier', [AnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::put('/annonces/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/annonces/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    Route::get('/messages', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::get('/messages/nouveau', [InquiryController::class, 'create'])->name('inquiries.create');
    Route::post('/messages', [InquiryController::class, 'store'])->name('inquiries.store');
    Route::get('/messages/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
    Route::post('/messages/{inquiry}/reponse', [InquiryController::class, 'reply'])->name('inquiries.reply');
    Route::post('/messages/{inquiry}/cloturer', [InquiryController::class, 'close'])->name('inquiries.close');

    Route::middleware('permission:reports.view')->group(function (): void {
        Route::get('/rapports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/rapports/presence', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/rapports/resultats', [ReportController::class, 'grades'])->name('reports.grades');
        Route::get('/rapports/finance', [ReportController::class, 'finance'])->name('reports.finance');
    });
});
