<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review\Review;
use App\Models\Review\ReviewImage;

class ReviewImagesSeeder extends Seeder
{
    /**
     * Seed des images d'avis
     */
    public function run(): void
    {
        $reviews = Review::where('status', 'approved')
                         ->where('rating', '>=', 4)
                         ->take(15)
                         ->get();

        foreach ($reviews as $index => $review) {
            // 1-3 images par avis
            $imageCount = rand(1, 3);
            
            for ($i = 0; $i < $imageCount; $i++) {
                ReviewImage::create([
                    'review_id' => $review->id,
                    'image_path' => 'reviews/review_' . $review->id . '_' . ($i + 1) . '.jpg',
                    'thumbnail_path' => 'reviews/review_' . $review->id . '_' . ($i + 1) . '_thumb.jpg',
                    'alt_text' => 'Photo produit avis ' . ($i + 1),
                    'caption' => $i === 0 ? 'Photo principale' : 'Photo supplémentaire',
                    'width' => 800,
                    'height' => 800,
                    'file_size' => rand(100000, 500000),
                    'type' => 'image',
                    'position' => $i,
                    'is_active' => true,
                ]);
            }
        }
    }
}