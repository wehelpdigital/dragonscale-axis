<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AsContentComment;
use Carbon\Carbon;

class SampleCommentsSeeder extends Seeder
{
    public function run()
    {
        // Clear existing test comments
        AsContentComment::truncate();

        $now = Carbon::now();

        // Sample student data
        $students = [
            ['name' => 'Maria Santos', 'email' => 'maria.santos@email.com', 'id' => 101],
            ['name' => 'Juan dela Cruz', 'email' => 'juan.delacruz@email.com', 'id' => 102],
            ['name' => 'Ana Reyes', 'email' => 'ana.reyes@email.com', 'id' => 103],
            ['name' => 'Pedro Garcia', 'email' => 'pedro.garcia@email.com', 'id' => 104],
            ['name' => 'Luisa Fernandez', 'email' => 'luisa.fernandez@email.com', 'id' => 105],
            ['name' => 'Carlos Mendoza', 'email' => 'carlos.mendoza@email.com', 'id' => 106],
            ['name' => 'Elena Ramos', 'email' => 'elena.ramos@email.com', 'id' => 107],
            ['name' => 'Miguel Torres', 'email' => 'miguel.torres@email.com', 'id' => 108],
            ['name' => 'Sofia Cruz', 'email' => 'sofia.cruz@email.com', 'id' => 109],
        ];

        // ========== CONTENT COMMENTS (contentId = 1, courseId = 2) ==========

        // 1. Unanswered comment (no reply) - 2 days ago
        $c1 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[0]['id'],
            'authorName' => $students[0]['name'],
            'authorEmail' => $students[0]['email'],
            'commentText' => "Hello! I'm having trouble understanding the concept of nitrogen fixation in the soil. Can someone explain it in simpler terms? 🤔",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'likesCount' => 5,
            'heartsCount' => 2,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(2),
            'updated_at' => $now->copy()->subDays(2),
        ]);

        // 2. Unanswered comment (no reply) - 1 day ago
        $c2 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[1]['id'],
            'authorName' => $students[1]['name'],
            'authorEmail' => $students[1]['email'],
            'commentText' => "What is the recommended NPK ratio for rice paddies during the vegetative stage? I want to make sure I apply the correct amount.",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(1)->subHours(5),
            'updated_at' => $now->copy()->subDays(1)->subHours(5),
        ]);

        // 3. Answered comment with admin reply - 3 days ago
        $c3 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[2]['id'],
            'authorName' => $students[2]['name'],
            'authorEmail' => $students[2]['email'],
            'commentText' => "Is it better to apply fertilizer in the morning or evening? Does it really matter for plant absorption?",
            'isAnswered' => true,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(3),
            'updated_at' => $now->copy()->subDays(3),
        ]);

        // Admin reply to c3
        AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => $c3->id,
            'authorType' => 'admin',
            'authorId' => 1,
            'authorName' => 'Admin',
            'authorEmail' => 'admin@anisenso.com',
            'commentText' => "Morning application is generally better as plants are more active in photosynthesis. Avoid midday when it's too hot as it may cause leaf burn. Evening application can also work but there's a higher risk of fungal issues due to moisture. 🌱",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(2)->subHours(12),
            'updated_at' => $now->copy()->subDays(2)->subHours(12),
        ]);

        // 4. Pinned comment with nested discussion (answered) - 5 days ago
        $c4 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[3]['id'],
            'authorName' => $students[3]['name'],
            'authorEmail' => $students[3]['email'],
            'commentText' => "I noticed my crops turning yellow even after fertilization. What could be the problem? I've been following the schedule from the video.",
            'isAnswered' => true,
            'isApproved' => true,
            'isPinned' => true,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(5),
            'updated_at' => $now->copy()->subDays(5),
        ]);

        // Admin reply to c4
        $c4_reply = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => $c4->id,
            'authorType' => 'admin',
            'authorId' => 1,
            'authorName' => 'Admin',
            'authorEmail' => 'admin@anisenso.com',
            'commentText' => "Yellow leaves can indicate nitrogen deficiency, iron chlorosis, or overwatering. Can you share a photo of your crops? Also, what is your current fertilization schedule and soil pH?",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(4)->subHours(20),
            'updated_at' => $now->copy()->subDays(4)->subHours(20),
        ]);

        // Student follow-up to admin reply
        $c4_followup = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => $c4_reply->id,
            'authorType' => 'student',
            'authorId' => $students[3]['id'],
            'authorName' => $students[3]['name'],
            'authorEmail' => $students[3]['email'],
            'commentText' => "I apply 14-14-14 every 2 weeks. The yellowing is mostly on older leaves. My soil pH is around 6.5. Thank you for helping!",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(4)->subHours(18),
            'updated_at' => $now->copy()->subDays(4)->subHours(18),
        ]);

        // Final admin response
        AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => $c4_followup->id,
            'authorType' => 'admin',
            'authorId' => 1,
            'authorName' => 'Admin',
            'authorEmail' => 'admin@anisenso.com',
            'commentText' => "If yellowing is on older/lower leaves first, it's likely nitrogen deficiency since N is mobile in plants. Try increasing nitrogen application or switch to 21-0-0 (urea) for a quick boost. Your pH is good! 👍",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(4)->subHours(16),
            'updated_at' => $now->copy()->subDays(4)->subHours(16),
        ]);

        // 5. Recent unanswered comment - 6 hours ago
        $c5 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[4]['id'],
            'authorName' => $students[4]['name'],
            'authorEmail' => $students[4]['email'],
            'commentText' => "The video mentioned foliar application. What are the advantages over soil application? When should I use each method?",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subHours(6),
            'updated_at' => $now->copy()->subHours(6),
        ]);

        // 6. Very recent unanswered - 30 minutes ago
        $c6 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[5]['id'],
            'authorName' => $students[5]['name'],
            'authorEmail' => $students[5]['email'],
            'commentText' => "Great content! Quick question - how do I know if my soil is deficient in micronutrients like zinc or boron? Are there visible signs? 🌾",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subMinutes(30),
            'updated_at' => $now->copy()->subMinutes(30),
        ]);

        // 7. Another unanswered - 2 hours ago
        $c7 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[6]['id'],
            'authorName' => $students[6]['name'],
            'authorEmail' => $students[6]['email'],
            'commentText' => "Can organic fertilizers like compost replace synthetic NPK completely? I want to go fully organic on my farm.",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'likesCount' => 3,
            'heartsCount' => 1,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subHours(2),
            'updated_at' => $now->copy()->subHours(2),
        ]);

        // 8. Another unanswered - 45 minutes ago
        $c8 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => 1,
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[8]['id'],
            'authorName' => $students[8]['name'],
            'authorEmail' => $students[8]['email'],
            'commentText' => "What's the best time of year to start implementing precision fertilization techniques? Should I wait for a specific season?",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'likesCount' => 2,
            'heartsCount' => 0,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subMinutes(45),
            'updated_at' => $now->copy()->subMinutes(45),
        ]);

        // ========== QUESTIONNAIRE COMMENTS (questionnaireId treated as contentId, courseId = 2) ==========
        // Note: For questionnaires, we use the questionnaire ID as contentId with a convention or separate field
        // Based on the implementation, questionnaire comments might use a different approach
        // Let's add some general course-level comments too

        // 8. Questionnaire comment - unanswered (using contentId = null for course-level)
        $q1 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => null, // Course-level comment (or could be questionnaire)
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[7]['id'],
            'authorName' => $students[7]['name'],
            'authorEmail' => $students[7]['email'],
            'commentText' => "The quiz was challenging! I got confused on question 3 about the NPK ratios. Can you clarify why 14-14-14 is better than 16-20-0 for that scenario?",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subHours(3),
            'updated_at' => $now->copy()->subHours(3),
        ]);

        // 8. Another course-level comment with reply (answered)
        $q2 = AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => null,
            'parentCommentId' => null,
            'authorType' => 'student',
            'authorId' => $students[0]['id'],
            'authorName' => $students[0]['name'],
            'authorEmail' => $students[0]['email'],
            'commentText' => "This course is amazing! When will you add more chapters about pest management? 😊",
            'isAnswered' => true,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(7),
            'updated_at' => $now->copy()->subDays(7),
        ]);

        // Admin reply
        AsContentComment::create([
            'asCoursesId' => 2,
            'contentId' => null,
            'parentCommentId' => $q2->id,
            'authorType' => 'admin',
            'authorId' => 1,
            'authorName' => 'Admin',
            'authorEmail' => 'admin@anisenso.com',
            'commentText' => "Thank you for the kind words! We're working on the pest management chapter and should have it ready within the next month. Stay tuned! 🎉",
            'isAnswered' => false,
            'isApproved' => true,
            'isPinned' => false,
            'deleteStatus' => true,
            'created_at' => $now->copy()->subDays(6),
            'updated_at' => $now->copy()->subDays(6),
        ]);

        echo "Created " . AsContentComment::count() . " sample comments successfully!\n";
        echo "- Unanswered root comments: " . AsContentComment::whereNull('parentCommentId')->where('isAnswered', false)->count() . "\n";
        echo "- Answered root comments: " . AsContentComment::whereNull('parentCommentId')->where('isAnswered', true)->count() . "\n";
        echo "- Reply comments: " . AsContentComment::whereNotNull('parentCommentId')->count() . "\n";
    }
}
