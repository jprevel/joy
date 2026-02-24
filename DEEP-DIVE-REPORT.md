# Joy — Technical Deep Dive Report
*Generated: 2026-02-16*

## What Joy IS Right Now

A **content calendar and collaboration platform** for marketing agencies. Built with Laravel 12 + Livewire + Filament 4 + Tailwind 4 + Alpine.js + Postgres.

### Core Features (Built & Working)
- **Content Calendar** — monthly view, filter by client, role-based views (client/agency/admin)
- **Content Items** — create, edit, schedule across platforms (Facebook, Instagram, LinkedIn, Twitter, Blog)
- **Content Review** — date-based review page for approvals
- **Client Management** — CRUD via Filament admin panel + Livewire pages
- **User Management** — roles (admin/agency/client), teams, permissions (Spatie)
- **Magic Links** — token-based client access without login
- **Statusfaction** — weekly status updates for account managers (gate-protected)
- **Audit Logging** — comprehensive with cleanup, formatters, security tracking
- **Comments** — on content items with Slack notifications
- **Slack Integration** — workspace connection, notifications on comments/status changes
- **Trello Integration** — board/list mapping per client
- **Filament Admin** — Users, Clients, Roles resources

### Models (10)
AuditLog, Client, ClientStatusfactionUpdate, Comment, ContentItem, MagicLink, SlackNotification, SlackWorkspace, Team, User

### Migrations: 25 (Sept-Oct 2025)
Heavy refactoring history — client_workspaces → clients, variants → content_items, concepts consolidated. Schema is stable now.

---

## 🔴 Bugs Found

### P0 — Will Break Things
1. **Missing `content.detail` route** — `EditContent.php` lines 101 & 110 redirect to `route('content.detail')` but this route doesn't exist in `web.php`. After editing content, users hit a 500 error.
   - **Fix:** Add route in web.php: `Route::get('/content/{contentItem}', ContentDetail::class)->name('content.detail');`

2. **Seeder runs on every deploy** — Dockerfile CMD runs `db:seed --force` every time. After first deploy, this will either error (duplicate data) or truncate + recreate (ContentItemSeeder calls `ContentItem::truncate()`).
   - **Fix:** Remove `db:seed` from CMD, or add `--class=DatabaseSeeder` with idempotent checks.

### P1 — Needs Fixing Before Launch
3. **`access statusfaction` permission not in seeders** — Gate checks for it but PermissionSeeder doesn't create it. Statusfaction page will 403 for everyone.
   - **Fix:** Add to PermissionSeeder.

4. **Debug route exposed** — `Route::get('/debug', ...)` serves `calendar-debug.html` in production. Security risk.
   - **Fix:** Remove or wrap in `APP_DEBUG` check.

5. **AdminAuth IP whitelist too strict** — Only allows localhost IPs. On Railway, all requests come from load balancer IPs. Admin panel may be inaccessible.
   - **Fix:** The "testing mode" bypass (`isTestingModeAdminAccess`) exists but should be verified on Railway.

6. **APP_DEBUG likely `true` in production** — `.env.example` has `APP_DEBUG=true`. If Railway inherited this, stack traces are exposed.
   - **Fix:** Verify Railway env vars, ensure `APP_DEBUG=false`.

### P2 — Should Fix
7. **ContentReview hardcoded to first client** — `$this->client = Client::first()` regardless of auth. In production, should respect user's client access.

8. **No HTTPS forced** — No `ForceScheme` middleware or `FORCE_HTTPS` env. Railway provides HTTPS but mixed content possible.

9. **Slack credentials empty** — Integration exists but won't work without `SLACK_BOT_TOKEN`, `SLACK_CLIENT_ID`, `SLACK_CLIENT_SECRET` in env.

---

## 🟡 Launch Blocklist (Must-Fix for Real Users)

| # | Item | Effort | Priority |
|---|------|--------|----------|
| 1 | Fix `content.detail` missing route | 5 min | P0 |
| 2 | Remove `db:seed` from Dockerfile CMD | 5 min | P0 |
| 3 | Add `access statusfaction` permission to seeder | 10 min | P1 |
| 4 | Remove/protect debug route | 5 min | P1 |
| 5 | Verify APP_DEBUG=false on Railway | 5 min | P1 |
| 6 | Test AdminAuth middleware on Railway | 30 min | P1 |
| 7 | Fix ContentReview client scoping | 30 min | P2 |
| 8 | Add HTTPS forcing | 10 min | P2 |
| 9 | Replace seed data with real MajorMajor clients | 1 hr | P1 |
| 10 | Change default admin credentials | 10 min | P0 |

---

## Feature Status Matrix

| Feature | Status | Notes |
|---------|--------|-------|
| Content Calendar | ✅ Built | Monthly view, role-based, client switching |
| Content CRUD | ✅ Built | Add, edit, image uploads, platform selection |
| Content Review | ⚠️ Partial | Works but hardcoded to first client |
| Client Management | ✅ Built | Filament + Livewire admin pages |
| User Management | ✅ Built | Roles, permissions, teams |
| Magic Links | ✅ Built | Token-based client portal access |
| Statusfaction | ⚠️ Broken | Permission not seeded, will 403 |
| Audit Logging | ✅ Built | Comprehensive, with cleanup command |
| Comments | ✅ Built | With Slack notification observers |
| Slack Integration | ⚠️ Stub | Code exists, no credentials configured |
| Trello Integration | ⚠️ Stub | CRUD exists, no active sync |
| NPS Tracking | ❌ Not built | Spec exists in Life Ops, not started |
| Reporting/Analytics | ❌ Not built | — |
| Email Notifications | ❌ Not built | Mail driver set to `log` |
| File Storage (S3) | ❌ Not configured | Using local disk, won't persist on Railway |

---

## Code Quality Notes

**Good:**
- Clean service layer (ContentItemService, CommentService, MagicLinkService, etc.)
- Contracts/interfaces for all services
- DTOs for audit logging
- Observers for Slack notifications
- Proper factory pattern for testing
- Constitutional TDD scripts (ambitious!)

**Concerns:**
- Content calendar loads ALL content items for a client, then filters in PHP — no date range query
- No pagination on content items
- `ContentItemSeeder::truncate()` is dangerous in production
- Some N+1 potential in calendar (loads items then checks dates per-day)

---

## Recommended 2-Week Sprint Plan

### Week 1: Fix & Stabilize
- [ ] **Day 1:** Fix all P0 bugs (missing route, seeder, default creds)
- [ ] **Day 1:** Fix P1 bugs (debug route, APP_DEBUG, statusfaction permission)
- [ ] **Day 2:** Replace seed data with real MajorMajor clients + team members
- [ ] **Day 2:** Test full login flow on Railway (admin, agency, client roles)
- [ ] **Day 3:** Configure file storage (S3 or Railway volume) for image uploads
- [ ] **Day 3:** Fix ContentReview client scoping
- [ ] **Day 4:** Test magic link flow end-to-end
- [ ] **Day 5:** Add date-range filtering to calendar queries (performance)

### Week 2: Polish & Ship
- [ ] **Day 6-7:** Slack integration — get bot token, test notifications
- [ ] **Day 8:** Email notifications for magic links + content approvals
- [ ] **Day 9:** UI polish — loading states, error handling, mobile responsive check
- [ ] **Day 10:** User acceptance testing with MajorMajor team (Judith, Shaira)

---

## Dockerfile Recommendation

```dockerfile
# Change from php:8.4-cli to php:8.4-fpm or use Octane for production
# Current: artisan serve (single-threaded, not production-ready)
# Recommend: Add nginx or switch to Laravel Octane
```

The `php artisan serve` in production handles only one request at a time. For MajorMajor's scale (small team) this is probably fine initially, but should move to Octane or nginx+fpm before scaling.

---

## Summary

Joy is surprisingly feature-complete for a content calendar MVP. The bones are solid — clean architecture, proper service layer, role-based access. The main risks are:

1. **3 bugs that will crash on first use** (missing route, seeder loop, statusfaction 403)
2. **Default credentials** need changing before real users
3. **File storage** won't persist between Railway deploys
4. **Single-threaded server** (acceptable for now)

With ~3-4 days of bug fixes and config, this is launchable for internal MajorMajor use.
