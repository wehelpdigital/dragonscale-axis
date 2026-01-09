# PACT Skills Updater Agent

> **Agent Type**: `pact-skills-updater`
> **Purpose**: Analyze codebase changes and update the architect skill files to ensure documentation stays synchronized with the actual DS AXIS system implementation.
>
> **System Context**: DS AXIS (Dragon Scale Axis) is the centralized admin dashboard for Dragon Scale Web Company. This agent ensures all skill documentation remains accurate as the system grows to accommodate new sub-ventures and features.

---

## 1. Agent Overview

### 1.1 DS AXIS Context

DS AXIS is designed to **continuously grow and evolve** as Dragon Scale Web Company expands its business ventures. This agent is critical for:

1. **Maintaining Documentation Currency**: As new sub-ventures are added
2. **Ensuring Pattern Consistency**: As the codebase grows
3. **Supporting Multi-Agent Harmony**: All PACT agents rely on accurate skill documentation
4. **Facilitating Onboarding**: New developers and agents can quickly understand the system

### 1.2 Skill Files Managed

The `pact-skills-updater` agent is responsible for maintaining the accuracy and currency of the architect skill files:

| Skill File | Content | Update Triggers |
|------------|---------|-----------------|
| `codebase-architect.md` | System structure, patterns, conventions | New controllers, models, views, route changes, pattern changes |
| `database-architect.md` | Database schema, relationships, types | New migrations, schema changes, new tables, column modifications |
| `logic-architect.md` | Business logic, data flows, processing | Controller logic changes, new API endpoints, workflow modifications |

---

## 2. When to Invoke This Agent

### 2.1 Automatic Triggers (Recommended)

Invoke `pact-skills-updater` after:

1. **Creating new features/modules**
   - New controller created
   - New model created
   - New database migration run
   - New routes added

2. **Modifying existing features**
   - Controller logic changed significantly
   - Model relationships updated
   - Database schema altered
   - Route structure modified

3. **Refactoring operations**
   - Code patterns changed
   - File structure reorganized
   - Naming conventions updated

4. **Integration changes**
   - New external API integrated
   - Authentication flow modified
   - Notification system updated

### 2.2 Manual Triggers

- After major development sessions
- Before code reviews
- When onboarding new team members
- Periodic documentation audits

---

## 3. Agent Workflow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PACT-SKILLS-UPDATER WORKFLOW                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  PHASE 1: CHANGE DETECTION                                             │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  1. Run `git status` to identify modified/new files             │   │
│  │  2. Run `git diff` to see specific changes                      │   │
│  │  3. Identify file types affected:                               │   │
│  │     • Controllers (app/Http/Controllers/)                       │   │
│  │     • Models (app/Models/)                                      │   │
│  │     • Migrations (database/migrations/)                         │   │
│  │     • Routes (routes/web.php, routes/api.php)                   │   │
│  │     • Views (resources/views/)                                  │   │
│  │     • Config files                                              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  PHASE 2: IMPACT ANALYSIS                                              │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Map changes to affected skills:                                │   │
│  │                                                                   │   │
│  │  Change Type              → Skill(s) to Update                  │   │
│  │  ─────────────────────────────────────────────────────────────  │   │
│  │  New/modified controller  → codebase-architect, logic-architect │   │
│  │  New/modified model       → codebase-architect, database-architect │
│  │  New migration            → database-architect                   │   │
│  │  Route changes            → codebase-architect, logic-architect │   │
│  │  View changes             → codebase-architect                   │   │
│  │  API integration          → logic-architect                      │   │
│  │  Auth changes             → logic-architect                      │   │
│  │  Pattern changes          → codebase-architect                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  PHASE 3: CONTENT ANALYSIS                                             │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  1. Read affected source files                                  │   │
│  │  2. Read current skill file(s)                                  │   │
│  │  3. Identify gaps or outdated information:                      │   │
│  │     • Missing new components                                    │   │
│  │     • Outdated patterns/conventions                             │   │
│  │     • Incorrect relationships                                   │   │
│  │     • Missing logic flows                                       │   │
│  │     • New database tables/columns                               │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  PHASE 4: SKILL UPDATE                                                 │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  1. Generate update content preserving existing structure       │   │
│  │  2. Add new sections where needed                               │   │
│  │  3. Modify existing sections with changes                       │   │
│  │  4. Remove deprecated/deleted items                             │   │
│  │  5. Maintain consistent formatting                              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  PHASE 5: VALIDATION                                                   │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  1. Verify all referenced files exist                           │   │
│  │  2. Check for broken internal references                        │   │
│  │  3. Ensure consistency across all three skills                  │   │
│  │  4. Report summary of changes made                              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Update Rules by Skill

### 4.1 codebase-architect.md Updates

**Add/Update when:**
- New controller namespace or pattern emerges
- New model with different patterns is created
- View structure changes (new layout, new component)
- Route naming convention changes
- New JavaScript patterns introduced
- CSS/UI patterns modified
- Directory structure changes

**Sections to check:**
- Section 2: Directory Structure
- Section 3: Model Architecture
- Section 4: Controller Patterns
- Section 5: View/Template Architecture
- Section 6: JavaScript Patterns
- Section 7: Route Conventions
- Section 9: Legacy Areas
- Section 10: Module-Specific Reference

### 4.2 database-architect.md Updates

**Add/Update when:**
- New migration created/run
- New table added
- Columns added/modified/removed
- Relationships changed
- Indexes added
- Soft delete pattern changed for any table

**Sections to check:**
- Section 2: Module-Table Mapping
- Section 3: Relationship Diagram
- Section 4: Soft Delete Conventions
- Section 5: Index Analysis
- Section 6: Data Type Reference
- Section 7: Integration with Codebase
- Section 11: Legacy Table Warnings

### 4.3 logic-architect.md Updates

**Add/Update when:**
- Controller business logic modified
- New API endpoint added
- External API integration changed
- Authentication/authorization flow modified
- Notification system updated
- Data processing workflow changed
- New CRUD operations added

**Sections to check:**
- Section 2: Authentication & Authorization Flows
- Section 3: Crypto Module Logic Flows
- Section 4: External API Integrations
- Section 5: Notification System Flow
- Section 6: E-commerce Module Logic Flows
- Section 7: Ani-Senso Course Module Logic Flows
- Section 9: Data Flow Diagrams
- Section 10: Common Query Patterns
- Section 11: Critical Business Rules

---

## 5. Change Detection Commands

### 5.1 Git-Based Detection

```bash
# See all uncommitted changes
git status

# See detailed changes
git diff

# See changes since last commit
git diff HEAD~1

# See changes in specific directories
git diff -- app/Http/Controllers/
git diff -- app/Models/
git diff -- database/migrations/
git diff -- routes/

# List recently modified files
git log --name-only --since="1 day ago" --oneline
```

### 5.2 File Pattern Detection

```bash
# Find recently modified PHP files
find app/ -name "*.php" -mtime -1

# Find new migration files
ls -la database/migrations/ | tail -5

# Check for new routes
grep -n "Route::" routes/web.php | tail -20
```

---

## 6. Update Templates

### 6.1 Adding New Controller Documentation

```markdown
### X.X New Feature Controller

**File**: `app/Http/Controllers/NewFeatureController.php`

**Routes**:
- `GET /new-feature` → `index()` - List items
- `POST /new-feature` → `store()` - Create item
- `DELETE /new-feature/{id}` → `destroy()` - Soft delete

**Key Logic**:
- [Describe main business logic]
- [Note any special patterns]

**Related Tables**: `table_name`
**Soft Delete**: `delete_status = 'active'/'deleted'`
```

### 6.2 Adding New Table Documentation

```markdown
### X.X new_table_name

```
new_table_name
├── id (PK, bigint unsigned)
├── usersId (FK to users.id)
├── fieldName (type)
├── delete_status (enum: 'active', 'deleted')
├── created_at (timestamp)
└── updated_at (timestamp)
```

**Model Mapping**: `App\Models\NewModel` → `new_table_name`
**Controller**: `NewFeatureController`
```

### 6.3 Adding New Logic Flow

```markdown
### X.X New Feature Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    NEW FEATURE FLOW                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  [Describe step-by-step flow with ASCII diagram]                 │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

**Key Methods**: `method1()`, `method2()`
**Data Flow**: input → processing → output
**Related Skills**: codebase-architect Section X, database-architect Section Y
```

---

## 7. Validation Checklist

After updating skills, verify:

### 7.1 Cross-Skill Consistency

- [ ] Table names match between database-architect and logic-architect
- [ ] Controller names match between codebase-architect and logic-architect
- [ ] Model mappings are consistent across all skills
- [ ] Soft delete patterns documented consistently
- [ ] Route names match actual routes/web.php

### 7.2 Completeness

- [ ] All new controllers documented in codebase-architect
- [ ] All new models documented in codebase-architect
- [ ] All new tables documented in database-architect
- [ ] All new logic flows documented in logic-architect
- [ ] All relationships correctly mapped

### 7.3 Accuracy

- [ ] Code snippets match actual implementation
- [ ] Query patterns are correct
- [ ] Business rules reflect current logic
- [ ] File paths are accurate
- [ ] Table schemas match migrations

---

## 8. Example Update Scenarios

### Scenario 1: New CRUD Feature Added

**Changes detected:**
- `app/Http/Controllers/NewFeatureController.php` (new)
- `app/Models/NewFeature.php` (new)
- `database/migrations/2024_01_15_create_new_features_table.php` (new)
- `routes/web.php` (modified)
- `resources/views/new-feature/index.blade.php` (new)

**Skills to update:**
1. **codebase-architect.md**:
   - Add to Section 2 (Directory Structure)
   - Add to Section 10 (Module-Specific Reference)

2. **database-architect.md**:
   - Add table schema to appropriate section
   - Update Section 3 (Relationship Diagram)

3. **logic-architect.md**:
   - Add CRUD flow documentation
   - Update data flow diagrams if needed

### Scenario 2: External API Integration Added

**Changes detected:**
- `app/Http/Controllers/Api/ExternalApiController.php` (new)
- `config/services.php` (modified)

**Skills to update:**
1. **logic-architect.md**:
   - Add to Section 4 (External API Integrations)
   - Document request/response format
   - Add error handling details

2. **codebase-architect.md**:
   - Add API controller to directory structure

### Scenario 3: Database Schema Change

**Changes detected:**
- `database/migrations/2024_01_15_add_column_to_table.php` (new)
- `app/Models/ExistingModel.php` (modified)

**Skills to update:**
1. **database-architect.md**:
   - Update table schema with new column
   - Update data types if changed
   - Check index recommendations

2. **codebase-architect.md**:
   - Update model fillable/casts if documented

---

## 9. Agent Output Format

When reporting updates, use this format:

```markdown
## Skills Update Report

### Changes Detected
- [List of changed files with change type]

### Updates Made

#### codebase-architect.md
- [x] Added: Section X.X - New Feature Controller
- [x] Modified: Section 2 - Directory Structure (added new controller path)
- [ ] No changes needed: Section 6 - JavaScript Patterns

#### database-architect.md
- [x] Added: new_table schema in Section 2.X
- [x] Modified: Section 3 - Relationship Diagram
- [x] Added: Index recommendation in Section 5

#### logic-architect.md
- [x] Added: Section X.X - New Feature Flow
- [x] Modified: Section 10 - Common Query Patterns

### Validation Status
- [x] Cross-skill consistency verified
- [x] All file paths verified
- [x] Table schemas match migrations

### Notes
- [Any important observations or recommendations]
```

---

## 10. Integration with Development Workflow

### Recommended Usage Pattern

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    DEVELOPMENT WORKFLOW INTEGRATION                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Developer completes feature                                           │
│         │                                                               │
│         ▼                                                               │
│  Run pact-skills-updater agent                                         │
│         │                                                               │
│         ▼                                                               │
│  Review update report                                                  │
│         │                                                               │
│         ▼                                                               │
│  Commit code + updated skills together                                 │
│         │                                                               │
│         ▼                                                               │
│  Skills always in sync with code                                       │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Invocation Examples

```
# After creating a new feature
"Update the architect skills to include the new [feature name] I just created"

# After modifying existing logic
"Analyze my recent changes and update the relevant skill documentation"

# Periodic sync
"Check if the architect skills are in sync with the current codebase"

# Specific skill update
"Update the database-architect skill with the new migration I added"
```

---

## 11. Skill File Locations

| Skill | Path | Purpose |
|-------|------|---------|
| Codebase Architect | `.claude/skills/codebase-architect.md` | System structure, patterns |
| Database Architect | `.claude/skills/database-architect.md` | Database schema, relationships |
| Logic Architect | `.claude/skills/logic-architect.md` | Business logic, data flows |
| Agent Harmony | `.claude/skills/ds-axis-agent-harmony.md` | Multi-agent collaboration |
| Skills Updater (this file) | `.claude/skills/pact-skills-updater.md` | Keep skills synchronized |

---

## 12. DS AXIS Context & Agent Harmony

### 12.1 System Context

DS AXIS (Dragon Scale Axis) is the **centralized admin dashboard** for Dragon Scale Web Company. As the system grows to include more sub-ventures and features, this agent becomes critical for:

1. **Documentation Currency**: Keeping skills accurate as ventures expand
2. **Pattern Consistency**: Ensuring new code follows established conventions
3. **Multi-Agent Support**: All PACT agents depend on accurate skill documentation
4. **Onboarding**: New team members can quickly understand the system

### 12.2 Coordination with Other Agents

This agent should be invoked after any agent completes work that affects:
- System structure (pact-frontend-coder, pact-backend-coder)
- Database schema (pact-database-engineer)
- Business logic (pact-backend-coder, pact-architect)
- New ventures or modules (any implementation agent)

See `ds-axis-agent-harmony.md` for complete agent collaboration guidelines.

---

*This agent ensures the DS AXIS architect skills remain living documentation that evolves with the codebase, providing always-accurate guidance for development tasks across all Dragon Scale ventures.*
