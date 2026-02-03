<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SampleTopicProgressSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Get all active enrollments
        $enrollments = DB::table('as_course_enrollments')
            ->where('deleteStatus', 1)
            ->get();

        if ($enrollments->isEmpty()) {
            $this->command->info('No enrollments found. Skipping topic progress seeding.');
            return;
        }

        foreach ($enrollments as $enrollment) {
            // Get all topics for this enrollment's course
            $topics = DB::table('as_courses_topics as t')
                ->join('as_courses_chapters as ch', 't.chapterId', '=', 'ch.id')
                ->where('ch.asCoursesId', $enrollment->asCoursesId)
                ->where('ch.deleteStatus', true)
                ->where('t.deleteStatus', true)
                ->orderBy('ch.chapterOrder')
                ->orderBy('t.topicsOrder')
                ->pluck('t.id')
                ->toArray();

            if (empty($topics)) {
                continue;
            }

            // Determine how many topics to mark complete (random percentage based on enrollment)
            // Use enrollment ID to create varied progress
            $completionRates = [0.25, 0.50, 0.75, 1.0, 0.33, 0.66, 0.10, 0.90];
            $rate = $completionRates[$enrollment->id % count($completionRates)];
            $topicsToComplete = (int) ceil(count($topics) * $rate);

            // Complete topics sequentially (first N topics)
            $completedTopicIds = array_slice($topics, 0, $topicsToComplete);

            foreach ($completedTopicIds as $index => $topicId) {
                // Check if progress already exists
                $existing = DB::table('as_topic_progress')
                    ->where('enrollmentId', $enrollment->id)
                    ->where('topicId', $topicId)
                    ->where('deleteStatus', 1)
                    ->first();

                if (!$existing) {
                    DB::table('as_topic_progress')->insert([
                        'enrollmentId' => $enrollment->id,
                        'topicId' => $topicId,
                        'completedAt' => $now->copy()->subDays(rand(1, 30))->subHours(rand(0, 23)),
                        'deleteStatus' => 1,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
            }

            $this->command->info("Enrollment #{$enrollment->id}: Completed {$topicsToComplete}/" . count($topics) . " topics (" . round($rate * 100) . "%)");
        }

        $this->command->info('Sample topic progress data created successfully!');
    }
}
