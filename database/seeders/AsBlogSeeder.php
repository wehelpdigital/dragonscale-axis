<?php

namespace Database\Seeders;

use App\Models\AsBlog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class AsBlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = 1; // Admin user

        $blogs = [
            [
                'blogTitle' => '10 Essential Rice Farming Tips for Higher Yields in the Philippines',
                'blogCategory' => 'Farming Tips',
                'blogExcerpt' => 'Discover proven rice farming techniques that Filipino farmers are using to increase their yields by up to 40%. From proper land preparation to optimal harvesting times, these tips will transform your rice production.',
                'blogContent' => $this->getRiceFarmingContent(),
                'focusKeyword' => 'rice farming tips',
                'metaTitle' => '10 Essential Rice Farming Tips for Higher Yields | Ani-Senso',
                'metaDescription' => 'Learn proven rice farming techniques used by successful Filipino farmers. Increase your yields by up to 40% with proper land preparation and harvesting tips.',
                'metaKeywords' => 'rice farming, palay farming, rice cultivation, farming tips, Philippine agriculture, rice yield',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Ani-Senso Team',
                'schemaType' => 'HowTo',
            ],
            [
                'blogTitle' => 'Success Story: How Juan Dela Cruz Tripled His Farm Income Using Modern Techniques',
                'blogCategory' => 'Success Stories',
                'blogExcerpt' => 'Meet Juan Dela Cruz, a farmer from Nueva Ecija who transformed his 2-hectare farm into a thriving agricultural business. Learn how he combined traditional wisdom with modern farming technology.',
                'blogContent' => $this->getSuccessStoryContent(),
                'focusKeyword' => 'farming success story',
                'metaTitle' => 'Success Story: Tripling Farm Income with Modern Techniques',
                'metaDescription' => 'Read how Juan Dela Cruz from Nueva Ecija tripled his farm income by combining traditional wisdom with modern agricultural technology.',
                'metaKeywords' => 'farming success, agricultural success, modern farming, Nueva Ecija farmer, farm income',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Maria Santos',
                'schemaType' => 'Article',
            ],
            [
                'blogTitle' => 'Complete Guide to Organic Vegetable Farming for Beginners',
                'blogCategory' => 'Guides',
                'blogExcerpt' => 'Start your organic vegetable farming journey with this comprehensive guide. Learn about soil preparation, natural pest control, composting, and which vegetables are best for Philippine climate.',
                'blogContent' => $this->getOrganicFarmingContent(),
                'focusKeyword' => 'organic vegetable farming',
                'metaTitle' => 'Complete Guide to Organic Vegetable Farming | Ani-Senso',
                'metaDescription' => 'Start organic vegetable farming with our complete beginner guide. Learn soil preparation, natural pest control, and best vegetables for Philippine climate.',
                'metaKeywords' => 'organic farming, vegetable farming, organic vegetables, natural farming, composting, pest control',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'Dr. Elena Reyes',
                'schemaType' => 'HowTo',
            ],
            [
                'blogTitle' => 'Understanding Soil Health: The Foundation of Successful Farming',
                'blogCategory' => 'Farming Tips',
                'blogExcerpt' => 'Healthy soil is the secret to productive farming. Learn how to test your soil, understand pH levels, and improve soil structure for better crop yields.',
                'blogContent' => $this->getSoilHealthContent(),
                'focusKeyword' => 'soil health',
                'metaTitle' => 'Understanding Soil Health for Better Farming | Ani-Senso',
                'metaDescription' => 'Learn the fundamentals of soil health including pH testing, soil structure improvement, and organic matter management for better crop yields.',
                'metaKeywords' => 'soil health, soil testing, pH levels, soil fertility, organic matter, crop yields',
                'blogStatus' => 'published',
                'isFeatured' => false,
                'authorName' => 'Ani-Senso Team',
                'schemaType' => 'Article',
            ],
            [
                'blogTitle' => 'New Ani-Senso Course: Advanced Crop Management Techniques',
                'blogCategory' => 'Product Updates',
                'blogExcerpt' => 'We are excited to announce our newest course covering advanced crop management techniques including precision agriculture, IoT sensors for farming, and data-driven decision making.',
                'blogContent' => $this->getProductUpdateContent(),
                'focusKeyword' => 'crop management course',
                'metaTitle' => 'New Course: Advanced Crop Management Techniques | Ani-Senso',
                'metaDescription' => 'Enroll in our new Advanced Crop Management course. Learn precision agriculture, IoT sensors, and data-driven farming decisions.',
                'metaKeywords' => 'crop management, precision agriculture, IoT farming, farming course, agriculture training',
                'blogStatus' => 'published',
                'isFeatured' => true,
                'authorName' => 'Ani-Senso Academy',
                'schemaType' => 'NewsArticle',
            ],
            [
                'blogTitle' => 'Upcoming Farmers Summit 2026: Registration Now Open',
                'blogCategory' => 'Events',
                'blogExcerpt' => 'Join us at the Farmers Summit 2026 in Manila! Connect with fellow farmers, learn from agricultural experts, and discover the latest farming technologies. Early bird registration available.',
                'blogContent' => $this->getEventContent(),
                'focusKeyword' => 'farmers summit 2026',
                'metaTitle' => 'Farmers Summit 2026 - Registration Open | Ani-Senso',
                'metaDescription' => 'Register for Farmers Summit 2026 in Manila. Connect with farmers, learn from experts, and discover latest farming technologies. Early bird pricing available.',
                'metaKeywords' => 'farmers summit, farming event, agriculture conference, Manila event, farming expo',
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
                'blogCategoryColor' => AsBlog::getCategories()[$blogData['blogCategory']] ?? 'brand-green',
                'blogExcerpt' => $blogData['blogExcerpt'],
                'blogContent' => $blogData['blogContent'],
                'useBuilder' => false,
                'metaTitle' => $blogData['metaTitle'],
                'metaDescription' => $blogData['metaDescription'],
                'metaKeywords' => $blogData['metaKeywords'],
                'focusKeyword' => $blogData['focusKeyword'],
                'blogStatus' => $blogData['blogStatus'],
                'publishedAt' => now()->subDays(rand(1, 30)),
                'isFeatured' => $blogData['isFeatured'],
                'authorName' => $blogData['authorName'],
                'schemaType' => $blogData['schemaType'],
                'viewCount' => rand(50, 500),
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
    }

    private function getRiceFarmingContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">Rice is the staple food of millions of Filipinos, and mastering rice farming techniques can significantly impact your yield and income. In this comprehensive guide, we share 10 essential tips that successful farmers use to maximize their rice production.</p>

    <div class="row mb-5">
        <div class="col-md-6">
            <img src="https://images.unsplash.com/photo-1536054695882-b13e2e7fc5a0?w=600" class="img-fluid rounded shadow-sm" alt="Rice paddy field in the Philippines">
        </div>
        <div class="col-md-6 d-flex align-items-center">
            <div>
                <h2 class="h4 mb-3">Why These Tips Matter</h2>
                <p>Filipino farmers who implement these techniques have reported yield increases of 30-40%. The key is consistency and proper timing throughout the growing season.</p>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4"><span class="text-success">1.</span> Proper Land Preparation</h2>
    <p>Good land preparation is the foundation of successful rice farming. Start by plowing the field 2-3 times to break up soil clumps and incorporate crop residues. Follow with harrowing to level the field and create a fine tilth.</p>

    <blockquote class="blockquote border-start border-4 border-success ps-4 py-2 my-4 bg-light">
        <p class="mb-0">"A well-prepared field is half the battle won. Never rush the land preparation phase."</p>
        <footer class="blockquote-footer mt-2">Agricultural Extension Officer, PhilRice</footer>
    </blockquote>

    <h2 class="h3 mb-4 mt-5"><span class="text-success">2.</span> Choose the Right Rice Variety</h2>
    <p>Select rice varieties that are suited to your location and season. Consider factors like:</p>
    <ul class="mb-4">
        <li>Maturity period (early, medium, or late)</li>
        <li>Resistance to common pests and diseases</li>
        <li>Grain quality and market demand</li>
        <li>Drought or flood tolerance for your area</li>
    </ul>

    <div class="alert alert-info border-0 mb-4">
        <h5><i class="bx bx-bulb me-2"></i>Pro Tip</h5>
        <p class="mb-0">Consult with your local agricultural office for certified seeds that perform best in your region.</p>
    </div>

    <h2 class="h3 mb-4 mt-5"><span class="text-success">3.</span> Optimal Seedling Management</h2>
    <p>Healthy seedlings are crucial for high yields. Prepare your seedbed properly and transplant seedlings at 21-25 days old. Younger seedlings establish faster and produce more tillers.</p>

    <h2 class="h3 mb-4 mt-5"><span class="text-success">4.</span> Proper Water Management</h2>
    <p>Maintain 2-3 inches of standing water during the vegetative stage. Drain the field during the reproductive stage to encourage root development and reduce pest problems.</p>

    <h2 class="h3 mb-4 mt-5"><span class="text-success">5.</span> Balanced Fertilizer Application</h2>
    <p>Apply fertilizers based on soil test results. The general recommendation is:</p>
    <ul>
        <li><strong>Basal application:</strong> Apply complete fertilizer at transplanting</li>
        <li><strong>First top-dress:</strong> Apply nitrogen 25-30 days after transplanting</li>
        <li><strong>Second top-dress:</strong> Apply at panicle initiation stage</li>
    </ul>

    <h2 class="h3 mb-4 mt-5"><span class="text-success">6.</span> Integrated Pest Management</h2>
    <p>Monitor your field regularly for pests and diseases. Use biological controls when possible and apply pesticides only when necessary. Early detection is key to preventing major outbreaks.</p>

    <h2 class="h3 mb-4 mt-5"><span class="text-success">7.</span> Proper Weed Control</h2>
    <p>Weeds compete with rice for nutrients, water, and sunlight. Control weeds through proper land preparation, maintaining standing water, and timely weeding.</p>

    <h2 class="h3 mb-4 mt-5"><span class="text-success">8.</span> Monitor Crop Growth</h2>
    <p>Regular field visits help you identify problems early. Look for signs of nutrient deficiency, pest damage, or water stress. Keep a farm diary to track your observations.</p>

    <h2 class="h3 mb-4 mt-5"><span class="text-success">9.</span> Harvest at the Right Time</h2>
    <p>Harvest when 80-85% of grains are straw-colored. Late harvesting causes grain shattering and quality loss, while early harvesting results in immature grains.</p>

    <h2 class="h3 mb-4 mt-5"><span class="text-success">10.</span> Post-Harvest Handling</h2>
    <p>Proper drying and storage are crucial to maintain grain quality. Dry grains to 14% moisture content and store in clean, dry containers.</p>

    <hr class="my-5">

    <h3 class="mb-4">Ready to Improve Your Rice Farming?</h3>
    <p>These tips are just the beginning. Enroll in our comprehensive rice farming course to learn advanced techniques and connect with successful farmers.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Explore Our Courses</a>
</div>
HTML;
    }

    private function getSuccessStoryContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">In the heart of Nueva Ecija, known as the Rice Granary of the Philippines, one farmer's story stands out as an inspiration to agricultural communities across the country.</p>

    <div class="row mb-5 align-items-center">
        <div class="col-md-5">
            <img src="https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=500" class="img-fluid rounded shadow-sm" alt="Filipino farmer in rice field">
        </div>
        <div class="col-md-7">
            <h2 class="h4 mb-3">Meet Juan Dela Cruz</h2>
            <p>At 45 years old, Juan has been farming for over two decades. But it wasn't until five years ago that his farm truly transformed from a struggling operation into a thriving agricultural enterprise.</p>
            <p><strong>Location:</strong> Cabanatuan City, Nueva Ecija<br>
            <strong>Farm Size:</strong> 2 hectares<br>
            <strong>Main Crops:</strong> Rice, vegetables</p>
        </div>
    </div>

    <h2 class="h3 mb-4">The Challenge</h2>
    <p>Like many Filipino farmers, Juan faced numerous challenges: low yields, high input costs, unpredictable weather, and limited access to markets. His annual income barely covered his family's needs, and he was considering giving up farming altogether.</p>

    <blockquote class="blockquote border-start border-4 border-warning ps-4 py-2 my-4 bg-light">
        <p class="mb-0">"I was ready to sell our land and look for work in Manila. My children's education was at stake, and I didn't see a future in farming."</p>
        <footer class="blockquote-footer mt-2">Juan Dela Cruz</footer>
    </blockquote>

    <h2 class="h3 mb-4 mt-5">The Turning Point</h2>
    <p>Everything changed when Juan attended an Ani-Senso agricultural training program in 2021. The three-day intensive course introduced him to:</p>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bx bx-water text-primary" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3">Water Management</h5>
                    <p class="text-muted mb-0">Alternate wetting and drying technique</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bx bx-leaf text-success" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3">Organic Inputs</h5>
                    <p class="text-muted mb-0">Compost and bio-fertilizers</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bx bx-line-chart text-info" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3">Market Access</h5>
                    <p class="text-muted mb-0">Direct selling to restaurants</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">The Results</h2>
    <p>Within two years of implementing what he learned, Juan's farm underwent a remarkable transformation:</p>

    <table class="table table-bordered mb-4">
        <thead class="table-success">
            <tr>
                <th>Metric</th>
                <th>Before (2020)</th>
                <th>After (2023)</th>
                <th>Change</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Rice Yield (cavans/hectare)</td>
                <td>80</td>
                <td>140</td>
                <td class="text-success fw-bold">+75%</td>
            </tr>
            <tr>
                <td>Annual Income</td>
                <td>₱120,000</td>
                <td>₱380,000</td>
                <td class="text-success fw-bold">+217%</td>
            </tr>
            <tr>
                <td>Input Costs</td>
                <td>₱45,000</td>
                <td>₱35,000</td>
                <td class="text-success fw-bold">-22%</td>
            </tr>
        </tbody>
    </table>

    <h2 class="h3 mb-4 mt-5">Key Success Factors</h2>
    <ol>
        <li class="mb-3"><strong>Continuous Learning:</strong> Juan didn't stop at one training. He enrolled in multiple Ani-Senso courses and regularly attends farmer field days.</li>
        <li class="mb-3"><strong>Network Building:</strong> He connected with other progressive farmers and formed a cooperative for bulk buying and collective selling.</li>
        <li class="mb-3"><strong>Record Keeping:</strong> Juan maintains detailed records of his farming activities, costs, and yields, allowing him to make data-driven decisions.</li>
        <li class="mb-3"><strong>Diversification:</strong> He added vegetable production during off-season, creating additional income streams.</li>
    </ol>

    <div class="alert alert-success border-0 mb-4">
        <h5><i class="bx bx-award me-2"></i>Recognition</h5>
        <p class="mb-0">In 2023, Juan was recognized as an Outstanding Farmer by the Department of Agriculture Region 3.</p>
    </div>

    <h2 class="h3 mb-4 mt-5">Juan's Advice to Fellow Farmers</h2>
    <blockquote class="blockquote border-start border-4 border-success ps-4 py-2 my-4">
        <p class="mb-0">"Don't be afraid to try new things. Our ancestors had wisdom, but we can combine that with modern knowledge. Education changed my life, and it can change yours too."</p>
    </blockquote>

    <hr class="my-5">

    <h3 class="mb-4">Start Your Success Story</h3>
    <p>Juan's transformation began with a simple decision to learn. Our courses are designed to help farmers like you achieve similar results.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Explore Courses</a>
</div>
HTML;
    }

    private function getOrganicFarmingContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">Organic vegetable farming is not just better for the environment—it can also be more profitable. This comprehensive guide will help you start your organic farming journey with confidence.</p>

    <img src="https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Organic vegetable garden">

    <h2 class="h3 mb-4">What is Organic Farming?</h2>
    <p>Organic farming is a production system that avoids or largely excludes the use of synthetic fertilizers, pesticides, and genetically modified organisms. Instead, it relies on natural processes and inputs to maintain soil fertility and control pests.</p>

    <h2 class="h3 mb-4 mt-5">Getting Started: Soil Preparation</h2>
    <p>Healthy soil is the foundation of successful organic farming. Here's how to prepare your soil:</p>

    <h3 class="h5 mt-4">1. Test Your Soil</h3>
    <p>Before planting, have your soil tested for pH and nutrient levels. Most vegetables prefer a pH between 6.0 and 7.0.</p>

    <h3 class="h5 mt-4">2. Add Organic Matter</h3>
    <p>Incorporate compost, aged manure, or other organic materials to improve soil structure and fertility. Aim for 2-3 inches of compost mixed into the top 6 inches of soil.</p>

    <div class="row my-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <i class="bx bx-check-circle me-2"></i>Good Organic Amendments
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Compost</li>
                    <li class="list-group-item">Vermicompost</li>
                    <li class="list-group-item">Aged animal manure</li>
                    <li class="list-group-item">Green manure cover crops</li>
                    <li class="list-group-item">Rice hull ash</li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bx bx-x-circle me-2"></i>Avoid These
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Synthetic fertilizers</li>
                    <li class="list-group-item">Fresh manure (uncomposted)</li>
                    <li class="list-group-item">Sewage sludge</li>
                    <li class="list-group-item">Non-organic compost</li>
                    <li class="list-group-item">Treated wood chips</li>
                </ul>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Best Vegetables for Philippine Climate</h2>
    <p>Some vegetables thrive in our tropical climate. Here are the best choices for beginners:</p>

    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <span style="font-size: 2rem;">🍆</span>
                    <h6 class="mt-2">Eggplant</h6>
                    <small class="text-muted">Heat-loving</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <span style="font-size: 2rem;">🍅</span>
                    <h6 class="mt-2">Tomato</h6>
                    <small class="text-muted">Year-round</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <span style="font-size: 2rem;">🌶️</span>
                    <h6 class="mt-2">Chili Pepper</h6>
                    <small class="text-muted">Easy to grow</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <span style="font-size: 2rem;">🥬</span>
                    <h6 class="mt-2">Kangkong</h6>
                    <small class="text-muted">Fast harvest</small>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Natural Pest Control Methods</h2>
    <p>Managing pests without chemicals requires a proactive approach:</p>

    <ol>
        <li class="mb-2"><strong>Companion Planting:</strong> Plant basil near tomatoes to repel aphids</li>
        <li class="mb-2"><strong>Beneficial Insects:</strong> Encourage ladybugs and praying mantis</li>
        <li class="mb-2"><strong>Physical Barriers:</strong> Use nets and row covers</li>
        <li class="mb-2"><strong>Botanical Sprays:</strong> Neem oil, garlic spray, chili pepper solution</li>
        <li class="mb-2"><strong>Crop Rotation:</strong> Break pest cycles by rotating crops</li>
    </ol>

    <div class="alert alert-info border-0 my-4">
        <h5><i class="bx bx-bulb me-2"></i>DIY Pest Spray Recipe</h5>
        <p class="mb-0">Mix 2 tablespoons of liquid soap with 1 liter of water and add 10 crushed garlic cloves. Strain and spray on affected plants in the evening.</p>
    </div>

    <h2 class="h3 mb-4 mt-5">Making Your Own Compost</h2>
    <p>Composting is essential for organic farming. Here's a simple method:</p>

    <ol>
        <li class="mb-2">Layer brown materials (dried leaves, straw) and green materials (kitchen scraps, grass)</li>
        <li class="mb-2">Keep the pile moist but not waterlogged</li>
        <li class="mb-2">Turn the pile every 1-2 weeks</li>
        <li class="mb-2">Compost is ready when it's dark, crumbly, and smells earthy (2-3 months)</li>
    </ol>

    <hr class="my-5">

    <h3 class="mb-4">Continue Your Learning</h3>
    <p>This guide covers the basics, but there's so much more to learn about organic farming. Our comprehensive courses include hands-on training and ongoing support.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Explore Organic Farming Courses</a>
</div>
HTML;
    }

    private function getSoilHealthContent(): string
    {
        return <<<HTML
<div class="container">
    <p class="lead mb-4">The secret to productive farming lies beneath your feet. Understanding and improving soil health is the most important investment you can make in your farm's future.</p>

    <img src="https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Healthy soil in farmer's hands">

    <h2 class="h3 mb-4">Why Soil Health Matters</h2>
    <p>Healthy soil provides:</p>
    <ul>
        <li>Essential nutrients for plant growth</li>
        <li>Good drainage and water retention</li>
        <li>Home for beneficial microorganisms</li>
        <li>Support for root development</li>
        <li>Natural disease suppression</li>
    </ul>

    <h2 class="h3 mb-4 mt-5">Understanding Soil pH</h2>
    <p>Soil pH measures how acidic or alkaline your soil is. Most crops prefer a pH between 6.0 and 7.0.</p>

    <table class="table table-bordered mb-4">
        <thead class="table-light">
            <tr>
                <th>pH Level</th>
                <th>Classification</th>
                <th>Suitable Crops</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Below 5.5</td>
                <td>Very Acidic</td>
                <td>Blueberries, potatoes</td>
            </tr>
            <tr>
                <td>5.5 - 6.5</td>
                <td>Slightly Acidic</td>
                <td>Most vegetables</td>
            </tr>
            <tr>
                <td>6.5 - 7.0</td>
                <td>Neutral</td>
                <td>Most crops thrive</td>
            </tr>
            <tr>
                <td>Above 7.0</td>
                <td>Alkaline</td>
                <td>Brassicas, legumes</td>
            </tr>
        </tbody>
    </table>

    <div class="alert alert-warning border-0 mb-4">
        <h5><i class="bx bx-info-circle me-2"></i>Important</h5>
        <p class="mb-0">To lower pH (make more acidic): Add sulfur or organic matter. To raise pH (make more alkaline): Add agricultural lime.</p>
    </div>

    <h2 class="h3 mb-4 mt-5">The Five Components of Healthy Soil</h2>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body">
                    <h5><span class="badge bg-success me-2">1</span>Minerals</h5>
                    <p class="mb-0">Sand, silt, and clay particles that provide structure and nutrients.</p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h5><span class="badge bg-success me-2">2</span>Organic Matter</h5>
                    <p class="mb-0">Decomposed plant and animal materials that feed soil life and improve structure.</p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h5><span class="badge bg-success me-2">3</span>Water</h5>
                    <p class="mb-0">Essential for nutrient transport and biological activity.</p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h5><span class="badge bg-success me-2">4</span>Air</h5>
                    <p class="mb-0">Roots and soil organisms need oxygen to survive.</p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h5><span class="badge bg-success me-2">5</span>Living Organisms</h5>
                    <p class="mb-0">Bacteria, fungi, earthworms, and other creatures that process nutrients.</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">How to Improve Your Soil</h2>
    <ol>
        <li class="mb-3"><strong>Add organic matter regularly</strong> - Compost, manure, crop residues</li>
        <li class="mb-3"><strong>Practice crop rotation</strong> - Different crops use and add different nutrients</li>
        <li class="mb-3"><strong>Use cover crops</strong> - Prevent erosion and add nutrients</li>
        <li class="mb-3"><strong>Minimize tillage</strong> - Excessive tillage destroys soil structure</li>
        <li class="mb-3"><strong>Keep soil covered</strong> - Mulch protects from sun and rain</li>
    </ol>

    <hr class="my-5">

    <h3 class="mb-4">Get Your Soil Tested</h3>
    <p>Understanding your soil's current condition is the first step to improvement. Our courses include hands-on soil testing workshops.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Learn More</a>
</div>
HTML;
    }

    private function getProductUpdateContent(): string
    {
        return <<<HTML
<div class="container">
    <div class="alert alert-success border-0 mb-4">
        <h5 class="alert-heading"><i class="bx bx-party me-2"></i>New Course Launch!</h5>
        <p class="mb-0">We're excited to announce our most advanced course yet, designed for farmers ready to take their operations to the next level.</p>
    </div>

    <p class="lead mb-4">Introducing our comprehensive Advanced Crop Management Techniques course, combining cutting-edge technology with proven agricultural practices.</p>

    <img src="https://images.unsplash.com/photo-1574943320219-553eb213f72d?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Modern farming technology">

    <h2 class="h3 mb-4">What You'll Learn</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-target-lock text-success me-2"></i>Precision Agriculture</h5>
                    <p class="text-muted">Learn to use GPS, drones, and sensors to optimize your farming operations for maximum efficiency.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-chip text-success me-2"></i>IoT in Farming</h5>
                    <p class="text-muted">Discover how Internet of Things devices can automate irrigation, monitor soil conditions, and more.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-bar-chart-alt-2 text-success me-2"></i>Data-Driven Decisions</h5>
                    <p class="text-muted">Use data analytics to make informed decisions about planting, fertilization, and harvesting.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5><i class="bx bx-cloud text-success me-2"></i>Climate Adaptation</h5>
                    <p class="text-muted">Strategies to protect your crops and adapt your farming practices to changing weather patterns.</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Course Details</h2>
    <table class="table table-bordered">
        <tbody>
            <tr>
                <td><strong>Duration</strong></td>
                <td>8 weeks (self-paced)</td>
            </tr>
            <tr>
                <td><strong>Format</strong></td>
                <td>Online video lessons + Live Q&A sessions</td>
            </tr>
            <tr>
                <td><strong>Includes</strong></td>
                <td>Downloadable resources, Certificate, Community access</td>
            </tr>
            <tr>
                <td><strong>Prerequisites</strong></td>
                <td>Basic farming experience recommended</td>
            </tr>
            <tr>
                <td><strong>Price</strong></td>
                <td>₱2,999 (Early bird: ₱1,999)</td>
            </tr>
        </tbody>
    </table>

    <div class="alert alert-info border-0 my-4">
        <h5><i class="bx bx-gift me-2"></i>Early Bird Special</h5>
        <p class="mb-0">Register before March 31, 2026 to get ₱1,000 off the regular price!</p>
    </div>

    <hr class="my-5">

    <h3 class="mb-4">Ready to Modernize Your Farm?</h3>
    <p>Join hundreds of farmers who are already using these techniques to increase their productivity and income.</p>
    <a href="/courses" class="btn btn-success btn-lg mt-3">Enroll Now</a>
</div>
HTML;
    }

    private function getEventContent(): string
    {
        return <<<HTML
<div class="container">
    <div class="text-center mb-5">
        <span class="badge bg-success fs-6 mb-3">Save the Date</span>
        <h2 class="display-5 mb-3">Farmers Summit 2026</h2>
        <p class="lead">The Philippines' Premier Agricultural Gathering</p>
        <p class="h4 text-success">April 15-17, 2026 | SMX Convention Center, Manila</p>
    </div>

    <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800" class="img-fluid rounded shadow-sm mb-5" alt="Conference venue">

    <h2 class="h3 mb-4">Event Highlights</h2>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 d-inline-block mb-3">
                        <i class="bx bx-microphone text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h5>30+ Expert Speakers</h5>
                    <p class="text-muted mb-0">Learn from agricultural scientists, successful farmers, and industry leaders</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 d-inline-block mb-3">
                        <i class="bx bx-store text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h5>50+ Exhibitors</h5>
                    <p class="text-muted mb-0">Discover the latest farming equipment, seeds, and technologies</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 d-inline-block mb-3">
                        <i class="bx bx-group text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h5>2,000+ Attendees</h5>
                    <p class="text-muted mb-0">Network with farmers and agri-business professionals from across the country</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Event Schedule</h2>

    <div class="accordion mb-4" id="scheduleAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#day1">
                    <strong>Day 1 - April 15:</strong>&nbsp;Innovation & Technology
                </button>
            </h2>
            <div id="day1" class="accordion-collapse collapse show" data-bs-parent="#scheduleAccordion">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">🕘 9:00 AM - Opening Ceremony</li>
                        <li class="mb-2">🕙 10:00 AM - Keynote: The Future of Philippine Agriculture</li>
                        <li class="mb-2">🕐 1:00 PM - Workshop: Introduction to Precision Farming</li>
                        <li class="mb-2">🕓 4:00 PM - Panel: Technology Adoption in Small Farms</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#day2">
                    <strong>Day 2 - April 16:</strong>&nbsp;Sustainable Practices
                </button>
            </h2>
            <div id="day2" class="accordion-collapse collapse" data-bs-parent="#scheduleAccordion">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">🕘 9:00 AM - Organic Farming Certification Process</li>
                        <li class="mb-2">🕙 10:30 AM - Workshop: Making Organic Fertilizers</li>
                        <li class="mb-2">🕐 1:00 PM - Climate-Smart Agriculture</li>
                        <li class="mb-2">🕓 4:00 PM - Success Stories Panel</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#day3">
                    <strong>Day 3 - April 17:</strong>&nbsp;Business & Markets
                </button>
            </h2>
            <div id="day3" class="accordion-collapse collapse" data-bs-parent="#scheduleAccordion">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">🕘 9:00 AM - Farm Business Management</li>
                        <li class="mb-2">🕙 10:30 AM - Accessing Credit and Financing</li>
                        <li class="mb-2">🕐 1:00 PM - Direct Marketing Strategies</li>
                        <li class="mb-2">🕓 4:00 PM - Closing Ceremony & Awards</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h3 mb-4 mt-5">Registration Packages</h2>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white text-center">
                    <h5 class="mb-0">Early Bird</h5>
                </div>
                <div class="card-body text-center">
                    <h3 class="mb-3">₱1,500</h3>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2">✓ 3-Day Access</li>
                        <li class="mb-2">✓ All Sessions</li>
                        <li class="mb-2">✓ Lunch & Snacks</li>
                        <li class="mb-2">✓ Event Kit</li>
                    </ul>
                    <small class="text-muted">Until March 15, 2026</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-light text-center">
                    <h5 class="mb-0">Regular</h5>
                </div>
                <div class="card-body text-center">
                    <h3 class="mb-3">₱2,500</h3>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2">✓ 3-Day Access</li>
                        <li class="mb-2">✓ All Sessions</li>
                        <li class="mb-2">✓ Lunch & Snacks</li>
                        <li class="mb-2">✓ Event Kit</li>
                    </ul>
                    <small class="text-muted">After March 15, 2026</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark text-center">
                    <h5 class="mb-0">VIP</h5>
                </div>
                <div class="card-body text-center">
                    <h3 class="mb-3">₱5,000</h3>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2">✓ Everything in Regular</li>
                        <li class="mb-2">✓ VIP Seating</li>
                        <li class="mb-2">✓ Networking Dinner</li>
                        <li class="mb-2">✓ 1-on-1 Consultation</li>
                    </ul>
                    <small class="text-muted">Limited spots available</small>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="mb-4">Secure Your Spot Today</h3>
        <p>Don't miss the biggest agricultural event of the year!</p>
        <a href="/register" class="btn btn-success btn-lg mt-3">Register Now</a>
    </div>
</div>
HTML;
    }
}
