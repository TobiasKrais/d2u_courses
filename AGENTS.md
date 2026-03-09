# D2U Courses - Redaxo Addon

A Redaxo 5 CMS addon for managing courses, events, and registrations. Includes a session-based shopping cart, participant management, payment options, and multiple plugins for locations, target groups, schedule categories, customer bookings, and KuferSQL sync.

## Tech Stack

- **Language:** PHP >= 8.0
- **CMS:** Redaxo >= 5.16.0
- **Frontend Framework:** Bootstrap 4/5 (via d2u_helper templates)
- **Namespace:** `TobiasKrais\D2UCourses`

## Project Structure

```text
d2u_courses/
├── boot.php               # Addon bootstrap (extension points, permissions)
├── install.php             # Installation (database tables, URL profiles, sprog wildcards)
├── update.php              # Update (calls install.php)
├── uninstall.php           # Cleanup (database tables, views, URL profiles, sprog wildcards)
├── package.yml             # Addon configuration, version, dependencies
├── README.md
├── assets/                 # Icons (cart, delete, search)
├── lang/                   # Backend translations (de_de only)
├── lib/                    # PHP classes
│   ├── Cart.php            # Session-based shopping cart
│   ├── Category.php        # Category model (hierarchical)
│   ├── Course.php          # Course model (main entity)
│   ├── FrontendHelper.php  # Frontend utilities (alternate URLs, breadcrumbs)
│   ├── LangHelper.php      # Sprog wildcard provider (~120 wildcards)
│   ├── Module.php          # Module definitions and revisions
│   ├── deprecated_classes.php
│   └── deprecated_helper_classes.php
├── modules/                # 3 module variants in group 26
│   └── 26/
│       ├── 1/              # Ausgabe Veranstaltungen
│       ├── 2/              # Warenkorb
│       └── 3/              # Ausgabe Veranstaltungen einer Kategorie in Boxen
├── pages/                  # Backend pages
│   ├── index.php           # Page router
│   ├── course.php          # Course management (CRUD, clone, status)
│   ├── category.php        # Category management
│   ├── settings.php        # Addon settings
│   ├── setup.php           # Module manager + changelog
│   └── help.php            # Help page
└── plugins/                # 5 plugins
    ├── customer_bookings/  # Customer booking management + export
    ├── kufer_sync/         # KuferSQL XML import/sync (cronjob)
    ├── locations/          # Event locations + location categories
    ├── schedule_categories/ # Schedule categories
    └── target_groups/      # Target groups
```

## Coding Conventions

- **Namespace:** `TobiasKrais\D2UCourses` for all classes
- **Deprecated Namespace:** `D2U_Courses` (backward compatibility, deprecated since 3.5.0)
- **Naming:** camelCase for variables, PascalCase for classes
- **Indentation:** 4 spaces in PHP classes, tabs in module files
- **Comments:** English comments only
- **Frontend labels:** Use `Sprog\Wildcard::get()` backed by `LangHelper`, not `rex_i18n::msg()`
- **Backend labels:** Use `rex_i18n::msg()` with keys from `lang/` files

## AGENTS.md Maintenance

- When new project insights are gained during work and they are relevant to agent guidance, workflows, conventions, architecture, or known pitfalls, update this AGENTS.md accordingly.

## Key Classes

| Class | Description |
| ----- | ----------- |
| `Course` | Course model: name, teaser, description, dates, times, pricing (normal/discount/income-based), participant management (min/max/waitlist), registration status, instructor, downloads, Google JSON-LD |
| `Category` | Category model: hierarchical categories (up to 4 levels), color, picture, priority, Kufer mapping |
| `Cart` | Session-based shopping cart: add/remove courses, participant data, KuferSQL XML registration, booking storage, confirmation emails, multinewsletter integration |
| `FrontendHelper` | Frontend utilities: alternate URLs, breadcrumbs (courses, categories, locations, schedule categories, target groups), configurable SQL WHERE clause for course visibility |
| `LangHelper` | Sprog wildcard provider with ~120 wildcards in English and German |
| `Module` | Module definitions and revision numbers |

## Database Tables

| Table | Description |
| ----- | ----------- |
| `rex_d2u_courses_categories` | Categories: name, description, color, picture, parent category, priority |
| `rex_d2u_courses_courses` | Courses: name, teaser, description, dates, pricing, participants, registration, status, instructor |
| `rex_d2u_courses_2_categories` | Many-to-many: courses ↔ categories |

### Database Views (for URL addon)

- `rex_d2u_courses_url_categories` — Online categories with hierarchy
- `rex_d2u_courses_url_courses` — Online courses

## Architecture

### Extension Points

| Extension Point | Location | Purpose |
| --------------- | -------- | ------- |
| `ART_PRE_DELETED` | boot.php (backend) | Prevents deletion of articles used by the addon |
| `MEDIA_IS_IN_USE` | boot.php (backend) | Prevents deletion of media files used by courses |
| `D2U_HELPER_BREADCRUMBS` | boot.php (frontend) | Provides breadcrumb segments for courses and categories |

### Modules

3 module variants in group 26:

| Module | Name | Description |
| ------ | ---- | ----------- |
| 26-1 | D2U Veranstaltungen - Ausgabe Veranstaltungen | Course list and detail view |
| 26-2 | D2U Veranstaltungen - Warenkorb | Shopping cart with JS |
| 26-3 | D2U Veranstaltungen - Ausgabe Kategorie in Boxen | Category box display |

#### Module Versioning

Each module has a revision number defined in `lib/Module.php` inside the `getModules()` method. When a module is changed:

1. Add a changelog entry in `pages/setup.php` describing the change.
2. Increment the module's revision number in `Module::getModules()` by one.

**Important:** The revision only needs to be incremented **once per release**, not per commit. Check the changelog: if the version number is followed by `-DEV`, the release is still in development and no additional revision bump is needed.

### Plugins

| Plugin | Description | Key Classes |
| ------ | ----------- | ----------- |
| `customer_bookings` | Customer booking management and export | `CustomerBooking` |
| `kufer_sync` | KuferSQL XML import/sync via cronjob. Requires PHP xml extension + cronjob addon | `KuferSync`, `KuferSyncCronjob` |
| `locations` | Event locations and location categories | `Location`, `LocationCategory` |
| `schedule_categories` | Schedule categories for courses | `ScheduleCategory` |
| `target_groups` | Target groups for courses | `TargetGroup` |

## Settings

Managed via `pages/settings.php` and stored in `rex_config`:

- `article_id_courses` — Article for course page
- `article_id_shopping_cart` — Shopping cart article
- `article_id_conditions` — Terms and conditions article
- `article_id_terms_of_participation` — Participation terms article
- `allow_company` — Allow company registrations
- `forward_single_course` — Forward single course directly
- `show_time` — Course visibility timing
- `payment_options` — Payment methods
- `request_form_email` — Recipient email
- `company_name` — Provider name (for Google JSON-LD)
- `default_category_sort` — Sort by name or priority

## Dependencies

| Package | Version | Purpose |
| ------- | ------- | ------- |
| `d2u_helper` | >= 1.14.0 | Backend/frontend helpers, module manager |
| `phpmailer` | >= 2.12.0 | Email sending for cart confirmations |
| `sprog` | >= 1.0.0 | Frontend translation wildcards |
| `url` | >= 2.1 | SEO-friendly URLs |
| `yrewrite` | >= 2.0.1 | URL rewriting |

## Multi-language Support

- **Backend:** de_de only
- **Frontend (Sprog Wildcards):** DE, EN (2 languages, ~120 wildcards)

## Versioning

This addon follows [Semantic Versioning](https://semver.org/). The version number is maintained in `package.yml`. During development, the changelog uses a `-DEV` suffix.

## Changelog

The changelog is located in `pages/setup.php`.
