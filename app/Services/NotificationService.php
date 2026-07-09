<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Notification;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Support\Carbon;

class NotificationService
{
    public function notify(User $user, string $type, string $title, ?string $body = null, ?array $payload = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
        ]);
    }

    public function studentJoined(Classroom $classroom, User $student): void
    {
        $teacher = $classroom->teacher;

        if ($teacher) {
            $this->notify(
                $teacher,
                'classroom.join',
                'Student joined',
                "{$student->name} joined {$classroom->name}.",
                ['classroom_id' => $classroom->id, 'student_id' => $student->id]
            );
        }
    }

    public function sessionStarted(QuizSession $session): void
    {
        $session->loadMissing('classroom.students', 'quiz');
        $this->notifyClassroomStudents(
            $session,
            'session.started',
            'Quiz session started',
            "A live quiz “{$session->quiz->title}” has started in {$session->classroom->name}."
        );
    }

    public function sessionEnded(QuizSession $session): void
    {
        $session->loadMissing('classroom.students', 'quiz');
        $this->notifyClassroomStudents(
            $session,
            'session.results',
            'Quiz results available',
            "Results for “{$session->quiz->title}” are ready."
        );
    }

    /**
     * Batch-insert classroom-wide notifications (avoids N synchronous create() calls).
     */
    private function notifyClassroomStudents(
        QuizSession $session,
        string $type,
        string $title,
        string $body
    ): void {
        $students = $session->classroom->students;
        if ($students->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $payload = json_encode([
            'quiz_session_id' => $session->id,
            'classroom_id' => $session->classroom_id,
        ]);

        $rows = $students->map(fn (User $student) => [
            'user_id' => $student->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 100) as $chunk) {
            Notification::query()->insert($chunk);
        }
    }

    public function unreadCount(User $user): int
    {
        return $user->appNotifications()->whereNull('read_at')->count();
    }
}
