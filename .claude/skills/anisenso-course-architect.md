# Ani-Senso Course Project Architecture

This skill file documents the **Ani-Senso Course** frontend application - the customer-facing member portal for Ani-Senso Academy. This project works in tandem with DS AXIS (btc-check) which serves as the admin backend.

---

## 1. Project Identity

| Property | Value |
|----------|-------|
| **Project Name** | Ani-Senso Course Client Application |
| **Directory** | `C:\xampp\htdocs\anisenso-course` |
| **Purpose** | Customer-facing LMS / Member Portal |
| **Parent System** | DS AXIS (btc-check) - Admin Backend |
| **Framework** | Laravel 12 on PHP 8.2+ |
| **UI Framework** | Tailwind CSS v4 + Alpine.js v3 (CDN) |
| **Build Tool** | Vite |
| **Port** | http://localhost:8001 |
| **Timezone** | Asia/Manila |

### Relationship with DS AXIS

```
┌─────────────────────────────────────────┐
│  DS AXIS (btc-check)                    │
│  http://localhost:8000                  │
│  ADMIN BACKEND                          │
│  ─────────────────────────────          │
│  • Course Management                    │
│  • Student Enrollment                   │
│  • Content Creation (TinyMCE)           │
│  • Certificate Designer                 │
│  • Website Pages (CMS)                  │
│  • Access Tags Management               │
└─────────────────────────────────────────┘
           │
           │ SHARED DATABASE
           │ onmartph_axis
           ▼
┌─────────────────────────────────────────┐
│  Ani-Senso Course                       │
│  http://localhost:8001                  │
│  CUSTOMER FRONTEND                      │
│  ─────────────────────────────          │
│  • Student Login (Email/Phone)          │
│  • Browse Enrolled Courses              │
│  • Watch/Read Content                   │
│  • Track Progress                       │
│  • Take Quizzes                         │
│  • Download Certificates                │
│  • Leave Reviews                        │
└─────────────────────────────────────────┘
```

---

## 2. Database Architecture

### Shared Database Connection
Both applications connect to the **same MySQL database: `onmartph_axis`**

```
Host: 15.235.219.232 (Production) / localhost (Development)
Database: onmartph_axis
Port: 3306
```

### Tables Used by Ani-Senso Course

| Table | Purpose | Soft Delete | Notes |
|-------|---------|-------------|-------|
| `clients_access_login` | Student accounts | `deleteStatus = 1/0` | Auth source |
| `as_courses` | Course listings | `deleteStatus = 'true'/'false'` | **STRING** |
| `as_courses_chapters` | Chapters | `deleteStatus = 1/0` | Integer |
| `as_courses_topics` | Topics | `deleteStatus = 1/0` | Integer |
| `as_topic_contents` | Content items | `deleteStatus = 1/0` | Integer |
| `as_topics_resources` | Downloads | N/A | Attached files |
| `as_course_enrollments` | Enrollments | `deleteStatus = 1/0` | Student ↔ Course |
| `as_topic_progress` | Progress | N/A | Completion tracking |
| `as_questionnaires` | Quizzes | `deleteStatus = 1/0` | Per topic |
| `as_questions` | Questions | `deleteStatus = 1/0` | Quiz questions |
| `as_course_reviews` | Reviews | `deleteStatus = 1/0` | Student feedback |
| `as_content_comments` | Comments | `deleteStatus = 1/0` | Discussion |
| `as_certificate_templates` | Certificates | N/A | Templates from admin |
| `axis_tags` | Access control | `deleteStatus = 1/0` | Permissions |
| `as_website_pages` | CMS Pages | `deleteStatus = active/deleted` | Homepage, About, etc. |

### CRITICAL: Soft Delete Inconsistency

**WARNING:** The `as_courses` table uses **STRING** values for deleteStatus:
```php
// as_courses - STRING pattern
->where('deleteStatus', 'true')    // Active records
->where('deleteStatus', 'false')   // Deleted records

// All other as_* tables - INTEGER pattern
->where('deleteStatus', 1)         // Active records
->where('deleteStatus', 0)         // Deleted records
```

---

## 3. Authentication System

### Guard Configuration
```php
// config/auth.php
'guards' => [
    'client' => [
        'driver' => 'session',
        'provider' => 'clients',  // Uses ClientAccessLogin model
    ],
]

'providers' => [
    'clients' => [
        'driver' => 'eloquent',
        'model' => App\Models\ClientAccessLogin::class,
    ],
]
```

### Login Methods
- **Email Login**: `clientEmailAddress`
- **Phone Login**: `clientPhoneNumber`

### Authentication Flow
```
1. User visits /login
2. Enters email OR phone + password
3. ClientAccessLogin::findForLogin() checks both fields
4. Validates: deleteStatus = 1 AND isActive = 1
5. Hash::check() verifies password
6. Auth::guard('client')->login() creates session
7. Redirects to /dashboard
```

### Template Auth Checks
```blade
@auth('client')
    <!-- Authenticated content -->
    {{ auth('client')->user()->clientFirstName }}
@endauth

@guest('client')
    <!-- Guest content -->
@endguest
```

---

## 4. Current Routes

### Public Routes (No Auth)
```php
GET  /                    → home.blade.php
GET  /about               → about.blade.php
GET  /courses             → courses/index.blade.php (stub)
GET  /login               → auth/login.blade.php
POST /login               → LoginController@login
```

### Protected Routes (auth:client)
```php
POST /logout              → LoginController@logout
GET  /dashboard           → dashboard.blade.php (stub)
```

### Routes To Be Implemented
```php
// Course Browsing
GET  /courses                         → List enrolled courses
GET  /courses/{slug}                  → Course detail page
GET  /courses/{slug}/chapters/{id}    → Chapter view
GET  /courses/{slug}/topics/{id}      → Topic content view

// Learning Progress
POST /progress/topic/{id}             → Mark topic complete
GET  /progress/course/{id}            → Course progress summary

// Quizzes
GET  /quiz/{id}                       → Take quiz
POST /quiz/{id}/submit                → Submit answers

// Reviews & Comments
POST /reviews                         → Submit review
POST /comments                        → Add comment

// Certificates
GET  /certificates                    → List earned certificates
GET  /certificates/{id}/download      → Download PDF

// Profile
GET  /profile                         → View/edit profile
POST /profile                         → Update profile
```

---

## 5. Project Structure

```
anisenso-course/
├── app/
│   ├── Http/Controllers/
│   │   ├── Controller.php              # Base (empty)
│   │   └── Auth/
│   │       └── LoginController.php     # Auth logic
│   └── Models/
│       ├── ClientAccessLogin.php       # Primary auth model
│       └── User.php                    # Legacy (unused)
│
├── resources/views/
│   ├── layouts/
│   │   ├── app.blade.php              # Master layout
│   │   └── partials/
│   │       ├── header.blade.php       # Navigation
│   │       └── footer.blade.php       # Footer
│   ├── auth/
│   │   └── login.blade.php            # Login form
│   ├── courses/
│   │   └── index.blade.php            # Courses list (stub)
│   ├── home.blade.php                 # Homepage
│   ├── about.blade.php                # About page
│   └── dashboard.blade.php            # Member dashboard (stub)
│
├── routes/
│   └── web.php                        # All routes
│
├── config/
│   └── auth.php                       # Auth guards
│
└── CLAUDE.md                          # Project guide
```

---

## 6. Frontend Stack

### CSS: Tailwind v4 (CDN)
```html
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                'brand-dark': '#1a1a1a',
                'brand-yellow': '#f5c518',
                'brand-yellow-hover': '#e6b800',
                'brand-green': '#4a7c2a',
                'brand-green-dark': '#2d5016',
                'brand-green-light': '#6b9f3d',
                'brand-olive': '#5a6f2a',
            }
        }
    }
}
</script>
```

### JavaScript: Alpine.js v3 (CDN)
```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
```

### Fonts
- **Headings**: Instrument Sans (Google Fonts)
- **Body**: Nunito Sans (Google Fonts)

### Custom Animations
```css
.animate-fadeInUp { ... }
.animate-fadeInLeft { ... }
.animate-slideInRight { ... }
.delay-100, .delay-200, .delay-300, .delay-400 { ... }
```

---

## 7. Models to Create

The following models need to be created to enable full functionality:

```php
// app/Models/Course.php
class Course extends Model {
    protected $table = 'as_courses';
    // Note: deleteStatus is STRING 'true'/'false'

    public function scopeActive($query) {
        return $query->where('deleteStatus', 'true');  // STRING!
    }

    public function chapters() {
        return $this->hasMany(CourseChapter::class, 'asCoursesId');
    }
}

// app/Models/CourseChapter.php
class CourseChapter extends Model {
    protected $table = 'as_courses_chapters';

    public function scopeActive($query) {
        return $query->where('deleteStatus', 1);  // INTEGER
    }

    public function topics() {
        return $this->hasMany(CourseTopic::class, 'asCoursesChaptersId');
    }
}

// app/Models/CourseTopic.php
class CourseTopic extends Model {
    protected $table = 'as_courses_topics';

    public function contents() {
        return $this->hasMany(TopicContent::class, 'asCoursesTopicsId');
    }
}

// app/Models/CourseEnrollment.php
class CourseEnrollment extends Model {
    protected $table = 'as_course_enrollments';

    public function course() {
        return $this->belongsTo(Course::class, 'asCoursesId');
    }

    public function student() {
        return $this->belongsTo(ClientAccessLogin::class, 'clientsAccessLoginId');
    }
}

// app/Models/TopicProgress.php
class TopicProgress extends Model {
    protected $table = 'as_topic_progress';
}
```

---

## 8. Controllers to Create

```php
// app/Http/Controllers/CourseController.php
class CourseController extends Controller {
    public function index()           // List enrolled courses
    public function show($slug)       // Course detail
    public function chapter($id)      // View chapter
    public function topic($id)        // View topic content
}

// app/Http/Controllers/ProgressController.php
class ProgressController extends Controller {
    public function markComplete($topicId)  // Mark topic done
    public function summary($courseId)       // Progress summary
}

// app/Http/Controllers/QuizController.php
class QuizController extends Controller {
    public function show($id)         // Display quiz
    public function submit($id)       // Submit answers
}

// app/Http/Controllers/CertificateController.php
class CertificateController extends Controller {
    public function index()           // List certificates
    public function download($id)     // Download PDF
}
```

---

## 9. Integration with DS AXIS CMS

### Website Pages (as_website_pages)

The admin system (DS AXIS) manages website pages that should be rendered by the frontend:

```php
// In anisenso-course, create a model:
class WebsitePage extends Model {
    protected $table = 'as_website_pages';

    public function scopeActive($query) {
        return $query->where('deleteStatus', 'active');
    }

    public function scopePublished($query) {
        return $query->where('pageStatus', 'published');
    }
}

// Controller to render CMS pages:
class PageController extends Controller {
    public function show($slug) {
        $page = WebsitePage::active()
            ->published()
            ->where('pageSlug', $slug)
            ->firstOrFail();

        return view('pages.show', compact('page'));
    }
}

// Route:
Route::get('/page/{slug}', [PageController::class, 'show']);
```

### Homepage Content

The Homepage content managed in DS AXIS (as_website_pages with pageSlug = 'home') should be fetched and rendered:

```php
// In home controller or view:
$homePage = WebsitePage::active()
    ->published()
    ->where('pageSlug', 'home')
    ->first();
```

---

## 10. Development Commands

```bash
# Install dependencies
composer install
npm install

# Run development server
php artisan serve --port=8001

# Or use Composer script
composer run dev

# Build assets
npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 11. Data Flow: Admin → Frontend

### Course Content Flow
```
1. Admin creates course in DS AXIS
   └─→ Saved to as_courses (deleteStatus = 'true')

2. Admin adds chapters
   └─→ Saved to as_courses_chapters

3. Admin adds topics with content
   └─→ Saved to as_courses_topics
   └─→ Content saved to as_topic_contents

4. Admin enrolls student
   └─→ Saved to as_course_enrollments

5. Student logs into anisenso-course
   └─→ Queries as_course_enrollments for their courses
   └─→ Displays enrolled courses on dashboard

6. Student views course content
   └─→ Queries chapters/topics
   └─→ Marks progress in as_topic_progress
```

### Website CMS Flow
```
1. Admin creates/edits page in DS AXIS
   └─→ Saved to as_website_pages

2. Admin publishes page (pageStatus = 'published')

3. Frontend renders page content
   └─→ Queries as_website_pages by slug
   └─→ Renders pageContent (HTML from TinyMCE)
```

---

## 12. Security Considerations

### Authentication
- Session-based with `auth:client` guard
- Password hashing via Laravel's `Hash::check()`
- Session regeneration on login/logout
- CSRF protection on all forms

### Access Control
- Students only see enrolled courses
- Course visibility controlled by `isActive` flag
- Access tags (`axis_tags`) for granular permissions
- Enrollment expiry dates honored

### Data Validation
- All user input validated in controllers
- Eloquent ORM prevents SQL injection
- XSS prevention in Blade templates (auto-escaping)

---

## 13. Current Implementation Status

### Implemented ✓
- [x] Authentication (login/logout)
- [x] Session management
- [x] Basic layout (header/footer)
- [x] Homepage
- [x] About page
- [x] Login page

### Stub/Placeholder (Needs Work)
- [ ] Dashboard (shows placeholder data)
- [ ] Courses list page
- [ ] Course detail pages
- [ ] Content viewing
- [ ] Progress tracking
- [ ] Quizzes
- [ ] Reviews/Comments
- [ ] Certificates
- [ ] Profile page

### Not Started
- [ ] API endpoints
- [ ] Search functionality
- [ ] Email notifications
- [ ] Social login
- [ ] Mobile responsiveness testing

---

## 14. Reference to DS AXIS Skills

When working on anisenso-course, consult these DS AXIS skill files:

| Skill | Path | Purpose |
|-------|------|---------|
| database-architect | `.claude/skills/database-architect.md` | Full schema reference |
| codebase-architect | `.claude/skills/codebase-architect.md` | Admin patterns |
| logic-architect | `.claude/skills/logic-architect.md` | Business logic flows |

---

## 15. Quick Reference

### Auth Guard
```php
auth('client')                    // Get guard instance
auth('client')->user()            // Get logged-in user
auth('client')->check()           // Is authenticated?
auth('client')->id()              // Get user ID
```

### User Model Fields
```php
$user->clientFirstName
$user->clientLastName
$user->clientEmailAddress
$user->clientPhoneNumber
$user->full_name                  // Accessor
$user->initials                   // Accessor
```

### Route Protection
```php
Route::middleware('auth:client')->group(function () {
    // Protected routes here
});
```

### Blade Auth
```blade
@auth('client')
    Welcome, {{ auth('client')->user()->clientFirstName }}!
@endauth
```

---

*This skill file should be updated as the anisenso-course project evolves. Last updated: March 2026*
