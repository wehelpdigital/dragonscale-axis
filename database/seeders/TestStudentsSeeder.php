<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestStudentsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Add test clients to clients_access_login
        $testClients = [
            [
                'clientFirstName' => 'Juan',
                'clientMiddleName' => 'Cruz',
                'clientLastName' => 'Dela Cruz',
                'clientEmailAddress' => 'juan.delacruz@test.com',
                'clientPhoneNumber' => '09171234567',
                'clientPassword' => bcrypt('password123'),
                'productStore' => 'Ani-Senso',
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'clientFirstName' => 'Maria',
                'clientMiddleName' => 'Santos',
                'clientLastName' => 'Garcia',
                'clientEmailAddress' => 'maria.garcia@test.com',
                'clientPhoneNumber' => '09181234568',
                'clientPassword' => bcrypt('password123'),
                'productStore' => 'Ani-Senso',
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'clientFirstName' => 'Pedro',
                'clientMiddleName' => 'Reyes',
                'clientLastName' => 'Santos',
                'clientEmailAddress' => 'pedro.santos@test.com',
                'clientPhoneNumber' => '09191234569',
                'clientPassword' => bcrypt('password123'),
                'productStore' => 'Ani-Senso',
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'clientFirstName' => 'Ana',
                'clientMiddleName' => 'Lopez',
                'clientLastName' => 'Reyes',
                'clientEmailAddress' => 'ana.reyes@test.com',
                'clientPhoneNumber' => '09201234570',
                'clientPassword' => bcrypt('password123'),
                'productStore' => 'Ani-Senso',
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'clientFirstName' => 'Carlos',
                'clientMiddleName' => 'Miguel',
                'clientLastName' => 'Torres',
                'clientEmailAddress' => 'carlos.torres@test.com',
                'clientPhoneNumber' => '09211234571',
                'clientPassword' => bcrypt('password123'),
                'productStore' => 'Ani-Senso',
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
        ];

        // Insert clients and get their IDs
        $clientIds = [];
        foreach ($testClients as $client) {
            // Check if email already exists
            $existing = DB::table('clients_access_login')
                ->where('clientEmailAddress', $client['clientEmailAddress'])
                ->first();

            if (!$existing) {
                $clientIds[] = DB::table('clients_access_login')->insertGetId($client);
            } else {
                $clientIds[] = $existing->id;
            }
        }

        // Get all active courses
        $courses = DB::table('as_courses')->where('deleteStatus', true)->pluck('id')->toArray();

        if (empty($courses)) {
            $this->command->info('No courses found. Skipping enrollments.');
            return;
        }

        // Create enrollments
        $enrollments = [];

        // Enroll first 3 clients in first course (with varied expirations)
        if (isset($courses[0])) {
            $enrollments[] = [
                'accessClientId' => $clientIds[0],
                'asCoursesId' => $courses[0],
                'enrollmentDate' => $now->copy()->subDays(30),
                'expirationDate' => $now->copy()->addDays(60), // Active
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ];
            $enrollments[] = [
                'accessClientId' => $clientIds[1],
                'asCoursesId' => $courses[0],
                'enrollmentDate' => $now->copy()->subDays(45),
                'expirationDate' => $now->copy()->addDays(5), // Expiring soon
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ];
            $enrollments[] = [
                'accessClientId' => $clientIds[2],
                'asCoursesId' => $courses[0],
                'enrollmentDate' => $now->copy()->subDays(90),
                'expirationDate' => $now->copy()->subDays(10), // Expired
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        // Enroll clients 2-4 in second course
        if (isset($courses[1])) {
            $enrollments[] = [
                'accessClientId' => $clientIds[1],
                'asCoursesId' => $courses[1],
                'enrollmentDate' => $now->copy()->subDays(15),
                'expirationDate' => null, // Lifetime
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ];
            $enrollments[] = [
                'accessClientId' => $clientIds[3],
                'asCoursesId' => $courses[1],
                'enrollmentDate' => $now->copy()->subDays(20),
                'expirationDate' => $now->copy()->addDays(90),
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        // Enroll client 5 in third course if exists
        if (isset($courses[2])) {
            $enrollments[] = [
                'accessClientId' => $clientIds[4],
                'asCoursesId' => $courses[2],
                'enrollmentDate' => $now->copy()->subDays(7),
                'expirationDate' => $now->copy()->addDays(180),
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        // Insert enrollments (skip duplicates)
        foreach ($enrollments as $enrollment) {
            $existing = DB::table('as_course_enrollments')
                ->where('accessClientId', $enrollment['accessClientId'])
                ->where('asCoursesId', $enrollment['asCoursesId'])
                ->where('deleteStatus', 1)
                ->first();

            if (!$existing) {
                DB::table('as_course_enrollments')->insert($enrollment);
            }
        }

        $this->command->info('Test students and enrollments created successfully!');
    }
}
