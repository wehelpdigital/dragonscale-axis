# Database Architect - DS AXIS Database Map

> **Purpose**: This skill provides a comprehensive architectural map of the DS AXIS (Dragon Scale Axis) MySQL database to help agents understand the complete data structure, relationships, conventions, and integration with the Laravel application layer defined in `codebase-architect.md`.
>
> **System Context**: DS AXIS is the centralized admin dashboard for Dragon Scale Web Company, managing multiple sub-business ventures. This database serves as the unified data store for all ventures.

---

## 1. Database Overview

| Property | Value |
|----------|-------|
| **System** | DS AXIS (Dragon Scale Axis) |
| **Database Name** | `onmartph_axis` |
| **Host** | `15.235.219.232` (Remote Production) |
| **Port** | `3306` |
| **Engine** | MySQL/MariaDB |
| **Charset** | UTF-8 |
| **Total Tables** | 44 |
| **Architecture** | Multi-Venture Unified Database |

### Database Design Philosophy

As DS AXIS is the **centralized admin system** for Dragon Scale Web Company, the database:

1. **Unified Storage**: All sub-ventures share one database with prefixed tables
2. **Multi-Tenant Ready**: Uses `usersId` and store identifiers for data isolation
3. **Extensible Structure**: Designed to accommodate new ventures/modules
4. **Admin-Focused**: Optimized for administrative operations, not customer-facing load

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    DS AXIS DATABASE ARCHITECTURE                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  onmartph_axis Database                                                │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                                                                   │   │
│  │  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐        │   │
│  │  │  Core Tables  │  │ Crypto Venture│  │E-com Venture  │        │   │
│  │  │  • users      │  │ • task        │  │ • ecom_*      │        │   │
│  │  │  • axis_tags  │  │ • historical_*│  │               │        │   │
│  │  │  • clients_*  │  │ • income_*    │  │               │        │   │
│  │  └───────────────┘  └───────────────┘  └───────────────┘        │   │
│  │                                                                   │   │
│  │  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐        │   │
│  │  │Ani-Senso Vent.│  │ Notification  │  │Future Ventures│        │   │
│  │  │ • as_*        │  │ • notification│  │ • [expandable]│        │   │
│  │  │               │  │ • threshold_* │  │               │        │   │
│  │  └───────────────┘  └───────────────┘  └───────────────┘        │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Data Volume (Current Records)

| Table | Records | Notes |
|-------|---------|-------|
| `users` | 6 | Admin users |
| `task` | 1 | Active crypto trading task |
| `historical_price` | 31,445 | High-volume price snapshots |
| `historical_ladder` | 31,440 | High-volume ladder data |
| `difference_history` | 31,443 | High-volume difference tracking |
| `notification_history` | 41 | Alert notifications |
| `ecom_products` | 8 | Products catalog |
| `ecom_products_variants` | 9 | Product variations |
| `ecom_orders` | 0 | Orders (new module) |
| `as_courses` | 7 | Educational courses |
| `clients_all_database` | 6 | Client records |
| `axis_tags` | 6 | Access trigger tags |

---

## 2. Module-Table Mapping

### 2.1 User Management Module

```
users
├── id (PK, bigint unsigned, auto_increment)
├── name (varchar 191)
├── email (varchar 191)
├── email_verified_at (timestamp, nullable)
├── password (varchar 191)
├── dob (date, nullable)
├── avatar (text, nullable)
├── remember_token (varchar 100, nullable)
├── delete_status (text) ← SOFT DELETE: 'active' or 'deleted'
├── created_at (timestamp)
└── updated_at (timestamp)

password_resets
├── email (varchar, indexed)
├── token (varchar)
└── created_at (timestamp)

personal_access_tokens (Laravel Sanctum)
├── id (PK, bigint unsigned)
├── tokenable_type (varchar)
├── tokenable_id (bigint, indexed)
├── name (varchar)
├── token (varchar 64, unique)
├── abilities (text, nullable)
├── last_used_at (timestamp, nullable)
├── expires_at (timestamp, nullable)
├── created_at (timestamp)
└── updated_at (timestamp)
```

**Model Mapping**: `App\Models\User` → `users` table

---

### 2.2 Crypto Tracking Module

#### Core Task Table
```
task
├── id (PK, int, auto_increment)
├── usersId (int) ← FK to users.id
├── taskCoin (text) ← e.g., "BTC", "ETH"
├── taskType (text) ← 'to sell' or 'to buy'
├── status (text) ← 'current', 'completed', etc.
│
│   [To Sell Tracking]
├── currentCoinValue (decimal 18,8) ← Current crypto value
├── startingPhpValue (decimal 10,2) ← Starting PHP value
├── minThreshold (int) ← Minimum threshold
├── intervalThreshold (int) ← Interval for notifications
│
│   [To Buy Tracking]
├── toBuyCurrentCashValue (decimal 10,2)
├── toBuyStartingCoinValue (decimal 18,8)
├── toBuyMinThreshold (int)
├── toBuyIntervalThreshold (int)
│
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**: `App\Models\Task` → `task` table
**Controller**: `CryptoSetController`, `CryptoCheckerController`

#### Threshold Tracking
```
threshold_task
├── id (PK, int)
├── usersId (int) ← FK to users.id
├── taskId (int) ← FK to task.id
├── thresholdQuotient (int)
├── date (date)
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**: `App\Models\ThresholdTask` → `threshold_task` table

#### Income Logging
```
income_logger
├── id (PK, int)
├── usersId (int) ← FK to users.id
├── taskCoin (text) ← Coin type
├── taskType (text) ← 'to buy' or 'to sell'
├── originalPhpValue (decimal 10,0)
├── newPhpValue (decimal 10,0)
├── difference (decimal 10,0) ← Calculated field
├── transactionDateTime (datetime)
├── delete_status (text) ← SOFT DELETE: 'active'/'deleted'
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**: `App\Models\IncomeLogger` → `income_logger` table
**Controller**: `CryptoIncomeLoggerController`

#### Historical Data (High-Volume Tables)
```
historical_price (~31K records)
├── id (PK, int)
├── coinType (text) ← e.g., "BTC", "ETH"
├── valueInPhp (decimal 10,2)
├── created_at (datetime)
└── updated_at (datetime)

historical_ladder (~31K records)
├── id (PK, int)
├── data (text) ← JSON-encoded ladder data
├── created_at (datetime)
└── updated_at (datetime)

difference_history (~31K records)
├── id (PK, int)
├── usersId (int) ← FK to users.id
├── taskType (text)
├── toSellCurrentCoinValue (decimal 18,8)
├── toSellStartingPhpValue (decimal 10,2)
├── toBuyCurrentCashValue (decimal 10,2)
├── toBuyStartingCoinValue (decimal 18,8)
├── cashDifference (decimal 10,2)
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**:
- `App\Models\HistoricalPrice` → `historical_price`
- `App\Models\HistoricalLadder` → `historical_ladder`
- `App\Models\DifferenceHistory` → `difference_history`

#### Notification System
```
notification_history
├── id (PK, int)
├── taskId (int) ← FK to task.id
├── usersId (int) ← FK to users.id
├── finalAmount (int)
├── difference (int)
├── created_at (datetime)
└── updated_at (datetime)

notification_email
├── id (PK, int)
├── usersId (int) ← FK to users.id
├── email (text) ← SMTP sender email
├── password (text) ← SMTP password
├── smtp_host (text)
└── smtp_port (int)

notification_receiver
├── id (PK, int)
├── usersId (int) ← FK to users.id
└── emailRecipient (text) ← Recipient email
```

**Model Mapping**:
- `App\Models\NotificationHistory` → `notification_history`
- `App\Models\NotificationEmail` → `notification_email`
- `App\Models\NotificationReceiver` → `notification_receiver`

---

### 2.3 E-commerce Module

#### Product Catalog
```
ecom_products
├── id (PK, int)
├── productName (text)
├── productDescription (text)
├── productStore (text) ← Store identifier
├── productType (text) ← 'ship', 'digital', etc.
├── shipCoverage (text, nullable) ← For shippable products
├── isActive (int) ← 1=active, 0=inactive
├── deleteStatus (int) ← SOFT DELETE: 1=active, 0=deleted
├── created_at (datetime)
└── updated_at (datetime)

ecom_products_variants
├── id (PK, int)
├── ecomProductsId (int) ← FK to ecom_products.id
├── ecomVariantName (text)
├── ecomVariantDescription (text)
├── ecomVariantPrice (decimal 10,2) ← Selling price
├── ecomRawVariantPrice (decimal 10,2, nullable) ← Original price
├── costPrice (decimal 10,2, nullable) ← Cost for profit calc
├── affiliatePrice (decimal 10,2, nullable)
├── stocksAvailable (int)
├── maxOrderPerTransaction (int, default 1)
├── isActive (int) ← 1=active
├── deleteStatus (int) ← SOFT DELETE: 1=active, 0=deleted
├── created_at (datetime)
└── updated_at (datetime)

ecom_products_variants_images
├── id (PK, int)
├── ecomVariantsId (int) ← FK to variants.id
├── imageName (text)
├── imageLink (text) ← Image URL/path
├── imageOrder (int) ← Display order
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)

ecom_products_variants_videos
├── id (PK, int)
├── ecomVariantsId (int) ← FK to variants.id
├── videoLink (text)
├── videoOrder (int)
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**:
- `App\Models\EcomProduct` → `ecom_products`
- `App\Models\EcomProductVariant` → `ecom_products_variants`
- `App\Models\EcomProductVariantImage` → `ecom_products_variants_images`
- `App\Models\EcomProductVariantVideo` → `ecom_products_variants_videos`

#### Product Stores
```
ecom_product_stores
├── id (PK, int)
├── storeName (text)
├── storeDescription (text, nullable)
├── storeLogo (text, nullable)
├── isActive (int)
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**: `App\Models\EcomProductStore` → `ecom_product_stores`

#### Shipping Configuration
```
ecom_products_shipping
├── id (PK, int)
├── shippingName (text) ← e.g., "Standard", "Express"
├── shippingDescription (text)
├── defaultPrice (int) ← Base shipping price
├── defaultMaxQuantity (int)
├── isActive (int)
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)

ecom_products_shipping_options
├── id (PK, int)
├── shippingId (int) ← FK to shipping.id
├── provinceTarget (text) ← Philippine province
├── maxQuantity (int)
├── shippingPrice (decimal 10,2)
├── isActive (int)
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)

ecom_products_variants_shipping (Junction Table)
├── id (PK, int)
├── ecomVariantId (int) ← FK to variants.id
├── ecomShippingId (int) ← FK to shipping.id
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**:
- `App\Models\EcomProductsShipping` → `ecom_products_shipping`
- `App\Models\EcomProductsShippingOptions` → `ecom_products_shipping_options`
- `App\Models\EcomProductsVariantsShipping` → `ecom_products_variants_shipping`

#### Discounts
```
ecom_products_discount
├── id (PK, int)
├── discountName (text)
├── discountDescription (text)
├── discountType (text, nullable) ← 'product', 'order', 'shipping'
├── discountTrigger (text, nullable) ← 'auto', 'code', etc.
├── discountCode (text, nullable) ← Promo code
├── amountType (text, nullable) ← 'percentage', 'fixed', 'replacement'
├── valuePercent (decimal 10,0, nullable)
├── valueAmount (int, nullable)
├── valueReplacement (int, nullable)
├── discountCapType (text, nullable)
├── discountCapValue (int, nullable)
├── totalDiscountType (int, nullable)
├── perProductDiscountCap (int, nullable)
├── usageLimit (int, nullable)
├── expirationType (text, nullable) ← 'date', 'timer', 'never'
├── dateTimeExpiration (datetime, nullable)
├── timerCountdown (int, nullable)
├── isActive (int)
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**: `App\Models\EcomProductDiscount` → `ecom_products_discount`

#### Tags/Triggers
```
axis_tags
├── id (PK, int)
├── tagName (text) ← Tag identifier
├── tagType (text) ← 'access', 'discount', etc.
├── targetId (int) ← Related entity ID
├── expirationLength (int) ← Days until expiry
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)

ecom_products_variants_tags (Junction Table)
├── id (PK, int)
├── axisTagId (int) ← FK to axis_tags.id
├── ecomVariantsId (int) ← FK to variants.id
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)
```

#### Orders
```
ecom_orders
├── id (PK, int)
├── orderNumber (text) ← Unique order ID
├── paymentStatus (text) ← 'pending', 'paid', 'failed'
├── shippingStatus (text) ← 'pending', 'shipped', 'delivered'
├── customerFullName (text)
├── paymentAmount (decimal 8,2)
├── paymentDiscount (decimal 8,2)
├── shippingAmount (decimal 8,2)
├── totalToPay (decimal 8,2)
├── handledBy (text) ← Admin who processed
├── created_at (datetime)
└── updated_at (datetime)

ecom_orders_client
├── id (PK, int)
├── ordersId (int) ← FK to ecom_orders.id
├── customerFirstName (text)
├── customerLastName (text)
├── customerEmail (text)
├── customerPhone (text)
├── created_at (datetime)
└── updated_at (datetime)

ecom_orders_products
├── id (PK, int)
├── ordersId (int) ← FK to ecom_orders.id
├── productName (text) ← Snapshot of product
├── productDescription (text)
├── productStore (text)
├── variantName (text)
├── variantDescription (text)
├── variantPrice (decimal 8,2)
├── orderQuantity (int)
├── itemTotalPrice (decimal 8,2)
├── originalProductId (int) ← Reference to original product
├── originalVariantId (int) ← Reference to original variant
├── created_at (datetime)
└── updated_at (datetime)

ecom_orders_shipping
├── id (PK, int)
├── ordersId (int) ← FK to ecom_orders.id
├── houseNumber (text)
├── streetNumber (text)
├── zoneNumber (text)
├── barangayName (text)
├── municipalityCity (text)
├── provinceName (text)
├── zipCode (text)
├── orderRecipient (text)
├── recipientNumber (text)
├── additionalNotes (text)
├── shippingType (text)
├── shippingFee (int)
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**: `App\Models\EcomOrder` → `ecom_orders`

---

### 2.4 Ani-Senso Course Module

```
as_courses
├── id (PK, int)
├── courseName (text)
├── courseSmallDescription (text)
├── courseBigDescription (text)
├── coursePrice (decimal 10,2)
├── courseImage (text) ← Image path
├── isActive (varchar 5) ← 'true'/'false' as string!
├── deleteStatus (varchar 5) ← SOFT DELETE: 'true'/'false'
├── created_at (datetime)
└── updated_at (datetime)

as_courses_chapters
├── id (PK, int)
├── asCoursesId (int) ← FK to as_courses.id
├── chapterTitle (text)
├── chapterDescription (text)
├── chapterCoverPhoto (text)
├── chapterOrder (int) ← Display order (drag-drop)
├── deleteStatus (int) ← SOFT DELETE: 1=active
├── created_at (datetime)
└── updated_at (datetime)

as_courses_topics
├── id (PK, int)
├── chapterId (int) ← FK to chapters.id
├── topicTitle (text)
├── topicDescription (text)
├── topicContent (text) ← Rich HTML content
├── topicsOrder (int) ← Display order
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)

as_topics_resources
├── id (PK, int)
├── asTopicsId (int) ← FK to topics.id
├── fileName (text)
├── fileLink (text) ← Resource URL/path
├── created_at (datetime)
└── updated_at (datetime)

as_image_library
├── id (PK, int)
├── imageUrl (text) ← Uploaded image URL
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**:
- `App\Models\AsCourse` → `as_courses`
- `App\Models\AsCourseChapter` → `as_courses_chapters`
- `App\Models\AsTopic` → `as_courses_topics`
- `App\Models\AsTopicResource` → `as_topics_resources`
- `App\Models\AsImageLibrary` → `as_image_library`

---

### 2.5 Client Management

```
clients_all_database
├── id (PK, int)
├── clientFirstName (text)
├── clientMiddleName (text)
├── clientLastName (text)
├── clientPhoneNumber (text)
├── clientEmailAddress (text)
├── created_at (datetime)
└── updated_at (datetime)

clients_access_login
├── id (PK, int)
├── clientFirstName (text)
├── clientMiddleName (text)
├── clientLastName (text)
├── productStore (text) ← Store access
├── clientPhoneNumber (text)
├── clientEmailAddress (text)
├── clientPassword (text) ← Hashed password
├── isActive (int)
├── deleteStatus (int) ← SOFT DELETE
├── created_at (datetime)
└── updated_at (datetime)
```

**Model Mapping**: `App\Models\ClientAllDatabase` → `clients_all_database`

---

### 2.6 System/Legacy Tables

```
customers (Skote Demo - may be unused)
├── id (PK, bigint unsigned)
├── username (varchar 250, nullable)
├── email (varchar 250, nullable)
├── phone (varchar 250, nullable)
├── address (varchar 250, nullable)
├── rating (double, nullable)
├── balance (double, nullable)
├── joining_date (date, nullable)
├── created_at (timestamp)
└── updated_at (timestamp)

access-token (Legacy - hyphenated name)
├── id (PK, int)
├── userid (int) ← FK to users.id
└── token (text)

main-account (Legacy - hyphenated name)
├── [structure unavailable - legacy table]

domain_access
├── id (PK, int)
└── domainName (int) ← Domain identifier

failed_jobs (Laravel Queue)
├── id (PK, bigint)
├── uuid (varchar, unique)
├── connection (text)
├── queue (text)
├── payload (longtext)
├── exception (longtext)
├── failed_at (timestamp)

migrations (Laravel)
├── id (PK, int)
├── migration (varchar)
└── batch (int)
```

---

## 3. Relationship Diagram

### 3.1 Entity Relationships

```
                                    users
                                      │
         ┌──────────────┬─────────────┼─────────────┬──────────────┐
         ▼              ▼             ▼             ▼              ▼
       task        income_logger  notification  difference    threshold
         │              │          history       history         task
         │              │             │                           │
         ▼              │             │                           │
   threshold_task ◄─────┘             │                           │
         │                            │                           │
         └────────────────────────────┴───────────────────────────┘
                              (All scoped by usersId)

                    ecom_products
                         │
                         ▼
              ecom_products_variants
                    │    │    │
         ┌──────────┘    │    └──────────┐
         ▼               ▼               ▼
    variants_images  variants_tags  variants_shipping
                         │               │
                         ▼               ▼
                    axis_tags     ecom_products_shipping
                                        │
                                        ▼
                              shipping_options

                    ecom_orders
                    │    │    │
         ┌──────────┘    │    └──────────┐
         ▼               ▼               ▼
   orders_client   orders_products  orders_shipping

                    as_courses
                         │
                         ▼
               as_courses_chapters
                         │
                         ▼
                as_courses_topics
                         │
                         ▼
               as_topics_resources
```

### 3.2 Foreign Key Patterns (Application-Level)

**No database-level foreign keys are defined.** All relationships are enforced at the application (Laravel) level.

| Parent Table | Child Table | FK Column | Parent Column |
|--------------|-------------|-----------|---------------|
| `users` | `task` | `usersId` | `id` |
| `users` | `income_logger` | `usersId` | `id` |
| `users` | `notification_history` | `usersId` | `id` |
| `users` | `difference_history` | `usersId` | `id` |
| `users` | `threshold_task` | `usersId` | `id` |
| `users` | `notification_email` | `usersId` | `id` |
| `users` | `notification_receiver` | `usersId` | `id` |
| `task` | `notification_history` | `taskId` | `id` |
| `task` | `threshold_task` | `taskId` | `id` |
| `ecom_products` | `ecom_products_variants` | `ecomProductsId` | `id` |
| `ecom_products_variants` | `*_images` | `ecomVariantsId` | `id` |
| `ecom_products_variants` | `*_videos` | `ecomVariantsId` | `id` |
| `ecom_products_variants` | `*_shipping` | `ecomVariantId` | `id` |
| `ecom_products_variants` | `*_tags` | `ecomVariantsId` | `id` |
| `ecom_products_shipping` | `*_options` | `shippingId` | `id` |
| `ecom_orders` | `orders_*` | `ordersId` | `id` |
| `as_courses` | `as_courses_chapters` | `asCoursesId` | `id` |
| `as_courses_chapters` | `as_courses_topics` | `chapterId` | `id` |
| `as_courses_topics` | `as_topics_resources` | `asTopicsId` | `id` |
| `axis_tags` | `*_variants_tags` | `axisTagId` | `id` |

---

## 4. Soft Delete Conventions

**CRITICAL: Different modules use DIFFERENT soft delete patterns!**

| Module | Column | Type | Active Value | Deleted Value |
|--------|--------|------|--------------|---------------|
| **Users/Crypto** | `delete_status` | text | `'active'` | `'deleted'` |
| **E-commerce** | `deleteStatus` | int | `1` | `0` |
| **Ani-Senso** | `deleteStatus` | varchar(5)/int | `'true'`/`1` | `'false'`/`0` |

### Recommended Pattern for NEW Tables

Use the Users/Crypto pattern for consistency:
```sql
delete_status ENUM('active', 'deleted') NOT NULL DEFAULT 'active'
```

Query pattern:
```php
Model::where('delete_status', 'active')->get();
// Or use scope:
Model::active()->get();
```

---

## 5. Index Analysis

### 5.1 Current Indexes

| Table | Index Name | Column(s) | Type |
|-------|------------|-----------|------|
| All tables | `PRIMARY` | `id` | Unique |
| `failed_jobs` | `failed_jobs_uuid_unique` | `uuid` | Unique |
| `password_resets` | `password_resets_email_index` | `email` | Non-unique |
| `personal_access_tokens` | `*_tokenable_type_tokenable_id_index` | `tokenable_type`, `tokenable_id` | Composite |
| `personal_access_tokens` | `*_token_unique` | `token` | Unique |

### 5.2 Recommended Missing Indexes

For optimal query performance, consider adding:

```sql
-- Crypto module (high-volume tables)
ALTER TABLE historical_price ADD INDEX idx_coinType_created (coinType(10), created_at);
ALTER TABLE difference_history ADD INDEX idx_usersId_created (usersId, created_at);
ALTER TABLE income_logger ADD INDEX idx_usersId_deleteStatus (usersId, delete_status(10));

-- E-commerce
ALTER TABLE ecom_products ADD INDEX idx_productStore_deleteStatus (productStore(50), deleteStatus);
ALTER TABLE ecom_products_variants ADD INDEX idx_ecomProductsId_deleteStatus (ecomProductsId, deleteStatus);
ALTER TABLE ecom_orders ADD INDEX idx_paymentStatus_created (paymentStatus(20), created_at);

-- General pattern for user-scoped tables
ALTER TABLE [table_name] ADD INDEX idx_usersId (usersId);
```

---

## 6. Data Type Reference

### 6.1 Column Type Conventions

| Data Type | MySQL Type | Laravel Cast | Example |
|-----------|------------|--------------|---------|
| Primary Key | `int(11)` or `bigint unsigned` | (none) | `id` |
| Foreign Key | `int(11)` | (none) | `usersId`, `taskId` |
| Currency (PHP) | `decimal(10,2)` or `decimal(8,2)` | `'decimal:2'` | `paymentAmount` |
| Crypto Values | `decimal(18,8)` | `'decimal:8'` | `currentCoinValue` |
| Boolean Flags | `int(11)` | `'boolean'` | `isActive` |
| Short Text | `varchar(191)` or `varchar(250)` | (none) | `name`, `email` |
| Long Text | `text` | (none) | `productDescription` |
| Soft Delete | `text` or `int(11)` | (none) | `delete_status` |
| Dates | `datetime` or `timestamp` | `'datetime'` | `created_at` |

### 6.2 Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Table names | snake_case, often with prefix | `ecom_products`, `as_courses` |
| Column names | camelCase | `usersId`, `productName`, `isActive` |
| Primary keys | `id` | All tables |
| Foreign keys | `{parent}Id` | `usersId`, `ecomProductsId`, `ordersId` |
| Order columns | `{item}Order` | `chapterOrder`, `topicsOrder`, `imageOrder` |

---

## 7. Integration with Codebase

### 7.1 Model-Database Alignment

| Model (app/Models/) | Table | Extends | Soft Delete |
|---------------------|-------|---------|-------------|
| `User` | `users` | Model | `delete_status='active'` |
| `Task` | `task` | BaseModel | None (uses `status`) |
| `IncomeLogger` | `income_logger` | BaseModel | `delete_status='active'` |
| `HistoricalPrice` | `historical_price` | BaseModel | None |
| `DifferenceHistory` | `difference_history` | BaseModel | None |
| `EcomProduct` | `ecom_products` | Model | `deleteStatus=1` |
| `EcomProductVariant` | `ecom_products_variants` | Model | `deleteStatus=1` |
| `EcomOrder` | `ecom_orders` | Model | None |
| `AsCourse` | `as_courses` | Model | `deleteStatus='true'` |

### 7.2 Query Patterns by Module

**Crypto Module (Recommended Pattern)**
```php
// User-scoped with soft delete
$data = IncomeLogger::active()
    ->forUser(Auth::id())
    ->orderBy('created_at', 'desc')
    ->get();
```

**E-commerce Module (Legacy Pattern)**
```php
// Different soft delete check
$products = EcomProduct::where('deleteStatus', 1)
    ->where('isActive', 1)
    ->get();
```

**Ani-Senso Module (Mixed Pattern)**
```php
// Boolean as string!
$courses = AsCourse::where('deleteStatus', 'true')
    ->where('isActive', 'true')
    ->get();
```

---

## 8. Performance Considerations

### 8.1 High-Volume Tables

These tables accumulate data rapidly and need special attention:

| Table | Records | Growth Rate | Optimization |
|-------|---------|-------------|--------------|
| `historical_price` | ~31K | Continuous | Consider partitioning by date |
| `historical_ladder` | ~31K | Continuous | Archive old data |
| `difference_history` | ~31K | Continuous | Add composite indexes |

### 8.2 Query Optimization Tips

1. **Always index `usersId`** on user-scoped tables
2. **Index `delete_status`** alongside `usersId` for filtered queries
3. **Use `created_at` indexes** for date-range queries on historical tables
4. **Consider pagination** (limit 100-200 rows) for large result sets
5. **Avoid `SELECT *`** on tables with `text` columns

---

## 9. Migration Guidelines

### 9.1 Creating New Tables

```php
Schema::create('new_feature', function (Blueprint $table) {
    // Primary key
    $table->id(); // Uses bigint unsigned

    // User scoping (required for user-specific data)
    $table->unsignedBigInteger('usersId');

    // Business columns (camelCase)
    $table->string('fieldName', 255);
    $table->text('description')->nullable();
    $table->decimal('priceValue', 10, 2)->default(0);

    // Soft delete (use enum for new tables)
    $table->enum('delete_status', ['active', 'deleted'])->default('active');

    // Timestamps
    $table->timestamps(); // created_at, updated_at

    // Indexes
    $table->index(['usersId', 'delete_status']);
});
```

### 9.2 Adding Columns to Existing Tables

```php
Schema::table('existing_table', function (Blueprint $table) {
    $table->string('newColumn', 255)->nullable()->after('existingColumn');
});
```

---

## 10. Quick Reference

### 10.1 Common Query Patterns

```php
// Get active records for current user
Model::where('usersId', Auth::id())
    ->where('delete_status', 'active')
    ->orderBy('created_at', 'desc')
    ->get();

// Soft delete
$record->update(['delete_status' => 'deleted']);

// With relationships
EcomProduct::with(['variants' => function($q) {
    $q->where('deleteStatus', 1);
}])->where('deleteStatus', 1)->get();
```

### 10.2 Database Validation Checklist

When creating new database structures, verify:

- [ ] Table name uses snake_case with appropriate prefix
- [ ] Column names use camelCase
- [ ] Primary key is `id` (auto-increment)
- [ ] Foreign keys follow `{parent}Id` pattern
- [ ] `usersId` column exists for user-scoped data
- [ ] Soft delete uses `delete_status` enum (preferred)
- [ ] `created_at` and `updated_at` timestamps exist
- [ ] Appropriate indexes are defined
- [ ] Decimal precision matches data type (10,2 for PHP, 18,8 for crypto)

---

## 11. Legacy Table Warnings

### Tables to AVOID modifying:

| Table | Issue | Recommendation |
|-------|-------|----------------|
| `access-token` | Hyphenated name (non-standard) | Create new `access_tokens` if needed |
| `main-account` | Hyphenated name (non-standard) | Create new `accounts` if needed |
| `customers` | Skote demo table | Use `clients_all_database` instead |
| `domain_access` | Minimal structure | Expand or replace |

### Inconsistencies to be aware of:

1. `as_courses.isActive` is varchar(5) storing 'true'/'false' strings
2. E-commerce uses integer `deleteStatus` (1/0) instead of enum
3. Some tables lack `usersId` for multi-user support
4. No database-level foreign key constraints

---

## 12. Related Skills & Agent Harmony

### 12.1 Skill Ecosystem

This skill is part of the DS AXIS architect skill system:

| Skill | Purpose | When to Reference |
|-------|---------|-------------------|
| **codebase-architect** | System structure, patterns | Model patterns, naming conventions |
| **database-architect** (this) | Database schema, relationships | WHAT data exists, data types |
| **logic-architect** | Business logic, data flows | HOW queries are used, data processing |
| **ds-axis-agent-harmony** | Agent collaboration rules | Multi-agent development |
| **pact-skills-updater** | Keep skills synchronized | After schema changes |

### 12.2 Cross-References

- For model implementation → See `codebase-architect.md`
- For query usage patterns → See `logic-architect.md`
- For agent workflows → See `ds-axis-agent-harmony.md`

---

*This document should be referenced alongside `codebase-architect.md` to ensure database implementations align with both application patterns and DS AXIS data structure conventions. For multi-agent development, also reference `ds-axis-agent-harmony.md`.*
