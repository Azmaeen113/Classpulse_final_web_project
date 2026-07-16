<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Services\AiQuestionGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ThemeAndAiErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_preference_returns_json_without_error(): void
    {
        $response = $this->postJson(route('preferences.theme'), [
            'theme' => 'dark',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('theme', 'dark');
    }

    public function test_ai_generation_failure_shows_generic_message(): void
    {
        $teacher = User::factory()->teacher()->create();
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => \App\Models\Classroom::create([
                'teacher_id' => $teacher->id,
                'name' => 'AI Class',
                'room_code' => 'AICODE',
                'is_active' => true,
            ])->id,
            'title' => 'AI Quiz',
            'is_published' => false,
            'default_time_limit_seconds' => 30,
        ]);

        $this->mock(AiQuestionGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->andThrow(new RuntimeException('sk-secret-provider-leak'));
        });

        $response = $this->actingAs($teacher)->from(route('teacher.questions.ai', $quiz))->post(
            route('teacher.questions.ai.store', $quiz),
            [
                'topic' => 'Photosynthesis',
                'count' => 3,
                'difficulty' => 'easy',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors(['topic']);
        $errors = session('errors')->get('topic');
        $this->assertStringContainsString("Couldn't generate questions right now", $errors[0]);
        $this->assertStringNotContainsString('sk-secret', $errors[0]);
    }
}
