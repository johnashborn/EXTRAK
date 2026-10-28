# EXTRACK system specification

Status: draft v0.4 (2026-10-29). The process may change based on panel feedback on the research proposal. Anything marked **[Open]** is not final. Record changes in the [change log](#12-change-log).

## 1. Purpose

EXTRACK records, approves and reports extension services delivered by Bohol Island State University (BISU) campuses to barangays. It tracks each campus's yearly extension budget. It also warns users when a new request is similar to work already done or planned in the same barangay, including work by other campuses.

## 2. Roles

| Role | Scope | Creates requests | Approves | Notes |
|---|---|---|---|---|
| Extension Director (Director of Extension) | Own campus | Yes, the only role that creates requests | No | Carries out approved services and submits accomplishment reports |
| Extension Director's Assistant | Own campus | Drafts for the Extension Director | No | Works under one Extension Director |
| Campus Director | Own campus | No | First approval of their own campus's requests only | Cannot see or approve other campuses' requests. Monitors campus extension work and budget |
| Campus Director's Assistant | Own campus | No | No | Prepares reviews for the Campus Director |
| University President | All campuses | No | Final approval of every request (section 4) | Sees all campuses |
| System Admin | System | No | No | Manages users, campuses, barangays, budgets and settings |

### 2.1 Assistants

- Every assistant has their own account, linked to one director (`users.principal_id`). Assistants do not use the director's login.
- An assistant sees their director's dashboard and records, and can do everything the director can except approve, return, decline, or sign off a final report.
- Records show both people: "Submitted by Ma. Elena Racines (Extension Director), encoded by J. Cruz (Assistant)".
- Deactivating an assistant does not affect the director's account or records.

### 2.2 Campus scoping

- Every role except President and System Admin sees only its own campus's records.
- The scope is enforced in queries (Eloquent global scope and policies), not just hidden in the UI.
- Exceptions:
  - The overlap check (section 5) shows a limited summary of other campuses' activities: title, campus, barangay, dates and status.
  - The extension map (section 8) shows every campus's ongoing and completed services so directors can learn from each other: title, campus, barangay, dates, category, description, beneficiaries, partners, the accomplishment narrative and photos. Other campuses' budgets are not shown.

## 3. Extension requests

An **extension request** is the main record. It starts as a request and becomes the activity once approved, so a service has one record from request to archive.

### 3.1 Who requests and who approves

Requests go one way only. Every request needs two approvals:

| Step | Who |
|---|---|
| Creates and submits the request | Extension Director (an assistant can draft it) |
| First approval | Campus Director of the **same campus**. A Campus Director cannot approve, return or decline another campus's requests |
| Final approval | University President |
| Carries out the service and reports | Extension Director (and assistant) |

The Extension Director does not approve requests.

### 3.2 Fields

- Title
- Description: what will be done, for whom, and the expected change
- Category: Agriculture & Fisheries, Livelihood, Health, Education, Environment, Technology & Digital, Others **[Open]: confirm the list with the extension office**
- Municipality and barangay (one or more barangays)
- Start and end dates
- Proposed budget (₱). It is charged to the campus's extension budget for the year of the start date.
- Expected beneficiaries: number and sector, such as fisherfolk, women, youth or senior citizens
- Partner agencies or LGU (optional)
- Barangay letter of request (optional attachment)
- Supporting documents

### 3.3 Accomplishment report (after the service)

- Actual dates, actual budget used, actual beneficiaries (number and sector)
- Narrative of what was done and the outcome
- Photos and attendance sheets
- Encoded by the Extension Director or their assistant, and submitted by the Extension Director

**[Open]** Does the Campus Director need to approve the accomplishment report before the request is marked Completed?

## 4. Workflow

### 4.1 Statuses

```
Draft ─submit─▶ Submitted ─campus approves─▶ For President ─President approves─▶ Approved ─start─▶ Ongoing ─report─▶ Completed ─▶ Archived
                  │  ▲                            │  
          return  │  │ resubmit           return / decline
                  ▼  │                            │
                Returned ◀────────────────────────┘
                  │
               decline ─▶ Declined
```

| Status | Meaning | Who moves it forward |
|---|---|---|
| Draft | Being written, only visible to the Extension Director and their assistant | Extension Director |
| Submitted | Waiting for campus approval | Campus Director of the same campus |
| For President | Approved at campus level, waiting for final approval | University President |
| Returned | Sent back with a remark, so the Extension Director can edit and resubmit. A resubmitted request starts again at campus approval | Extension Director |
| Declined | Closed, will not proceed. A remark is required | — |
| Approved | Cleared to proceed, not yet started | Extension Director |
| Ongoing | Being carried out | Extension Director |
| Completed | Accomplishment report submitted | — |
| Archived | Read-only, in the historical archive | Automatic |

- Returning or declining always requires a remark. The Campus Director and the President can both return or decline.
- Every status change is written to `request_events` (who, when, from, to, and remark).
- **[Open]** When the President returns a request, does it go back to the Extension Director (current design) or to the Campus Director?
- **[Open]** When does a Completed request move to Archived: right away, at the end of the quarter, or at the end of the year?

### 4.2 Overdue rules

- An Approved request that has passed its start date without moving to Ongoing gets an "Overdue start" warning.
- An Ongoing request that is more than 14 days past its end date without a report gets "Report overdue". **[Open]: confirm the 14 days**

## 5. Overlap check

- Runs when a request is saved as a draft or submitted. It runs as a queued job, and the result shows on the form and on the request.
- It compares against all non-declined requests from **all campuses** within ±12 months.
- **Signals:**
  - same barangay (strong) or same municipality (weak)
  - overlapping or nearby dates
  - similar description. v1 uses keyword/TF-IDF similarity; later versions may use text embeddings **[Open]**
- If the score is at or above the threshold (default 70%), the request is flagged with the score, the matching record and the shared terms.
- A flag does not block submission. The Campus Director and the President both see it. The Campus Director records a resolution, and the President can change it. The Extension Director sees the flag but does not resolve it:
  - **Allow both:** the services are different enough
  - **Coordinate:** the campuses will work together
  - **Revise:** returned to the Extension Director to change place, dates or scope

## 6. Campus extension budget

Each campus has a fixed extension budget per year. Requests do not need a separate budget approval, but EXTRACK tracks how much of the budget is used.

- The System Admin sets each campus's allocation per year (`campus_budgets`).
- For each campus and year, EXTRACK shows:

| Figure | How it's calculated |
|---|---|
| Allocation | Set by the Admin |
| Pending | Proposed budget of requests that are Submitted or For President |
| Committed | Proposed budget of requests that are Approved or Ongoing |
| Spent | Actual budget of Completed and Archived requests |
| Remaining | Allocation − Committed − Spent |

- If a request's proposed budget is more than the Remaining amount (or Remaining minus Pending), the form, the Campus Director and the President see a warning: "This request is ₱X over Calape Campus's remaining 2026 extension budget". The warning does not block submission or approval.
- **[Open]** Can a campus move unused budget into the next year, or does each year start fresh?

## 7. Dashboards

### 7.1 Extension Director (and assistant)

1. Action counts, each clickable to a filtered list: *Waiting for approval*, *Returned to me*, *Reports overdue*, *Overlaps flagged*
2. Needs your action: returned requests to edit and resubmit, overdue accomplishment reports, approved services about to start, and drafts. No approve buttons, since the Extension Director does not approve
3. Waiting for approval: submitted requests and whether each is with the Campus Director or the President, with overlap and budget flags
4. Ongoing services and upcoming end dates
5. Campus budget for the year: allocation, committed, spent, remaining
6. Campus coverage map: barangays shaded by number of services
7. "New request" button

### 7.2 Campus Director (and assistant)

Shows only the Campus Director's own campus.

1. Action counts: *Waiting for my approval*, *Overlaps to resolve*, *Over budget*
2. Requests from the Extension Director waiting for approval, with the overlap result and budget warning shown inline, and approve, return and decline actions (Campus Director only, not the assistant)
3. Requests already sent to the President and their status
4. Ongoing services on campus
5. Campus budget for the year and a campus summary: services, beneficiaries

### 7.3 University President

1. **Waiting for your approval:** every campus-approved request, showing the campus, overlap flag and budget warning, with approve, return and decline actions
2. University totals for the selected year: services, beneficiaries, budget spent, barangays reached
3. Campus comparison table: services, completed, ongoing, beneficiaries, budget used vs allocation, overdue reports
4. Bohol map of all barangays served, filterable by campus and category
5. Overlaps between campuses
6. Trend by quarter and year
7. Drill-down from any number to the records behind it

## 8. Other screens

- **All requests:** search and filters for campus (President only), barangay, status, category and dates
- **Request detail:** fields, attachments, overlap result, budget impact, timeline of `request_events`, and a remarks thread
- **Reports & export:** consolidated accomplishment report by campus, quarter or category, including budget use, exported to PDF and Excel (CHED-ready)
- **Historical archive:** read-only list of Archived services, with the accomplishment report photos
- **Extension map** (all roles): a map of Bohol with a pin for every barangay that has received an ongoing or completed service from any campus. Pins show the number of services and the campus colour. Selecting a pin lists that barangay's services; selecting one shows its details and photos. Filters: campus, category, ongoing or completed. **[Open]** Map provider: the prototype uses OpenStreetMap through Leaflet; Google Maps needs an API key with billing. Barangay coordinates also need an official source.
- **Notifications** (section 9)
- **Settings** (Admin): users, roles, director–assistant links, campuses, municipalities, barangays, categories, yearly campus budgets and thresholds

## 9. Notifications

| Event | Who is notified |
|---|---|
| Request submitted | Campus Director of the same campus and their assistant |
| Approved at campus level | Extension Director, and the President |
| Request returned or declined | Extension Director and their assistant |
| Final approval by the President | Extension Director and Campus Director |
| Overlap flagged | Extension Director and Campus Director |
| Request over remaining budget | Campus Director (with the request) |
| End date in 7 days | Extension Director and assistant |
| Report overdue | Extension Director and assistant, plus the Campus Director |

Notifications are in-app (Filament database notifications). **[Open]** Should email be sent too?

## 10. Data model (v1)

```
campuses            id, name, municipality_id
campus_budgets      id, campus_id, year, allocation          unique (campus_id, year)
municipalities      id, name
barangays           id, municipality_id, name
categories          id, name
users               id, name, email, password, role, campus_id (null for President/Admin),
                    principal_id (assistant → director), is_active
extension_requests  id, code (e.g. CAL-2026-014), campus_id, budget_year, requested_by, encoded_by,
                    title, description, category_id, start_date, end_date,
                    proposed_budget, expected_beneficiaries, beneficiary_sectors (json), partners,
                    status, campus_approved_by, campus_approved_at, president_approved_at,
                    timestamps, soft deletes
request_barangay    extension_request_id, barangay_id
request_events      id, extension_request_id, user_id, acting_for_id, from_status, to_status, remark, created_at
request_remarks     id, extension_request_id, user_id, body, created_at
accomplishments     id, extension_request_id, actual_start, actual_end, actual_budget,
                    actual_beneficiaries, sectors (json), narrative, submitted_by, encoded_by, submitted_at
attachments         id, attachable_type, attachable_id, kind (letter|document|photo|attendance), path, uploaded_by
overlap_flags       id, request_id, matched_request_id, score, signals (json),
                    resolution, resolved_by, resolved_at, remark
settings            key, value (overlap_threshold, report_grace_days)
notifications       Laravel default
```

## 11. Implementation notes

- Laravel 13 + Filament 3, one panel at `/admin` with a dashboard that shows different widgets per role
- Roles: a PHP enum `App\Enums\Role` (`ExtensionDirector`, `ExtensionAssistant`, `CampusDirector`, `CampusAssistant`, `President`, `Admin`) plus policies
- Campus scoping: a global scope on `ExtensionRequest` applied when the user is campus-bound. `CampusApproveRequest` also checks in its policy that the Campus Director's campus matches the request's campus
- Business logic in Actions (`app/Actions`): `SubmitRequest`, `CampusApproveRequest`, `PresidentApproveRequest`, `ReturnRequest`, `DeclineRequest`, `StartRequest`, `SubmitAccomplishment`, `ResolveOverlap`
- Status changes go only through these Actions, which write the `request_events` row and send notifications
- Budget figures come from a `CampusBudgetService` (allocation, pending, committed, spent, remaining for a campus and year)
- Overlap detection: a `DetectOverlaps` queued job that calls an `OverlapScorer` service, so the scoring method can be replaced later
- Seed data: 6 campuses (Main/Tagbilaran, Balilihan, Bilar, Calape, Candijay, Clarin), Bohol municipalities and barangays, sample budgets, and demo users for each role

### Build order

1. Roles enum, campuses, municipalities, barangays, categories, users with assistants (plus seeders)
2. `ExtensionRequest` resource: create, edit, list, detail, with campus scoping and policies
3. Workflow Actions and statuses (campus approval, then President approval), `request_events`, and remarks
4. Campus budgets and the budget warning
5. Accomplishment reports and archive
6. Overlap check
7. Role dashboards (Extension Director, Campus Director, President)
8. Notifications
9. Reports & export (PDF/Excel)

## 12. Change log

| Date | Change | Reason |
|---|---|---|
| 2026-10-28 | First draft | Initial planning |
| 2026-10-28 | Renamed RIE Director to Extension Director (Director of Extension). The University President now gives final approval to every request after campus approval. Removed the budget threshold and the "Needs President's approval" option. Added yearly campus budgets with an over-budget warning | Clarified with the extension office |
| 2026-10-28 | Requests go one way only: the Extension Director requests, the Campus Director of the same campus approves, then the President. Removed Campus → Extension requests; the Extension Director no longer approves anything. Campus Directors cannot act on other campuses' requests | Clarified by the user |
| 2026-10-29 | Added the extension map for all three roles, showing every campus's ongoing and completed services with photos (no budgets for other campuses). Added accomplishment report photos to the archive | Suggested by the user, so directors can learn from other campuses |
