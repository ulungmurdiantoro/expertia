<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Enums\EditorialStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(12),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'status' => ArticleStatus::Draft->value,
            'editorial_status' => EditorialStatus::Draft->value,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::InReview->value,
            'editorial_status' => EditorialStatus::PendingReview->value,
            'submitted_at' => now(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::Published->value,
            'editorial_status' => EditorialStatus::Approved->value,
            'published_at' => now()->subDay(),
        ]);
    }
}
