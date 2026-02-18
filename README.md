# RSYI Student Affairs Management System
### معهد البحر الأحمر للتخطيط البحري – نظام شؤون الطلاب

A complete intranet Student Affairs Management System built as a custom WordPress plugin for **Red Sea Yacht Institute (El Gouna)** scholarship students.

---

## 1. Plugin Folder Structure

```
rsyi-student-affairs/
├── rsyi-student-affairs.php          ← Main plugin file, constants, autoloader, bootstrap
├── composer.json                      ← Dependencies (mPDF for PDF generation)
│
├── includes/
│   ├── class-db-schema.sql           ← Annotated schema reference
│   ├── class-db-installer.php        ← Activation: dbDelta tables + roles + seeds
│   ├── class-roles.php               ← 5 custom WP roles + capability map
│   ├── class-audit-log.php           ← Immutable audit trail writer/reader
│   ├── class-email-notifications.php ← Centralised mailer (all events)
│   ├── class-secure-download.php     ← Signed URL download handler
│   │
│   ├── modules/
│   │   ├── class-accounts.php        ← Student registration, profile CRUD, activation
│   │   ├── class-documents.php       ← Upload (MIME-validated), approve, reject
│   │   ├── class-requests.php        ← Exit + overnight permit workflows
│   │   ├── class-behavior.php        ← Violations, thresholds, warnings, expulsion
│   │   └── class-cohorts.php         ← Cohort CRUD + Dean-approved transfer workflow
│   │
│   ├── admin/
│   │   └── class-admin-menu.php      ← All WP admin menus + page renderers
│   │
│   ├── portal/
│   │   └── class-portal-shortcodes.php ← 5 frontend shortcodes for student portal
│   │
│   └── pdf/
│       └── class-pdf-generator.php   ← mPDF/TCPDF/HTML fallback renderer
│
├── templates/
│   ├── admin/
│   │   ├── dashboard.php             ← Stats overview + quick actions + recent audit
│   │   ├── students-list.php         ← Filterable student list
│   │   ├── violations-list.php       ← Violations log + add form
│   │   └── daily-report.php         ← PDF generator UI
│   │
│   ├── portal/
│   │   ├── dashboard.php             ← Student home with pending warning acknowledgments
│   │   ├── documents.php             ← 8-doc upload grid with status indicators
│   │   ├── requests.php              ← Submit exit/overnight + history tables
│   │   ├── behavior.php              ← Points meter, violations, acknowledgments
│   │   └── register.php             ← Self-registration form
│   │
│   ├── email/                        ← HTML email templates (all events)
│   │   ├── document-approved.php
│   │   ├── document-rejected.php
│   │   ├── account-activated.php
│   │   ├── request-pending.php
│   │   ├── request-approved.php
│   │   ├── request-rejected.php
│   │   ├── behavior-warning.php
│   │   ├── expulsion-created.php
│   │   ├── expulsion-executed-student.php
│   │   ├── expulsion-executed-staff.php
│   │   └── cohort-transfer-approved.php
│   │
│   └── pdf/
│       ├── daily-report.php          ← Aggregated exit + overnight report (RTL)
│       └── expulsion-letter.php      ← Official expulsion letter with signature block
│
├── assets/
│   ├── css/
│   │   ├── admin.css                 ← RTL admin styles + badges + stat grid
│   │   └── portal.css               ← RTL portal styles + doc grid + forms
│   └── js/
│       ├── admin.js                  ← Approve/reject/execute AJAX bindings
│       └── portal.js                 ← Upload, validation, acknowledgment
│
└── languages/                        ← .pot / .po / .mo translation files
```

---

## 2. Database Schema (11 Tables)

| Table | Purpose |
|---|---|
| `wp_rsyi_cohorts` | Cohort registry (name, code, dates) |
| `wp_rsyi_student_profiles` | Extended student data linked to `wp_users` |
| `wp_rsyi_documents` | 8 mandatory document records + file paths |
| `wp_rsyi_exit_permits` | 2-step exit permit workflow |
| `wp_rsyi_overnight_permits` | 3-step overnight permit workflow |
| `wp_rsyi_violation_types` | Violation catalog (points, flags) |
| `wp_rsyi_violations` | Individual violation incidents |
| `wp_rsyi_behavior_warnings` | Threshold events + acknowledgment timestamps |
| `wp_rsyi_expulsion_cases` | Dean-approved expulsion workflow |
| `wp_rsyi_cohort_transfers` | Immutable cohort transfer history |
| `wp_rsyi_audit_log` | Full audit trail (actor, entity, action, JSON) |

All files stored in `wp-content/uploads/rsyi-docs/` — protected by `.htaccess` (`Deny from all`). Served only via **Secure Download Handler** after capability check + nonce verification.

---

## 3. Roles & Capabilities

| Role | Key Capabilities |
|---|---|
| `rsyi_dean` | All capabilities; final approval on overnight, expulsion, cohort transfer; up to 30 violation pts |
| `rsyi_student_affairs_mgr` | Approve/reject docs, exit (step 2), overnight (step 2), create violations (≤20 pts) |
| `rsyi_student_supervisor` | Approve overnight (step 1), create violations (≤10 pts), view all students |
| `rsyi_dorm_supervisor` | Approve exit (step 1), print daily report |
| `rsyi_student` | Upload own docs, submit permits, view own violations, acknowledge warnings |

WP `administrator` gets all capabilities added on activation.

---

## 4. Request Workflows

### Exit Permit
```
Student ──► pending_dorm ──► [Dorm Supervisor: approve/reject]
                   │
                   ▼ (approve)
            pending_manager ──► [SA Manager: approve/reject]
                   │
                   ▼ (approve)
              approved ──► [Mark Executed]
```

### Overnight Permit
```
Student ──► pending_supervisor ──► [Student Supervisor]
                   │
                   ▼ (approve)
            pending_manager ──► [SA Manager]
                   │
                   ▼ (approve)
            pending_dean ──► [Dean]
                   │
                   ▼ (approve)
              approved ──► [Dorm Supervisor: Execute + print daily PDF]
```

---

## 5. Behavior System

- Points are variable per violation (1–30), capped by role and violation type `max_points`
- **Thresholds**: at 10, 20, 30 points → email student + show acknowledgment action in portal
- **At 40 points**: create `Expulsion Case` → email Dean → Dean approves/rejects → if approved: generate expulsion PDF, update student status to `expelled`, email student + staff
- Violations can be overturned by Dean (full audit recorded)

---

## 6. Shortcodes for Student Portal

| Shortcode | Page Purpose |
|---|---|
| `[rsyi_portal_register]` | Self-registration form |
| `[rsyi_portal_dashboard]` | Student home + pending warning acknowledgments |
| `[rsyi_portal_documents]` | Upload 8 mandatory documents |
| `[rsyi_portal_requests]` | Submit exit/overnight permits + view history |
| `[rsyi_portal_behavior]` | View points, violations, warnings timeline |

---

## 7. PDF Generation

The `PDF_Generator` class uses a **priority chain**:
1. **mPDF** (via Composer) – best RTL Arabic support, proper font embedding
2. **TCPDF** (if available) – fallback
3. **Styled HTML file** – final fallback; browsers can print-to-PDF

To enable mPDF: run `composer install` inside the plugin directory.

Generated PDFs are stored in `wp-content/uploads/rsyi-docs/pdfs/` and served via the Secure Download handler.

---

## 8. Installation

### Requirements
- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

### Steps
1. Upload plugin folder to `/wp-content/plugins/rsyi-student-affairs/`
2. Run `composer install` (for mPDF PDF generation)
3. Activate in **Plugins → Installed Plugins**
4. Create portal pages and add shortcodes (see §6)
5. Assign staff users their roles under **Users → Edit User**

---

## 9. Implementation Plan

### Phase 1 – Foundation (Week 1–2) — ~40 hrs
- [x] DB schema + `DB_Installer` (activation hook)
- [x] Custom roles + capabilities
- [x] Secure file upload directory + `.htaccess` guard
- [x] Audit log class
- [x] Autoloader + main plugin bootstrap

### Phase 2 – Accounts & Documents (Week 2–3) — ~30 hrs
- [x] Student self-registration + staff-created profiles
- [x] 8 mandatory document upload with MIME validation
- [x] Document approve/reject + student activation trigger
- [x] Secure download handler (nonce + capability)
- [x] Email notifications (approved/rejected/activated)

### Phase 3 – Request Workflows (Week 3–4) — ~35 hrs
- [x] Exit permit 2-step workflow (AJAX)
- [x] Overnight permit 3-step workflow (AJAX)
- [x] Execute action + email notifications at each step
- [x] Admin list screens with approve/reject buttons

### Phase 4 – Behavior System (Week 4–5) — ~30 hrs
- [x] Violation types catalog (admin CRUD)
- [x] Violation recording with role-based point limits
- [x] Threshold logic (10/20/30) → email + portal acknowledgment
- [x] At-40-points expulsion case creation
- [x] Dean approve/reject expulsion → PDF letter generation

### Phase 5 – Cohort Governance & PDF (Week 5–6) — ~25 hrs
- [x] Cohort CRUD
- [x] Cohort transfer request → Dean approval → execution + audit
- [x] Daily aggregated PDF report (mPDF RTL)
- [x] Expulsion letter PDF template

### Phase 6 – Admin UI & Portal Polish (Week 6–7) — ~30 hrs
- [x] Admin menu with all sub-pages
- [x] RTL admin CSS + admin JS (approve/reject helpers)
- [x] Student portal shortcodes (5 pages)
- [x] Portal CSS RTL + portal JS (upload, validation)
- [ ] WP-List-Table implementations for all entities (enhancement)
- [ ] Bulk actions (approve/reject multiple documents)
- [ ] Export to CSV

### Phase 7 – QA & Hardening (Week 7–8) — ~20 hrs
- [ ] PHPUnit test suite (DB installer, behavior threshold logic)
- [ ] PHPCS WordPress coding standards audit
- [ ] Nginx rules for upload directory protection (server config)
- [ ] Load testing for PDF generation
- [ ] Security review (nonce audit, capability checks, SQL injection)

### Phase 8 – Localization & Deployment (Week 8) — ~10 hrs
- [ ] Generate `.pot` file with WP-CLI (`wp i18n make-pot`)
- [ ] Arabic translations (`.po` / `.mo`)
- [ ] Production deployment checklist
- [ ] Staff training documentation

---

**Total Estimated Effort: ~220 hours** (solo developer)
**With 2 developers: ~10–12 weeks**

---

## 10. Security Notes

- Files stored **outside webroot** with `.htaccess Deny from all`
- All downloads routed through nonce-verified + capability-checked handler
- All AJAX endpoints use `check_ajax_referer()` / `wp_verify_nonce()`
- MIME type detected from file content (not extension)
- Path traversal guard on download handler (`realpath()` prefix check)
- All DB queries use `$wpdb->prepare()` with typed placeholders
- Points capped by both role limit and violation type `max_points`
- Expulsion is never automatic — always requires explicit Dean approval
