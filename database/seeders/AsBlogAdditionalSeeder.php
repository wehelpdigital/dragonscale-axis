<?php

namespace Database\Seeders;

use App\Models\AsBlog;
use Illuminate\Database\Seeder;

class AsBlogAdditionalSeeder extends Seeder
{
    /**
     * Run the database seeds - Additional blogs with featured images.
     */
    public function run(): void
    {
        $userId = 1; // Admin user

        $blogs = [
            // NEWS CATEGORY
            [
                'blogTitle' => 'Ani-Senso Launches New Mobile App for Filipino Farmers',
                'blogCategory' => 'News',
                'blogCategoryColor' => 'blue',
                'blogExcerpt' => 'Download the new Ani-Senso mobile app and access farming courses, weather updates, market prices, and connect with fellow farmers—all from your smartphone.',
                'blogContent' => $this->getMobileAppContent(),
                'blogFeaturedImage' => 'images/blogs/mobile-app-launch.jpg',
                'focusKeyword' => 'ani-senso mobile app',
                'metaTitle' => 'New Ani-Senso Mobile App for Farmers | Download Now',
                'metaDescription' => 'Access farming courses, weather updates, and market prices with the new Ani-Senso mobile app. Available for Android and iOS.',
                'metaKeywords' => 'ani-senso app, farming app, agriculture mobile app, farmer app philippines',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Ani-Senso Tech Team',
                'schemaType' => 'NewsArticle',
            ],
            [
                'blogTitle' => 'Government Announces ₱5 Billion Subsidy Program for Small-Scale Farmers',
                'blogCategory' => 'News',
                'blogCategoryColor' => 'blue',
                'blogExcerpt' => 'The Department of Agriculture reveals a new financial assistance program targeting smallholder farmers. Learn about eligibility requirements and how to apply.',
                'blogContent' => $this->getSubsidyNewsContent(),
                'blogFeaturedImage' => 'images/blogs/government-subsidy.jpg',
                'focusKeyword' => 'farmer subsidy program',
                'metaTitle' => '₱5 Billion Farmer Subsidy Program Announced | Ani-Senso',
                'metaDescription' => 'New DA subsidy program for small-scale farmers. Learn about eligibility and application process for agricultural financial assistance.',
                'metaKeywords' => 'farmer subsidy, DA assistance, agricultural loan, farmer financial aid',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'News Desk',
                'schemaType' => 'NewsArticle',
            ],

            // ANNOUNCEMENTS CATEGORY
            [
                'blogTitle' => 'Important: Updated Guidelines for Organic Certification 2026',
                'blogCategory' => 'Announcements',
                'blogCategoryColor' => 'red',
                'blogExcerpt' => 'The Bureau of Agriculture and Fisheries Standards has released updated organic certification guidelines. All organic farmers must comply by June 2026.',
                'blogContent' => $this->getOrganicCertificationContent(),
                'blogFeaturedImage' => 'images/blogs/organic-certification.jpg',
                'focusKeyword' => 'organic certification guidelines',
                'metaTitle' => 'Updated Organic Certification Guidelines 2026 | Ani-Senso',
                'metaDescription' => 'New organic certification requirements from BAFS. Learn about updated guidelines and compliance deadlines for organic farmers.',
                'metaKeywords' => 'organic certification, BAFS guidelines, organic farming standards, organic compliance',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Compliance Team',
                'schemaType' => 'Article',
            ],
            [
                'blogTitle' => 'Platform Maintenance Notice: March 25-26, 2026',
                'blogCategory' => 'Announcements',
                'blogCategoryColor' => 'red',
                'blogExcerpt' => 'Scheduled maintenance will temporarily affect access to some Ani-Senso features. We apologize for any inconvenience and appreciate your patience.',
                'blogContent' => $this->getMaintenanceContent(),
                'blogFeaturedImage' => 'images/blogs/maintenance-notice.jpg',
                'focusKeyword' => 'platform maintenance',
                'metaTitle' => 'Scheduled Maintenance Notice | Ani-Senso',
                'metaDescription' => 'Ani-Senso platform maintenance scheduled for March 25-26, 2026. Some features may be temporarily unavailable.',
                'metaKeywords' => 'maintenance, system update, downtime notice',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'Technical Team',
                'schemaType' => 'Article',
            ],

            // FARMING TIPS CATEGORY
            [
                'blogTitle' => 'Water Conservation Techniques Every Filipino Farmer Should Know',
                'blogCategory' => 'Farming Tips',
                'blogCategoryColor' => 'brand-green',
                'blogExcerpt' => 'With climate change affecting rainfall patterns, water conservation is more important than ever. Learn practical techniques to maximize every drop on your farm.',
                'blogContent' => $this->getWaterConservationContent(),
                'blogFeaturedImage' => 'images/blogs/water-conservation.jpg',
                'focusKeyword' => 'water conservation farming',
                'metaTitle' => 'Water Conservation Techniques for Farmers | Ani-Senso',
                'metaDescription' => 'Essential water conservation methods for Philippine farms. Learn drip irrigation, mulching, and rainwater harvesting techniques.',
                'metaKeywords' => 'water conservation, irrigation techniques, drip irrigation, rainwater harvesting, drought farming',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Dr. Jose Aquino',
                'schemaType' => 'HowTo',
            ],
            [
                'blogTitle' => 'Pest Identification Guide: 15 Common Pests in Philippine Farms',
                'blogCategory' => 'Farming Tips',
                'blogCategoryColor' => 'brand-green',
                'blogExcerpt' => 'Know your enemy! This comprehensive guide helps you identify common agricultural pests and provides organic solutions for each one.',
                'blogContent' => $this->getPestGuideContent(),
                'blogFeaturedImage' => 'images/blogs/pest-identification.jpg',
                'focusKeyword' => 'farm pest identification',
                'metaTitle' => 'Common Farm Pests in the Philippines - Identification Guide',
                'metaDescription' => 'Identify and control 15 common agricultural pests in Philippine farms. Includes organic pest management solutions.',
                'metaKeywords' => 'pest identification, farm pests, pest control, organic pest management, crop pests philippines',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'Entomology Team',
                'schemaType' => 'HowTo',
            ],
            [
                'blogTitle' => 'Maximizing Yields with Companion Planting: A Filipino Farmer\'s Guide',
                'blogCategory' => 'Farming Tips',
                'blogCategoryColor' => 'brand-green',
                'blogExcerpt' => 'Discover how planting certain crops together can boost yields, repel pests naturally, and improve soil health without additional cost.',
                'blogContent' => $this->getCompanionPlantingContent(),
                'blogFeaturedImage' => 'images/blogs/companion-planting.jpg',
                'focusKeyword' => 'companion planting',
                'metaTitle' => 'Companion Planting Guide for Philippine Farms | Ani-Senso',
                'metaDescription' => 'Learn companion planting combinations that work in Philippine climate. Boost yields and control pests naturally.',
                'metaKeywords' => 'companion planting, intercropping, plant combinations, natural pest control, yield improvement',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'Garden Expert Team',
                'schemaType' => 'HowTo',
            ],

            // SUCCESS STORIES CATEGORY
            [
                'blogTitle' => 'From Backyard Garden to Commercial Farm: Maria Santos\' Inspiring Journey',
                'blogCategory' => 'Success Stories',
                'blogCategoryColor' => 'brand-yellow',
                'blogExcerpt' => 'Starting with just 100 square meters, Maria Santos built a thriving organic vegetable business that now supplies restaurants across Metro Manila.',
                'blogContent' => $this->getMariaSuccessStory(),
                'blogFeaturedImage' => 'images/blogs/maria-success-story.jpg',
                'focusKeyword' => 'backyard farming success',
                'metaTitle' => 'Success Story: From Backyard to Commercial Farm | Ani-Senso',
                'metaDescription' => 'How Maria Santos transformed her 100sqm backyard into a commercial organic farm supplying Metro Manila restaurants.',
                'metaKeywords' => 'farming success story, backyard farming, organic farm business, urban farming',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Featured Stories Team',
                'schemaType' => 'Article',
            ],
            [
                'blogTitle' => 'Youth in Agriculture: How 22-Year-Old Mark Reyes Built a Tech-Powered Farm',
                'blogCategory' => 'Success Stories',
                'blogCategoryColor' => 'brand-yellow',
                'blogExcerpt' => 'Meet Mark Reyes, a young entrepreneur combining traditional farming knowledge with modern technology to revolutionize his family\'s farm.',
                'blogContent' => $this->getMarkSuccessStory(),
                'blogFeaturedImage' => 'images/blogs/young-farmer-tech.jpg',
                'focusKeyword' => 'young farmer technology',
                'metaTitle' => 'Young Farmer Uses Technology to Transform Family Farm',
                'metaDescription' => 'How 22-year-old Mark Reyes integrated IoT sensors and data analytics into traditional farming practices.',
                'metaKeywords' => 'young farmer, agricultural technology, smart farming, farm innovation, youth in agriculture',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'Youth Programs Team',
                'schemaType' => 'Article',
            ],

            // GUIDES CATEGORY
            [
                'blogTitle' => 'Step-by-Step: Building Your First Greenhouse on a Budget',
                'blogCategory' => 'Guides',
                'blogCategoryColor' => 'teal',
                'blogExcerpt' => 'Build a functional greenhouse for under ₱15,000 using locally available materials. This complete guide walks you through every step.',
                'blogContent' => $this->getGreenhouseGuideContent(),
                'blogFeaturedImage' => 'images/blogs/diy-greenhouse.jpg',
                'focusKeyword' => 'build greenhouse budget',
                'metaTitle' => 'How to Build a Budget Greenhouse | Complete Guide',
                'metaDescription' => 'Step-by-step guide to building a functional greenhouse for under ₱15,000. Includes materials list and construction plans.',
                'metaKeywords' => 'diy greenhouse, budget greenhouse, greenhouse construction, protected cultivation',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Construction Guide Team',
                'schemaType' => 'HowTo',
            ],
            [
                'blogTitle' => 'Complete Mushroom Cultivation Guide for Philippine Climate',
                'blogCategory' => 'Guides',
                'blogCategoryColor' => 'teal',
                'blogExcerpt' => 'Mushroom farming is one of the most profitable small-scale agricultural ventures. Learn how to grow oyster and shiitake mushrooms at home.',
                'blogContent' => $this->getMushroomGuideContent(),
                'blogFeaturedImage' => 'images/blogs/mushroom-farming.jpg',
                'focusKeyword' => 'mushroom cultivation philippines',
                'metaTitle' => 'Mushroom Farming Guide for Philippines | Ani-Senso',
                'metaDescription' => 'Learn mushroom cultivation in Philippine climate. Complete guide covering oyster and shiitake mushroom growing techniques.',
                'metaKeywords' => 'mushroom farming, oyster mushroom, shiitake, mushroom cultivation, high-value crops',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'Specialty Crops Team',
                'schemaType' => 'HowTo',
            ],
            [
                'blogTitle' => 'Starting a Small-Scale Poultry Business: From 50 Chickens to Profit',
                'blogCategory' => 'Guides',
                'blogCategoryColor' => 'teal',
                'blogExcerpt' => 'A practical guide for starting your poultry business with minimal investment. Learn about breeds, housing, feeding, and marketing your products.',
                'blogContent' => $this->getPoultryGuideContent(),
                'blogFeaturedImage' => 'images/blogs/poultry-business.jpg',
                'focusKeyword' => 'small poultry business',
                'metaTitle' => 'Start a Small Poultry Business | Complete Guide',
                'metaDescription' => 'How to start a profitable small-scale poultry business. Covers breeds, housing, feeding, and marketing for beginners.',
                'metaKeywords' => 'poultry business, chicken farming, egg production, backyard poultry, livestock farming',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'Livestock Team',
                'schemaType' => 'HowTo',
            ],

            // PRODUCT UPDATES CATEGORY
            [
                'blogTitle' => 'Introducing Ani-Senso Premium: Enhanced Learning Experience',
                'blogCategory' => 'Product Updates',
                'blogCategoryColor' => 'purple',
                'blogExcerpt' => 'Upgrade to Ani-Senso Premium for exclusive content, live mentorship sessions, downloadable resources, and priority support.',
                'blogContent' => $this->getPremiumAnnouncementContent(),
                'blogFeaturedImage' => 'images/blogs/premium-launch.jpg',
                'focusKeyword' => 'ani-senso premium',
                'metaTitle' => 'Ani-Senso Premium - Enhanced Learning Experience',
                'metaDescription' => 'Discover Ani-Senso Premium features: exclusive content, live mentorship, downloadable resources, and priority support.',
                'metaKeywords' => 'ani-senso premium, premium membership, exclusive courses, mentorship program',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Product Team',
                'schemaType' => 'NewsArticle',
            ],
            [
                'blogTitle' => 'New Feature: AI-Powered Crop Disease Detection Tool',
                'blogCategory' => 'Product Updates',
                'blogCategoryColor' => 'purple',
                'blogExcerpt' => 'Simply take a photo of your plant and our AI will identify diseases and suggest treatments. Available now in the Ani-Senso app.',
                'blogContent' => $this->getAIToolContent(),
                'blogFeaturedImage' => 'images/blogs/ai-disease-detection.jpg',
                'focusKeyword' => 'crop disease detection ai',
                'metaTitle' => 'AI Crop Disease Detection Tool | New Feature',
                'metaDescription' => 'Use AI to identify crop diseases instantly. Take a photo and get diagnosis with treatment recommendations.',
                'metaKeywords' => 'ai disease detection, crop diagnosis, plant disease app, agricultural ai',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'AI Team',
                'schemaType' => 'NewsArticle',
            ],

            // EVENTS CATEGORY
            [
                'blogTitle' => 'Free Webinar: Introduction to Aquaponics for Beginners',
                'blogCategory' => 'Events',
                'blogCategoryColor' => 'orange',
                'blogExcerpt' => 'Join our free 2-hour webinar and learn how to grow vegetables and raise fish in one integrated system. Perfect for urban and small-space farming.',
                'blogContent' => $this->getAquaponicsWebinarContent(),
                'blogFeaturedImage' => 'images/blogs/aquaponics-webinar.jpg',
                'focusKeyword' => 'aquaponics webinar',
                'metaTitle' => 'Free Aquaponics Webinar for Beginners | Register Now',
                'metaDescription' => 'Learn aquaponics basics in our free 2-hour webinar. Discover how to grow vegetables and fish together.',
                'metaKeywords' => 'aquaponics, free webinar, urban farming, fish farming, integrated farming',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Events Team',
                'schemaType' => 'Article',
            ],
            [
                'blogTitle' => 'Regional Farm Tour: Discover Organic Farms in Laguna',
                'blogCategory' => 'Events',
                'blogCategoryColor' => 'orange',
                'blogExcerpt' => 'Join fellow farmers on a guided tour of three successful organic farms in Laguna. Learn hands-on techniques and network with experienced farmers.',
                'blogContent' => $this->getFarmTourContent(),
                'blogFeaturedImage' => 'images/blogs/farm-tour-laguna.jpg',
                'focusKeyword' => 'organic farm tour laguna',
                'metaTitle' => 'Organic Farm Tour in Laguna | Join Now',
                'metaDescription' => 'Visit successful organic farms in Laguna. Learn hands-on techniques and network with experienced farmers.',
                'metaKeywords' => 'farm tour, organic farms, laguna farms, farmer networking, agricultural tourism',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'Events Team',
                'schemaType' => 'Article',
            ],
        ];

        foreach ($blogs as $blogData) {
            $blog = AsBlog::create([
                'usersId' => $userId,
                'blogTitle' => $blogData['blogTitle'],
                'blogSlug' => AsBlog::generateSlug($blogData['blogTitle']),
                'blogCategory' => $blogData['blogCategory'],
                'blogCategoryColor' => $blogData['blogCategoryColor'],
                'blogFeaturedImage' => $blogData['blogFeaturedImage'],
                'blogExcerpt' => $blogData['blogExcerpt'],
                'blogContent' => $blogData['blogContent'],
                'useBuilder' => false,
                'metaTitle' => $blogData['metaTitle'],
                'metaDescription' => $blogData['metaDescription'],
                'metaKeywords' => $blogData['metaKeywords'],
                'focusKeyword' => $blogData['focusKeyword'],
                'blogStatus' => $blogData['blogStatus'],
                'publishedAt' => now()->subDays(rand(1, 60)),
                'isFeatured' => $blogData['isFeatured'],
                'authorName' => $blogData['authorName'],
                'schemaType' => $blogData['schemaType'],
                'viewCount' => rand(100, 2000),
                'deleteStatus' => 'active',
            ]);

            // Calculate SEO score
            $seoResult = $blog->analyzeSeo();
            $blog->update([
                'seoScore' => $seoResult['score'],
                'seoAnalysis' => $seoResult['analysis'],
                'readingTime' => $blog->calculateReadingTime(),
            ]);
        }

        $this->command->info('Created ' . count($blogs) . ' additional blog posts with featured images.');
    }

    private function getMobileAppContent(): string
    {
        return <<<HTML
<div class="container">
    <div class="alert alert-success border-0 mb-4">
        <h5 class="alert-heading"><i class="bx bx-mobile-alt me-2"></i>Now Available!</h5>
        <p class="mb-0">Download the Ani-Senso app from Google Play Store and Apple App Store.</p>
    </div>

    <p class="lead mb-4">We are thrilled to announce the launch of the Ani-Senso mobile application, designed specifically for Filipino farmers who want to access agricultural knowledge anytime, anywhere.</p>

    <div class="row mb-5 align-items-center">
        <div class="col-md-6">
            <img src="https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=500" class="img-fluid rounded shadow-sm" alt="Mobile app on smartphone">
        </div>
        <div class="col-md-6">
            <h2 class="h4 mb-3">Farm Management in Your Pocket</h2>
            <p>The Ani-Senso app brings all the resources you need to succeed in farming directly to your smartphone. Whether you're in the field or at home, knowledge is just a tap away.</p>
        </div>
    </div>

    <h2 class="h3 mb-4">Key Features</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-book-reader text-primary me-2"></i>Offline Course Access</h5>
                    <p class="text-dark mb-0">Download courses and watch lessons even without internet connection. Perfect for areas with limited connectivity.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-cloud-rain text-info me-2"></i>Real-Time Weather</h5>
                    <p class="text-dark mb-0">Get accurate weather forecasts for your exact location. Receive alerts for extreme weather events.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-line-chart text-success me-2"></i>Market Prices</h5>
                    <p class="text-dark mb-0">View daily commodity prices from major markets. Make informed decisions about when and where to sell.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-group text-warning me-2"></i>Farmer Community</h5>
                    <p class="text-dark mb-0">Connect with farmers across the Philippines. Share experiences, ask questions, and learn together.</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">How to Download</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card bg-dark text-white">
                <div class="card-body text-center py-4">
                    <i class="bx bxl-play-store" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Google Play Store</h5>
                    <p class="mb-3">For Android devices</p>
                    <a href="#" class="btn btn-light">Download for Android</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card bg-dark text-white">
                <div class="card-body text-center py-4">
                    <i class="bx bxl-apple" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Apple App Store</h5>
                    <p class="mb-3">For iOS devices</p>
                    <a href="#" class="btn btn-light">Download for iPhone</a>
                </div>
            </div>
        </div>
    </div>

    <blockquote class="blockquote border-start border-4 border-primary ps-4 py-2 my-4 bg-light">
        <p class="mb-0">"This app has changed how I manage my farm. I can check weather, learn new techniques, and connect with other farmers—all while having coffee in the morning."</p>
        <footer class="blockquote-footer mt-2">Pedro Gonzales, Rice Farmer from Pangasinan</footer>
    </blockquote>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="mb-4">Questions About the App?</h3>
        <p>Our support team is ready to help you get started.</p>
        <a href="/contact" class="btn btn-primary btn-lg mt-3">Contact Support</a>
    </div>
</div>
HTML;
    }

    private function getSubsidyNewsContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">The Department of Agriculture has announced a ₱5 billion financial assistance program aimed at supporting small-scale farmers across the Philippines. Here's everything you need to know about eligibility and how to apply.</p>

    <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Government assistance program">

    <h2 class="h3 mb-4">Program Overview</h2>
    <p>The Agricultural Productivity Enhancement Program (APEP) 2026 aims to provide financial assistance to farmers with landholdings of 3 hectares or less. The program includes:</p>

    <ul class="mb-4">
        <li class="mb-2">Direct cash assistance of up to ₱15,000 per hectare</li>
        <li class="mb-2">Subsidized seeds and fertilizers</li>
        <li class="mb-2">Low-interest production loans at 3% per annum</li>
        <li class="mb-2">Free crop insurance coverage</li>
    </ul>

    <h2 class="h3 mb-4 mt-5">Eligibility Requirements</h2>

    <div class="card border-primary mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Who Can Apply?</h5>
        </div>
        <div class="card-body">
            <ul class="mb-0">
                <li class="mb-2 text-dark">Filipino citizen, 18 years old and above</li>
                <li class="mb-2 text-dark">Registered in the Registry System for Basic Sectors in Agriculture (RSBSA)</li>
                <li class="mb-2 text-dark">Actively engaged in farming activities</li>
                <li class="mb-2 text-dark">Cultivating 3 hectares or less (owned, leased, or tenant)</li>
                <li class="mb-2 text-dark">Not a beneficiary of similar programs in the current fiscal year</li>
            </ul>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">How to Apply</h2>

    <ol class="mb-4">
        <li class="mb-3">
            <strong>Visit your Municipal/City Agriculture Office</strong>
            <p class="text-secondary mb-0">Bring valid ID and proof of landholding or tenancy agreement</p>
        </li>
        <li class="mb-3">
            <strong>Complete the APEP Application Form</strong>
            <p class="text-secondary mb-0">Available at all DA regional and provincial offices</p>
        </li>
        <li class="mb-3">
            <strong>Submit required documents</strong>
            <p class="text-secondary mb-0">RSBSA registration, valid ID, lot plan or tax declaration</p>
        </li>
        <li class="mb-3">
            <strong>Wait for verification</strong>
            <p class="text-secondary mb-0">Processing takes approximately 15-30 working days</p>
        </li>
    </ol>

    <div class="alert alert-warning border-0 mb-4">
        <h5><i class="bx bx-calendar me-2"></i>Important Deadline</h5>
        <p class="mb-0">Applications must be submitted before <strong>April 30, 2026</strong>. Late applications will not be accepted.</p>
    </div>

    <h2 class="h3 mb-4 mt-5">Regional Offices Contact</h2>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Region</th>
                <th>Contact Number</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>NCR</td><td>(02) 8928-8741</td></tr>
            <tr><td>Region III - Central Luzon</td><td>(045) 961-2372</td></tr>
            <tr><td>Region IV-A - CALABARZON</td><td>(049) 531-7651</td></tr>
            <tr><td>Region VI - Western Visayas</td><td>(033) 337-2847</td></tr>
            <tr><td>Region VII - Central Visayas</td><td>(032) 232-2457</td></tr>
        </tbody>
    </table>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="mb-4">Need Help with Your Application?</h3>
        <p>Our team can assist you in understanding the requirements and preparing your documents.</p>
        <a href="/contact" class="btn btn-success btn-lg mt-3">Get Assistance</a>
    </div>
</div>
HTML;
    }

    private function getOrganicCertificationContent(): string
    {
        return <<<HTML
<div class="container">
    <div class="alert alert-danger border-0 mb-4">
        <h5 class="alert-heading"><i class="bx bx-error-circle me-2"></i>Action Required</h5>
        <p class="mb-0">All organic farmers must comply with updated guidelines by <strong>June 30, 2026</strong>.</p>
    </div>

    <p class="lead mb-4">The Bureau of Agriculture and Fisheries Standards (BAFS) has released updated guidelines for organic certification. These changes aim to align Philippine organic standards with international requirements.</p>

    <img src="https://images.unsplash.com/photo-1574943320219-553eb213f72d?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Organic certification">

    <h2 class="h3 mb-4">Key Changes in 2026 Guidelines</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="bx bx-file me-2"></i>Documentation</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="text-dark">Digital record-keeping now required</li>
                        <li class="text-dark">Traceability documents for all inputs</li>
                        <li class="text-dark">Monthly production logs</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bx bx-search me-2"></i>Inspections</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="text-dark">Unannounced inspections now permitted</li>
                        <li class="text-dark">Soil and water testing every 6 months</li>
                        <li class="text-dark">Residue testing for high-risk products</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Compliance Timeline</h2>

    <table class="table table-bordered">
        <thead class="table-success">
            <tr>
                <th>Deadline</th>
                <th>Requirement</th>
            </tr>
        </thead>
        <tbody>
            <tr><td><strong>April 1, 2026</strong></td><td>Submit updated farm management plan</td></tr>
            <tr><td><strong>May 15, 2026</strong></td><td>Complete digital record-keeping setup</td></tr>
            <tr><td><strong>June 30, 2026</strong></td><td>Full compliance with new standards</td></tr>
        </tbody>
    </table>

    <div class="alert alert-info border-0 my-4">
        <h5><i class="bx bx-help-circle me-2"></i>Need Help?</h5>
        <p class="mb-0">BAFS is offering free compliance workshops. Contact your regional DA office to register.</p>
    </div>

    <hr class="my-5">

    <h3 class="mb-4">Download Official Documents</h3>
    <ul class="list-unstyled">
        <li class="mb-2"><i class="bx bx-download me-2"></i><a href="#">Updated Organic Standards Guidelines (PDF)</a></li>
        <li class="mb-2"><i class="bx bx-download me-2"></i><a href="#">Farm Management Plan Template (Word)</a></li>
        <li class="mb-2"><i class="bx bx-download me-2"></i><a href="#">Digital Record-Keeping Guide (PDF)</a></li>
    </ul>
</div>
HTML;
    }

    private function getMaintenanceContent(): string
    {
        return <<<HTML
<div class="container">
    <div class="alert alert-info border-0 mb-4">
        <h5 class="alert-heading"><i class="bx bx-wrench me-2"></i>Scheduled Maintenance</h5>
        <p class="mb-0">Our platform will undergo scheduled maintenance to improve your experience.</p>
    </div>

    <p class="lead mb-4">We are committed to providing you with the best possible learning experience. To achieve this, we need to perform essential system upgrades.</p>

    <h2 class="h3 mb-4">Maintenance Schedule</h2>

    <table class="table table-bordered">
        <tbody>
            <tr>
                <td><strong>Start Time</strong></td>
                <td>March 25, 2026 at 11:00 PM (PHT)</td>
            </tr>
            <tr>
                <td><strong>End Time</strong></td>
                <td>March 26, 2026 at 6:00 AM (PHT)</td>
            </tr>
            <tr>
                <td><strong>Duration</strong></td>
                <td>Approximately 7 hours</td>
            </tr>
        </tbody>
    </table>

    <h2 class="h3 mb-4 mt-5">What to Expect</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">Unavailable During Maintenance</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item text-dark">Online course viewing</li>
                    <li class="list-group-item text-dark">Account login</li>
                    <li class="list-group-item text-dark">Forum discussions</li>
                    <li class="list-group-item text-dark">Certificate downloads</li>
                </ul>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-success">
                <div class="card-header bg-success text-white">Still Available</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item text-dark">Downloaded courses (mobile app)</li>
                    <li class="list-group-item text-dark">Email support</li>
                    <li class="list-group-item text-dark">Weather alerts (mobile app)</li>
                </ul>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Improvements Coming</h2>
    <ul>
        <li class="mb-2">Faster video streaming</li>
        <li class="mb-2">Improved search functionality</li>
        <li class="mb-2">Enhanced mobile experience</li>
        <li class="mb-2">New discussion forum features</li>
    </ul>

    <p>We apologize for any inconvenience this may cause. Thank you for your patience and understanding.</p>

    <hr class="my-5">

    <div class="text-center">
        <p><strong>Questions or concerns?</strong></p>
        <a href="mailto:support@ani-senso.com" class="btn btn-primary">Contact Support</a>
    </div>
</div>
HTML;
    }

    private function getWaterConservationContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">Water scarcity is becoming increasingly common in many parts of the Philippines. Learning to conserve water not only reduces costs but ensures your farm's sustainability during dry seasons.</p>

    <img src="https://images.unsplash.com/photo-1473973266408-ed4e27abdd47?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Drip irrigation system">

    <h2 class="h3 mb-4">Why Water Conservation Matters</h2>
    <p>Filipino farmers face unique water challenges:</p>
    <ul class="mb-4">
        <li>Unpredictable rainfall due to climate change</li>
        <li>Competing demands from urban areas</li>
        <li>Increasing irrigation costs</li>
        <li>Depleting groundwater sources</li>
    </ul>

    <h2 class="h3 mb-4 mt-5">Top Water Conservation Techniques</h2>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">1. Drip Irrigation</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="text-dark">Drip irrigation delivers water directly to plant roots, reducing water use by 30-50% compared to flood irrigation. While initial setup costs more, the long-term savings are significant.</p>
                    <p class="mb-0 text-dark"><strong>Best for:</strong> Vegetables, fruit trees, row crops</p>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <span class="display-4 text-success">50%</span>
                        <p class="text-secondary">Water Savings</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">2. Mulching</h5>
        </div>
        <div class="card-body">
            <p class="text-dark">Covering soil with organic materials (rice straw, grass clippings, leaves) or plastic mulch reduces evaporation by up to 70%. Mulch also suppresses weeds and adds organic matter to soil.</p>
            <p class="mb-0 text-dark"><strong>Materials:</strong> Rice straw, dried leaves, grass clippings, coconut coir, plastic mulch</p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">3. Rainwater Harvesting</h5>
        </div>
        <div class="card-body">
            <p class="text-dark">Collect and store rainwater during the wet season for use during dry periods. A simple system with gutters, pipes, and storage tanks can capture thousands of liters.</p>
            <div class="alert alert-light border mb-0">
                <strong>Calculate Your Potential:</strong> A 100 sqm roof area can collect approximately 1,000 liters for every 10mm of rainfall.
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">4. Alternate Wetting and Drying (AWD)</h5>
        </div>
        <div class="card-body">
            <p class="text-dark">For rice farmers, AWD allows fields to dry to a certain level before re-flooding. This technique can reduce water use by 15-30% without affecting yields.</p>
            <p class="mb-0 text-dark"><strong>How it works:</strong> Install a perforated pipe in the field to monitor water level. Irrigate only when water drops 15cm below soil surface.</p>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Additional Tips</h2>

    <ol>
        <li class="mb-2"><strong>Water early morning or late afternoon</strong> to minimize evaporation</li>
        <li class="mb-2"><strong>Use soil moisture sensors</strong> to avoid over-irrigation</li>
        <li class="mb-2"><strong>Maintain irrigation systems</strong> to prevent leaks</li>
        <li class="mb-2"><strong>Choose drought-tolerant varieties</strong> for water-scarce areas</li>
        <li class="mb-2"><strong>Practice contour farming</strong> to reduce runoff on slopes</li>
    </ol>

    <hr class="my-5">

    <h3 class="mb-4">Learn More About Water Management</h3>
    <p>Our Water-Smart Farming course covers these techniques in detail with hands-on demonstrations.</p>
    <a href="/courses" class="btn btn-primary btn-lg mt-3">Explore Courses</a>
</div>
HTML;
    }

    private function getPestGuideContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">Early identification is key to effective pest management. This guide helps you recognize the most common agricultural pests in Philippine farms and provides organic control methods.</p>

    <img src="https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Healthy crops">

    <h2 class="h3 mb-4">Rice Pests</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="text-danger"><i class="bx bx-bug me-2"></i>Rice Stem Borer</h5>
                    <p class="text-dark"><strong>Signs:</strong> "Deadheart" in vegetative stage, "whitehead" in reproductive stage</p>
                    <p class="text-dark mb-0"><strong>Control:</strong> Remove stubbles after harvest, use light traps, apply neem extract</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="text-danger"><i class="bx bx-bug me-2"></i>Brown Planthopper (BPH)</h5>
                    <p class="text-dark"><strong>Signs:</strong> "Hopperburn" - plants turn yellow then brown</p>
                    <p class="text-dark mb-0"><strong>Control:</strong> Avoid excessive nitrogen, maintain field sanitation, encourage natural predators</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Vegetable Pests</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="text-warning"><i class="bx bx-bug me-2"></i>Aphids</h5>
                    <p class="text-dark"><strong>Signs:</strong> Stunted growth, curled leaves, sticky honeydew on leaves</p>
                    <p class="text-dark mb-0"><strong>Control:</strong> Spray with soap solution, introduce ladybugs, use neem oil</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="text-warning"><i class="bx bx-bug me-2"></i>Diamondback Moth</h5>
                    <p class="text-dark"><strong>Signs:</strong> Small holes in leaves, presence of small green larvae</p>
                    <p class="text-dark mb-0"><strong>Control:</strong> Bacillus thuringiensis (Bt) spray, crop rotation, remove crop residues</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="text-warning"><i class="bx bx-bug me-2"></i>Fruit Fly</h5>
                    <p class="text-dark"><strong>Signs:</strong> Fruits with puncture marks, larvae inside fruits</p>
                    <p class="text-dark mb-0"><strong>Control:</strong> Protein bait traps, sanitation, bagging of fruits</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="text-warning"><i class="bx bx-bug me-2"></i>Cutworms</h5>
                    <p class="text-dark"><strong>Signs:</strong> Young plants cut at soil level, found during night</p>
                    <p class="text-dark mb-0"><strong>Control:</strong> Handpicking at night, use of collars around seedlings</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">DIY Organic Pesticide Recipes</h2>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0">Garlic-Chili Spray</h6>
        </div>
        <div class="card-body">
            <p class="text-dark"><strong>Ingredients:</strong> 10 garlic cloves, 5 hot chili peppers, 1 tbsp liquid soap, 1 liter water</p>
            <p class="text-dark mb-0"><strong>Instructions:</strong> Blend garlic and chili with water, strain, add soap. Spray on affected plants in evening.</p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">Neem Oil Solution</h6>
        </div>
        <div class="card-body">
            <p class="text-dark"><strong>Ingredients:</strong> 2 tbsp neem oil, 1 tsp liquid soap, 1 liter water</p>
            <p class="text-dark mb-0"><strong>Instructions:</strong> Mix neem oil with soap (emulsifier), add to water. Spray weekly as preventive measure.</p>
        </div>
    </div>

    <hr class="my-5">

    <h3 class="mb-4">Want to Learn More?</h3>
    <p>Our Integrated Pest Management course provides in-depth training on identifying and controlling pests organically.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Explore IPM Course</a>
</div>
HTML;
    }

    private function getCompanionPlantingContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">Companion planting is an ancient agricultural practice where certain plants are grown together to benefit each other. This guide shows you which combinations work best in Philippine conditions.</p>

    <img src="https://images.unsplash.com/photo-1466692476868-aef1dfb1e735?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Companion planting garden">

    <h2 class="h3 mb-4">How Companion Planting Works</h2>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="bx bx-shield text-success" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3">Pest Deterrence</h5>
                    <p class="text-secondary mb-0">Some plants repel pests that attack their companions</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="bx bx-leaf text-success" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3">Nutrient Sharing</h5>
                    <p class="text-secondary mb-0">Legumes fix nitrogen that other plants can use</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="bx bx-sun text-success" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3">Space Optimization</h5>
                    <p class="text-secondary mb-0">Different root depths share soil space efficiently</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Best Companion Planting Combinations</h2>

    <table class="table table-bordered">
        <thead class="table-success">
            <tr>
                <th>Main Crop</th>
                <th>Good Companions</th>
                <th>Avoid Planting With</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-dark"><strong>Tomatoes</strong></td>
                <td class="text-dark">Basil, carrots, parsley, marigolds</td>
                <td class="text-dark">Cabbage, fennel, corn</td>
            </tr>
            <tr>
                <td class="text-dark"><strong>Eggplant</strong></td>
                <td class="text-dark">Beans, peppers, spinach</td>
                <td class="text-dark">Fennel</td>
            </tr>
            <tr>
                <td class="text-dark"><strong>Cabbage</strong></td>
                <td class="text-dark">Onions, celery, dill, chamomile</td>
                <td class="text-dark">Strawberries, tomatoes</td>
            </tr>
            <tr>
                <td class="text-dark"><strong>Beans</strong></td>
                <td class="text-dark">Corn, squash, carrots, cucumbers</td>
                <td class="text-dark">Onions, garlic, peppers</td>
            </tr>
            <tr>
                <td class="text-dark"><strong>Corn</strong></td>
                <td class="text-dark">Beans, squash, pumpkin, melons</td>
                <td class="text-dark">Tomatoes</td>
            </tr>
        </tbody>
    </table>

    <div class="alert alert-success border-0 my-4">
        <h5><i class="bx bx-bulb me-2"></i>The Three Sisters</h5>
        <p class="mb-0">Corn, beans, and squash planted together is a traditional companion planting system used by indigenous peoples. Corn provides support for beans, beans fix nitrogen for all three, and squash leaves shade the soil to retain moisture.</p>
    </div>

    <h2 class="h3 mb-4 mt-5">Pest-Repelling Plants</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5><span style="font-size: 1.5rem;">🌿</span> Basil</h5>
                    <p class="text-dark mb-0">Repels flies, mosquitoes, and aphids. Plant near tomatoes and peppers.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5><span style="font-size: 1.5rem;">🌻</span> Marigolds</h5>
                    <p class="text-dark mb-0">Repels nematodes, whiteflies, and aphids. Border gardens with marigolds.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5><span style="font-size: 1.5rem;">🧄</span> Garlic</h5>
                    <p class="text-dark mb-0">Repels aphids and spider mites. Interplant with roses and vegetables.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5><span style="font-size: 1.5rem;">🌸</span> Lemongrass</h5>
                    <p class="text-dark mb-0">Repels mosquitoes and flies. Plant around sitting areas and gardens.</p>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <h3 class="mb-4">Create Your Companion Planting Plan</h3>
    <p>Our garden planning course includes detailed companion planting charts and layout templates for your farm.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Explore Courses</a>
</div>
HTML;
    }

    private function getMariaSuccessStory(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">When Maria Santos started growing vegetables in her small backyard in Cavite, she never imagined it would become a thriving business supplying some of Metro Manila's finest restaurants.</p>

    <div class="row mb-5 align-items-center">
        <div class="col-md-5">
            <img src="https://images.unsplash.com/photo-1592878849122-facb97520f9e?w=500" class="img-fluid rounded shadow-sm" alt="Urban farm">
        </div>
        <div class="col-md-7">
            <h2 class="h4 mb-3">From Hobby to Business</h2>
            <p class="text-dark">Maria started with just 100 square meters—the size of a small basketball court. Today, she manages 5,000 square meters of certified organic farmland and employs 12 people from her community.</p>
            <p class="text-dark"><strong>Location:</strong> Silang, Cavite<br>
            <strong>Started:</strong> 2019<br>
            <strong>Current Revenue:</strong> ₱2.5M annually</p>
        </div>
    </div>

    <h2 class="h3 mb-4">The Beginning</h2>
    <p class="text-dark">Maria was a corporate accountant who felt disconnected from nature. In 2019, she started growing lettuce and herbs as a stress-relief hobby. Her vegetables were so good that friends and neighbors started asking to buy them.</p>

    <blockquote class="blockquote border-start border-4 border-warning ps-4 py-2 my-4 bg-light">
        <p class="mb-0">"I never planned to be a farmer. But when I saw how much people appreciated fresh, chemical-free vegetables, I realized this was my calling."</p>
        <footer class="blockquote-footer mt-2">Maria Santos</footer>
    </blockquote>

    <h2 class="h3 mb-4 mt-5">The Growth Journey</h2>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-success">2019</h6>
                    <p class="mb-0 text-dark">Started with 100 sqm backyard</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-success">2020</h6>
                    <p class="mb-0 text-dark">Leased 500 sqm adjacent lot</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-success">2022</h6>
                    <p class="mb-0 text-dark">Earned organic certification</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-success">2024</h6>
                    <p class="mb-0 text-dark">Expanded to 5,000 sqm</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Key Success Factors</h2>

    <ol>
        <li class="mb-3 text-dark"><strong>Quality Over Quantity:</strong> Maria focused on producing the best possible vegetables rather than the most.</li>
        <li class="mb-3 text-dark"><strong>Direct Relationships:</strong> She built personal relationships with chefs and restaurant owners, understanding their specific needs.</li>
        <li class="mb-3 text-dark"><strong>Continuous Learning:</strong> She took multiple Ani-Senso courses on organic farming, post-harvest handling, and business management.</li>
        <li class="mb-3 text-dark"><strong>Reinvestment:</strong> All profits in the first three years were reinvested into the farm.</li>
    </ol>

    <h2 class="h3 mb-4 mt-5">Products & Clients</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-success text-white">Currently Growing</div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="text-dark">Premium salad greens (lettuce, arugula, kale)</li>
                        <li class="text-dark">Fresh herbs (basil, rosemary, thyme)</li>
                        <li class="text-dark">Cherry tomatoes and microgreens</li>
                        <li class="text-dark">Edible flowers</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-info text-white">Major Clients</div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="text-dark">5 farm-to-table restaurants in Makati</li>
                        <li class="text-dark">3 hotels in BGC</li>
                        <li class="text-dark">Weekly farmers market in Alabang</li>
                        <li class="text-dark">Online delivery customers (200+)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Maria's Advice</h2>

    <blockquote class="blockquote border-start border-4 border-success ps-4 py-2 my-4">
        <p class="mb-0">"Start small but start now. Don't wait for perfect conditions. Learn from every mistake, and never compromise on quality. Your reputation is everything."</p>
    </blockquote>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="mb-4">Ready to Start Your Journey?</h3>
        <p>Learn the same techniques that helped Maria build her successful farm business.</p>
        <a href="/courses" class="btn btn-success btn-lg mt-3">Explore Our Courses</a>
    </div>
</div>
HTML;
    }

    private function getMarkSuccessStory(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">At just 22 years old, Mark Reyes is proving that the future of Philippine agriculture lies in combining traditional farming wisdom with cutting-edge technology.</p>

    <div class="row mb-5 align-items-center">
        <div class="col-md-5">
            <img src="https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=500" class="img-fluid rounded shadow-sm" alt="Young tech farmer">
        </div>
        <div class="col-md-7">
            <h2 class="h4 mb-3">A New Generation Farmer</h2>
            <p class="text-dark">Mark graduated with a degree in Agricultural Engineering from UPLB. Instead of pursuing a corporate career, he chose to return to his family's 3-hectare rice farm in Tarlac—with a vision to transform it using technology.</p>
            <p class="text-dark"><strong>Location:</strong> Paniqui, Tarlac<br>
            <strong>Farm Size:</strong> 3 hectares<br>
            <strong>Yield Increase:</strong> 65% in 2 years</p>
        </div>
    </div>

    <h2 class="h3 mb-4">The Challenge</h2>
    <p class="text-dark">When Mark took over the farm in 2024, yields were declining and his father was ready to sell. "My father had farmed for 40 years, but he couldn't compete with rising costs and unpredictable weather," Mark recalls.</p>

    <h2 class="h3 mb-4 mt-5">The Tech Solution</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-chip text-primary me-2"></i>IoT Soil Sensors</h5>
                    <p class="text-dark">Mark installed 15 soil sensors across the farm that monitor moisture, pH, and nutrient levels in real-time. Data is sent to his smartphone every hour.</p>
                    <p class="text-secondary mb-0"><strong>Investment:</strong> ₱45,000</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-drone text-info me-2"></i>Drone Monitoring</h5>
                    <p class="text-dark">A weekly drone survey identifies pest problems and nutrient deficiencies before they become visible to the naked eye.</p>
                    <p class="text-secondary mb-0"><strong>Investment:</strong> ₱85,000</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-water text-success me-2"></i>Smart Irrigation</h5>
                    <p class="text-dark">An automated irrigation system waters the field based on sensor data, reducing water use by 40% while maintaining optimal moisture.</p>
                    <p class="text-secondary mb-0"><strong>Investment:</strong> ₱65,000</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-line-chart text-warning me-2"></i>Data Analytics</h5>
                    <p class="text-dark">Mark uses a custom dashboard to analyze farm data and make decisions on fertilizer timing, pest control, and harvest scheduling.</p>
                    <p class="text-secondary mb-0"><strong>Investment:</strong> ₱15,000 (software)</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Results After 2 Years</h2>

    <table class="table table-bordered">
        <thead class="table-success">
            <tr>
                <th>Metric</th>
                <th>Before Tech</th>
                <th>After Tech</th>
                <th>Change</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-dark">Yield (cavans/hectare)</td>
                <td class="text-dark">85</td>
                <td class="text-dark">140</td>
                <td class="text-success fw-bold">+65%</td>
            </tr>
            <tr>
                <td class="text-dark">Water Usage</td>
                <td class="text-dark">100%</td>
                <td class="text-dark">60%</td>
                <td class="text-success fw-bold">-40%</td>
            </tr>
            <tr>
                <td class="text-dark">Fertilizer Efficiency</td>
                <td class="text-dark">Base</td>
                <td class="text-dark">+25%</td>
                <td class="text-success fw-bold">Better absorption</td>
            </tr>
            <tr>
                <td class="text-dark">Labor Hours</td>
                <td class="text-dark">100%</td>
                <td class="text-dark">70%</td>
                <td class="text-success fw-bold">-30%</td>
            </tr>
        </tbody>
    </table>

    <blockquote class="blockquote border-start border-4 border-primary ps-4 py-2 my-4 bg-light">
        <p class="mb-0">"Technology is not about replacing farmers—it's about giving us superpowers. I can now manage what used to take my father and three workers using just my phone and one helper."</p>
        <footer class="blockquote-footer mt-2">Mark Reyes</footer>
    </blockquote>

    <h2 class="h3 mb-4 mt-5">What's Next</h2>
    <p class="text-dark">Mark is now developing a cooperative to help neighboring farmers adopt similar technologies at shared costs. He's also creating YouTube tutorials to share his knowledge with young Filipino farmers.</p>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="mb-4">Learn Smart Farming</h3>
        <p>Our Precision Agriculture course teaches the same techniques Mark used to transform his farm.</p>
        <a href="/courses" class="btn btn-primary btn-lg mt-3">Explore Tech Farming Courses</a>
    </div>
</div>
HTML;
    }

    private function getGreenhouseGuideContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">A greenhouse extends your growing season, protects crops from extreme weather, and can significantly increase yields. This guide shows you how to build one for under ₱15,000.</p>

    <img src="https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=800" class="img-fluid rounded shadow-sm mb-5" alt="DIY greenhouse">

    <h2 class="h3 mb-4">Materials Needed</h2>

    <table class="table table-bordered">
        <thead class="table-success">
            <tr>
                <th>Material</th>
                <th>Quantity</th>
                <th>Est. Cost</th>
            </tr>
        </thead>
        <tbody>
            <tr><td class="text-dark">PVC pipes (3/4 inch, 6m)</td><td class="text-dark">20 pieces</td><td class="text-dark">₱2,000</td></tr>
            <tr><td class="text-dark">PVC connectors (various)</td><td class="text-dark">30 pieces</td><td class="text-dark">₱600</td></tr>
            <tr><td class="text-dark">UV-stabilized plastic sheet</td><td class="text-dark">50 sqm</td><td class="text-dark">₱5,000</td></tr>
            <tr><td class="text-dark">Insect net</td><td class="text-dark">20 sqm</td><td class="text-dark">₱1,500</td></tr>
            <tr><td class="text-dark">Steel rebars (ground anchors)</td><td class="text-dark">20 pieces</td><td class="text-dark">₱1,000</td></tr>
            <tr><td class="text-dark">Cable ties, clamps, clips</td><td class="text-dark">Assorted</td><td class="text-dark">₱500</td></tr>
            <tr><td class="text-dark">Door hinges, handle</td><td class="text-dark">1 set</td><td class="text-dark">₱300</td></tr>
            <tr><td class="text-dark">Lumber for door frame</td><td class="text-dark">4 pcs</td><td class="text-dark">₱500</td></tr>
        </tbody>
        <tfoot class="table-light">
            <tr><td colspan="2"><strong>Total Estimated Cost</strong></td><td><strong>₱11,400</strong></td></tr>
        </tfoot>
    </table>

    <h2 class="h3 mb-4 mt-5">Step-by-Step Construction</h2>

    <div class="card mb-3">
        <div class="card-body">
            <h5><span class="badge bg-success me-2">Step 1</span>Site Preparation</h5>
            <p class="text-dark mb-0">Choose a level area with good drainage. Clear vegetation and level the ground. Mark out a 3m x 6m rectangle using stakes and string. Ensure the long side faces east-west for optimal sunlight.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5><span class="badge bg-success me-2">Step 2</span>Install Ground Anchors</h5>
            <p class="text-dark mb-0">Drive steel rebars 45cm into the ground at 1-meter intervals along both long sides. Leave 15cm above ground for PVC insertion. You'll need 14 rebars total (7 on each side).</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5><span class="badge bg-success me-2">Step 3</span>Create the Hoops</h5>
            <p class="text-dark mb-0">Bend 3m PVC pipes into arches by inserting each end onto opposing rebars. The natural curve creates the tunnel shape. Secure with cable ties at the base.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5><span class="badge bg-success me-2">Step 4</span>Add Ridge Pole</h5>
            <p class="text-dark mb-0">Run a straight PVC pipe along the top of all arches as a ridge pole. Secure to each hoop using cable ties or clamps. This adds stability and prevents sagging.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5><span class="badge bg-success me-2">Step 5</span>Cover with Plastic</h5>
            <p class="text-dark mb-0">Drape UV-stabilized plastic over the frame on a calm day. Secure along the bottom by burying edges in soil or weighing down with sandbags. Leave ends open for now.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5><span class="badge bg-success me-2">Step 6</span>Build End Walls</h5>
            <p class="text-dark mb-0">Create end walls using insect net for ventilation. Build a simple door frame from lumber on one end. Attach insect net to both ends, allowing the door to open.</p>
        </div>
    </div>

    <div class="alert alert-info border-0 my-4">
        <h5><i class="bx bx-bulb me-2"></i>Pro Tips</h5>
        <ul class="mb-0">
            <li>Install a shade net (50%) above the plastic for hot summer months</li>
            <li>Add drip irrigation inside for efficient watering</li>
            <li>Consider roll-up sides for additional ventilation</li>
        </ul>
    </div>

    <hr class="my-5">

    <h3 class="mb-4">Want Video Tutorials?</h3>
    <p>Our Protected Cultivation course includes step-by-step video guides for building different greenhouse designs.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Watch Video Tutorials</a>
</div>
HTML;
    }

    private function getMushroomGuideContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">Mushroom farming requires minimal space and can be highly profitable. This guide covers everything you need to start growing oyster and shiitake mushrooms at home.</p>

    <img src="https://images.unsplash.com/photo-1504545102780-26774c1bb073?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Oyster mushrooms">

    <h2 class="h3 mb-4">Why Grow Mushrooms?</h2>

    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center h-100 border-0 shadow-sm">
                <div class="card-body">
                    <span class="display-4">📦</span>
                    <h6 class="mt-2">Small Space</h6>
                    <p class="text-secondary small mb-0">Just 10 sqm can produce ₱15,000/month</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center h-100 border-0 shadow-sm">
                <div class="card-body">
                    <span class="display-4">⚡</span>
                    <h6 class="mt-2">Fast Harvest</h6>
                    <p class="text-secondary small mb-0">First harvest in 2-3 weeks</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center h-100 border-0 shadow-sm">
                <div class="card-body">
                    <span class="display-4">💰</span>
                    <h6 class="mt-2">High Value</h6>
                    <p class="text-secondary small mb-0">Sells for ₱180-250/kg</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center h-100 border-0 shadow-sm">
                <div class="card-body">
                    <span class="display-4">🔄</span>
                    <h6 class="mt-2">Multiple Flushes</h6>
                    <p class="text-secondary small mb-0">3-5 harvests per bag</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Getting Started: Oyster Mushrooms</h2>

    <h3 class="h5 mt-4">Materials Needed</h3>
    <ul class="mb-4">
        <li class="text-dark">Substrate (rice straw, sawdust, or banana leaves)</li>
        <li class="text-dark">Mushroom spawn (buy from certified suppliers)</li>
        <li class="text-dark">Plastic bags (PP bags, 6x12 inches)</li>
        <li class="text-dark">PVC neck rings and cotton plugs</li>
        <li class="text-dark">Sterilization setup (drum or steamer)</li>
        <li class="text-dark">Growing house (simple bahay kubo works)</li>
    </ul>

    <h3 class="h5 mt-4">Step-by-Step Process</h3>

    <ol class="mb-4">
        <li class="mb-3 text-dark">
            <strong>Prepare Substrate:</strong> Soak rice straw in water overnight. Drain and chop into 2-3 inch pieces. Mix with rice bran (10:1 ratio) and adjust moisture to 65%.
        </li>
        <li class="mb-3 text-dark">
            <strong>Sterilize:</strong> Pack substrate into PP bags loosely. Steam for 2-3 hours at 100°C. Allow to cool completely before inoculation.
        </li>
        <li class="mb-3 text-dark">
            <strong>Inoculate:</strong> In a clean area, add spawn (5% of substrate weight) in layers. Insert neck ring, plug with cotton, and seal.
        </li>
        <li class="mb-3 text-dark">
            <strong>Incubation:</strong> Store bags in dark room at 25-28°C for 14-21 days until fully colonized (all white).
        </li>
        <li class="mb-3 text-dark">
            <strong>Fruiting:</strong> Move to growing house with high humidity (80-90%), good ventilation, and indirect light. Cut small X on bag for mushrooms to emerge.
        </li>
        <li class="mb-3 text-dark">
            <strong>Harvest:</strong> Pick when caps flatten but before spores drop (edges still slightly curved). Twist and pull gently.
        </li>
    </ol>

    <div class="alert alert-warning border-0 my-4">
        <h5><i class="bx bx-info-circle me-2"></i>Common Mistakes to Avoid</h5>
        <ul class="mb-0">
            <li>Not sterilizing substrate properly (causes contamination)</li>
            <li>Too much or too little moisture</li>
            <li>Poor ventilation during fruiting (causes weak stems)</li>
            <li>Harvesting too late (reduces quality)</li>
        </ul>
    </div>

    <h2 class="h3 mb-4 mt-5">Expected Income</h2>

    <table class="table table-bordered">
        <tbody>
            <tr>
                <td class="text-dark"><strong>100 bags production</strong></td>
                <td class="text-dark">Yields approximately 30-40 kg per flush</td>
            </tr>
            <tr>
                <td class="text-dark"><strong>Selling price</strong></td>
                <td class="text-dark">₱180-250/kg (fresh)</td>
            </tr>
            <tr>
                <td class="text-dark"><strong>Potential monthly income</strong></td>
                <td class="text-dark">₱10,000-25,000 (with 3-4 flushes)</td>
            </tr>
        </tbody>
    </table>

    <hr class="my-5">

    <h3 class="mb-4">Learn from the Experts</h3>
    <p>Our Mushroom Cultivation course includes hands-on training at our demonstration farm.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Explore Mushroom Course</a>
</div>
HTML;
    }

    private function getPoultryGuideContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">Poultry farming is one of the most accessible livestock businesses for beginners. This guide shows you how to start with just 50 chickens and grow a profitable operation.</p>

    <img src="https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Free range chickens">

    <h2 class="h3 mb-4">Choosing Your Focus</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">🥚 Layer Chickens (Eggs)</h5>
                </div>
                <div class="card-body">
                    <p class="text-dark"><strong>Pros:</strong> Continuous income, less labor-intensive</p>
                    <p class="text-dark"><strong>Cons:</strong> Higher initial cost, longer payback period</p>
                    <p class="text-dark mb-0"><strong>Best breeds:</strong> Lohmann Brown, Hy-Line, ISA Brown</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">🍗 Broiler Chickens (Meat)</h5>
                </div>
                <div class="card-body">
                    <p class="text-dark"><strong>Pros:</strong> Fast turnaround (45 days), quick cash flow</p>
                    <p class="text-dark"><strong>Cons:</strong> More labor, batch income (not continuous)</p>
                    <p class="text-dark mb-0"><strong>Best breeds:</strong> Cobb, Ross, Arbor Acres</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Starting with 50 Layers</h2>

    <h3 class="h5">Initial Investment</h3>

    <table class="table table-bordered mb-4">
        <thead class="table-light">
            <tr>
                <th>Item</th>
                <th>Cost</th>
            </tr>
        </thead>
        <tbody>
            <tr><td class="text-dark">50 ready-to-lay pullets (16 weeks)</td><td class="text-dark">₱17,500 (₱350 each)</td></tr>
            <tr><td class="text-dark">Simple housing (20 sqm)</td><td class="text-dark">₱15,000</td></tr>
            <tr><td class="text-dark">Feeders and waterers</td><td class="text-dark">₱3,000</td></tr>
            <tr><td class="text-dark">Nest boxes (10 units)</td><td class="text-dark">₱2,000</td></tr>
            <tr><td class="text-dark">First month feed supply</td><td class="text-dark">₱7,500</td></tr>
            <tr><td class="text-dark">Medications and supplements</td><td class="text-dark">₱1,500</td></tr>
        </tbody>
        <tfoot class="table-success">
            <tr><td><strong>Total Initial Investment</strong></td><td><strong>₱46,500</strong></td></tr>
        </tfoot>
    </table>

    <h3 class="h5">Expected Monthly Returns</h3>

    <table class="table table-bordered mb-4">
        <tbody>
            <tr><td class="text-dark"><strong>Eggs produced (80% laying rate)</strong></td><td class="text-dark">1,200 eggs/month</td></tr>
            <tr><td class="text-dark"><strong>Revenue (₱7.50/egg)</strong></td><td class="text-dark">₱9,000</td></tr>
            <tr><td class="text-dark"><strong>Feed cost (150g/bird/day)</strong></td><td class="text-dark">₱5,625</td></tr>
            <tr><td class="text-dark"><strong>Other expenses</strong></td><td class="text-dark">₱500</td></tr>
        </tbody>
        <tfoot class="table-success">
            <tr><td><strong>Net Monthly Profit</strong></td><td><strong>₱2,875</strong></td></tr>
        </tfoot>
    </table>

    <h2 class="h3 mb-4 mt-5">Essential Care Tips</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-primary text-white">Daily Tasks</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item text-dark">Provide fresh water and feed</li>
                    <li class="list-group-item text-dark">Collect eggs (morning and afternoon)</li>
                    <li class="list-group-item text-dark">Check for sick or injured birds</li>
                    <li class="list-group-item text-dark">Clean feeders and waterers</li>
                </ul>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-success text-white">Weekly Tasks</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item text-dark">Clean housing and replace bedding</li>
                    <li class="list-group-item text-dark">Check and repair equipment</li>
                    <li class="list-group-item text-dark">Record egg production and feed consumption</li>
                    <li class="list-group-item text-dark">Monitor body weight (sample 5 birds)</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="alert alert-info border-0 my-4">
        <h5><i class="bx bx-shield me-2"></i>Biosecurity Tips</h5>
        <ul class="mb-0">
            <li>Limit visitors to your poultry area</li>
            <li>Use footbaths at entry points</li>
            <li>Quarantine new birds for 2 weeks before mixing</li>
            <li>Keep feed storage away from rodents</li>
        </ul>
    </div>

    <hr class="my-5">

    <h3 class="mb-4">Learn Poultry Management</h3>
    <p>Our Poultry Farming course covers everything from breed selection to disease prevention and marketing.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Explore Poultry Course</a>
</div>
HTML;
    }

    private function getPremiumAnnouncementContent(): string
    {
        return <<<HTML
<div class="container">
    <div class="text-center mb-5">
        <span class="badge bg-warning text-dark fs-6 mb-3">New Membership Tier</span>
        <h2 class="display-5 mb-3">Introducing Ani-Senso Premium</h2>
        <p class="lead">Take your agricultural learning to the next level</p>
    </div>

    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Premium learning">

    <h2 class="h3 mb-4">What's Included</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-star text-warning me-2"></i>Exclusive Courses</h5>
                    <p class="text-dark mb-0">Access premium-only courses on advanced topics like precision agriculture, farm business planning, and export strategies.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-video text-primary me-2"></i>Live Mentorship</h5>
                    <p class="text-dark mb-0">Weekly live sessions with agricultural experts. Ask questions, get personalized advice, and learn from real case studies.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-download text-success me-2"></i>Downloadable Resources</h5>
                    <p class="text-dark mb-0">Farm templates, business plans, checklists, and guides. Print and use on your farm.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-support text-danger me-2"></i>Priority Support</h5>
                    <p class="text-dark mb-0">Get faster responses from our support team. Direct chat with course instructors.</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Membership Plans</h2>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center">
                <div class="card-header">
                    <h5 class="mb-0">Monthly</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-primary">₱499<small class="text-secondary fs-6">/mo</small></h2>
                    <p class="text-secondary">Perfect for trying out</p>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2 text-dark">✓ All Premium features</li>
                        <li class="mb-2 text-dark">✓ Cancel anytime</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Annual <span class="badge bg-warning text-dark">Best Value</span></h5>
                </div>
                <div class="card-body">
                    <h2 class="text-success">₱3,999<small class="text-secondary fs-6">/yr</small></h2>
                    <p class="text-secondary">Save ₱1,989 (33% off)</p>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2 text-dark">✓ All Premium features</li>
                        <li class="mb-2 text-dark">✓ 2 months FREE</li>
                        <li class="mb-2 text-dark">✓ Bonus: Farm planning template</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Lifetime</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-dark">₱9,999<small class="text-secondary fs-6"> once</small></h2>
                    <p class="text-secondary">Pay once, access forever</p>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2 text-dark">✓ All Premium features</li>
                        <li class="mb-2 text-dark">✓ Future courses included</li>
                        <li class="mb-2 text-dark">✓ VIP community access</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-success border-0 my-4">
        <h5><i class="bx bx-gift me-2"></i>Launch Special</h5>
        <p class="mb-0">Get 50% off your first month with code <strong>PREMIUM50</strong>. Valid until April 30, 2026.</p>
    </div>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="mb-4">Ready to Go Premium?</h3>
        <a href="/premium" class="btn btn-success btn-lg mt-3">Upgrade Now</a>
    </div>
</div>
HTML;
    }

    private function getAIToolContent(): string
    {
        return <<<HTML
<div class="container">
    <div class="alert alert-info border-0 mb-4">
        <h5 class="alert-heading"><i class="bx bx-chip me-2"></i>New Feature!</h5>
        <p class="mb-0">AI-powered crop disease detection is now available in the Ani-Senso app.</p>
    </div>

    <p class="lead mb-4">Simply take a photo of your plant, and our AI will identify diseases, nutrient deficiencies, and pest damage within seconds—with 95% accuracy.</p>

    <img src="https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800" class="img-fluid rounded shadow-sm mb-5" alt="AI technology">

    <h2 class="h3 mb-4">How It Works</h2>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card text-center h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-4 d-inline-block mb-3">
                        <i class="bx bx-camera text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h5>1. Take a Photo</h5>
                    <p class="text-secondary mb-0">Open the app and take a clear photo of the affected leaf or plant part</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="bg-success bg-opacity-10 rounded-circle p-4 d-inline-block mb-3">
                        <i class="bx bx-analyse text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h5>2. AI Analysis</h5>
                    <p class="text-secondary mb-0">Our AI analyzes the image using trained models from millions of plant photos</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="bg-warning bg-opacity-10 rounded-circle p-4 d-inline-block mb-3">
                        <i class="bx bx-check-shield text-warning" style="font-size: 2rem;"></i>
                    </div>
                    <h5>3. Get Treatment</h5>
                    <p class="text-secondary mb-0">Receive instant diagnosis with recommended organic and chemical treatments</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Supported Crops</h2>

    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-2"><span class="badge bg-success w-100 py-2">Rice</span></div>
        <div class="col-6 col-md-3 mb-2"><span class="badge bg-success w-100 py-2">Corn</span></div>
        <div class="col-6 col-md-3 mb-2"><span class="badge bg-success w-100 py-2">Tomatoes</span></div>
        <div class="col-6 col-md-3 mb-2"><span class="badge bg-success w-100 py-2">Eggplant</span></div>
        <div class="col-6 col-md-3 mb-2"><span class="badge bg-success w-100 py-2">Peppers</span></div>
        <div class="col-6 col-md-3 mb-2"><span class="badge bg-success w-100 py-2">Cabbage</span></div>
        <div class="col-6 col-md-3 mb-2"><span class="badge bg-success w-100 py-2">Banana</span></div>
        <div class="col-6 col-md-3 mb-2"><span class="badge bg-success w-100 py-2">Mango</span></div>
    </div>

    <p class="text-secondary">More crops being added monthly. Request your crop through the app.</p>

    <h2 class="h3 mb-4 mt-5">What Users Are Saying</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="text-warning">★★★★★</div>
                    </div>
                    <p class="text-dark">"Saved my tomato crop! Identified bacterial wilt early so I could remove infected plants before it spread."</p>
                    <p class="text-secondary mb-0">— Roberto, Laguna</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="text-warning">★★★★★</div>
                    </div>
                    <p class="text-dark">"Like having an agricultural expert in my pocket. The treatment recommendations actually work!"</p>
                    <p class="text-secondary mb-0">— Ana, Pangasinan</p>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="mb-4">Try It Now</h3>
        <p>The AI Disease Detection tool is available free for all Ani-Senso app users.</p>
        <a href="#" class="btn btn-primary btn-lg mt-3">Download the App</a>
    </div>
</div>
HTML;
    }

    private function getAquaponicsWebinarContent(): string
    {
        return <<<HTML
<div class="container">
    <div class="text-center mb-5">
        <span class="badge bg-primary fs-6 mb-3">Free Webinar</span>
        <h2 class="display-5 mb-3">Introduction to Aquaponics</h2>
        <p class="lead">Grow vegetables and raise fish in one integrated system</p>
        <p class="h4 text-primary">April 5, 2026 | 2:00 PM - 4:00 PM (PHT)</p>
    </div>

    <img src="https://images.unsplash.com/photo-1580910051074-3eb694886f8b?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Aquaponics system">

    <h2 class="h3 mb-4">What is Aquaponics?</h2>
    <p class="text-dark">Aquaponics combines aquaculture (fish farming) with hydroponics (soilless plant growing) in a symbiotic environment. Fish waste provides nutrients for plants, and plants clean the water for fish. It's sustainable, water-efficient, and perfect for urban farming.</p>

    <h2 class="h3 mb-4 mt-5">What You'll Learn</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-book text-primary me-2"></i>Aquaponics Basics</h5>
                    <ul class="text-dark mb-0">
                        <li>How the fish-plant cycle works</li>
                        <li>Types of aquaponics systems</li>
                        <li>Nitrogen cycle explained</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-home text-success me-2"></i>Setting Up</h5>
                    <ul class="text-dark mb-0">
                        <li>Choosing the right system for your space</li>
                        <li>Essential equipment list</li>
                        <li>Budget planning</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-leaf text-info me-2"></i>Best Plants & Fish</h5>
                    <ul class="text-dark mb-0">
                        <li>Best vegetables for Philippine climate</li>
                        <li>Tilapia vs other fish species</li>
                        <li>Stocking ratios</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-wrench text-warning me-2"></i>Maintenance</h5>
                    <ul class="text-dark mb-0">
                        <li>Daily and weekly tasks</li>
                        <li>Water quality monitoring</li>
                        <li>Troubleshooting common problems</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Your Instructor</h2>

    <div class="row mb-4 align-items-center">
        <div class="col-md-3 text-center mb-3">
            <div class="bg-secondary rounded-circle mx-auto" style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                <i class="bx bx-user text-white" style="font-size: 4rem;"></i>
            </div>
        </div>
        <div class="col-md-9">
            <h4>Engr. Paolo Villanueva</h4>
            <p class="text-secondary">Agricultural Engineer | Aquaponics Specialist</p>
            <p class="text-dark">Paolo has been practicing aquaponics for over 8 years and has helped establish more than 50 aquaponics systems across the Philippines. He runs a commercial aquaponics farm in Bulacan that produces 200kg of vegetables weekly.</p>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">How to Join</h2>

    <ol class="mb-4">
        <li class="mb-2 text-dark">Register using the form below (free)</li>
        <li class="mb-2 text-dark">Receive Zoom link via email</li>
        <li class="mb-2 text-dark">Join on April 5 at 2:00 PM</li>
        <li class="mb-2 text-dark">Participate in live Q&A at the end</li>
    </ol>

    <div class="alert alert-warning border-0 my-4">
        <h5><i class="bx bx-gift me-2"></i>Bonus for Attendees</h5>
        <p class="mb-0">All attendees will receive a free Aquaponics Starter Guide (PDF) and 20% discount on our full Aquaponics course.</p>
    </div>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="mb-4">Reserve Your Spot</h3>
        <p>Limited to 500 participants. Register now to secure your place.</p>
        <a href="/register-webinar" class="btn btn-primary btn-lg mt-3">Register Free</a>
    </div>
</div>
HTML;
    }

    private function getFarmTourContent(): string
    {
        return <<<HTML
<div class="container">
    <div class="text-center mb-5">
        <span class="badge bg-orange text-white fs-6 mb-3">Farm Tour</span>
        <h2 class="display-5 mb-3">Organic Farms of Laguna</h2>
        <p class="lead">A hands-on learning experience at three successful farms</p>
        <p class="h4 text-orange">April 20, 2026 | 6:00 AM - 5:00 PM</p>
    </div>

    <img src="https://images.unsplash.com/photo-1500076656116-558758c991c1?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Organic farm tour">

    <h2 class="h3 mb-4">Tour Highlights</h2>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Stop 1: Green Harvest Farm</h6>
                </div>
                <div class="card-body">
                    <p class="text-dark"><strong>Focus:</strong> Mixed vegetable production</p>
                    <p class="text-dark"><strong>Learn:</strong> Intensive cropping systems, organic pest management, direct marketing strategies</p>
                    <p class="text-secondary mb-0"><em>Light breakfast provided</em></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">Stop 2: Lotus Leaf Gardens</h6>
                </div>
                <div class="card-body">
                    <p class="text-dark"><strong>Focus:</strong> Specialty greens & herbs</p>
                    <p class="text-dark"><strong>Learn:</strong> Protected cultivation, value-adding (dried herbs, pesto), restaurant partnerships</p>
                    <p class="text-secondary mb-0"><em>Herb workshop included</em></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">Stop 3: Integrated AgriVentures</h6>
                </div>
                <div class="card-body">
                    <p class="text-dark"><strong>Focus:</strong> Integrated farming system</p>
                    <p class="text-dark"><strong>Learn:</strong> Livestock-crop integration, composting at scale, farm business management</p>
                    <p class="text-secondary mb-0"><em>Farm lunch included</em></p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Tour Schedule</h2>

    <table class="table table-bordered">
        <tbody>
            <tr><td class="text-dark"><strong>6:00 AM</strong></td><td class="text-dark">Assembly at SM City San Pablo parking lot</td></tr>
            <tr><td class="text-dark"><strong>6:30 AM</strong></td><td class="text-dark">Departure by air-conditioned bus</td></tr>
            <tr><td class="text-dark"><strong>7:30 AM</strong></td><td class="text-dark">Green Harvest Farm visit + breakfast</td></tr>
            <tr><td class="text-dark"><strong>10:00 AM</strong></td><td class="text-dark">Lotus Leaf Gardens visit + workshop</td></tr>
            <tr><td class="text-dark"><strong>12:30 PM</strong></td><td class="text-dark">Integrated AgriVentures visit + lunch</td></tr>
            <tr><td class="text-dark"><strong>3:00 PM</strong></td><td class="text-dark">Q&A and networking session</td></tr>
            <tr><td class="text-dark"><strong>4:00 PM</strong></td><td class="text-dark">Departure back to SM City San Pablo</td></tr>
            <tr><td class="text-dark"><strong>5:00 PM</strong></td><td class="text-dark">Estimated arrival</td></tr>
        </tbody>
    </table>

    <h2 class="h3 mb-4 mt-5">What's Included</h2>

    <div class="row mb-4">
        <div class="col-md-6">
            <ul class="list-unstyled">
                <li class="mb-2 text-dark">✓ Air-conditioned bus transportation</li>
                <li class="mb-2 text-dark">✓ Breakfast and lunch</li>
                <li class="mb-2 text-dark">✓ Farm tour guide at each stop</li>
                <li class="mb-2 text-dark">✓ Herb workshop materials</li>
            </ul>
        </div>
        <div class="col-md-6">
            <ul class="list-unstyled">
                <li class="mb-2 text-dark">✓ Networking session</li>
                <li class="mb-2 text-dark">✓ Tour certificate</li>
                <li class="mb-2 text-dark">✓ Seedling pack to take home</li>
                <li class="mb-2 text-dark">✓ Event photos</li>
            </ul>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Registration</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Early Bird (until April 10)</h5>
                    <h2 class="text-success">₱1,200</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Regular (April 11-18)</h5>
                    <h2>₱1,500</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info border-0 my-4">
        <h5><i class="bx bx-info-circle me-2"></i>What to Bring</h5>
        <ul class="mb-0">
            <li>Comfortable walking shoes</li>
            <li>Hat and sunscreen</li>
            <li>Notebook and pen</li>
            <li>Camera (optional)</li>
            <li>Reusable water bottle</li>
        </ul>
    </div>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="mb-4">Join the Tour</h3>
        <p>Limited to 40 participants for a personalized experience.</p>
        <a href="/register-tour" class="btn btn-success btn-lg mt-3">Register Now</a>
    </div>
</div>
HTML;
    }
}
