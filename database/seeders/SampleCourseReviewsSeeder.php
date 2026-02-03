<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SampleCourseReviewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing enrollments
        $enrollments = DB::table('as_course_enrollments')
            ->where('deleteStatus', 1)
            ->get();

        if ($enrollments->isEmpty()) {
            $this->command->info('No enrollments found. Please seed enrollments first.');
            return;
        }

        // Sample review data
        $reviewTemplates = [
            // 5-star reviews
            [
                'rating' => 5,
                'reviewTitle' => 'Absolutely Excellent Course!',
                'reviewText' => 'This course exceeded all my expectations. The content is well-organized, easy to follow, and the instructor explains concepts clearly. Highly recommended for anyone looking to learn!',
            ],
            [
                'rating' => 5,
                'reviewTitle' => 'Best Investment in My Education',
                'reviewText' => 'I have taken many online courses, but this one stands out. The practical examples and hands-on exercises really helped me understand the material. Worth every penny!',
            ],
            [
                'rating' => 5,
                'reviewTitle' => 'Life-Changing Content',
                'reviewText' => 'The knowledge I gained from this course has already helped me in my career. The instructor is knowledgeable and passionate about teaching.',
            ],
            // 4-star reviews
            [
                'rating' => 4,
                'reviewTitle' => 'Great Course with Minor Issues',
                'reviewText' => 'Overall a very good course. The content is comprehensive and well-presented. I only wish there were more practice exercises. Still highly recommend!',
            ],
            [
                'rating' => 4,
                'reviewTitle' => 'Very Informative',
                'reviewText' => 'Learned a lot from this course. The pace was good and the explanations were clear. Would have liked more real-world examples, but still a solid course.',
            ],
            [
                'rating' => 4,
                'reviewTitle' => 'Good Value for Money',
                'reviewText' => 'The course covers all the essential topics well. Some sections could be more in-depth, but overall a good learning experience.',
            ],
            // 3-star reviews
            [
                'rating' => 3,
                'reviewTitle' => 'Decent Course, Room for Improvement',
                'reviewText' => 'The course content is okay, but I expected more advanced topics. Good for beginners, but intermediate learners might find it too basic.',
            ],
            [
                'rating' => 3,
                'reviewTitle' => 'Average Experience',
                'reviewText' => 'Some good information here, but the presentation could be better. The course felt a bit rushed in certain sections.',
            ],
            // 2-star reviews
            [
                'rating' => 2,
                'reviewTitle' => 'Below Expectations',
                'reviewText' => 'I was hoping for more depth in the course material. Many topics were covered superficially. Needs improvement.',
            ],
            // 1-star reviews
            [
                'rating' => 1,
                'reviewTitle' => 'Disappointed',
                'reviewText' => 'Unfortunately, this course did not meet my expectations. The content was too basic and I did not learn much new information.',
            ],
        ];

        // Sample reply templates
        $replyTemplates = [
            'Thank you so much for your kind words! We are thrilled that you enjoyed the course and found it valuable. Your success is our motivation!',
            'We really appreciate your feedback! Thank you for taking the time to share your experience with us.',
            'Thank you for the constructive feedback. We are constantly working to improve our courses and your input is valuable.',
            'We appreciate your honest review. We will take your suggestions into consideration for future updates.',
            'Thank you for sharing your thoughts. We are sorry the course did not meet your expectations. Please reach out if you have specific concerns.',
        ];

        $this->command->info('Creating sample reviews...');

        $reviewsCreated = 0;
        $repliesCreated = 0;

        foreach ($enrollments as $enrollment) {
            // Randomly decide if this enrollment has a review (70% chance)
            if (rand(1, 100) <= 70) {
                // Pick a random review template
                $template = $reviewTemplates[array_rand($reviewTemplates)];

                // Create review with random date in last 6 months
                $createdAt = Carbon::now()->subDays(rand(1, 180));

                $reviewId = DB::table('as_course_reviews')->insertGetId([
                    'asCoursesId' => $enrollment->asCoursesId,
                    'enrollmentId' => $enrollment->id,
                    'rating' => $template['rating'],
                    'reviewTitle' => $template['reviewTitle'],
                    'reviewText' => $template['reviewText'],
                    'isApproved' => rand(1, 100) <= 85, // 85% approved
                    'isFeatured' => $template['rating'] >= 4 && rand(1, 100) <= 20, // 20% of 4+ star reviews featured
                    'deleteStatus' => 1,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $reviewsCreated++;

                // 40% chance of having admin reply
                if (rand(1, 100) <= 40) {
                    $replyCreatedAt = $createdAt->copy()->addDays(rand(1, 14));

                    DB::table('as_review_replies')->insert([
                        'reviewId' => $reviewId,
                        'userId' => 1, // Admin user
                        'userName' => 'Admin',
                        'replyText' => $replyTemplates[array_rand($replyTemplates)],
                        'deleteStatus' => 1,
                        'created_at' => $replyCreatedAt,
                        'updated_at' => $replyCreatedAt,
                    ]);

                    $repliesCreated++;
                }
            }
        }

        $this->command->info("Created {$reviewsCreated} reviews and {$repliesCreated} replies.");
    }
}
