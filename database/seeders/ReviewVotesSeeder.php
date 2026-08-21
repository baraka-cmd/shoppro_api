<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Review\Review;
use App\Models\Review\ReviewVote;

class ReviewVotesSeeder extends Seeder
{
    /**
     * Seed des votes sur les avis
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        $reviews = Review::where('status', 'approved')->take(20)->get();

        foreach ($reviews as $reviewIndex => $review) {
            // 2-6 votes par avis
            $voteCount = rand(2, 6);
            
            for ($i = 0; $i < $voteCount; $i++) {
                $user = $users[rand(0, count($users) - 1)];
                
                // Éviter les doublons
                $existingVote = ReviewVote::where('review_id', $review->id)
                                          ->where('user_id', $user->id)
                                          ->first();
                
                if ($existingVote) {
                    continue;
                }
                
                ReviewVote::create([
                    'review_id' => $review->id,
                    'user_id' => $user->id,
                    'vote_type' => rand(0, 1) === 0 ? 'helpful' : 'not_helpful',
                    'voted_at' => now()->subDays(rand(1, 15)),
                ]);
            }
            
            // Mettre à jour les compteurs
            $review->update([
                'helpful_count' => $review->votes()->where('vote_type', 'helpful')->count(),
                'not_helpful_count' => $review->votes()->where('vote_type', 'not_helpful')->count(),
            ]);
        }
    }
}