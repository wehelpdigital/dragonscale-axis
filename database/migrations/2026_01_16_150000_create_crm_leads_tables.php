<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Lead Sources table - predefined sources for leads
        Schema::create('crm_lead_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId')->nullable(); // null = system default
            $table->string('sourceName', 100);
            $table->text('sourceDescription')->nullable();
            $table->string('sourceIcon', 50)->nullable(); // MDI icon class
            $table->string('sourceColor', 20)->nullable(); // hex color for badge
            $table->integer('sourceOrder')->default(0);
            $table->boolean('isActive')->default(true);
            $table->boolean('isSystemDefault')->default(false);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['usersId', 'delete_status']);
            $table->index('isActive');
        });

        // Main Leads table
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId'); // owner/creator

            // Lead Status & Priority
            $table->enum('leadStatus', [
                'new',           // Just created
                'contacted',     // Initial contact made
                'qualified',     // Qualified as potential customer
                'proposal',      // Proposal/quote sent
                'negotiation',   // In negotiation
                'won',           // Converted to customer
                'lost',          // Did not convert
                'dormant'        // No activity, may revisit
            ])->default('new');
            $table->enum('leadPriority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            // Lead Source
            $table->unsignedBigInteger('leadSourceId')->nullable();
            $table->string('leadSourceOther', 255)->nullable(); // if source is "Other"
            $table->string('referredBy', 255)->nullable(); // who referred this lead

            // Contact Information
            $table->string('firstName', 100);
            $table->string('middleName', 100)->nullable();
            $table->string('lastName', 100);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('alternatePhone', 50)->nullable();

            // Company Information (B2B)
            $table->string('companyName', 255)->nullable();
            $table->string('jobTitle', 150)->nullable();
            $table->string('department', 150)->nullable();
            $table->string('industry', 150)->nullable();
            $table->string('companySize', 50)->nullable(); // 1-10, 11-50, 51-200, etc.
            $table->string('website', 255)->nullable();

            // Interest & Requirements
            $table->string('interestCategory', 100)->nullable(); // Product/Service category
            $table->text('interestDetails')->nullable(); // Specific interests
            $table->string('budgetRange', 100)->nullable();
            $table->enum('timeline', [
                'immediate',     // Within a week
                'short_term',    // Within a month
                'medium_term',   // 1-3 months
                'long_term',     // 3+ months
                'undecided'
            ])->nullable();

            // Address Information
            $table->string('province', 150)->nullable();
            $table->string('municipality', 150)->nullable();
            $table->string('barangay', 150)->nullable();
            $table->text('streetAddress')->nullable();
            $table->string('zipCode', 20)->nullable();
            $table->string('country', 100)->default('Philippines');

            // Social Media
            $table->string('facebookUrl', 500)->nullable();
            $table->string('instagramUrl', 500)->nullable();
            $table->string('linkedinUrl', 500)->nullable();
            $table->string('twitterUrl', 500)->nullable();
            $table->string('tiktokUrl', 500)->nullable();

            // Assignment & Follow-up
            $table->unsignedBigInteger('assignedTo')->nullable(); // assigned user
            $table->date('nextFollowUpDate')->nullable();
            $table->time('nextFollowUpTime')->nullable();
            $table->text('followUpNotes')->nullable();
            $table->datetime('lastContactDate')->nullable();

            // Conversion Tracking
            $table->unsignedBigInteger('convertedToClientId')->nullable();
            $table->datetime('conversionDate')->nullable();
            $table->decimal('estimatedValue', 15, 2)->nullable();
            $table->decimal('actualValue', 15, 2)->nullable();

            // Loss Tracking
            $table->string('lossReason', 255)->nullable();
            $table->text('lossDetails')->nullable();

            // General
            $table->text('notes')->nullable();
            $table->text('tags')->nullable(); // comma-separated tags
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // Indexes
            $table->index('usersId');
            $table->index('leadStatus');
            $table->index('leadPriority');
            $table->index('leadSourceId');
            $table->index('assignedTo');
            $table->index('nextFollowUpDate');
            $table->index('delete_status');
            $table->index(['usersId', 'delete_status']);
            $table->index(['leadStatus', 'delete_status']);
            $table->index('email');
            $table->index('phone');

            // Foreign keys
            $table->foreign('leadSourceId')->references('id')->on('crm_lead_sources')->onDelete('set null');
        });

        // Lead Activities table - track all interactions
        Schema::create('crm_lead_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leadId');
            $table->unsignedBigInteger('usersId'); // who performed the activity

            $table->enum('activityType', [
                'call_outbound',    // Made a call
                'call_inbound',     // Received a call
                'email_sent',       // Sent email
                'email_received',   // Received email
                'meeting',          // Had a meeting
                'note',             // General note
                'status_change',    // Status was changed
                'follow_up',        // Follow-up scheduled/completed
                'proposal_sent',    // Proposal/quote sent
                'document_sent',    // Document sent
                'social_media',     // Social media interaction
                'other'
            ]);

            $table->string('activitySubject', 255)->nullable();
            $table->text('activityDescription');
            $table->datetime('activityDate');
            $table->integer('durationMinutes')->nullable(); // for calls/meetings

            // For status changes
            $table->string('previousStatus', 50)->nullable();
            $table->string('newStatus', 50)->nullable();

            // Attachments (JSON array of file paths)
            $table->text('attachments')->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // Indexes
            $table->index('leadId');
            $table->index('usersId');
            $table->index('activityType');
            $table->index('activityDate');
            $table->index('delete_status');

            // Foreign keys
            $table->foreign('leadId')->references('id')->on('crm_leads')->onDelete('cascade');
        });

        // Insert default lead sources
        DB::table('crm_lead_sources')->insert([
            [
                'sourceName' => 'Facebook',
                'sourceDescription' => 'Lead from Facebook ads or organic posts',
                'sourceIcon' => 'mdi-facebook',
                'sourceColor' => '#1877F2',
                'sourceOrder' => 1,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sourceName' => 'Instagram',
                'sourceDescription' => 'Lead from Instagram ads or organic posts',
                'sourceIcon' => 'mdi-instagram',
                'sourceColor' => '#E4405F',
                'sourceOrder' => 2,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sourceName' => 'TikTok',
                'sourceDescription' => 'Lead from TikTok ads or organic content',
                'sourceIcon' => 'mdi-music-note',
                'sourceColor' => '#000000',
                'sourceOrder' => 3,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sourceName' => 'Website',
                'sourceDescription' => 'Lead from company website inquiry form',
                'sourceIcon' => 'mdi-web',
                'sourceColor' => '#4CAF50',
                'sourceOrder' => 4,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sourceName' => 'Referral',
                'sourceDescription' => 'Lead referred by existing customer or partner',
                'sourceIcon' => 'mdi-account-multiple',
                'sourceColor' => '#9C27B0',
                'sourceOrder' => 5,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sourceName' => 'Walk-in',
                'sourceDescription' => 'Lead who visited the physical store/office',
                'sourceIcon' => 'mdi-walk',
                'sourceColor' => '#FF9800',
                'sourceOrder' => 6,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sourceName' => 'Phone Inquiry',
                'sourceDescription' => 'Lead who called directly',
                'sourceIcon' => 'mdi-phone',
                'sourceColor' => '#2196F3',
                'sourceOrder' => 7,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sourceName' => 'Email Inquiry',
                'sourceDescription' => 'Lead who sent an email inquiry',
                'sourceIcon' => 'mdi-email',
                'sourceColor' => '#607D8B',
                'sourceOrder' => 8,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sourceName' => 'Event/Trade Show',
                'sourceDescription' => 'Lead from events, trade shows, or exhibitions',
                'sourceIcon' => 'mdi-calendar-star',
                'sourceColor' => '#E91E63',
                'sourceOrder' => 9,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sourceName' => 'Other',
                'sourceDescription' => 'Other lead source not listed',
                'sourceIcon' => 'mdi-dots-horizontal',
                'sourceColor' => '#795548',
                'sourceOrder' => 99,
                'isActive' => true,
                'isSystemDefault' => true,
                'delete_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_lead_activities');
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('crm_lead_sources');
    }
};
