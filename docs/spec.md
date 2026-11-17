# EXTRACK system specification

Status: draft v0.8 (2026-11-17). After the research proposal defense, the panel scaled the system down to a **repository of completed extension services**. This version adds the BISU Software and Integration Standardization (section 1.4). Anything marked **[Open]** is not final, and section 12 lists those items. Record changes in the [change log](#13-change-log).

## 1. Purpose

EXTRACK is the central repository of extension services completed by Bohol Island State University (BISU) campuses. Each campus records its finished services with the accomplishment report, photos and supporting documents. EXTRACK stores the files, makes every record searchable, shows the work on a map of Bohol, and produces reports across campuses.

### 1.1 Scope

| In scope | Removed by the panel (2026-11-09) |
|---|---|
| Recording completed services (accomplishment reports) | Extension requests and the request form |
| Storing and serving the documents and photos for each service | Campus Director and University President approval of requests |
| Search, filters and a read-only view of every record | Request statuses (Draft, Submitted, For President, Returned, Declined, Approved, Ongoing) |
| Map of services across Bohol | Overlap check before approval |
| Dashboards with totals by campus, category and year | Yearly campus extension budgets, allocations and top-ups |
| Consolidated and per-service report export | Approval notifications and overdue warnings |
| Accounts created by the University President (super admin) | Public sign-up and the role picker on the login page |

EXTRACK no longer decides or approves anything. It records what was done.

### 1.2 Provided by the university

BISU supplies the following. EXTRACK is built so each one can be plugged in without rework.

| Item | What we build until it arrives | **[Open]** to ask |
|---|---|---|
| Fonts and colours | **Colours received** (section 1.4): primary #330066, secondary #FFCC00, accent #FFFFFF. Typography and spacing are left to the developers. All fonts and colours sit in one set of design tokens, so changes are a one-file edit | The neutral palette; the university logo file (from Engr. Libay); whether EXTRACK keeps its name and "Crossing" mark or gets an assigned app identity |
| Database | Laravel migrations and Eloquent only (no engine-specific SQL), so it runs on MySQL, MariaDB, PostgreSQL or SQL Server. Developed on local MySQL | Engine and version, who hosts it, access for development, and whether EXTRACK gets its own database or tables inside an existing one |
| API | **Standard received** (section 1.4): EXTRACK's own API follows BISU's REST rules. Any university service EXTRACK calls (such as the identity provider) sits behind a service class with a local stand-in | The JSON request, response and error shapes, pagination parameters and rate limits (not yet written in the standard); access to the BISU Postman project |
| Hosting | **[Open]** not yet stated | Server, domain, HTTPS certificate, mail account for invites and password resets, and backups |

### 1.3 Deadline

- **The system is finished by the end of January 2027.** BISU presents it to the director in February 2027.
- Section 11.2 has the schedule and the dates by which the university's items are needed.

### 1.4 BISU software and integration standards

Source: *BISU Software and Integration Standardization*, version 1, September 25, 2026. Items the document assigns to a person ("%") and has not filled in yet are marked **Pending** with that person's name.

| # | Standard | What it says | What EXTRACK does |
|---|---|---|---|
| 1 | Architecture | Model-View-Controller. Laravel, CodeIgniter and Django are named as already following it | Laravel (MVC). Business logic stays in Actions and Services, called from controllers |
| 2 | API design | RESTful. Resource names are nouns, lowercase, with underscores | Every endpoint follows this, for example `/api/v1/services`, `/api/v1/services/{id}/documents`, `/api/v1/beneficiary_sectors` |
| 3 | Authentication | Invite only, no registration unless needed. Google sign-in is for Alumni systems. Zitadel or Clerk as the identity provider | Invite only (section 2.1). **[Open]** Zitadel or Clerk, and who runs it |
| 4 | Permissions | Role-based access control | Roles and permissions in section 2, enforced on the server |
| 5a–f | Errors and responses | One standard JSON shape for POST, GET, PATCH, PUT and DELETE requests, responses and errors; standard pagination, filtering and sorting parameters | **Pending** (not written yet). EXTRACK formats every response in one place, so the shape can be changed to the standard without touching each endpoint |
| 5g | Rate limiting | **Pending**, Daryl Valdez | Laravel's rate limiter, with limits set in configuration |
| 6 | API versioning | Postman. One BISU Postman project with every member added | Version in the URL (`/api/v1`). Every endpoint is documented in a collection in the BISU Postman project. **[Open]** access to that project |
| 7a | University logo | One source file, same resolution and size. **Pending**, Engr. Libay | Uses the official file only |
| 7b | Application identity | Standardised application name, icon and description (examples: Procurement, Alumni Connect) | **[Open]** keep "EXTRACK" and the "Crossing" mark, or use an assigned name and icon |
| 7c | Colours | Primary #330066, secondary #FFCC00, accent #FFFFFF, and a neutral palette | Design tokens use these. **[Open]** the neutral palette. #FFCC00 is too light for text on white, so it is used for fills and highlights, not text |
| 7d–e | Typography and spacing | Left to the developers | One type scale and one spacing scale in the design tokens |
| 7f | Layout | Left sidebar | Left sidebar navigation, as in the prototype |
| 7g | Footer | Three columns: BISU logo (left), copyright and year (centre), social media and developers (right). Terms and Conditions link | As specified. **[Open]** the Terms and Conditions text and the social media links |
| 7h | Header | Two columns: app logo and name (left); user profile, settings and logout (right) | As specified |
| 7i | Component library | **Pending**, Daryl Valdez | **[Open]** its technology and when it is available. This affects the choice of UI stack (section 11) |
| 7j | UX and interaction | Loading states (developers; background motion graphics with the motion.dev library), notifications (developers), others (developers) | Loading states use motion.dev. Notifications appear in the notification center (7m) |
| 7k–l | Design philosophy and visual style | Minimalism, intuitive for users | Plain screens, few actions per screen |
| 7m | University-wide application shell | University header; application switcher (a portal linking every BISU system); user profile on the right of the header; notification center beside the profile; breadcrumbs | All five are included. **[Open]** whether BISU provides the shell as shared components, and where the application switcher gets its list of systems |
| — | Other matters | Benefits for faculty developers; subscriptions and other software requirements; official repository | **[Open]** where the official repository is, so EXTRACK's code is kept there |

## 2. Roles

| Role | Scope | Adds and edits records | Views records | Manages the system |
|---|---|---|---|---|
| University President (**super admin**) | All campuses | **[Open]** Can edit or delete any record | All campuses | Yes: accounts, campuses, categories, barangays, sectors and settings |
| Extension Director | Own campus | Yes, for their own campus | **[Open]** Own campus only, or all campuses | No |
| Extension Director's Assistant **[Open]: keep this role?** | Own campus | Encodes records for the Extension Director (section 3.2) | Same as their director | No |
| Campus Director **[Open]: keep this role?** | Own campus | No | Own campus | No |

- The separate System Admin role is gone. The University President is the super admin.
- **[Open]** Who does the day-to-day account work? If the President delegates it, a staff member (for example in the Extension Office) could get an "Admin" account with the same system rights but no reporting role.

### 2.1 Accounts

- Invite only, as the BISU standard requires (section 1.4, item 3). There is no public sign-up. The super admin invites every user, with a fixed role and campus. The login page has no role picker.
- Sign-in goes through the university's identity provider, Zitadel or Clerk **[Open]: which one, and who runs it**. Passwords, password resets and invite emails are then handled by the identity provider, and EXTRACK stores no passwords. EXTRACK keeps each user's role, campus and active status.
- Fallback if the identity provider is not ready by the January cutoff: Laravel's own login with invite links. **[Open]** which mail service BISU allows for the invite emails.
- Google sign-in is not used. The standard reserves it for Alumni systems.
- Accounts are deactivated, never deleted, so names stay on the records they created.
- An assistant's account is linked to one Extension Director (`users.principal_id`).

### 2.2 Campus scoping

- Campus-bound users can add and edit only their own campus's records. This is enforced in queries and policies, not only hidden in the UI.
- **[Open]** Can campus users read other campuses' records (the earlier design showed every campus's completed work on the map so directors could learn from each other), or only their own?

## 3. Service records

A **service record** is one completed extension service with its accomplishment report. It replaces the old extension request.

### 3.1 Fields

- Reference code, generated by EXTRACK (for example `CAL-2026-014`) **[Open]: or the code the Extension Office already uses**
- Campus
- Title
- Description: what was done and for whom
- Category: Agriculture & Fisheries, Livelihood, Health, Education, Environment, Technology & Digital, Others **[Open]: confirm the list with the Extension Office**
- Municipality and barangays (one or more)
- Actual start and end dates
- Beneficiaries reached: number and sectors (fisherfolk, women, youth, senior citizens and so on)
- Partner agencies or LGU (optional)
- Amount spent (₱), for information only. There is no budget tracking.
- Narrative of what was done and the outcome
- Photos
- Supporting documents (section 4)
- Encoded by, published by, and the dates of both

### 3.2 Record states

```text
Draft ─publish─▶ Published ─(super admin)─▶ Deleted (soft, with a reason)
```

| State | Meaning | Who can see it |
|---|---|---|
| Draft | Being encoded, can be incomplete | The campus's Extension Director and their assistant |
| Published | In the repository, counted in dashboards and reports | Everyone allowed to view the campus (section 2.2) |
| Deleted | Hidden from the repository, kept in the database with who deleted it and why | Super admin |

- **[Open]** Who publishes: only the Extension Director (the assistant only drafts), or the assistant too?
- **[Open]** Does a published record need the Campus Director's confirmation first? The panel removed approvals, so the current design says no.
- Editing a published record is allowed for its campus's Extension Director and is written to the activity log (section 9). **[Open]** whether records lock after a period, such as the end of the reporting year.

### 3.3 Past reports

- **[Open]** Should the Extension Office's existing reports from earlier years be encoded into EXTRACK? If so, which years, who encodes them, and is a scanned report enough without every field filled in?

## 4. Document storage

EXTRACK stores the documents for each service.

- **Document types:** accomplishment report (the signed file), attendance sheets, photos, barangay letter of request, project proposal, MOA or partnership letter, and other. Each file is tagged with its type. **[Open]** which types are required before a record can be published.
- **File formats:** PDF, DOCX, XLSX, JPG, PNG and WebP. The limit is 20 MB per file. **[Open]** confirm the limit with how large the scanned reports are.
- **Storage:** private disk. Files are never public and are served only through EXTRACK to users allowed to view the record. **[Open]** where the files live: a BISU server or a cloud service, decided with the university's IT office.
- **Viewing:** PDFs and images open in the browser. Each file can be downloaded on its own, with its original file name. **[Open]** a "Download all" ZIP per record.
- **Backups:** the database and the files are backed up together on a schedule. **[Open]** frequency and where the backups are kept.
- **Privacy (Data Privacy Act of 2012):** attendance sheets and photos contain names, signatures and faces. Only signed-in users with access to the record can open them, and every download is logged. **[Open]** how long records and files are kept.
- Replacing a file keeps the old version until the record is saved, and the change is logged.

## 5. Repository

- **List:** every published record the user can see, newest first.
- **Search:** title, description, narrative, code, municipality, barangay and partners. **[Open]** searching the text inside uploaded PDFs.
- **Filters:** campus (for users who can see more than one), year, category, municipality and barangay, sector.
- **Sort:** newest, oldest, most people reached, title.
- **Record page:** all fields, a photo gallery, the document list with view and download, who encoded and published it, and the edit history.
- **Similar services (optional) [Open]:** the earlier overlap check could become a "similar past services in this barangay" panel on the record page, using the same keyword method, or AI text embeddings in production. It is advice only and never blocks anything.

## 6. Dashboards

### 6.1 University President

1. Totals for the selected year: services completed, people reached, barangays served, amount spent
2. Services completed per campus (chart), and a campus table: services, people reached, barangays, amount spent
3. Services by category
4. Recently published records across all campuses
5. Map of all services
6. Drill-down from any number to the records behind it

### 6.2 Extension Director (and assistant)

1. "Add completed service" button
2. Drafts not yet published
3. Campus totals for the year: services, people reached, barangays, amount spent
4. Recently published records from the campus
5. Campus coverage map

### 6.3 Campus Director (if the role is kept)

Read-only: the campus totals, the campus's records and the map.

## 7. Reports and export

- **Consolidated report:** whatever the repository filters show (for example one campus, one year, one category), with totals, summaries by campus and category, and the list of services. Saved as PDF or downloaded for Excel.
- **Per-service report:** one record's accomplishment report with its details, photos and signature lines. Saved as PDF or downloaded for Excel.
- Both layouts in the prototype are samples. **[Open]** the Extension Office's or CHED's official templates, the reporting period (quarter, semester or year), and the required file type.

## 8. Map

A map of Bohol with a pin for every barangay that has a published service. Pins show the number of services and the campus colour. Selecting a pin lists that barangay's services, and selecting a service opens its record. Filters: campus, category and year.

**[Open]** Map provider: the prototype uses OpenStreetMap through Leaflet. Google Maps needs an API key with billing. Barangay coordinates also need an official source.

## 9. Activity log and notifications

- **Activity log:** every create, publish, edit, delete, file upload, file replacement and file download, with who, when and what changed. The super admin can view it.
- **Notifications (in-app):** a record is published (to the President, and to the Campus Director if the role is kept); an account is created (invite email to the new user). **[Open]** whether email is sent for anything besides the invite.

## 10. Data model (v2)

```text
campuses            id, name, municipality_id
municipalities      id, name
barangays           id, municipality_id, name, latitude, longitude
categories          id, name
sectors             id, name
users               id, name, email, password, role, campus_id (null for the President),
                    principal_id (assistant → Extension Director), is_active, last_login_at
services            id, code, campus_id, title, description, category_id,
                    start_date, end_date, beneficiaries, partners, amount_spent, narrative,
                    state (draft | published), encoded_by, published_by, published_at,
                    timestamps, soft deletes, deleted_by, delete_reason
service_barangay    service_id, barangay_id
service_sector      service_id, sector_id
documents           id, service_id, kind (report | attendance | photo | letter | proposal | moa | other),
                    original_name, path, mime, size, uploaded_by, replaced_by_id, created_at
activity_log        id, user_id, action, subject_type, subject_id, changes (json), created_at
settings            key, value
notifications       Laravel default
```

Removed from v1: `extension_requests` (replaced by `services`), `request_events`, `request_remarks`, `overlap_flags`, `request_embeddings`, `university_budgets`, `campus_budgets`, `budget_changes`.

## 11. Implementation notes

- Laravel 13 (MVC, as the BISU standard requires). **[Open]** the UI stack, to be settled in architecture planning. It has to fit the university header, footer, left sidebar, application shell and component library (section 1.4), so the prototype's Filament panel may not carry over.
- REST API under `/api/v1`, following the BISU naming rules, with responses formatted in one place (section 1.4, items 2, 5 and 6), and a Postman collection in the BISU project.
- Roles: a PHP enum `App\Enums\Role` (`President`, `ExtensionDirector`, `ExtensionAssistant`, `CampusDirector`) and policies. The President's super admin rights come from the role, not a separate flag. **[Open]** add `Admin` if the President delegates account work.
- Campus scoping: a global scope on `Service` when the user is campus-bound, plus policy checks on every action.
- Business logic in Actions (`app/Actions`): `CreateService`, `UpdateService`, `PublishService`, `DeleteService`, `AttachDocument`, `ReplaceDocument`, `CreateUser`, `DeactivateUser`. Each writes the activity log.
- Files: a controller streams documents from the private disk after a policy check and logs the download.
- Filament resources: `UserResource` (super admin only), `ServiceResource`, and reference data (campuses, barangays, categories, sectors).
- Exports: PDF (a print layout or a PDF library) and Excel (CSV now, XLSX if the template needs it).

### 11.1 What changes in the prototype

The prototype shown at the defense still has the request and approval workflow. To match this spec:

- Remove: the request form and drafts, the Campus Director's approval screens, the President's final approval, the overlap decision, campus budgets, and approval notifications.
- Keep and rework: the accomplishment report form becomes "Add completed service"; the Archive becomes the repository; the map, the President's per-campus chart and both exports stay.
- Replace the login role picker with fixed roles on each account, and add a Users page for the President.

These changes go straight into the production build (section 11.2) rather than into the prototype.

### 11.2 Build order and schedule

The deadline is the end of January 2027 (section 1.3). That leaves about 11 working weeks, with the Christmas break as buffer.

| Week | Dates | Build | Needed from BISU by the end of the week |
|---|---|---|---|
| 0 | Nov 9 to 15 | Meeting this week; settle section 12; finalise this spec; send BISU the list in section 1.2 | Answers to section 12, and an IT contact person |
| 1 to 2 | Nov 16 to 29 | Roles, accounts and the Users page (super admin); campuses, municipalities, barangays, categories, sectors, seeders; `Service` resource with campus scoping and policies | Database engine, version and development access (Nov 27) |
| 3 to 4 | Nov 30 to Dec 11 | Document storage (upload, tagging, view, download, replace, activity log); repository list, search, filters and record page | API documentation and test credentials; hosting details (Dec 11) |
| 5 | Dec 14 to 23 | Dashboards (President, Extension Director, Campus Director) and the map | Brand guide, fonts and colours (Dec 18) |
| — | Dec 24 to Jan 3 | Holiday break; buffer only | Official report template (by Jan 4) |
| 6 to 7 | Jan 4 to 15 | Reports and export; notifications and invite emails; backups; apply the university brand; connect the university database and API | — |
| 8 | Jan 18 to 22 | Testing with the Extension Office; encode a sample of past reports | Extension Office staff available to test |
| 9 | Jan 25 to 29 | Fixes; deploy to the BISU server; user guide and handover | Server ready |
| Feb 2027 | | Presentation to the director | |

- If a university item is late, building continues against the placeholder in section 1.2, and connecting the real item moves to weeks 6 to 7. Anything not received by **Jan 15** ships with the placeholder and is listed as pending in the handover.
- The prototype is not reworked separately. Time goes into the production build, which reuses the prototype's screen designs, map and report layouts.

## 12. Open questions

1. Roles: keep the Campus Director and the Extension Director's Assistant? Who publishes a record? Does the President do account work personally or delegate it?
2. Visibility: can campuses see each other's records, or only their own?
3. Records: are approvals truly gone, or does the Campus Director still confirm a report? Do records lock after a period?
4. Fields: the official category list and reference code format.
5. Documents: which are required, the file size limit, and whether past years' reports are encoded.
6. Storage: a BISU server or cloud, backups, and how long records are kept.
7. Reports: the official template, reporting period and file type.
8. Similar services panel: keep it (and the AI method), or drop it?
9. Email: which service, and what gets emailed.
10. Map: provider and the source of barangay coordinates.
11. University items (section 1.2): what the API is for, the database engine and hosting, the brand guide, and the dates each will be delivered.
12. Who "the director" in February is, and what they need to see in the presentation.
13. Identity provider: Zitadel or Clerk, who runs it, and when EXTRACK can connect to it.
14. Component library (Daryl Valdez): its technology and when it is available.
15. The standard JSON shapes, pagination parameters and rate limits: when they are published.
16. Application identity: EXTRACK's official name, icon and description, and the university logo file (Engr. Libay).
17. The neutral palette, the Terms and Conditions text and the footer's social media links.
18. Whether BISU provides the application shell (header, application switcher, notification center) as shared components.
19. The official repository for the code, and access to the BISU Postman project.

## 13. Change log

| Date | Change | Reason |
|---|---|---|
| 2026-10-28 | First draft | Initial planning |
| 2026-10-28 | Renamed RIE Director to Extension Director (Director of Extension). The University President now gives final approval to every request after campus approval. Removed the budget threshold and the "Needs President's approval" option. Added yearly campus budgets with an over-budget warning | Clarified with the extension office |
| 2026-10-28 | Requests go one way only: the Extension Director requests, the Campus Director of the same campus approves, then the President. Removed Campus → Extension requests; the Extension Director no longer approves anything. Campus Directors cannot act on other campuses' requests | Clarified by the user |
| 2026-10-29 | Added the extension map for all three roles, showing every campus's ongoing and completed services with photos (no budgets for other campuses). Added accomplishment report photos to the archive | Suggested by the user, so directors can learn from other campuses |
| 2026-10-29 | Supporting documents: several tagged files per request, viewable and downloadable by the Campus Director and the President, with a warning when the barangay letter or project proposal is missing | Requested by the user |
| 2026-10-29 | Beneficiary sectors can be added from the request form. The prototype now saves its data in MySQL and uploaded files on the server, so it works across browsers and devices | Requested by the user |
| 2026-10-29 | The University President sets each campus's yearly extension budget (previously the System Admin). Changes are logged and the campus is notified | The President sets the budgets |
| 2026-10-29 | The President also sets the university extension budget, divides it among campuses (with an automatic split), and keeps the rest as a reserve for mid-year top-ups | Requested by the user |
| 2026-10-29 | Overlap check: documented the prototype's keyword method and weights, and set the production design to AI text embeddings with an optional AI explanation, people deciding, privacy limits, a keyword fallback and an evaluation plan. Added the budget tables to the data model | Planning for production |
| 2026-11-07 | Accounts are created by an admin; no public sign-up. Consolidated report export added with a sample layout | Decided by the user |
| 2026-11-08 | Removed the Campus Director's Assistant role. Added per-service report export with a sample layout | The role had no task of its own; requested by the user |
| 2026-11-09 | **Scaled down to a repository of completed services.** Removed extension requests, the approval workflow, request statuses, the overlap check before approval, campus budgets and approval notifications. Added document storage, record states (Draft, Published), the activity log and data model v2. The University President is the super admin and the System Admin role is removed. Listed the open questions for this week's meeting | Proposal defense panel |
| 2026-11-09 | The university provides the fonts, colours, API and database (section 1.2). Deadline set: finished by the end of January 2027, presented to the director in February 2027. Added the schedule (section 11.2). The prototype will not be reworked separately | Told to the team after the defense |
| 2026-11-17 | Added the BISU Software and Integration Standardization (section 1.4): MVC, REST API naming, invite-only sign-in through Zitadel or Clerk, role-based access, standard JSON responses, Postman, university colours, header, footer, left sidebar, application shell and motion.dev loading states. Accounts now sign in through the identity provider (section 2.1). The UI stack is reopened for architecture planning | BISU standards document |
