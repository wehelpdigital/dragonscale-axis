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
        Schema::create('ecom_thank_you_page_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');

            // Main Header Section
            $table->string('mainHeading')->default('Salamat!');
            $table->string('subHeading')->default('Congratulations, Magsasaka!');

            // What's Next Section
            $table->string('whatsNextTitle')->default('Ano ang susunod?');
            $table->string('step1Text')->default('I-ve-verify namin ang payment mo <strong>within 24 hours</strong>.');
            $table->string('step2Text')->default('Makakatanggap ka ng <strong>email confirmation</strong> with login details.');
            $table->string('step3Text')->default('Simulan mo na ang <strong>pag-aaral</strong> at magsimulang kumita!');

            // Inspirational Message Section
            $table->string('inspirationalEmoji')->default('🌾');
            $table->string('inspirationalTitle')->default('Ito ang simula ng pagbabago!');
            $table->text('inspirationalMessage')->default('Ginawa mo ang pinakamahalagang hakbang para baguhin ang iyong buhay sa pagsasaka. Maligayang pagdating sa komunidad ng mga matagumpay na magsasaka!');

            // Bookmark Reminder Section
            $table->string('bookmarkTitle')->default('I-save ang page na ito!');
            $table->string('bookmarkMessage')->default('Puwede mong balikan ang page na ito anytime para ma-check ang status ng order mo.');

            // Action Buttons
            $table->string('copyLinkButtonText')->default('Copy Order Link');
            $table->string('copyLinkSuccessText')->default('Link Copied!');
            $table->string('savePhotoButtonText')->default('I-save bilang Photo');
            $table->string('savingText')->default('Saving...');
            $table->string('homeButtonText')->default('Bumalik sa Home');

            // Footer
            $table->string('footerText')->default('Secured by Ani-Senso Academy');

            // Status messages
            $table->string('statusVerifiedText')->default('Payment Verified');
            $table->string('statusPendingText')->default('Pending Verification');

            // Meta
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_thank_you_page_settings');
    }
};
