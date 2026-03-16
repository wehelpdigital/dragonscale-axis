<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Main table for section-level settings
        Schema::create('as_homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('sectionKey', 50)->unique(); // hero, award, about, partners, etc.
            $table->string('sectionName', 100);
            $table->string('sectionIcon', 50)->nullable();
            $table->boolean('isEnabled')->default(true);
            $table->integer('sectionOrder')->default(0);
            $table->json('settings')->nullable(); // Flexible JSON for section-specific settings
            $table->timestamps();
        });

        // Items within sections (features, services, testimonials, etc.)
        Schema::create('as_homepage_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sectionId')->constrained('as_homepage_sections')->onDelete('cascade');
            $table->string('itemType', 50); // feature, service, partner, comparison, etc.
            $table->string('title', 255)->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->string('image2', 500)->nullable(); // For before/after
            $table->string('icon', 100)->nullable();
            $table->string('linkUrl', 500)->nullable();
            $table->string('linkText', 100)->nullable();
            $table->json('extraData')->nullable(); // For any additional fields
            $table->integer('itemOrder')->default(0);
            $table->boolean('isActive')->default(true);
            $table->enum('deleteStatus', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('sectionId');
            $table->index('itemType');
            $table->index('deleteStatus');
        });

        // Seed default sections
        $sections = [
            [
                'sectionKey' => 'hero',
                'sectionName' => 'Hero Section',
                'sectionIcon' => 'bx-image',
                'sectionOrder' => 1,
                'settings' => json_encode([
                    'backgroundImage' => 'http://anisenso.test/wp-content/uploads/2025/12/488478569_2174213226369837_7066166916975308288_n.jpg',
                    'overlayOpacity' => 50,
                    'supertext' => 'Maximizing Crop Yields for Palay, Mais, and More',
                    'title' => 'Helping Filipino Farmers Reach',
                    'titleHighlight1' => 'Maximum Yield',
                    'titleMiddle' => 'and',
                    'titleHighlight2' => 'Income',
                    'description' => 'At <strong class="text-brand-yellow">Ani (Yield) + Senso (Sensei means Teacher and Asenso means Success)</strong> — we help farmers maximize their yield through our exclusive technical research, support, fertilization, and management technologies.',
                    'ctaText' => 'Join Our Community Now',
                    'ctaUrl' => '/courses',
                ])
            ],
            [
                'sectionKey' => 'hero_features',
                'sectionName' => 'Hero Features Bar',
                'sectionIcon' => 'bx-list-check',
                'sectionOrder' => 2,
                'settings' => json_encode([
                    'enabled' => true,
                ])
            ],
            [
                'sectionKey' => 'award',
                'sectionName' => 'Award Winning Section',
                'sectionIcon' => 'bx-award',
                'sectionOrder' => 3,
                'settings' => json_encode([
                    'videoUrl' => 'https://www.youtube.com/embed/V34MyFVO7kU',
                    'sideImage' => 'http://anisenso.test/wp-content/uploads/2025/12/palay-08.jpg',
                    'supertext' => 'Locally and Internationally Recognized',
                    'title' => 'Award Winning Technology',
                    'statNumber' => '45+',
                    'statLabel' => 'Years of Innovation',
                    'description' => 'Proven track record of helping Filipino farmers achieve maximum crop yields through science-backed fertilization and management technologies.',
                    'ctaText' => 'Learn More',
                    'ctaUrl' => '/about',
                ])
            ],
            [
                'sectionKey' => 'about',
                'sectionName' => 'About Us Section',
                'sectionIcon' => 'bx-info-circle',
                'sectionOrder' => 4,
                'settings' => json_encode([
                    'supertext' => 'About Us',
                    'title' => 'Real Science and Support Combined',
                    'subtitle' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus.',
                ])
            ],
            [
                'sectionKey' => 'partners',
                'sectionName' => 'Partners/Logos',
                'sectionIcon' => 'bx-building-house',
                'sectionOrder' => 5,
                'settings' => json_encode([
                    'headerText' => 'Trusted by Leading Agricultural Organizations',
                ])
            ],
            [
                'sectionKey' => 'before_after',
                'sectionName' => 'Before & After',
                'sectionIcon' => 'bx-git-compare',
                'sectionOrder' => 6,
                'settings' => json_encode([
                    'supertext' => 'Results That Speak',
                    'title' => 'Before & After',
                    'subtitle' => 'See the remarkable transformation achieved by farmers using AniSenso Technology',
                ])
            ],
            [
                'sectionKey' => 'seasonal_cta',
                'sectionName' => 'Seasonal CTA',
                'sectionIcon' => 'bx-calendar',
                'sectionOrder' => 7,
                'settings' => json_encode([
                    'videoUrl' => 'https://www.youtube.com/embed/qVk6thNT_uM',
                    'supertext' => 'Maximum Income and Sustainability',
                    'title' => 'Reach Your Crop\'s Maximum Potential for the Season',
                    'description' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
                    'ctaText' => 'Join Our Community',
                    'ctaUrl' => '/courses',
                ])
            ],
            [
                'sectionKey' => 'process',
                'sectionName' => 'How It Works',
                'sectionIcon' => 'bx-git-branch',
                'sectionOrder' => 8,
                'settings' => json_encode([
                    'supertext' => 'Your Journey to Success',
                    'title' => 'How It Works',
                    'ctaText' => 'Start Your Journey',
                    'ctaUrl' => '/courses',
                ])
            ],
            [
                'sectionKey' => 'success_stories',
                'sectionName' => 'Success Stories',
                'sectionIcon' => 'bx-star',
                'sectionOrder' => 9,
                'settings' => json_encode([
                    'supertext' => 'Real Results from Real People',
                    'title' => 'Success Stories',
                    'ctaText' => 'Join Our Community',
                    'ctaUrl' => '/courses',
                ])
            ],
            [
                'sectionKey' => 'testimonials',
                'sectionName' => 'Testimonials',
                'sectionIcon' => 'bx-conversation',
                'sectionOrder' => 10,
                'settings' => json_encode([
                    'videoUrl' => 'https://www.youtube.com/embed/IGUPJ0jcs0E',
                    'supertext' => 'What Our Farmers Say',
                    'title' => 'Testimonials',
                ])
            ],
        ];

        foreach ($sections as $section) {
            DB::table('as_homepage_sections')->insert(array_merge($section, [
                'isEnabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Seed default items
        $heroFeaturesSection = DB::table('as_homepage_sections')->where('sectionKey', 'hero_features')->first();
        $aboutSection = DB::table('as_homepage_sections')->where('sectionKey', 'about')->first();
        $partnersSection = DB::table('as_homepage_sections')->where('sectionKey', 'partners')->first();
        $beforeAfterSection = DB::table('as_homepage_sections')->where('sectionKey', 'before_after')->first();
        $seasonalSection = DB::table('as_homepage_sections')->where('sectionKey', 'seasonal_cta')->first();
        $processSection = DB::table('as_homepage_sections')->where('sectionKey', 'process')->first();
        $successSection = DB::table('as_homepage_sections')->where('sectionKey', 'success_stories')->first();
        $testimonialsSection = DB::table('as_homepage_sections')->where('sectionKey', 'testimonials')->first();

        // Hero Features
        $heroFeatures = [
            ['title' => 'Expert Courses', 'description' => 'Tested and Award Winning Learning Methodologies.', 'icon' => 'book-open'],
            ['title' => 'Research-Based', 'description' => 'Continuous Research and Development.', 'icon' => 'lightbulb'],
            ['title' => '24/7 Support', 'description' => 'Support for mentorship and problem solving.', 'icon' => 'support'],
        ];
        foreach ($heroFeatures as $i => $feature) {
            DB::table('as_homepage_items')->insert([
                'sectionId' => $heroFeaturesSection->id,
                'itemType' => 'feature',
                'title' => $feature['title'],
                'description' => $feature['description'],
                'icon' => $feature['icon'],
                'itemOrder' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // About Services
        $services = [
            ['title' => 'Fertilization', 'description' => 'Lorem Ipsum is simply dummy text of the printing industry.', 'image' => 'fertilizer.png'],
            ['title' => 'Biostimulants', 'description' => 'Lorem Ipsum is simply dummy text of the printing industry.', 'image' => 'biostimulant.png'],
            ['title' => 'Soil Restoration', 'description' => 'Lorem Ipsum is simply dummy text of the printing industry.', 'image' => 'soil-restoration.png'],
            ['title' => 'Technician Support', 'description' => 'Lorem Ipsum is simply dummy text of the printing industry.', 'image' => 'technician-support.png'],
        ];
        foreach ($services as $i => $service) {
            DB::table('as_homepage_items')->insert([
                'sectionId' => $aboutSection->id,
                'itemType' => 'service',
                'title' => $service['title'],
                'description' => $service['description'],
                'image' => $service['image'],
                'itemOrder' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Partners (6 placeholders)
        for ($i = 1; $i <= 6; $i++) {
            DB::table('as_homepage_items')->insert([
                'sectionId' => $partnersSection->id,
                'itemType' => 'partner',
                'title' => 'Partner ' . $i,
                'image' => 'https://placehold.co/150x60/e5e7eb/9ca3af?text=Partner+' . $i,
                'itemOrder' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Before/After comparisons
        $comparisons = [
            ['title' => 'Rice Field - Day 14', 'description' => 'Noticeable improvement in leaf color and plant vigor after applying AniSenso fertilization technology.'],
            ['title' => 'Root Development - Week 3', 'description' => 'Stronger and more extensive root system leading to better nutrient absorption and plant stability.'],
            ['title' => 'Tiller Count - Month 1', 'description' => 'Increased tiller production by 40% compared to traditional methods, resulting in higher yield potential.'],
            ['title' => 'Harvest Results - Season End', 'description' => 'Final harvest showing significant yield improvement with better grain quality and weight.'],
        ];
        foreach ($comparisons as $i => $comp) {
            DB::table('as_homepage_items')->insert([
                'sectionId' => $beforeAfterSection->id,
                'itemType' => 'comparison',
                'title' => $comp['title'],
                'description' => $comp['description'],
                'image' => 'https://placehold.co/600x400/e8e8e8/666666?text=Before',
                'image2' => 'https://placehold.co/600x400/d4edda/155724?text=After',
                'itemOrder' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seasonal carousel images
        $carouselImages = [
            ['image' => 'http://anisenso.test/wp-content/uploads/2025/12/banana.png', 'title' => 'Banana'],
            ['image' => 'http://anisenso.test/wp-content/uploads/2025/12/palm.png', 'title' => 'Palm'],
            ['image' => 'http://anisenso.test/wp-content/uploads/2025/12/mango.png', 'title' => 'Mango'],
        ];
        foreach ($carouselImages as $i => $img) {
            DB::table('as_homepage_items')->insert([
                'sectionId' => $seasonalSection->id,
                'itemType' => 'carousel',
                'title' => $img['title'],
                'image' => $img['image'],
                'itemOrder' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Process steps
        $steps = [
            ['title' => 'Join', 'subtitle' => 'Join the Community', 'description' => 'Sign up and join our network of farmers.', 'extraData' => ['color' => 'green', 'size' => 'medium']],
            ['title' => 'Learn', 'subtitle' => 'Learn Techniques', 'description' => 'Expert courses on fertilization & soil health.', 'extraData' => ['color' => 'dark-green', 'size' => 'large']],
            ['title' => 'Grow', 'subtitle' => 'Grow & Scale', 'description' => 'Expand your farm with proven methods.', 'extraData' => ['color' => 'green', 'size' => 'medium']],
            ['title' => 'Support', 'subtitle' => 'Get Expert Support', 'description' => '24/7 guidance from technicians.', 'extraData' => ['color' => 'olive', 'size' => 'large']],
            ['title' => 'Apply', 'subtitle' => 'Apply Methods', 'description' => 'Implement AniSenso techniques on your crops.', 'extraData' => ['color' => 'light-green', 'size' => 'medium-large']],
            ['title' => 'Harvest', 'subtitle' => 'Harvest Success!', 'description' => 'Maximum yield, better income & sustainable farming!', 'extraData' => ['color' => 'yellow', 'size' => 'large']],
        ];
        foreach ($steps as $i => $step) {
            DB::table('as_homepage_items')->insert([
                'sectionId' => $processSection->id,
                'itemType' => 'process_step',
                'title' => $step['title'],
                'subtitle' => $step['subtitle'],
                'description' => $step['description'],
                'extraData' => json_encode($step['extraData']),
                'itemOrder' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Success stories
        $successStories = [
            ['title' => 'Juan increased his yield by 45%', 'image' => 'https://placehold.co/400x400/4a7c2a/ffffff?text=Farmer+1'],
            ['title' => 'Maria\'s farm transformed in 3 months', 'image' => 'https://placehold.co/400x400/2d5016/ffffff?text=Farmer+2'],
            ['title' => 'Pedro achieved sustainable farming', 'image' => 'https://placehold.co/400x400/6b9f3d/ffffff?text=Farmer+3'],
        ];
        foreach ($successStories as $i => $story) {
            DB::table('as_homepage_items')->insert([
                'sectionId' => $successSection->id,
                'itemType' => 'success_story',
                'title' => $story['title'],
                'image' => $story['image'],
                'itemOrder' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Testimonials
        $testimonials = [
            ['name' => 'Roberto Santos', 'location' => 'Nueva Ecija, Philippines', 'quote' => 'AniSenso Technology has completely transformed my farm. My rice yield increased by 40% in just one season. The biostimulants are easy to apply and the results speak for themselves.'],
            ['name' => 'Maria Elena Cruz', 'location' => 'Guimaras, Philippines', 'quote' => 'I was skeptical at first, but after seeing the difference in my mango trees, I\'m now a believer. The fruits are bigger, sweeter, and I\'m getting better prices at the market.'],
            ['name' => 'Jose Miguel Reyes', 'location' => 'Davao del Sur, Philippines', 'quote' => 'The soil restoration program saved my farm. After years of chemical overuse, my soil was depleted. Now it\'s healthy again and producing like never before. Highly recommended!'],
            ['name' => 'Antonio Bautista', 'location' => 'Quezon Province, Philippines', 'quote' => 'As a coconut farmer, I\'ve tried many products over the years. AniSenso is different - the technician support alone is worth it. They really care about our success.'],
            ['name' => 'Carmen Villanueva', 'location' => 'Benguet, Philippines', 'quote' => 'My vegetable farm is now organic-certified thanks to AniSenso\'s guidance. The transition was smooth and my customers love knowing their food is chemical-free.'],
            ['name' => 'Ricardo Fernandez', 'location' => 'Compostela Valley, Philippines', 'quote' => 'The banana plantation I manage has seen remarkable improvements. Less disease, stronger plants, and consistent quality. Our export clients are very impressed.'],
        ];
        foreach ($testimonials as $i => $testimonial) {
            DB::table('as_homepage_items')->insert([
                'sectionId' => $testimonialsSection->id,
                'itemType' => 'testimonial',
                'title' => $testimonial['name'],
                'subtitle' => $testimonial['location'],
                'description' => $testimonial['quote'],
                'itemOrder' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('as_homepage_items');
        Schema::dropIfExists('as_homepage_sections');
    }
};
