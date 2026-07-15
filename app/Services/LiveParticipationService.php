<?php

namespace App\Services;

use App\Models\QuizSession;
use Illuminate\Support\Facades\Cache;

/**
 * Tracks which students have actually opened / answered in a live session
 * (so "everyone answered" does not wait on absent classmates).
 */
class LiveParticipationService
{
    public function touch(QuizSession $session, int $studentId): void
    {
        $key = $this->key($session);
        $map = Cache::get($key, []);
        $map[(string) $studentId] = now()->timestamp;
        Cache::put($key, $map, now()->addHours(6));
    }

    /**
     * @return list<int>
     */
    public function activeStudentIds(QuizSession $session): array
    {
        $map = Cache::get($this->key($session), []);
        $cutoff = now()->subMinutes(45)->timestamp;

        $ids = [];
        foreach ($map as $studentId => $seenAt) {
            if ((int) $seenAt >= $cutoff) {
                $ids[] = (int) $studentId;
            }
        }

        return $ids;
    }

    public function activeCount(QuizSession $session): int
    {
        return count($this->activeStudentIds($session));
    }

    private function key(QuizSession $session): string
    {
        return 'cp_live_participants:'.$session->id;
    }
}
