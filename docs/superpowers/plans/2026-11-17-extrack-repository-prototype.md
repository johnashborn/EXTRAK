# EXTRACK Repository Prototype Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the defense prototype with the first working slice of the real EXTRACK: a Laravel app where campuses upload completed extension reports with their documents, and every role can search, view, map and export them, under server-enforced permissions.

**Architecture:** Laravel 13 MVC with Blade views and Alpine for small interactions. Controllers validate (Form Requests), authorise (policies that read `config/extrack.php`) and call one Action; Actions write the activity log. Files live on the private local disk and are served only through a policy-checked controller. Sign-in goes through an `Authenticator` seam so Zitadel can replace the local login later.

**Tech Stack:** PHP 8.3+ (the dev machine runs 8.5), Laravel 13, MySQL (dev) / SQLite in-memory (tests), PHPUnit 12, Vite 8, Alpine.js, Leaflet, Motion (motion.dev), self-hosted Montserrat and Public Sans (`@fontsource`).

**Spec:** [docs/superpowers/specs/2026-11-17-extrack-repository-prototype-design.md](../specs/2026-11-17-extrack-repository-prototype-design.md) (the design). The whole-system spec is [docs/spec.md](../../spec.md) v0.8. Section numbers below (§) refer to the design.

## Before you start (the user does these)

1. Commit all current work, then tag it: `git tag defense-prototype`. Every "port from the prototype" step below reads files with `git show defense-prototype:<path>`.
2. Branch: `git checkout -b repository-prototype`.
3. The user commits themselves. Each task ends with a commit step: run it only if the user has said commits are fine for this build; otherwise stage nothing and tell them the task is ready.
4. The local database is rebuilt with `php artisan migrate:fresh --seed` in Task 1. It wipes the old prototype's sample data, which is intended (§6.3).

## Global Constraints

- PSR-12; Eloquent only, no raw SQL strings (CLAUDE.md). Every migration must run on SQLite (tests) and MySQL (dev).
- Thin controllers: validate with a Form Request, `authorize()` through a policy, call one Action, return a view or redirect.
- Every permission and validation runs on the server (§2 anti-goals). Alpine only improves the form; never rely on it.
- Rules the mentors may change are read from `config/extrack.php` (§6.10), never hard-coded, in code **and** in tests.
- Colours: primary `#330066`, secondary `#FFCC00`, accent `#FFFFFF`. `#FFCC00` is never used as text on white (§6.6).
- No CDN or Google Fonts calls: Alpine, Leaflet, Motion and both fonts come from npm through Vite (§6.1, §6.6).
- Files: private disk, `storage/app/private/documents/{service_id}/`, random stored names, original name kept in the `documents` table (§6.3).
- Light theme only (§6.6). Copy is plain English; buttons are a verb and an object ("Save draft", "Publish record").
- Commands assume Git Bash in `c:\laragon\www\EXTRAK`. Tests: `php artisan test`.

## Review Focus

1. **A file whose extension lies** (an `.exe` renamed `report.pdf`) must be rejected by MIME type, not accepted by extension. → Task 5 test `test_upload_rejects_file_whose_content_is_not_its_extension`.
2. **Two Extension Directors of the same campus publishing at the same moment** must still get different codes. → Task 3 test `test_codes_stay_unique_when_generated_back_to_back` plus a unique index on `services.code`.
3. **A request bigger than PHP's limits** (Laragon's default `upload_max_filesize` is 2 MB, `post_max_size` 8 MB) must show "This file is larger than the 20 MB limit", not a blank page or a 419. → Task 5 sets the limits in the README and maps `PostTooLargeException` to a friendly redirect; test `test_oversized_post_shows_friendly_error`.
4. **A barangay that is not in the chosen municipality** (a tampered form) must be rejected. → Task 5 test `test_barangay_outside_chosen_municipality_is_rejected`.
5. **Original file names with ñ, spaces or quotes** ("Accomplishment Report – Sta. Niño.pdf") must download with that name intact. → Task 6 test `test_download_keeps_non_ascii_original_name`.

---

## File Structure

```text
config/extrack.php                         every rule the mentors may change (§6.10)
app/Enums/Role.php, ServiceState.php, DocumentKind.php, CopyType.php
app/Models/User.php (modified), Campus.php (trimmed), Category.php, BeneficiarySector.php,
           Municipality.php, Barangay.php (rewritten), Service.php, Document.php, ActivityLog.php
app/Models/Scopes/HideOtherCampusDrafts.php
app/Policies/ServicePolicy.php, DocumentPolicy.php, UserPolicy.php
app/Actions/Services/{CreateService,UpdateService,PublishService,DiscardDraft,DeleteService}.php
app/Actions/Documents/{AttachDocument,RemoveDocument}.php
app/Actions/Users/{InviteUser,UpdateUser,SetUserActive}.php
app/Auth/{Authenticator,LocalAuthenticator,FindUserForSignIn}.php
app/Services/{ActivityLogger,ServiceCodeGenerator,RepositoryQuery,DashboardStats,ReportExport,ApiResponse}.php
app/Services/ReportReading/{ReportReader,NullReportReader,ReportFields}.php
app/Notifications/{RecordPublished,UserAccessChanged}.php
app/Http/Controllers/{Auth/*,DashboardController,ServiceController,DocumentController,MapController,
           ExportController,UserController,ReferenceDataController,ActivityController,NotificationController}.php
app/Http/Requests/{SaveServiceRequest,DeleteServiceRequest,InviteUserRequest,UpdateUserRequest,AcceptInviteRequest}.php
database/data/bohol_municipalities.csv, bohol_barangays.csv
database/migrations/2026_11_17_*           fresh schema (old prototype migrations deleted)
database/seeders/{ReferenceSeeder,BoholPlacesSeeder,DemoSeeder,DatabaseSeeder}.php
resources/css/{tokens.css,app.css}, resources/js/{app.js,map.js,upload.js}
resources/views/layouts/app.blade.php, components/shell/*, auth/*, dashboard/*, services/*,
           map/*, exports/*, users/*, settings/*, activity/*
routes/web.php
tests/Feature/... (one file per task, named below)
```

---

### Task 1: Remove the defense prototype and lay the new schema base

**Files:**
- Delete: `app/Filament/`, `app/Providers/Filament/`, `app/Support/PrototypeRole.php`, `app/Http/Responses/LoginResponse.php`, `app/Http/Controllers/Prototype/`, `app/Services/Prototype/`, `app/Actions/Prototype/`, `resources/views/prototype/`, `resources/views/filament/`, `resources/views/welcome.blade.php`, `public/js/prototype/`, `public/css/prototype.css`, `public/css/admin-login*.css`, `public/css/filament/`, `public/js/filament/`, `tests/Feature/Prototype*Test.php`, every model in `app/Models/` except `User`, `Campus`, `Category`, `BeneficiarySector`, `Barangay`, and the four migrations dated `2026_10_28_*` and `2026_11_02_*`
- Modify: `composer.json` (remove `filament/filament`), `bootstrap/providers.php`, `app/Providers/AppServiceProvider.php:41` (drop the `prototypeActor` view share), `routes/web.php`, `database/seeders/DatabaseSeeder.php`
- Create: `database/migrations/2026_11_17_000001_create_reference_tables.php`, `database/seeders/ReferenceSeeder.php` (from `ExtensionReferenceSeeder` minus officials; delete the old file)
- Test: `tests/Feature/CleanSlateTest.php`

**Interfaces:**
- Produces: tables `campuses (id, name unique, municipality, timestamps)`, `categories (id, name unique, timestamps)`, `beneficiary_sectors (id, name unique, is_custom bool, position uint, timestamps)`; `ReferenceSeeder::CAMPUSES` (name ⇒ municipality, the six from the old seeder), `::CATEGORIES`, `::SECTORS` unchanged.

- [ ] **Step 1: Write the failing test** `CleanSlateTest`:
  - `test_landing_page_still_loads`: `get('/')` → 200.
  - `test_old_routes_are_gone`: `get('/prototype')` and `get('/admin')` → 404.
  - `test_reference_seeder_fills_the_lists`: after `seed(ReferenceSeeder::class)`, `Campus::count() === 6`, `Category::count() === 7`, `BeneficiarySector::count() === 10`.
- [ ] **Step 2: Run** `php artisan test --filter=CleanSlateTest`. Expected: FAIL (`/admin` returns 200 or 302; `ReferenceSeeder` not found).
- [ ] **Step 3: Delete the files listed above**, run `composer remove filament/filament`, and remove Filament's provider from `bootstrap/providers.php`. `routes/web.php` keeps only `Route::view('/', 'landing')`. Remove Tailwind too (`npm remove tailwindcss @tailwindcss/vite`, and its plugin in `vite.config.js`): the app uses plain CSS with tokens.
- [ ] **Step 4: Write the reference-tables migration and `ReferenceSeeder`**, keeping the old schema for these three tables. `DatabaseSeeder` calls only `ReferenceSeeder` for now.
- [ ] **Step 5: Run** `php artisan test --filter=CleanSlateTest` → PASS. Then run `php artisan migrate:fresh --seed` → no errors.
- [ ] **Step 6: Point the landing page's sign-in link at `/login`** (`resources/views/landing.blade.php`; the route arrives in Task 4, so the test only checks the link text exists: add `test_landing_links_to_login`: `get('/')->assertSee('href="'.url('/login').'"', false)`).
- [ ] **Step 7: Commit** `chore: remove defense prototype and Filament; add reference schema`.

---

### Task 2: Settings file, roles and users

**Files:**
- Create: `config/extrack.php`, `app/Enums/Role.php`, `database/migrations/2026_11_17_000002_add_roles_to_users.php`
- Modify: `app/Models/User.php`, `database/factories/UserFactory.php`
- Test: `tests/Feature/RolesTest.php`

**Interfaces:**
- Produces:
  - `enum Role: string { President = 'president'; ExtensionDirector = 'extension_director'; ExtensionAssistant = 'extension_assistant'; CampusDirector = 'campus_director'; public function label(): string }`. The labels are "University President", "Extension Director", "Extension Director's Assistant", "Campus Director".
  - `users` gains `role` (string 30), `campus_id` (nullable FK → campuses), `principal_id` (nullable FK → users), `is_active` (bool, default true), `auth_provider` (string 20, default `local`), `external_id` (nullable string, unique), `last_login_at` (nullable datetime). `password` becomes nullable (invited users have none yet).
  - `User::hasAbility(string $ability): bool`. True when `$this->role->value` is in `config("extrack.permissions.$ability")`. Unknown ability ⇒ false.
  - `User::isCampusBound(): bool` (every role except President). Relations `campus()`, `principal()`. Casts: `role` ⇒ `Role`, `is_active` ⇒ bool.
  - Factory states: `president()`, `extensionDirector(Campus $c)`, `assistant(User $director)` (same campus as the director), `campusDirector(Campus $c)`, `inactive()`.
  - `config/extrack.php`:

```php
return [
    'demo' => env('APP_DEMO', false),
    'report_reader' => env('EXTRACK_REPORT_READER', 'null'),   // later: 'template'
    'auth_provider' => env('AUTH_PROVIDER', 'local'),          // later: 'zitadel'
    'invite_days' => 7,
    'campus_prefixes' => ['Main' => 'MAIN', 'Balilihan' => 'BAL', 'Bilar' => 'BIL', 'Calape' => 'CAL', 'Candijay' => 'CAN', 'Clarin' => 'CLA'],
    'code' => ['format' => '{PREFIX}-{YEAR}-{SEQ}', 'seq_digits' => 3, 'assign_on' => 'publish'], // or 'draft'
    'draft' => ['required' => ['report_file', 'title']],
    'publish' => ['required' => ['title', 'category_id', 'municipality_id', 'barangay_ids', 'start_date', 'end_date', 'beneficiaries', 'sector_ids', 'narrative', 'report_file']],
    'files' => ['max_mb' => 20, 'types' => ['pdf', 'docx', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'], 'report_types' => ['pdf', 'docx', 'jpg', 'jpeg', 'png']],
    // ability => roles. "own campus" limits are applied by the policies, not here.
    'permissions' => [
        'view_published'   => ['president', 'extension_director', 'extension_assistant', 'campus_director'],
        'view_drafts'      => ['extension_director', 'extension_assistant'],
        'create'           => ['extension_director', 'extension_assistant'],
        'edit_draft'       => ['extension_director', 'extension_assistant'],
        'edit_published'   => ['extension_director'],
        'publish'          => ['extension_director'],
        'discard_draft'    => ['extension_director', 'extension_assistant'],
        'delete_published' => ['president'],
        'manage_users'     => ['president'],
        'manage_reference' => ['president'],
        'view_activity'    => ['president'],
    ],
    'app_switcher' => [/* ['name' => 'Procurement', 'url' => '#'], ['name' => 'Alumni Connect', 'url' => '#'] */],
    'footer' => ['social' => [/* ['name' => 'Facebook', 'url' => 'https://www.facebook.com/...'] */], 'developers' => 'EXTRACK team'],
];
```

- [ ] **Step 1: Write failing tests** in `RolesTest`:
  - `test_abilities_follow_the_config`: an Extension Director has `publish`, an assistant does not. After `config(['extrack.permissions.publish' => ['extension_director', 'extension_assistant']])`, the assistant does.
  - `test_unknown_ability_is_denied`.
  - `test_president_is_not_campus_bound` and `test_assistant_inherits_director_campus`.
- [ ] **Step 2: Run** `php artisan test --filter=RolesTest` → FAIL.
- [ ] **Step 3: Implement** the config, enum, migration, model changes and factory states.
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat: roles, abilities and extrack settings`.

---

### Task 3: Places, service records, documents and the activity log

**Files:**
- Create: `database/data/bohol_municipalities.csv`, `database/data/bohol_barangays.csv`, `database/migrations/2026_11_17_000003_create_places.php`, `..._000004_create_services.php`, `..._000005_create_documents_and_activity.php` (plus Laravel's notifications table via `php artisan make:notifications-table`), `app/Models/{Municipality,Barangay,Service,Document,ActivityLog}.php`, `app/Enums/{ServiceState,DocumentKind,CopyType}.php`, `app/Models/Scopes/HideOtherCampusDrafts.php`, `app/Services/{ServiceCodeGenerator,ActivityLogger}.php`, `database/seeders/BoholPlacesSeeder.php`, factories for `Service` and `Document`
- Test: `tests/Feature/PlacesTest.php`, `tests/Feature/ServiceCodeTest.php`, `tests/Feature/DraftVisibilityTest.php`

**Interfaces:**
- Produces:
  - `municipalities (id, psgc_code unique, name unique, latitude decimal(9,6), longitude decimal(9,6))`; `barangays (id, psgc_code unique, municipality_id FK, name, latitude/longitude nullable; unique municipality_id+name)`.
  - `services`: id, code (nullable, unique), campus_id, title, description (nullable), category_id (nullable), municipality_id (nullable), start_date/end_date (nullable), beneficiaries (nullable uint), partners (nullable), amount_spent (nullable decimal 12,2), narrative (nullable text), state (string, default `draft`), reader_suggestions (nullable json), encoded_by, published_by (nullable), published_at (nullable), deleted_by (nullable), delete_reason (nullable), timestamps, softDeletes. Pivots: `service_barangay (service_id, barangay_id)` and `service_sector (service_id, beneficiary_sector_id)`, both cascade on delete.
  - `documents`: id, service_id (cascade), kind, copy_type, original_name, path, mime, size (uint), extracted_text (nullable longText), uploaded_by, timestamps. `activity_log`: id, user_id (nullable), action (string 40), subject_type, subject_id, changes (json nullable), created_at.
  - `enum ServiceState: string { Draft = 'draft'; Published = 'published'; }`, `enum DocumentKind: string { AccomplishmentReport = 'accomplishment_report'; Attendance = 'attendance'; Photo = 'photo'; Letter = 'letter'; Proposal = 'proposal'; Moa = 'moa'; Other = 'other'; public function label(): string }`, `enum CopyType: string { Soft = 'soft'; Scanned = 'scanned'; }`.
  - `Service` relations: `campus()`, `category()`, `municipality()`, `barangays()`, `sectors()`, `documents()`, `reportDocument(): HasOne` (kind = accomplishment_report), `photos()`, `encoder()`, `publisher()`. Global scope `HideOtherCampusDrafts`: when the signed-in user is campus-bound, hide drafts not of their campus; the President sees no drafts; when nobody is signed in (console, seeders), apply no filter.
  - `ServiceCodeGenerator::next(Campus $campus, int $year): string`. It applies `config('extrack.code.format')` with `{PREFIX}` from `campus_prefixes[$campus->name]` and `{SEQ}` zero-padded to `seq_digits`, at one more than the highest existing sequence for that prefix and year (soft-deleted rows included, so codes are never reused). It runs inside `DB::transaction` with `lockForUpdate()` on the campus row.
  - `ActivityLogger::log(?User $by, string $action, Model $subject, array $changes = []): ActivityLog`.
- Data: `bohol_municipalities.csv` columns `psgc_code,name,latitude,longitude` (48 rows: 47 municipalities and Tagbilaran City). `bohol_barangays.csv` columns `psgc_code,municipality_psgc_code,name,latitude,longitude` (1,109 rows; coordinates may be blank). Source: the PSA PSGC publication (psa.gov.ph/classification/psgc), latest quarter. Municipality coordinates come from each town's centre on OpenStreetMap. Put the source and date in a comment at the top of `BoholPlacesSeeder`. Keep the 10 town coordinates from `git show defense-prototype:public/js/prototype/extrack.js` (lines 16–19) where they agree.

- [ ] **Step 1: Build the two CSV files** from the PSGC file (filter province code for Bohol), and the municipality coordinates.
- [ ] **Step 2: Write failing tests:**
  - `PlacesTest::test_bohol_list_is_complete`: after `seed(BoholPlacesSeeder::class)`, there are 48 municipalities and 1,109 barangays. `Municipality::where('name', 'Tagbilaran City')->exists()`.
  - `PlacesTest::test_coordinates_are_inside_bohol`: every municipality has latitude 9.5–10.25 and longitude 123.65–124.65. So does every barangay that has coordinates.
  - `ServiceCodeTest::test_code_uses_prefix_year_and_sequence`: two calls for Balilihan 2026 ⇒ `BAL-2026-001`, `BAL-2026-002`. Calape 2026 ⇒ `CAL-2026-001`.
  - `ServiceCodeTest::test_soft_deleted_codes_are_not_reused`.
  - `ServiceCodeTest::test_codes_stay_unique_when_generated_back_to_back`: 20 sequential `next()` calls each saved onto a service give 20 distinct codes. Saving a duplicate code throws `UniqueConstraintViolationException`.
  - `ServiceCodeTest::test_format_comes_from_config`: with `extrack.code.format = '{YEAR}/{PREFIX}/{SEQ}'` ⇒ `2026/BAL/001`.
  - `DraftVisibilityTest::test_drafts_are_seen_only_by_their_campus_uploaders`: a Balilihan draft is visible to a Balilihan Extension Director and assistant, and hidden from a Calape Extension Director, a Balilihan Campus Director and the President. A published record is visible to all of them.
- [ ] **Step 3: Run** `php artisan test --filter='PlacesTest|ServiceCodeTest|DraftVisibilityTest'` → FAIL.
- [ ] **Step 4: Implement** the migrations, enums, models, scope, generator, logger and seeder.
- [ ] **Step 5: Run** → PASS.
- [ ] **Step 6: Commit** `feat: places, service records, documents and activity log`.

---

### Task 4: Sign-in through the Authenticator seam, invites and the Users page

**Files:**
- Create: `app/Auth/{Authenticator,LocalAuthenticator,FindUserForSignIn}.php`, `app/Http/Controllers/Auth/{LoginController,InviteController}.php`, `app/Http/Controllers/UserController.php`, `app/Http/Controllers/DashboardController.php` (stub `index()` behind `auth`, returning a plain "Dashboard" view; Task 7 wraps it in the shell and Task 9 fills it), `app/Actions/Users/{InviteUser,UpdateUser,SetUserActive}.php`, `app/Http/Requests/{InviteUserRequest,UpdateUserRequest,AcceptInviteRequest}.php`, `app/Policies/UserPolicy.php`, `app/Http/Middleware/EnsureUserIsActive.php`, views `auth/login.blade.php`, `auth/accept-invite.blade.php`, `users/index.blade.php`, `users/form.blade.php`
- Modify: `bootstrap/app.php` (append `EnsureUserIsActive` to the `web` group), `routes/web.php`, `app/Providers/AppServiceProvider.php` (bind `Authenticator` from `config('extrack.auth_provider')`)
- Test: `tests/Feature/SignInTest.php`, `tests/Feature/InviteTest.php`, `tests/Feature/UsersPageTest.php`

**Interfaces:**
- Produces:
  - `interface Authenticator { public function showLogin(Request $r): Response; public function authenticate(Request $r): User; }`. `LocalAuthenticator::showLogin` renders the form. `authenticate` validates the email and password, throws `ValidationException` with "These details don't match an active account." on failure, and returns the user. A future `ZitadelAuthenticator` redirects in `showLogin` and handles the Socialite callback in `authenticate`.
  - `FindUserForSignIn::handle(string $email, string $provider, ?string $externalId = null): ?User`. It returns an existing **active** user only; it never creates one (invite-only). On first Zitadel sign-in it stores `external_id` and sets `auth_provider`.
  - Routes: `GET /login`, `POST /login`, `POST /logout`, `GET /invites/{user}` (signed), `POST /invites/{user}` (signed), and `/users` resource (index, create, store, edit, update) plus `PATCH /users/{user}/active`. `POST /login/demo/{user}` exists only when `config('extrack.demo')` is true. After sign-in the user goes to `/dashboard`; `last_login_at` is set.
  - `InviteUser::handle(User $by, array $data): array{user: User, url: string}`. `$data` has name, email, role, campus_id, principal_id. It creates the user with a null password and returns `URL::temporarySignedRoute('invites.show', now()->addDays(config('extrack.invite_days')), ['user' => $id])`. It sends nothing in the prototype (mail stays on the log driver), and the Users page shows the URL with a Copy button.
  - `UpdateUser::handle(User $by, User $target, array $data): User`. `SetUserActive::handle(User $by, User $target, bool $active): User`. A user can't deactivate themselves. All three log to the activity log.
  - Validation in `InviteUserRequest`: role is required and must be a `Role`. `campus_id` is required unless the role is President. `principal_id` is required for an assistant and must be an active Extension Director of the same campus. Email must be unique.
  - `UserPolicy`: every method checks `hasAbility('manage_users')`.

- [ ] **Step 1: Write failing tests:**
  - `SignInTest::test_active_user_signs_in_and_lands_on_dashboard` (`assertRedirect('/dashboard')`, `last_login_at` not null).
  - `test_wrong_password_shows_error`.
  - `test_deactivated_user_cannot_sign_in`.
  - `test_deactivated_user_is_signed_out_on_next_request`: sign in, deactivate the user directly in the database, `get('/dashboard')` → redirect to `/login`, and the session no longer holds the user.
  - `test_demo_buttons_only_when_demo_is_on` (the login page shows "Sign in as" only with `extrack.demo=true`; `POST /login/demo/{id}` → 404 when off).
  - `test_find_user_never_creates_accounts`.
  - `InviteTest::test_invite_link_sets_password_once`: open the link → 200; post a password → signed in. The same link afterwards → 410 with "This invite has already been used".
  - `test_invite_link_expires_after_configured_days`: `travel(8)->days()` → 403.
  - `test_assistant_invite_requires_a_director_of_the_same_campus`.
  - `UsersPageTest::test_only_president_manages_users`: the Extension Director, assistant and Campus Director each get 403 on `/users`.
  - `test_president_cannot_deactivate_self`.
- [ ] **Step 2: Run** `php artisan test --filter='SignInTest|InviteTest|UsersPageTest'` → FAIL.
- [ ] **Step 3: Implement.**
  - The invite "used" check is `password !== null`.
  - `EnsureUserIsActive` logs out an inactive user, invalidates the session and redirects to `/login` with "Your account is deactivated."
  - The login view reuses the defense prototype's login art (`git show defense-prototype:resources/views/filament/login-art.blade.php` and `public/css/admin-login-glass.css`), restyled to the tokens in Task 7. Until Task 7, a bare form is fine.
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat: invite-only sign-in behind an Authenticator seam, Users page`.

---

### Task 5: Add, edit, publish and discard records

**Files:**
- Create: `app/Policies/ServicePolicy.php`, `app/Actions/Services/{CreateService,UpdateService,PublishService,DiscardDraft}.php`, `app/Actions/Documents/{AttachDocument,RemoveDocument}.php`, `app/Http/Requests/SaveServiceRequest.php`, `app/Http/Controllers/ServiceController.php` (create, store, edit, update, publish, destroy-draft), `app/Services/ApiResponse.php`, `app/Services/ReportReading/{ReportReader,NullReportReader,ReportFields}.php`, views `services/form.blade.php` with partials `_step-report`, `_step-details`, `_step-files`, `resources/js/upload.js`
- Modify: `bootstrap/app.php` (render `PostTooLargeException`), `app/Providers/AppServiceProvider.php` (bind `ReportReader` by `config('extrack.report_reader')`: `null` ⇒ `NullReportReader`), `README.md` (php.ini limits)
- Test: `tests/Feature/SaveServiceTest.php`, `tests/Feature/ServicePermissionsTest.php`

**Interfaces:**
- Consumes: `User::hasAbility`, `ServiceCodeGenerator::next`, `ActivityLogger::log` and the enums (Tasks 2 and 3).
- Produces:
  - `ServicePolicy`. `view` is allowed when published and `view_published`, or a draft of the user's own campus and `view_drafts`. `create` needs `create`. `update` needs `edit_draft` or `edit_published` for the state, on the user's own campus. `publish` needs `publish`, own campus, and a draft. `discard` needs `discard_draft`, own campus, and a draft. `delete` needs `delete_published` and a published record.
  - `CreateService::handle(User $by, array $fields, UploadedFile $report, CopyType $reportCopy, array $attachments = []): Service`. Each attachment is `['file' => UploadedFile, 'kind' => DocumentKind, 'copy_type' => CopyType]`. `$fields` keys match the `publish.required` names, plus `description`, `partners`, `amount_spent` and `municipality_id`. The record's campus is the user's campus. It runs in one transaction; files are stored after the rows are created and deleted again if the transaction fails. If `code.assign_on = draft`, it assigns the code here. It calls `ReportReader::read()` on the report document and stores the returned `ReportFields::toArray()` in `reader_suggestions`.
  - `UpdateService::handle(User $by, Service $s, array $fields, array $attachments = [], array $removeDocumentIds = []): Service`. It logs only the changed fields. The report document can be replaced, but never left missing.
  - `PublishService::handle(User $by, Service $s): Service`. It re-validates against `publish.required` (throwing `ValidationException`), assigns the code if missing using the year of `start_date`, and sets the state, `published_by` and `published_at`.
  - `DiscardDraft::handle(User $by, Service $s): void`. It force-deletes the record and its files.
  - `AttachDocument::handle(User $by, Service $s, UploadedFile $f, DocumentKind $kind, CopyType $copy): Document`. It stores the file as `documents/{service_id}/{Str::random(40)}.{ext}` on the `local` disk. `RemoveDocument::handle(User $by, Document $d): void`.
  - `interface ReportReader { public function read(Document $report): ReportFields; }`. `final class ReportFields { public function __construct(public readonly array $fields = []) {} public function toArray(): array }`. `NullReportReader` returns an empty `ReportFields`.
  - `ApiResponse::ok(mixed $data = null, array $meta = [], int $status = 200): JsonResponse` ⇒ `{"data":…, "meta":…}`. `ApiResponse::error(string $message, array $errors = [], int $status = 422): JsonResponse` ⇒ `{"message":…, "errors":…}`. This is the one place to change when BISU publishes its JSON shape.
  - `SaveServiceRequest`:
    - Rules depend on the submit button (`intent` = `draft` | `publish`). The required fields come from `config("extrack.$intent.required")`; all other fields are nullable.
    - Files are checked with `mimes:` built from `files.types` (or `files.report_types` for the report) and `max:` = `files.max_mb * 1024`.
    - `barangay_ids.*` must exist in `barangays` with `municipality_id` = the submitted `municipality_id`.
    - `end_date` must be after or equal to `start_date`, and `beneficiaries` must be an integer of at least 0.
    - The form posts with XHR (`upload.js`, a progress bar and Motion for the loading state) and accepts JSON. Success returns `ApiResponse::ok(['redirect' => route('services.show', $s)])`; validation errors return a 422 with the error shape above. Without JS, the normal form post still works.
  - Form layout (§6.4): Step 1 has the report file and a Soft / Scanned choice. Step 2 has the details; a field filled from `reader_suggestions` shows a "Please check" mark until it is edited (prototype: never shown, because the reader is null). Step 3 has repeatable rows of file + kind + soft/scanned. The buttons are "Save draft", "Publish record" (shown only when the user can publish) and "Discard draft" (on edit, with a confirm dialog).

- [ ] **Step 1: Write failing tests.**

  `SaveServiceTest`:
  - `test_draft_needs_only_report_and_title`.
  - `test_publish_requires_every_configured_field`: publishing a draft missing the narrative → 422 with a `narrative` error.
  - `test_publish_rules_follow_config`: remove `narrative` from `extrack.publish.required` → the same publish succeeds.
  - `test_published_record_gets_code_from_start_year`: start date 2026-03-02, Balilihan → `BAL-2026-001`; editing the start date to 2027 keeps that code.
  - `test_upload_rejects_file_whose_content_is_not_its_extension`: `UploadedFile::fake()->createWithContent('report.pdf', "MZ\x90\x00…")` → 422 on `report_file`.
  - `test_file_over_size_limit_is_rejected`: `UploadedFile::fake()->create('big.pdf', 21 * 1024, 'application/pdf')` → 422.
  - `test_oversized_post_shows_friendly_error`: throw `PostTooLargeException` through a test route → redirect back with "This file is larger than the 20 MB limit".
  - `test_barangay_outside_chosen_municipality_is_rejected`.
  - `test_files_are_stored_privately_with_random_names`: `Storage::fake('local')`; the stored path starts with `documents/{id}/`, does not contain the original name, and nothing is written under `public`.
  - `test_discard_removes_draft_and_files`.
  - `test_every_action_writes_activity_log`: create ⇒ `created`, update ⇒ `updated` with the changed keys only, publish ⇒ `published`, discard ⇒ `discarded`, attach ⇒ `document_added`.

  `ServicePermissionsTest`, using a data provider over `[role, action, own campus?, expected status]` for every row of the §5 table. Actions: create, edit draft, edit published, publish, discard. Expect 403 where denied, including:
  - an assistant trying to publish
  - a Calape Extension Director editing a Balilihan record
  - the Campus Director or the President creating a record
- [ ] **Step 2: Run** `php artisan test --filter='SaveServiceTest|ServicePermissionsTest'` → FAIL.
- [ ] **Step 3: Implement.** In `README.md`, document `upload_max_filesize = 25M` and `post_max_size = 200M` for Laragon's php.ini (Menu → PHP → php.ini).
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat: add, edit, publish and discard service records`.

---

### Task 6: Viewing and downloading documents

**Files:**
- Create: `app/Policies/DocumentPolicy.php`, `app/Http/Controllers/DocumentController.php`
- Modify: `routes/web.php` (`GET /documents/{document}` view, `GET /documents/{document}/download`)
- Test: `tests/Feature/DocumentAccessTest.php`

**Interfaces:**
- Consumes: `ServicePolicy::view` (Task 5), `ActivityLogger` (Task 3).
- Produces: `DocumentPolicy::view(User, Document)` delegates to `ServicePolicy::view` on its service. `DocumentController::show` streams inline for PDF and images and as a download otherwise. `download` always downloads. Both log `document_viewed` or `document_downloaded`. Both send `Content-Disposition` with the original name, using `Symfony HeaderUtils::makeDisposition($type, $originalName, Str::ascii($originalName))`, and add `X-Content-Type-Options: nosniff`.

- [ ] **Step 1: Write failing tests:**
  - `test_guest_is_redirected_to_login`.
  - `test_other_campus_cannot_open_draft_files` → 403.
  - `test_any_role_opens_published_files` → 200.
  - `test_pdf_opens_inline_and_docx_downloads`.
  - `test_download_keeps_non_ascii_original_name`: original "Accomplishment Report – Sta. Niño.pdf"; the header contains `filename*=utf-8''` with that name encoded.
  - `test_view_and_download_are_logged`.
- [ ] **Step 2: Run** `php artisan test --filter=DocumentAccessTest` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat: policy-checked document view and download`.

---

### Task 7: University shell, design tokens and front-end build

**Files:**
- Create: `resources/css/tokens.css`, rewrite `resources/css/app.css` (port components from `git show defense-prototype:public/css/prototype.css`: tiles, tables, chips, drawer, modal, gallery, buttons, filters), `resources/js/app.js` (Alpine start, Motion helpers `window.extrack.loading(el)`), `resources/views/layouts/app.blade.php`, `resources/views/components/shell/{header,sidebar,breadcrumbs,footer,app-switcher,notification-center,toast}.blade.php`, `resources/views/components/brand-mark.blade.php` (Crossing mark from `public/images/brand/extrack-mark.svg`, recoloured to `#330066` arms and `#FFCC00` gem), `app/Http/Controllers/NotificationController.php`
- Modify: `package.json` (`npm install alpinejs leaflet motion @fontsource/montserrat @fontsource/public-sans`), `vite.config.js` (inputs `resources/css/app.css`, `resources/js/app.js`, `resources/js/map.js`, `resources/js/upload.js`), `resources/views/auth/*` (use the tokens)
- Test: `tests/Feature/ShellTest.php`

**Interfaces:**
- Produces:
  - `<x-layouts.app :title="string" :breadcrumbs="array<array{label:string, url:?string}>">`. Every signed-in page uses it.
  - **Header:** brand mark and "EXTRACK" on the left. On the right, in order: the app switcher (from `config('extrack.app_switcher')`; shows "No other BISU systems yet" when empty), the notification center (the 10 latest database notifications, an unread count, and "Mark all as read" posting to `POST /notifications/read`), and the user menu (name, role label, Settings link for the President, Sign out).
  - **Sidebar by role:**
    - everyone: Dashboard, Repository, Map
    - Extension Director and assistant: + "Add completed service"
    - President: + Users, Reference data, Activity log
  - **Footer, three columns:** the BISU seal (`images/logo.png`) on the left; "© {year} Bohol Island State University" in the centre; social links and `footer.developers` on the right; a "Terms and Conditions" link to `/terms` (a placeholder page saying the text will come from BISU).
  - **Tokens** in `tokens.css`: `--color-primary:#330066; --color-secondary:#FFCC00; --color-accent:#FFFFFF;` and a neutral grey scale `--gray-50…--gray-900`, the type scale `--text-xs…--text-3xl`, the spacing scale `--space-1…--space-16`, radii, and `--font-display: 'Montserrat'; --font-body: 'Public Sans'`.
  - **Toasts:** a session flash `status` shows as a toast.

- [ ] **Step 1: Write failing tests:**
  - `ShellTest::test_shell_has_header_footer_and_breadcrumbs`: as any user, `get('/dashboard')` contains the brand name, the user's name, "© ".date('Y'), "Terms and Conditions" and the breadcrumb.
  - `test_sidebar_matches_role`: the President sees "Users", the Extension Director sees "Add completed service" but not "Users", the Campus Director sees neither.
  - `test_notifications_mark_as_read`.
  - `test_no_external_font_or_cdn_links`: the dashboard HTML does not contain `fonts.googleapis`, `unpkg`, `cdnjs` or `jsdelivr`.
- [ ] **Step 2: Run** `php artisan test --filter=ShellTest` → FAIL. (The `DashboardController` stub from Task 4 now renders `<x-layouts.app>`; Task 9 fills in its content.)
- [ ] **Step 3: Implement**, then `npm install && npm run build`.
- [ ] **Step 4: Run** → PASS. Open `http://127.0.0.1:8000/login` in a browser and check the header, sidebar and footer at 1440 px and 390 px widths: there must be no sideways scroll, and the secondary yellow must not be used as text.
- [ ] **Step 5: Commit** `feat: BISU application shell and design tokens`.

---

### Task 8: Repository, record page and deleting published records

**Files:**
- Create: `app/Services/RepositoryQuery.php`, `app/Actions/Services/DeleteService.php`, `app/Http/Requests/DeleteServiceRequest.php`, views `services/index.blade.php`, `services/show.blade.php` (with a photo lightbox ported from the prototype's gallery), `resources/views/services/_row.blade.php`
- Modify: `ServiceController` (index, show, delete), `routes/web.php`
- Test: `tests/Feature/RepositoryTest.php`, `tests/Feature/DeleteServiceTest.php`

**Interfaces:**
- Consumes: `ServicePolicy`, `HideOtherCampusDrafts`, `ActivityLog` (Tasks 3 and 5).
- Produces:
  - `RepositoryQuery::apply(Builder $q, array $filters): Builder`. Filters are `q`, `campus_id`, `year` (year of `end_date`), `category_id`, `municipality_id`, `sector_id`, and `sort` ∈ `newest|oldest|reached|title`; the default is `newest` by `end_date`. `q` matches title, description, narrative, code, partners, and barangay or municipality names, case-insensitive. The repository lists published records only; drafts appear on the dashboard (Task 9). The page shows 20 per page, and the query string is kept for the export button.
  - Record page:
    - all fields
    - the photo gallery
    - documents grouped by kind, each with View and Download links and a Soft/Scanned label
    - "Encoded by … on …" and "Published by … on …"
    - an edit history from `activity_log` (user, action, date, changed field names)
    - buttons by policy: Edit, Publish, Discard, Delete, and Export report (Task 11)
  - `DeleteService::handle(User $by, Service $s, string $reason): void`. It soft-deletes the record and sets `deleted_by` and `delete_reason`; the reason is required, minimum 10 characters. Deleted records are hidden everywhere.

- [ ] **Step 1: Write failing tests:**
  - `RepositoryTest::test_lists_published_only`.
  - `test_search_matches_title_code_and_barangay`.
  - `test_filters_by_campus_year_category_municipality_and_sector`, one assertion each.
  - `test_sort_by_people_reached`.
  - `test_record_page_shows_history_and_documents`.
  - `test_buttons_follow_permissions`: the President sees "Delete" but not "Edit", and the Extension Director of the same campus sees "Edit" but not "Delete".
  - `DeleteServiceTest::test_only_president_deletes_published_with_reason`: no reason → 422; the Extension Director → 403; the President with a reason → the record is gone from the repository and the activity log has `deleted` with the reason.
- [ ] **Step 2: Run** `php artisan test --filter='RepositoryTest|DeleteServiceTest'` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat: repository search, record page and deletion`.

---

### Task 9: Dashboards

**Files:**
- Create: `app/Services/DashboardStats.php`, views `dashboard/president.blade.php`, `dashboard/campus.blade.php`, `dashboard/_campus-chart.blade.php` (port from `git show defense-prototype:resources/views/prototype/partials/dash-campus-chart.blade.php` and its `.cc*` CSS, now fed by the server)
- Modify: `app/Http/Controllers/DashboardController.php`
- Test: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Produces: `DashboardStats::forYear(int $year, ?Campus $campus = null): array`. It returns `['services' => int, 'reached' => int, 'barangays' => int, 'spent' => float, 'by_campus' => list<array{campus:string, services:int, reached:int}>, 'by_category' => list<array{category:string, services:int}>, 'recent' => Collection<Service>]`, built from published records whose `end_date` falls in `$year`. The year comes from the `?year=` query and defaults to the current year; the selector lists the years that have records.
  - **President dashboard:** the four totals, the per-campus chart (one column per campus; a click opens the repository filtered to that campus and year), by category, and recently published (5).
  - **Campus dashboard:** "Add completed service" (when `create` is allowed), "Your drafts" (own campus, newest first, showing title, last edited and who encoded it), campus totals, and recently published from the campus.

- [ ] **Step 1: Write failing tests:**
  - `test_totals_count_published_records_in_year_only` (drafts, deleted records and other years are excluded; barangays are counted once).
  - `test_campus_dashboard_shows_own_drafts`.
  - `test_campus_director_sees_no_add_button`.
- [ ] **Step 2: Run** `php artisan test --filter=DashboardTest` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat: President and campus dashboards`.

---

### Task 10: Map

**Files:**
- Create: `app/Http/Controllers/MapController.php`, `resources/views/map/index.blade.php`, `resources/js/map.js` (port the Leaflet setup and pin styling from `git show defense-prototype:public/js/prototype/extrack.js`, around lines 1040–1120, using Leaflet from npm)
- Test: `tests/Feature/MapTest.php`

**Interfaces:**
- Produces: `GET /map` (page) and `GET /map/data?campus_id=&category_id=&year=` returning `ApiResponse::ok(list<{barangay_id, name, municipality, lat, lng, services: list<{id, code, title, campus, end_date, url}>}>)`. `lat` and `lng` are the barangay's own when present, otherwise the municipality's, with a small fixed offset derived from `barangay_id` so pins in one town don't stack. Pins are coloured by campus; clicking one lists its services, each linking to the record page.

- [ ] **Step 1: Write failing tests:**
  - `test_map_data_has_published_only`.
  - `test_falls_back_to_municipality_coordinates`.
  - `test_filters_apply`.
  - `test_response_uses_standard_shape` (top-level `data` key).
- [ ] **Step 2: Run** `php artisan test --filter=MapTest` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** → PASS, then check in a browser that tiles and pins load.
- [ ] **Step 5: Commit** `feat: map of published services`.

---

### Task 11: Report exports

**Files:**
- Create: `app/Services/ReportExport.php`, `app/Http/Controllers/ExportController.php`, views `exports/consolidated.blade.php`, `exports/service.blade.php` (port the layouts from `git show defense-prototype:resources/views/prototype/partials/consolidated.blade.php` and the `.rp-*` CSS; both are print-friendly pages)
- Test: `tests/Feature/ExportTest.php`

**Interfaces:**
- Consumes: `RepositoryQuery` (Task 8).
- Produces:
  - `GET /exports/consolidated?{repository filters}`: the print page. `GET /exports/consolidated.csv?...`: CSV.
  - `GET /services/{id}/export`: the print page. `GET /services/{id}/export.csv`: CSV.
  - `ReportExport::consolidated(array $filters): array` and `ReportExport::service(Service $s): array` return the rows the views and CSV share. `ReportExport::toCsv(array $rows): string` adds a UTF-8 BOM, CRLF line endings, and prefixes `'` to any cell starting with `= + - @ \t \r` (as in the prototype's `saveCsv`).
  - Both print pages carry a small "Sample layout" note in the screen toolbar only (hidden when printing) until BISU's template arrives. Signers: Prepared by (the campus's Extension Director), Noted by (the Campus Director), Approved by (the President), from the users table. When a role has no user, the line shows the role name.

- [ ] **Step 1: Write failing tests:**
  - `test_consolidated_respects_filters_and_totals`.
  - `test_csv_escapes_formula_cells`: a title "=HYPERLINK(...)" becomes `'=HYPERLINK(...)`.
  - `test_csv_has_bom_and_peso_amounts`.
  - `test_service_export_denied_for_hidden_draft` → 403.
- [ ] **Step 2: Run** `php artisan test --filter=ExportTest` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat: consolidated and per-service exports`.

---

### Task 12: Notifications, reference data and the activity log page

**Files:**
- Create: `database/migrations/2026_11_17_000006_add_is_hidden_to_reference_tables.php`, `app/Notifications/{RecordPublished,UserAccessChanged}.php` (database channel), `app/Http/Controllers/{ReferenceDataController,ActivityController}.php`, views `settings/{index,list}.blade.php`, `activity/index.blade.php`, `resources/views/terms.blade.php`
- Modify: `PublishService` (notify), `InviteUser` and `SetUserActive` (notify), `routes/web.php`
- Test: `tests/Feature/NotificationsTest.php`, `tests/Feature/ReferenceDataTest.php`, `tests/Feature/ActivityPageTest.php`

**Interfaces:**
- Produces:
  - `RecordPublished(Service $s)` goes to every active President and that campus's active Campus Director. Its text is "{campus} Campus published {title} ({code})." and it links to the record page.
  - `UserAccessChanged(User $target, string $change)` goes to every active President except the actor. `$change` is `invited`, `deactivated` or `reactivated`.
  - `/settings/{type}` where `type` ∈ `campuses|categories|sectors|barangays`: list, add and rename. Hiding uses a new `is_hidden` boolean column on `categories`, `beneficiary_sectors` and `barangays`. Hidden items stay on old records but are left out of form choices. Campuses can only be renamed. Barangays are filtered by municipality. All of it needs `manage_reference`.
  - `/activity`: paginated, filterable by user, action and date range. Needs `view_activity`.

- [ ] **Step 1: Write failing tests:**
  - `NotificationsTest::test_publish_notifies_president_and_campus_director_only`.
  - `test_deactivation_notifies_other_presidents`.
  - `ReferenceDataTest::test_hidden_category_not_offered_but_kept_on_records`.
  - `test_only_president_manages_reference_data`.
  - `ActivityPageTest::test_president_filters_log_by_action`.
  - `test_others_get_403`.
- [ ] **Step 2: Run** `php artisan test --filter='NotificationsTest|ReferenceDataTest|ActivityPageTest'` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat: notifications, reference data and activity log pages`.

---

### Task 13: Demo data, README and the full check

**Files:**
- Create: `database/seeders/DemoSeeder.php`, `database/seeders/data/demo_services.php` (about 15 published and 2 draft records; titles, places, dates, numbers and narratives taken from the defense prototype's sample requests in `git show defense-prototype:public/js/prototype/extrack.js`, lines ~270–470, mapped to real PSGC barangays)
- Modify: `database/seeders/DatabaseSeeder.php` (ReferenceSeeder, BoholPlacesSeeder, then DemoSeeder when `config('extrack.demo')` or the `local` environment), `README.md`, `.env.example` (`APP_DEMO=true`, `AUTH_PROVIDER=local`, `EXTRACK_REPORT_READER=null`)
- Test: `tests/Feature/DemoSeederTest.php`

**Interfaces:**
- Produces demo users, all with the password `password`:
  - `president@extrack.test`
  - `ed.balilihan@extrack.test`
  - `assistant.balilihan@extrack.test`
  - `cd.balilihan@extrack.test`
  - `ed.{campus}@extrack.test` for each other campus

  The names are taken from the defense prototype's people (for example Sir Marc Olaguir, Miss Jaquelyn L. Omapas, Mrs. Marietta Macalolot, Mr. Anthony M. Penaso). Every record gets a generated placeholder report PDF (a one-page PDF written with a minimal hand-built PDF string) and 1 to 3 placeholder photos (GD-drawn PNGs labelled "Sample photo"). Published records get codes through `ServiceCodeGenerator`.

- [ ] **Step 1: Write failing tests:**
  - `test_demo_seed_creates_publishable_records`: every published demo record passes the `publish.required` rules.
  - `test_every_role_has_a_demo_user`.
  - `test_files_exist_on_disk` (`Storage::fake`).
- [ ] **Step 2: Run** `php artisan test --filter=DemoSeederTest` → FAIL.
- [ ] **Step 3: Implement the seeder.** Rewrite the README's run section with these steps:
  1. Start MySQL in Laragon.
  2. Set the php.ini limits.
  3. Run `composer install`, `npm install` and `npm run build`.
  4. Run `php artisan migrate:fresh --seed` and `php artisan serve`.

  Also list the demo accounts, and note that `APP_DEMO=false` hides the "Sign in as" buttons.
- [ ] **Step 4: Run the whole suite:** `php artisan test` → all pass. Then `php artisan migrate:fresh --seed`, `npm run build` and `php artisan serve`.
- [ ] **Step 5: Manual walk-through in a browser:**
  1. Sign in as each role with the demo buttons.
  2. As the Balilihan assistant, add a record with a scanned report and two photos, and save it as a draft.
  3. As the Extension Director, publish it, and check that it gets `BAL-2026-0xx`.
  4. As the President, see the notification, find the record by barangay, open its PDF, export both reports, and delete it with a reason.
  5. Check the activity log.
  6. Check the map.

  Note anything broken and fix it before calling the task done.
- [ ] **Step 6: Commit** `feat: demo data and run instructions`.
