<?php

use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Api\PollController;
use App\Http\Controllers\Auth\ContinueZoneController;
use App\Http\Controllers\Auth\ResetBrowserSessionController;
use App\Http\Controllers\Auth\ZoneClaimController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\HistoryController as StudentHistoryController;
use App\Http\Controllers\Student\JoinClassroomController;
use App\Http\Controllers\Student\LiveAnswerController;
use App\Http\Controllers\Student\SessionResultController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Teacher\AiQuestionController;
use App\Http\Controllers\Teacher\ClassroomController;
use App\Http\Controllers\Teacher\HistoryController as TeacherHistoryController;
use App\Http\Controllers\Teacher\LiveSessionController;
use App\Http\Controllers\Teacher\QuestionController;
use App\Http\Controllers\Teacher\QuizController;
use App\Http\Controllers\Teacher\SessionAnalyticsController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/preferences/theme', [PreferenceController::class, 'setTheme'])->name('preferences.theme');

// One-time handoff into a role-zone session (supports multi-account tabs)
Route::get('/zone-claim/{zone}/{token}', ZoneClaimController::class)
    ->whereIn('zone', ['teacher', 'student', 'admin'])
    ->middleware('throttle:30,1')
    ->name('zone.claim');

// Shared-session → role-zone bridge (breaks login/dashboard redirect loops)
Route::get('/auth/continue', ContinueZoneController::class)
    ->middleware('auth')
    ->name('auth.continue');

// Emergency escape hatch for ERR_TOO_MANY_REDIRECTS
Route::get('/auth/reset-browser', ResetBrowserSessionController::class)
    ->name('auth.reset-browser');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::prefix('api/poll')->middleware('throttle:120,1')->group(function () {
        Route::get('/active-sessions', [PollController::class, 'activeSessionsForStudent'])->name('api.poll.active-sessions');
        Route::get('/sessions/{session}/state', [PollController::class, 'sessionState'])->name('api.poll.session-state');
        Route::get('/sessions/{session}/response-counter', [PollController::class, 'responseCounter'])->name('api.poll.response-counter');
        Route::get('/sessions/{session}/answer-distribution', [PollController::class, 'answerDistribution'])->name('api.poll.answer-distribution');
        Route::get('/sessions/{session}/leaderboard', [PollController::class, 'leaderboard'])->name('api.poll.leaderboard');
        Route::get('/notifications', [PollController::class, 'notificationsUnread'])->name('api.poll.notifications');
    });
});

Route::middleware(['auth', 'active', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

    Route::resource('classrooms', ClassroomController::class);

    Route::resource('quizzes', QuizController::class);
    Route::post('/quizzes/{quiz}/publish', [QuizController::class, 'publish'])->name('quizzes.publish');
    Route::get('/quizzes/{quiz}/questions/create', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/quizzes/{quiz}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('/quizzes/{quiz}/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/quizzes/{quiz}/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/quizzes/{quiz}/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('/quizzes/{quiz}/questions/reorder', [QuestionController::class, 'reorder'])->name('questions.reorder');
    Route::post('/quizzes/{quiz}/questions/timings', [QuestionController::class, 'updateTimings'])->name('questions.timings');

    Route::get('/quizzes/{quiz}/ai-questions', [AiQuestionController::class, 'create'])->name('questions.ai');
    Route::post('/quizzes/{quiz}/ai-questions', [AiQuestionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('questions.ai.store');

    Route::post('/quizzes/{quiz}/live/start', [LiveSessionController::class, 'start'])->name('live.start');
    Route::get('/live/{session}', [LiveSessionController::class, 'show'])->name('live.show');
    Route::post('/live/{session}/pause', [LiveSessionController::class, 'pause'])->name('live.pause');
    Route::post('/live/{session}/resume', [LiveSessionController::class, 'resume'])->name('live.resume');
    Route::post('/live/{session}/extend', [LiveSessionController::class, 'extend'])->name('live.extend');
    Route::post('/live/{session}/set-time', [LiveSessionController::class, 'setTime'])->name('live.set-time');
    Route::post('/live/{session}/next', [LiveSessionController::class, 'next'])->name('live.next');
    Route::post('/live/{session}/skip', [LiveSessionController::class, 'skip'])->name('live.skip');
    Route::post('/live/{session}/reveal', [LiveSessionController::class, 'reveal'])->name('live.reveal');
    Route::post('/live/{session}/end', [LiveSessionController::class, 'end'])->name('live.end');
    Route::get('/live/{session}/leaderboard', [LiveSessionController::class, 'leaderboard'])->name('live.leaderboard');

    Route::get('/analytics/{session}', [SessionAnalyticsController::class, 'show'])->name('analytics.show');
    Route::get('/analytics/{session}/export', [SessionAnalyticsController::class, 'exportCsv'])->name('analytics.export');

    Route::get('/history', [TeacherHistoryController::class, 'index'])->name('history');
});

Route::middleware(['auth', 'active', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

    Route::get('/join', [JoinClassroomController::class, 'show'])->name('join');
    Route::post('/join', [JoinClassroomController::class, 'join'])->name('join.submit');

    Route::get('/live/{session}', [LiveAnswerController::class, 'show'])->name('live.show');
    Route::post('/live/{session}/answer', [LiveAnswerController::class, 'submit'])->name('live.answer');

    Route::get('/history', [StudentHistoryController::class, 'index'])->name('history');
    Route::get('/sessions/{session}/result', [SessionResultController::class, 'show'])->name('sessions.result');
});

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/activate', [UserManagementController::class, 'activate'])->name('users.activate');
    Route::get('/activity', [AdminActivityLogController::class, 'index'])->name('activity.index');
});

require __DIR__.'/auth.php';
