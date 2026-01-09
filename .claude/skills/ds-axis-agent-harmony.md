# DS AXIS Agent & Skill Harmony Guide

> **Purpose**: This document defines how all PACT agents and architect skills work together seamlessly within the DS AXIS (Dragon Scale Axis) system. It establishes conventions, communication patterns, and conflict resolution strategies to ensure harmonious collaboration.

---

## 1. DS AXIS System Overview

### 1.1 What is DS AXIS?

**DS AXIS (Dragon Scale Axis)** is the main centralized admin dashboard for Dragon Scale Web Company. It serves as the **parent system** that manages all sub-business ventures:

| Venture | Description | Status |
|---------|-------------|--------|
| Dragon Scale Crypto | Cryptocurrency investment tracking | Active |
| Dragon Scale Store(s) | E-commerce product management | Active |
| Ani-Senso Academy | Educational courses & memberships | Active |
| CRM & Clients | Client relationship management | Active |
| Access & Triggers | Cross-venture access control | Active |
| Affiliates | Affiliate marketing system | Expanding |
| Future Ventures | Designed for continuous growth | Planned |

### 1.2 Key Architectural Principles

1. **Admin Backend Only**: DS AXIS handles administrative operations; customer-facing frontends are separate systems
2. **Multi-Venture Unified**: All ventures share common infrastructure and patterns
3. **Continuous Evolution**: System is designed to grow with new ventures
4. **Pattern Consistency**: All new code must follow established conventions

---

## 2. Agent Ecosystem

### 2.1 PACT Agent Family

The PACT (Project Architecture & Code Toolkit) agents work together as a cohesive system:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      DS AXIS PACT AGENT ECOSYSTEM                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    ARCHITECT SKILL LAYER                        │   │
│  │                                                                   │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │   │
│  │  │  codebase-   │  │  database-   │  │   logic-     │           │   │
│  │  │  architect   │  │  architect   │  │  architect   │           │   │
│  │  │              │  │              │  │              │           │   │
│  │  │ • Structure  │  │ • Schema     │  │ • Flows      │           │   │
│  │  │ • Patterns   │  │ • Relations  │  │ • Workflows  │           │   │
│  │  │ • Views      │  │ • Types      │  │ • APIs       │           │   │
│  │  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘           │   │
│  │         │                 │                 │                    │   │
│  │         └─────────────────┼─────────────────┘                    │   │
│  │                           │                                      │   │
│  │                           ▼                                      │   │
│  │              ┌─────────────────────────┐                        │   │
│  │              │   pact-skills-updater   │                        │   │
│  │              │   (Keeps skills synced) │                        │   │
│  │              └─────────────────────────┘                        │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    IMPLEMENTATION AGENTS                         │   │
│  │                                                                   │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │   │
│  │  │    pact-     │  │    pact-     │  │    pact-     │           │   │
│  │  │  architect   │  │  frontend-   │  │  backend-    │           │   │
│  │  │              │  │   coder      │  │   coder      │           │   │
│  │  │ Plans &      │  │ Blade, JS,   │  │ Controllers, │           │   │
│  │  │ Designs      │  │ CSS, UI      │  │ Models, API  │           │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘           │   │
│  │                                                                   │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │   │
│  │  │    pact-     │  │  pact-test-  │  │  performance-│           │   │
│  │  │  database-   │  │   engineer   │  │  optimizer   │           │   │
│  │  │  engineer    │  │              │  │              │           │   │
│  │  │ Migrations,  │  │ Testing,     │  │ Query opt,   │           │   │
│  │  │ Schema       │  │ Debugging    │  │ Caching      │           │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘           │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    QUALITY & ANALYSIS AGENTS                     │   │
│  │                                                                   │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │   │
│  │  │   security-  │  │    code-     │  │   library-   │           │   │
│  │  │    code-     │  │ consistency- │  │    scout     │           │   │
│  │  │   auditor    │  │  analyzer    │  │              │           │   │
│  │  │              │  │              │  │ Researches   │           │   │
│  │  │ Security     │  │ Pattern      │  │ Libraries    │           │   │
│  │  │ Reviews      │  │ Compliance   │  │              │           │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘           │   │
│  │                                                                   │   │
│  │  ┌──────────────┐  ┌──────────────┐                             │   │
│  │  │    pact-     │  │   ui-ux-     │                             │   │
│  │  │ preparation  │  │  engineer    │                             │   │
│  │  │              │  │              │                             │   │
│  │  │ Research &   │  │ Design &     │                             │   │
│  │  │ Planning     │  │ UX Patterns  │                             │   │
│  │  └──────────────┘  └──────────────┘                             │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Agent Responsibilities

| Agent | Primary Responsibility | Reads Skills | Updates Skills |
|-------|------------------------|--------------|----------------|
| **pact-architect** | Design & plan implementations | All three | No |
| **pact-frontend-coder** | Blade, JS, CSS implementation | codebase, logic | No |
| **pact-backend-coder** | Controllers, models, services | All three | No |
| **pact-database-engineer** | Migrations, schema design | database | No |
| **pact-test-engineer** | Testing & debugging | All three | No |
| **pact-skills-updater** | Keep documentation current | All three | **Yes** |
| **pact-preparation** | Research & requirements | All three | No |
| **security-code-auditor** | Security reviews | logic, codebase | No |
| **code-consistency-analyzer** | Pattern compliance | codebase | No |
| **performance-optimizer** | Query & code optimization | database, logic | No |
| **library-scout** | Library research | codebase | No |
| **ui-ux-engineer** | UI/UX design & implementation | codebase | No |

---

## 3. Skill Hierarchy & Relationships

### 3.1 Three-Pillar Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ARCHITECT SKILL RELATIONSHIPS                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                      CODEBASE-ARCHITECT                          │   │
│  │                                                                   │   │
│  │  "WHERE things are and HOW they're structured"                   │   │
│  │                                                                   │   │
│  │  • Directory structure          • Model patterns                 │   │
│  │  • Controller conventions       • View templates                 │   │
│  │  • Route patterns               • JavaScript patterns            │   │
│  │  • CSS/UI conventions           • File naming                    │   │
│  │                                                                   │   │
│  └──────────────────────────────┬──────────────────────────────────┘   │
│                                 │                                       │
│                                 │ Informs structure                     │
│                                 ▼                                       │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                      DATABASE-ARCHITECT                          │   │
│  │                                                                   │   │
│  │  "WHAT data exists and HOW it's organized"                       │   │
│  │                                                                   │   │
│  │  • Table schemas                • Relationships                  │   │
│  │  • Column conventions           • Data types                     │   │
│  │  • Soft delete patterns         • Indexes                        │   │
│  │  • Model mappings               • Foreign keys                   │   │
│  │                                                                   │   │
│  └──────────────────────────────┬──────────────────────────────────┘   │
│                                 │                                       │
│                                 │ Informs data flow                     │
│                                 ▼                                       │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                       LOGIC-ARCHITECT                            │   │
│  │                                                                   │   │
│  │  "HOW data moves and WHAT business logic executes"               │   │
│  │                                                                   │   │
│  │  • Processing workflows         • External API integrations      │   │
│  │  • Authentication flows         • Notification systems           │   │
│  │  • Data transformations         • Business rules                 │   │
│  │  • CRUD operations              • Query patterns                 │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Cross-Reference Guidelines

When agents reference skills, they should follow this pattern:

| Information Needed | Primary Skill | Supporting Skills |
|--------------------|---------------|-------------------|
| Where to create a file | codebase-architect | - |
| What pattern to follow | codebase-architect | logic-architect |
| Database table structure | database-architect | - |
| How to query data | database-architect | logic-architect |
| Business logic rules | logic-architect | database-architect |
| API integration details | logic-architect | - |
| Soft delete implementation | database-architect | codebase-architect |
| View template structure | codebase-architect | - |
| Route naming | codebase-architect | logic-architect |

---

## 4. Agent Workflow Patterns

### 4.1 New Feature Development Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    NEW FEATURE DEVELOPMENT FLOW                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  1. PREPARATION PHASE                                                  │
│     ┌─────────────────────────────────────────────────────────┐        │
│     │  pact-preparation                                        │        │
│     │  • Research requirements                                 │        │
│     │  • Gather documentation                                  │        │
│     │  • Identify dependencies                                 │        │
│     │                                                          │        │
│     │  library-scout (if external libs needed)                │        │
│     │  • Research library options                              │        │
│     │  • Evaluate compatibility                                │        │
│     └─────────────────────────────────────────────────────────┘        │
│                           │                                             │
│                           ▼                                             │
│  2. ARCHITECTURE PHASE                                                 │
│     ┌─────────────────────────────────────────────────────────┐        │
│     │  pact-architect                                          │        │
│     │  • Design component structure                            │        │
│     │  • Plan database schema                                  │        │
│     │  • Define UI layout                                      │        │
│     │  • Create implementation instructions                    │        │
│     │                                                          │        │
│     │  Reads: codebase-architect, database-architect,         │        │
│     │         logic-architect                                  │        │
│     └─────────────────────────────────────────────────────────┘        │
│                           │                                             │
│                           ▼                                             │
│  3. IMPLEMENTATION PHASE                                               │
│     ┌─────────────────────────────────────────────────────────┐        │
│     │                                                          │        │
│     │  pact-database-engineer     pact-backend-coder          │        │
│     │  • Create migrations        • Create controllers         │        │
│     │  • Design schema            • Create models              │        │
│     │                             • Implement logic            │        │
│     │                                                          │        │
│     │  pact-frontend-coder        ui-ux-engineer              │        │
│     │  • Create views             • Design UI components       │        │
│     │  • Implement JS             • Ensure UX patterns         │        │
│     │  • Style with CSS                                        │        │
│     │                                                          │        │
│     └─────────────────────────────────────────────────────────┘        │
│                           │                                             │
│                           ▼                                             │
│  4. QUALITY PHASE                                                      │
│     ┌─────────────────────────────────────────────────────────┐        │
│     │  code-consistency-analyzer                               │        │
│     │  • Verify patterns match conventions                     │        │
│     │                                                          │        │
│     │  security-code-auditor                                   │        │
│     │  • Check for vulnerabilities                             │        │
│     │                                                          │        │
│     │  pact-test-engineer                                      │        │
│     │  • Test functionality                                    │        │
│     │  • Debug issues                                          │        │
│     │                                                          │        │
│     │  performance-optimizer                                   │        │
│     │  • Optimize queries                                      │        │
│     │  • Improve performance                                   │        │
│     └─────────────────────────────────────────────────────────┘        │
│                           │                                             │
│                           ▼                                             │
│  5. DOCUMENTATION PHASE                                                │
│     ┌─────────────────────────────────────────────────────────┐        │
│     │  pact-skills-updater                                     │        │
│     │  • Update codebase-architect with new patterns           │        │
│     │  • Update database-architect with new tables             │        │
│     │  • Update logic-architect with new flows                 │        │
│     └─────────────────────────────────────────────────────────┘        │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Bug Fix Flow

```
pact-test-engineer (analyze & debug)
        │
        ▼
pact-backend-coder / pact-frontend-coder (fix)
        │
        ▼
code-consistency-analyzer (verify patterns)
        │
        ▼
pact-skills-updater (if patterns changed)
```

### 4.3 New Sub-Venture Addition Flow

When adding a new business venture to DS AXIS:

```
1. pact-preparation
   • Research venture requirements
   • Identify data models needed
   • Map to existing patterns

2. pact-architect
   • Design module structure
   • Plan database tables with proper prefix
   • Define API endpoints if needed

3. pact-database-engineer
   • Create migrations with venture prefix
   • Follow database-architect conventions

4. Implementation agents
   • Create controllers in dedicated folder
   • Create models extending BaseModel
   • Create views in dedicated folder

5. pact-skills-updater
   • Add venture to module list in all skills
   • Document new tables
   • Document new logic flows
```

---

## 5. Conflict Prevention Rules

### 5.1 Pattern Consistency

All agents MUST follow these established patterns from `codebase-architect.md`:

| Pattern | Convention | Enforcement |
|---------|------------|-------------|
| Model inheritance | Extend `BaseModel` | Mandatory for all new models |
| Soft delete | `delete_status = 'active'/'deleted'` | Preferred for new features |
| User scoping | `usersId` column with `forUser()` scope | Mandatory for user data |
| Controller structure | Standard CRUD pattern | Recommended |
| Route naming | `{feature}.{action}` | Mandatory |
| View extension | `@extends('layouts.master')` | Mandatory |

### 5.2 Agent Boundaries

To prevent conflicts, agents have clear boundaries:

| Agent | CAN DO | CANNOT DO |
|-------|--------|-----------|
| pact-frontend-coder | Blade, JS, CSS | PHP business logic |
| pact-backend-coder | Controllers, Models, Services | Database migrations, Views |
| pact-database-engineer | Migrations, Schema | Controller logic |
| pact-skills-updater | Update skill docs | Write application code |

### 5.3 Communication Channels

Agents communicate through:

1. **Skill Files**: Primary source of truth
2. **Code Comments**: For inline documentation
3. **Todo Lists**: For task tracking
4. **Git History**: For change tracking

---

## 6. DS AXIS-Specific Conventions

### 6.1 Module Prefixes

| Sub-Venture | Table Prefix | Controller Folder | View Folder |
|-------------|--------------|-------------------|-------------|
| Core AXIS | (none) | `Controllers/` | `views/` |
| Crypto | `task`, `historical_*`, `income_*` | `Controllers/Crypto*` | `crypto-*` |
| E-commerce | `ecom_*` | `Controllers/Ecommerce/` | `ecommerce/` |
| Ani-Senso | `as_*` | `Controllers/aniSensoAdmin/` | `aniSensoAdmin/` |
| Access/Tags | `axis_*`, `clients_*` | Various | Various |
| New Venture | `{prefix}_*` | `Controllers/{Venture}/` | `{venture}/` |

### 6.2 Naming Conventions

```php
// Models: PascalCase, singular
class NewFeature extends BaseModel { }

// Controllers: PascalCase + Controller
class NewFeatureController extends Controller { }

// Tables: snake_case, often with prefix
$table->string('featureName');  // Columns are camelCase

// Routes: kebab-case
Route::get('/new-feature', [...]);
Route::get('/new-feature-add', [...]);

// Views: kebab-case
resources/views/new-feature/index.blade.php
```

### 6.3 API Design for Frontend Integration

Since DS AXIS will have separate frontend systems, design APIs with:

1. **Consistent Response Format**:
```php
return response()->json([
    'success' => true,
    'message' => 'Operation completed',
    'data' => $result
]);
```

2. **Proper Error Handling**:
```php
return response()->json([
    'success' => false,
    'message' => 'Error description',
    'error' => $e->getMessage()
], 500);
```

3. **Authentication Consideration**: Plan for Sanctum tokens for future frontends

---

## 7. Skill Update Protocol

### 7.1 When to Trigger pact-skills-updater

| Event | Update Required | Skills Affected |
|-------|-----------------|-----------------|
| New controller created | Yes | codebase, logic |
| New model created | Yes | codebase, database |
| New migration run | Yes | database |
| New route added | Yes | codebase, logic |
| Business logic changed | Yes | logic |
| UI pattern changed | Yes | codebase |
| New venture added | Yes | All three |
| Bug fix | Only if patterns changed | Varies |

### 7.2 Update Checklist

After development, verify skills are updated:

- [ ] New files documented in codebase-architect
- [ ] New tables documented in database-architect
- [ ] New logic flows documented in logic-architect
- [ ] Cross-references are consistent
- [ ] Module list is updated if new venture

---

## 8. Future Growth Considerations

### 8.1 Anticipated Expansions

| Future Need | Preparation |
|-------------|-------------|
| New sub-venture | Follow module prefix pattern |
| Customer-facing APIs | Use Sanctum, document in logic-architect |
| Multi-language support | Plan for localization tables |
| Reporting/Analytics | Design reporting tables with proper indexes |
| Mobile app backend | API-first design already in place |

### 8.2 Scalability Patterns

1. **Database**: Use proper indexes on high-volume tables
2. **Code**: Keep modules isolated for independent scaling
3. **Documentation**: Skills must grow with the codebase
4. **Testing**: Build test coverage as features grow

---

## 9. Quick Reference

### 9.1 Skill File Locations

| Skill | Path |
|-------|------|
| Codebase Architect | `.claude/skills/codebase-architect.md` |
| Database Architect | `.claude/skills/database-architect.md` |
| Logic Architect | `.claude/skills/logic-architect.md` |
| Skills Updater | `.claude/skills/pact-skills-updater.md` |
| Agent Harmony (this file) | `.claude/skills/ds-axis-agent-harmony.md` |

### 9.2 Common Agent Commands

```
# Plan a new feature
"Use pact-architect to design [feature name]"

# Implement backend
"Use pact-backend-coder to implement [controller/model]"

# Implement frontend
"Use pact-frontend-coder to create [view/JS]"

# Create database
"Use pact-database-engineer to create migration for [table]"

# Check consistency
"Use code-consistency-analyzer to verify [feature]"

# Update documentation
"Use pact-skills-updater to sync skills with recent changes"
```

---

*This harmony guide ensures all PACT agents work together seamlessly within the DS AXIS ecosystem. All agents should reference this document when collaborating on development tasks.*
