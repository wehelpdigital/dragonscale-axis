<?php

namespace App\Models;

use Illuminate\Support\Str;

class AsBlog extends BaseModel
{
    protected $table = 'as_blogs';

    protected $fillable = [
        'usersId',
        'blogTitle',
        'blogSlug',
        'blogCategory',
        'blogCategoryColor',
        'blogFeaturedImage',
        'blogExcerpt',
        'blogContent',
        'builderContent',
        'useBuilder',
        // SEO Basic
        'metaTitle',
        'metaDescription',
        'metaKeywords',
        // Open Graph
        'ogTitle',
        'ogDescription',
        'ogImage',
        // Twitter Card
        'twitterTitle',
        'twitterDescription',
        'twitterImage',
        // Additional SEO
        'canonicalUrl',
        'focusKeyword',
        'seoScore',
        'seoAnalysis',
        'schemaType',
        // Publishing
        'blogStatus',
        'publishedAt',
        'isFeatured',
        'blogOrder',
        // Stats
        'viewCount',
        'readingTime',
        // Author
        'authorName',
        'authorImage',
        'deleteStatus',
    ];

    protected $casts = [
        'publishedAt' => 'datetime',
        'isFeatured' => 'boolean',
        'useBuilder' => 'boolean',
        'viewCount' => 'integer',
        'readingTime' => 'integer',
        'blogOrder' => 'integer',
        'seoScore' => 'integer',
        'seoAnalysis' => 'array',
        'builderContent' => 'array',
    ];

    /**
     * Available categories with their colors.
     */
    public static function getCategories()
    {
        return [
            'News' => 'blue',
            'Farming Tips' => 'brand-green',
            'Success Stories' => 'brand-yellow',
            'Product Updates' => 'purple',
            'Events' => 'orange',
            'Guides' => 'teal',
            'Announcements' => 'red',
        ];
    }

    /**
     * Available status options.
     */
    public static function getStatuses()
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ];
    }

    /**
     * Available schema types for structured data.
     */
    public static function getSchemaTypes()
    {
        return [
            'Article' => 'Article',
            'NewsArticle' => 'News Article',
            'BlogPosting' => 'Blog Post',
            'HowTo' => 'How-To Guide',
            'FAQPage' => 'FAQ Page',
        ];
    }

    /**
     * Scope a query to only include active records.
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 'active');
    }

    /**
     * Scope a query to only include published records.
     */
    public function scopePublished($query)
    {
        return $query->where('blogStatus', 'published')
                     ->whereNotNull('publishedAt')
                     ->where('publishedAt', '<=', now());
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope a query to only include featured posts.
     */
    public function scopeFeatured($query)
    {
        return $query->where('isFeatured', true);
    }

    /**
     * Get the user that owns this blog post.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Generate a unique slug from the title.
     */
    public static function generateSlug($title, $excludeId = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        $query = self::where('blogSlug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;

            $query = self::where('blogSlug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClass()
    {
        return match($this->blogStatus) {
            'published' => 'bg-success',
            'draft' => 'bg-warning text-dark',
            'archived' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    /**
     * Get the category badge class.
     */
    public function getCategoryBadgeClass()
    {
        $colorMap = [
            'blue' => 'bg-primary',
            'brand-green' => 'bg-success',
            'brand-yellow' => 'bg-warning text-dark',
            'purple' => 'bg-purple text-white',
            'orange' => 'bg-orange text-white',
            'teal' => 'bg-info text-white',
            'red' => 'bg-danger',
        ];

        return $colorMap[$this->blogCategoryColor] ?? 'bg-secondary';
    }

    /**
     * Get formatted published date.
     */
    public function getFormattedPublishedDate()
    {
        if ($this->publishedAt) {
            return $this->publishedAt->format('M j, Y');
        }
        return 'Not published';
    }

    /**
     * Increment view count.
     */
    public function incrementViews()
    {
        $this->increment('viewCount');
    }

    /**
     * Calculate reading time based on content.
     */
    public function calculateReadingTime()
    {
        $content = $this->useBuilder ? $this->getBuilderHtml() : $this->blogContent;
        $wordCount = str_word_count(strip_tags($content));
        $readingTime = max(1, ceil($wordCount / 200)); // Average reading speed: 200 words/min
        return $readingTime;
    }

    /**
     * Get HTML content from builder data.
     */
    public function getBuilderHtml()
    {
        if (!$this->useBuilder || empty($this->builderContent)) {
            return $this->blogContent;
        }

        return $this->builderContent['html'] ?? $this->blogContent;
    }

    /**
     * Get the final content for display.
     */
    public function getDisplayContent()
    {
        return $this->useBuilder ? $this->getBuilderHtml() : $this->blogContent;
    }

    /**
     * Get SEO score badge class.
     */
    public function getSeoScoreBadgeClass()
    {
        if ($this->seoScore >= 80) {
            return 'bg-success';
        } elseif ($this->seoScore >= 50) {
            return 'bg-warning text-dark';
        } else {
            return 'bg-danger';
        }
    }

    /**
     * Get effective meta title (fallback to blog title).
     */
    public function getEffectiveMetaTitle()
    {
        return $this->metaTitle ?: $this->blogTitle;
    }

    /**
     * Get effective meta description (fallback to excerpt).
     */
    public function getEffectiveMetaDescription()
    {
        return $this->metaDescription ?: Str::limit(strip_tags($this->blogExcerpt), 160);
    }

    /**
     * Get effective OG image (fallback to featured image).
     */
    public function getEffectiveOgImage()
    {
        return $this->ogImage ?: $this->blogFeaturedImage;
    }

    /**
     * Analyze SEO and return score with suggestions.
     */
    public function analyzeSeo()
    {
        $score = 0;
        $analysis = [];
        $content = $this->getDisplayContent();
        $contentText = strip_tags($content);

        // Check title length (ideal: 50-60 chars)
        $titleLength = strlen($this->blogTitle);
        if ($titleLength >= 50 && $titleLength <= 60) {
            $score += 15;
            $analysis['title'] = ['status' => 'good', 'message' => 'Title length is optimal'];
        } elseif ($titleLength >= 30 && $titleLength <= 70) {
            $score += 10;
            $analysis['title'] = ['status' => 'ok', 'message' => 'Title length is acceptable'];
        } else {
            $analysis['title'] = ['status' => 'bad', 'message' => 'Title should be 50-60 characters'];
        }

        // Check meta description (ideal: 150-160 chars)
        $metaDesc = $this->metaDescription ?: $this->blogExcerpt;
        $metaLength = strlen($metaDesc);
        if ($metaLength >= 150 && $metaLength <= 160) {
            $score += 15;
            $analysis['metaDescription'] = ['status' => 'good', 'message' => 'Meta description length is optimal'];
        } elseif ($metaLength >= 120 && $metaLength <= 180) {
            $score += 10;
            $analysis['metaDescription'] = ['status' => 'ok', 'message' => 'Meta description length is acceptable'];
        } else {
            $analysis['metaDescription'] = ['status' => 'bad', 'message' => 'Meta description should be 150-160 characters'];
        }

        // Check focus keyword
        if (!empty($this->focusKeyword)) {
            $score += 10;
            $keyword = strtolower($this->focusKeyword);

            // Check if keyword is in title
            if (stripos($this->blogTitle, $keyword) !== false) {
                $score += 10;
                $analysis['keywordInTitle'] = ['status' => 'good', 'message' => 'Focus keyword found in title'];
            } else {
                $analysis['keywordInTitle'] = ['status' => 'bad', 'message' => 'Add focus keyword to title'];
            }

            // Check if keyword is in content
            $keywordCount = substr_count(strtolower($contentText), $keyword);
            if ($keywordCount >= 3) {
                $score += 10;
                $analysis['keywordInContent'] = ['status' => 'good', 'message' => "Focus keyword appears {$keywordCount} times"];
            } elseif ($keywordCount >= 1) {
                $score += 5;
                $analysis['keywordInContent'] = ['status' => 'ok', 'message' => 'Consider using focus keyword more often'];
            } else {
                $analysis['keywordInContent'] = ['status' => 'bad', 'message' => 'Focus keyword not found in content'];
            }
        } else {
            $analysis['focusKeyword'] = ['status' => 'bad', 'message' => 'Set a focus keyword'];
        }

        // Check content length (ideal: 1000+ words)
        $wordCount = str_word_count($contentText);
        if ($wordCount >= 1000) {
            $score += 15;
            $analysis['contentLength'] = ['status' => 'good', 'message' => "Content has {$wordCount} words"];
        } elseif ($wordCount >= 500) {
            $score += 10;
            $analysis['contentLength'] = ['status' => 'ok', 'message' => "Content has {$wordCount} words. Aim for 1000+"];
        } else {
            $score += 5;
            $analysis['contentLength'] = ['status' => 'bad', 'message' => "Only {$wordCount} words. Add more content"];
        }

        // Check featured image
        if (!empty($this->blogFeaturedImage)) {
            $score += 10;
            $analysis['featuredImage'] = ['status' => 'good', 'message' => 'Featured image is set'];
        } else {
            $analysis['featuredImage'] = ['status' => 'bad', 'message' => 'Add a featured image'];
        }

        // Check internal/external links
        preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $content, $links);
        $linkCount = count($links[0]);
        if ($linkCount >= 2) {
            $score += 5;
            $analysis['links'] = ['status' => 'good', 'message' => "{$linkCount} links found"];
        } elseif ($linkCount >= 1) {
            $score += 3;
            $analysis['links'] = ['status' => 'ok', 'message' => 'Consider adding more links'];
        } else {
            $analysis['links'] = ['status' => 'bad', 'message' => 'Add internal or external links'];
        }

        // Check headings
        preg_match_all('/<h[2-6][^>]*>/i', $content, $headings);
        $headingCount = count($headings[0]);
        if ($headingCount >= 2) {
            $score += 10;
            $analysis['headings'] = ['status' => 'good', 'message' => "{$headingCount} subheadings found"];
        } elseif ($headingCount >= 1) {
            $score += 5;
            $analysis['headings'] = ['status' => 'ok', 'message' => 'Consider adding more subheadings'];
        } else {
            $analysis['headings'] = ['status' => 'bad', 'message' => 'Add subheadings (H2, H3) to structure content'];
        }

        return [
            'score' => min(100, $score),
            'analysis' => $analysis,
        ];
    }
}
