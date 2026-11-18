# EXTRACK repository prototype: design

Date: 2026-11-17. Status: draft for approval.
System spec: [docs/spec.md](../../spec.md) v0.8. This document covers what to build first. The spec covers the whole system.

## 1. Intent

The defense panel scaled EXTRACK down to a **repository of completed extension services**. Each campus uploads its accomplishment reports (as a soft copy or a scan) with supporting documents and photos. The people at the top (the University President, as super admin) can see every campus's documents. BISU wants the finished system by the end of January 2027 and will present it to the director in February 2027.

Before the full build, the team will show a **prototype to their mentor**. The mentor will judge three things: whether the features are right, whether the design works, and whether the logic is sound. The prototype is **the first working slice of the real system**, not a throwaway mock-up. Whatever the mentor approves carries straight into the January build, and the mentor's feedback applies to real rules rather than imitations.

## 2. Goals, non-goals, anti-goals

**Goals**

- A working Laravel app that a mentor can sign into and use as each role, with real data rules enforced on the server.
- The core loop works end to end: upload a report, fill in its details, attach documents and photos, save it as a draft, publish it, find it in the repository, view and download its files.
- It looks and behaves like a BISU system, following the BISU Software and Integration Standardization (spec section 1.4): colours, header, footer, left sidebar, application shell and MVC.
- It is built so the pending pieces can be added later without rework: Zitadel sign-in, automatic reading of reports, the REST API, BISU's component library and the university's real database.

**Non-goals for the prototype**

- Automatic reading of uploaded reports (the template reader). The form has a place for it (section 6.4), but uploaders type the fields by hand for now.
- Zitadel or Clerk sign-in. A Laravel login stands in for it (section 6.2).
- The public REST API (`/api/v1`) and the Postman collection. Responses are formatted through one class so the API is quick to add in December.
- Email sending, backups, the "similar services" panel, encoding past years' reports, and deployment to a BISU server.

**Anti-goals** (failures even if everything technically works)

- A prototype that only looks right while the rules live in the browser. Every permission and validation runs on the server.
- Anything personal (attendance sheets, photos, names) reachable without signing in, or by a user who should not see it.
- Work that has to be thrown away in December: no second data layer, no "prototype-only" models.
- Screens that feel heavy or complicated. BISU's standard asks for minimalism.

## 3. Constraints

- **Deadline:** the full system by the end of January 2027 (spec section 11.2). The prototype should take about two weeks.
- **BISU standards** (spec section 1.4): MVC; REST naming (nouns, lowercase, underscores); invite-only sign-in through Zitadel or Clerk; role-based access control; one standard JSON shape (not yet published); university colours #330066 (primary), #FFCC00 (secondary), #FFFFFF (accent); a two-column header, three-column footer and left sidebar; an application shell (university header, application switcher, user profile, notification center, breadcrumbs); motion.dev for loading states; minimalism.
- **Project rules** (CLAUDE.md): PHP 8.3+, PSR-12, Eloquent only (no raw SQL unless optimising), thin controllers with business logic in Actions or Services.
- **Stack in the repo today:** Laravel 13, Filament 3.2, Vite with Tailwind 4 (installed but unused by the prototype), MySQL through Laragon, PHPUnit 12. Sessions, cache and queue use the database driver.
- **The university supplies the database, API and branding later.** Queries must work on any database Laravel supports.
- **Data Privacy Act of 2012:** attendance sheets and photos contain personal data.

## 4. Approaches considered

### 4.1 How to build the prototype

| Approach | Trade-off | Decision |
|---|---|---|
| Rework the current Alpine prototype (sample data in the browser, one state blob) into the repository model | About 1 week, keeps the current look, but none of the logic is real and none of it carries into January | Rejected |
| **First slice of the real app with custom Blade screens** | About 2 weeks. Real tables, permissions and validation; the screens reuse the prototype's look restyled to BISU's standard; nearly everything carries into January | **Chosen by the team** |
| First slice built in Filament | 1 to 1.5 weeks. The logic is real, but the screens look like Filament's admin panel, and BISU's header, footer and shell are awkward to fit | Rejected |

### 4.2 UI stack

| Option | Decision |
|---|---|
| **A. Laravel with Blade** (plus Alpine for small interactions) | **Chosen by the team.** Closest to what the team knows; fastest |
| B. Laravel with React pages through Inertia | Rejected for now. Only worth it if BISU's component library turns out to be React |
| C. A Laravel API with a separate Next.js site | Rejected: two apps and sign-in handled twice, too risky for January |

Design tokens live in one stylesheet so they can be swapped for BISU's component library later.

### 4.3 Reading uploaded reports (later)

| Option | Decision |
|---|---|
| **Algorithm with BISU's official templates:** text from soft copies, Tesseract OCR for scans, then rules that find each template's labels | **Chosen for later.** Free, nothing leaves BISU, and the official templates make it workable. Built after the prototype |
| A small hosted AI model (for example Claude Haiku 5.5, about ₱0.20 per report) | Pending: the team will ask the mentor whether to switch to an AI model |
| An open-source model on BISU's server, or a free hosted tier | Rejected: needs a GPU server, or the free tier's data terms conflict with the Data Privacy Act |

### 4.4 Sign-in

Laravel's own login now, built so Zitadel replaces it later (section 6.2). Chosen by the team.

## 5. Roles and permissions

These are the prototype's defaults, since the meeting has not settled them (spec section 12). The mentor's feedback may change them. Each one is a single rule in a policy, so changing one is a small edit.

| Role | Add, edit and publish records | Read records | Delete records | Manage users and reference data |
|---|---|---|---|---|
| University President (super admin) | No | All campuses | Yes (soft delete, reason required) | Yes |
| Campus Uploader | Own campus: add, edit, publish | All campuses | No | No |
| Campus Director | No | All campuses | No | No |

- **Who uploads is not decided yet** (2026-11-18), so the uploading role has a neutral name, "Campus Uploader". Its display name is a setting (`role_labels`, section 6.10), so it can be renamed (for example to "Extension Director") once BISU decides. There is one uploader role per campus; the earlier assistant role was dropped.
- **Discarding drafts:** a Campus Uploader can discard their own campus's drafts. Discarding removes the draft and its files for good. Only the President deletes published records.
- "Read records" covers published records only. Drafts are visible only to their own campus's Campus Uploaders.
- Deactivated users cannot sign in, but their names stay on the records they created.

## 6. Design

### 6.1 Architecture

Laravel MVC, following the project rules.

- **Models:** Eloquent models for every table in section 6.3.
- **Policies:** one per model (`ServicePolicy`, `DocumentPolicy`, `UserPolicy`), carrying the section 5 matrix. A global scope hides other campuses' drafts.
- **Actions** (`app/Actions`): `CreateService`, `UpdateService`, `PublishService`, `DeleteService`, `AttachDocument`, `RemoveDocument`, `InviteUser`, `UpdateUser`, `DeactivateUser`. Each one writes the activity log. Controllers only validate (Form Requests), authorise and call an Action.
- **Services** (`app/Services`): `ServiceCodeGenerator`, `RepositoryQuery` (search, filters and sorting), `DashboardStats`, `ReportExport`, and the `ReportReader` interface (section 6.4).
- **Views:** Blade layouts and components, with Alpine for small interactions such as filters, the upload form and the photo viewer. Assets are built with Vite. Alpine, Leaflet and Motion are installed from npm, so pages need no CDN.
- **Responses:** an `ApiResponse` helper formats every JSON response (the upload form's file calls, map data). When BISU publishes its standard JSON shape, only this helper changes, and the `/api/v1` endpoints reuse it.

### 6.2 Sign-in, built to swap for Zitadel

- Sign-in goes through an `Authenticator` seam. The prototype uses `LocalAuthenticator` (email and password, Laravel sessions). Later, a `ZitadelAuthenticator` uses OpenID Connect through Laravel Socialite. The setting `AUTH_PROVIDER=local|zitadel` picks one.
- `users` gets `auth_provider` and `external_id` (nullable) columns, so a Zitadel account links to an existing EXTRACK user by email on first sign-in. Role, campus and active status always stay in EXTRACK.
- **Invite only:** the President invites a user, with a role and campus. The invite is a signed link that expires in 7 days, where the user sets a password. Mail uses the `log` driver in the prototype, so the Users page also shows the link with a Copy button for the demo. With Zitadel, the invite moves to Zitadel and this screen only records the role and campus.
- **Demo sign-in:** when `APP_DEMO=true`, the login page lists one seeded account per role with a "Sign in as" button, so the mentor can switch roles quickly. It is off in production.

### 6.3 Data model

New tables:

```text
users (existing, extended)
  + role (president | campus_uploader | campus_director)
  + campus_id (null for the President)
  + is_active, auth_provider (local | zitadel), external_id, last_login_at

services
  id, code (unique, e.g. BAL-2026-014), campus_id, title, description, category_id,
  municipality, start_date, end_date, beneficiaries (count), partners, amount_spent (nullable),
  narrative, state (draft | published), encoded_by, published_by, published_at,
  timestamps, soft deletes, deleted_by, delete_reason
service_barangay   service_id, barangay_id
service_sector     service_id, beneficiary_sector_id
documents
  id, service_id, kind (accomplishment_report | attendance | photo | letter | proposal | moa | other),
  copy_type (soft | scanned), original_name, path, mime, size, extracted_text (nullable, for later),
  uploaded_by, timestamps
activity_log       id, user_id, action, subject_type, subject_id, changes (json), created_at
notifications      Laravel's default table
```

- **Reused:** `campuses`, `categories` and `beneficiary_sectors`, with the existing `ExtensionReferenceSeeder` (minus its `officials` part).
- **Municipalities and barangays:** a new `municipalities` table (id, name, latitude, longitude). `barangays` gains `municipality_id`, `latitude` and `longitude` (both nullable), replacing its municipality text column. Both are seeded from the official PSGC list for Bohol (47 municipalities and Tagbilaran City, 1,109 barangays), with the source and date noted in the seeder. A chosen barangay must belong to the chosen municipality. The map uses a barangay's own coordinates when known, and otherwise its municipality's centre.
- **The old prototype is removed at the start of the build** (the team's decision). This covers:
  - the `/prototype` routes, views and `public/js/prototype/extrack.js`
  - the Filament panel, its login pages and the `filament/filament` package
  - `PrototypeRole`, `LoginResponse`, the `Prototype` controllers, services and Actions, and their tests
  - a migration that drops `extension_requests`, `officials`, `request_*`, `overlap_flags`, `accomplishment_reports`, `attachments`, `dashboard_notifications`, the budget tables, `prototype_*` and the old `barangays` columns

  It stays in git history; the team will tag the last commit before removal (`defense-prototype`). The prototype's CSS, chart, map code and report layouts are ported into the new views from that tag (`git show defense-prototype:<path>`), so nothing reusable is lost. The landing page (`/`) stays, with its sign-in link pointing to the new `/login`.
- **Reference code:** `{CAMPUS PREFIX}-{year of start date}-{3-digit sequence per campus and year}`, assigned **when a record is published** and never changed after that, even if its dates are edited. Drafts have no code; they are identified by id and labelled "Draft". The prefixes are MAIN, BAL, BIL, CAL, CAN and CLA, as in the prototype. Routes use the record's id (`/services/{id}`), and the code is displayed.
- **Files** are stored on the private local disk at `storage/app/private/documents/{service_id}/` under random names. They are never public.

### 6.4 Adding a completed service

One page with three steps:

1. **The report.** Pick the accomplishment report file (PDF, DOCX, JPG or PNG, up to 20 MB) and choose **Soft copy** or **Scanned copy**.
2. **The details.** The form fields from spec section 3.1:
   - title, category and description
   - municipality and barangays (chosen from the seeded list)
   - start and end dates
   - people reached and sectors
   - partners, amount spent and narrative

   When the template reader is added later, it fills these in, and each filled field shows a "Please check" mark until the uploader edits or confirms it.
3. **Supporting files.** Attendance sheets, photos, letter, proposal, MOA and other files, each tagged by kind and soft or scanned.

Buttons:
- **Save draft:** needs at least the report file and a title.
- **Publish:** Campus Uploaders only (a setting). Needs:
  - title, category, municipality, at least one barangay, start and end dates, people reached, at least one sector, narrative, and the report file
  - optional: description, partners, amount spent and the supporting files
- **Discard draft:** see section 5.

**Uploading:**
- The form sends everything in one request when Save draft or Publish is clicked, with an upload progress bar. Files are not uploaded on their own beforehand, so there are no orphan files and no temporary storage.
- When editing, files can be added or removed.
- Allowed types: PDF, DOCX, XLSX, JPG, PNG and WebP, up to 20 MB each. The report file itself must be PDF, DOCX, JPG or PNG.

- The `ReportReader` interface has one method, `read(Document): ReportFields`. The prototype binds `NullReportReader`, which returns nothing. Later, `TemplateReportReader` (text extraction plus OCR plus template rules) or an AI reader is bound instead, and nothing else changes.
- What the reader suggested is stored with the record, so EXTRACK can later count how many fields uploaders corrected. That count is the accuracy figure for the mentor and the thesis.

### 6.5 Screens

All screens use the university shell:

- **Header:** app logo and name on the left; notification center, user profile, settings and sign-out on the right.
- **Application switcher:** a list of BISU systems from a config file, as a placeholder until BISU provides it.
- **Left sidebar, and breadcrumbs** above each page title.
- **Footer** in three columns: the BISU logo (left), copyright and year (centre), social media links and the developers (right), plus a Terms and Conditions link (placeholder text).

| Screen | Route | Who | Contents |
|---|---|---|---|
| Dashboard (President) | `/dashboard` | President | Year totals (services, people reached, barangays, amount spent); services per campus chart, reused from the prototype; by category; recently published |
| Dashboard (campus) | `/dashboard` | Campus Uploader, Campus Director | "Add completed service" button (where allowed); the campus's drafts; campus year totals; recently published |
| Repository | `/services` | All | Search, filters (campus, year, category, municipality, sector), sort; one row per record with its first photo, place, dates and people reached; Export report button |
| Record page | `/services/{id}` | All who can see it | All fields, photo gallery, document list with View and Download, who encoded and published it, edit history; Edit, Publish, Discard, Delete and Export buttons by permission |
| Add or edit | `/services/create`, `/services/{id}/edit` | Campus Uploader | The three steps in section 6.4 |
| Map | `/map` | All | Leaflet and OpenStreetMap; a pin per barangay with published services, coloured by campus; filters for campus, category and year; reuses the prototype's map code and municipality coordinates |
| Users | `/users` | President | List, invite, edit role and campus, deactivate or reactivate, copy the invite link |
| Reference data | `/settings/*` | President | Campuses, categories, sectors and barangays: add, rename, hide |
| Activity log | `/activity` | President | Who did what and when, with filters |

- **Exports** reuse the prototype's two layouts: the consolidated report (from the repository's current filters) and the per-service report (from a record page), each as Save as PDF (browser print) or Download for Excel (CSV). Both are marked as sample layouts until BISU's template arrives.
- **Notifications** use Laravel's database notifications in the header's notification center. Prototype events: a record is published (to the President and that campus's Campus Director), and a user is invited or deactivated (to the President).
- **Loading states** use Motion for page and upload progress. Toasts confirm actions.

### 6.6 Look

- **Design tokens** in `resources/css/tokens.css`: primary #330066, secondary #FFCC00, accent #FFFFFF, plus a neutral grey scale (the builder's call until BISU publishes one), one type scale and one spacing scale. #FFCC00 is used for fills, highlights and the active sidebar marker, never as text on white.
- **Fonts:** Montserrat for headings and Public Sans for body text, as in the prototype, served from the app (no Google Fonts call), until BISU sets a typography standard.
- **Logo and name:** "EXTRACK" with the Crossing mark, recoloured to #330066 and #FFCC00, until BISU assigns an application identity. The BISU seal file from Engr. Libay replaces `images/logo.png` when it arrives.
- **Style:** minimal. The prototype's components (tiles, tables, chips, drawer, modals, gallery) are rebuilt as Blade components in the new tokens.
- **Light theme only** for the prototype. The prototype's dark mode is left out to save time; tokens are named so it can return later.

### 6.7 Activity log and privacy

- Every Action writes to `activity_log`: create, update (with changed fields), publish, delete, attach file, remove file, invite, deactivate. File views and downloads are logged too.
- Files are served by a `DocumentController` after `DocumentPolicy` allows it, with `Content-Disposition` set to the original file name. PDFs and images open inline; other types download.

### 6.8 Sample data

A seeder creates:

- the 6 campuses, categories and sectors (existing seeder), and Bohol's municipalities and barangays (PSGC)
- a President, a Campus Uploader and a Campus Director at Balilihan, and a Campus Uploader at each other campus
- about 15 published records and 2 drafts across campuses, reusing the current prototype's sample services
- generated placeholder PDFs and photos so every record has files

### 6.9 Testing

PHPUnit feature tests for:

- the permission matrix in section 5, for every role and action
- that other campuses' drafts are hidden
- the upload rules (file types, size, required fields for draft and for publish)
- that file download is refused without permission and logged with it
- that reference codes are unique per campus and year
- that a deactivated user cannot sign in
- that an invite link works once and expires

### 6.10 Rules the team can change after talking to the mentors

The team will review these defaults with their mentors. Each one is a setting in `config/extrack.php`, so changing one is a single edit, not a code change:

| Setting | Default |
|---|---|
| `publish.required` | The publish field list in section 6.4 |
| `draft.required` | `report_file`, `title` |
| `files.types`, `files.report_types`, `files.max_mb` | Section 6.4 |
| `permissions` | The section 5 table, as a role-to-ability map that the policies read (for example `discard_draft: [campus_uploader]`, `publish: [campus_uploader]`) |
| `role_labels` | University President, Campus Uploader, Campus Director |
| `code.format`, `code.assign_on` | `{PREFIX}-{YEAR}-{SEQ}`, `publish` (or `draft`) |
| `campus_prefixes` | MAIN, BAL, BIL, CAL, CAN, CLA |
| `demo` | `APP_DEMO`: shows the "Sign in as" list on the login page |
| `report_reader` | `null` (later `template` or an AI reader) |

The tests read these settings rather than hard-coding them, so they keep passing when a default changes.

## 7. Left to the builder

- The neutral palette, type scale and spacing scale.
- Which Blade components to build and how to name them.
- The placeholder file generator in the seeder.
- The exact layout of each screen, as long as it follows the shell in section 6.5 and the prototype's existing designs.
- The order of the work inside the two weeks.

## 8. Still open (decided by BISU or the mentor later; the prototype uses the defaults above)

- Roles and permissions (section 5), who uploads, the publish requirements and the reference code rule. All of these are settings (section 6.10).
- Zitadel or Clerk; the component library's technology; the standard JSON shape.
- Whether to use an AI model for reading reports (the mentor).
- The official templates for the template reader, and 3 to 5 filled-in examples (the team).
