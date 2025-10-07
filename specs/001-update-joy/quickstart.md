# Quickstart Guide: Joy Content Calendar System

## System Overview
The Joy Content Calendar System enables agencies to manage content for clients with secure review and approval workflows.

## Setup Requirements

### Prerequisites
- PHP 8.2+
- Laravel 11
- MySQL/PostgreSQL
- Composer
- Node.js (for frontend assets)

### Installation Steps
```bash
# Clone and setup
git clone [repository]
cd joy
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Build assets
npm run build
```

## Core User Scenarios

### Scenario 1: Agency Creates Content for Client Review

**Prerequisites**: Admin user exists, Client workspace created

**Steps**:
1. Agency user logs into admin panel
2. Navigate to Content management
3. Create new content item:
   - Select client workspace
   - Enter title: "Summer Campaign Post"
   - Select platform: "Facebook"
   - Add description and media
   - Save as "Draft" status
4. Update content status to "Review"
5. Generate magic link for client
6. Send magic link to client

**Expected Results**:
- Content item created with "Review" status
- Magic link generated with view, comment, approve scopes
- Client can access content via magic link
- Content appears in client's calendar view

**Test Validation**:
```bash
# Verify content creation
php artisan tinker
> $content = ContentItem::where('title', 'Summer Campaign Post')->first()
> $content->status // Should be 'review'
> $content->platform // Should be 'facebook'

# Verify magic link creation
> $link = MagicLink::where('client_id', $content->client_id)->latest()->first()
> $link->scopes // Should include ['view', 'comment', 'approve']
> $link->expires_at->isFuture() // Should be true
```

### Scenario 2: Client Reviews and Approves Content

**Prerequisites**: Content item in "Review" status, valid magic link exists

**Steps**:
1. Client opens magic link URL
2. Views calendar with content items
3. Clicks on content item to view details
4. Adds comment: "Looks great, ready to publish!"
5. Clicks "Approve" button
6. Content status changes to "Approved"

**Expected Results**:
- Client can access content without login
- Comment is saved and synced to Trello
- Content status updates to "Approved"
- Audit log records client approval
- Agency receives notification (via Trello/Slack)

**Test Validation**:
```bash
# Verify comment creation and Trello sync
php artisan tinker
> $comment = Comment::where('content', 'LIKE', '%ready to publish%')->first()
> $comment->content_item->status // Should be 'approved'
> $trelloCard = TrelloCard::where('comment_id', $comment->id)->first()
> $trelloCard->sync_status // Should be 'synced'

# Verify audit logging
> AuditLog::where('event', 'approved')->where('auditable_id', $comment->content_item->id)->exists() // Should be true
```

### Scenario 3: Calendar View with Multi-Platform Content

**Prerequisites**: Multiple content items across different platforms and dates

**Steps**:
1. Create content items for different platforms:
   - Facebook post for Monday
   - Instagram story for Tuesday
   - LinkedIn article for Wednesday
   - Twitter thread for Friday
2. Access calendar view via magic link
3. Navigate between months
4. Filter by platform
5. View timeline chronological list

**Expected Results**:
- Calendar displays content on correct dates
- Platform color coding is visible
- Comment counts show on calendar items
- Timeline shows chronological order
- Filters work correctly

**Test Validation**:
```bash
# Verify calendar data structure
php artisan tinker
> $client = Client::first()
> $content = ContentItem::where('client_id', $client->id)
    ->whereBetween('scheduled_at', [now()->startOfMonth(), now()->endOfMonth()])
    ->get()
> $content->pluck('platform')->unique() // Should show multiple platforms
> $content->sortBy('scheduled_at') // Should be in chronological order
```

### Scenario 4: Magic Link Security and Expiration

**Prerequisites**: Magic links with different expiration times and PIN protection

**Steps**:
1. Create magic link with 24-hour expiration
2. Create magic link with PIN protection
3. Access link before expiration - should work
4. Wait for expiration (or manually expire)
5. Access expired link - should fail
6. Access PIN-protected link without PIN - should request PIN
7. Access PIN-protected link with correct PIN - should work

**Expected Results**:
- Valid links grant access
- Expired links show expiration message
- PIN protection enforced
- All access attempts logged in audit trail

**Test Validation**:
```bash
# Test link expiration
php artisan tinker
> $link = MagicLink::factory()->expired()->create()
> $link->expires_at->isPast() // Should be true

# Test PIN protection
> $pinLink = MagicLink::factory()->withPin('1234')->create()
> $pinLink->pin // Should be '1234'

# Verify access logging
> AuditLog::where('event', 'accessed')->where('auditable_type', 'MagicLink')->count() // Should track access
```

## API Testing

### Content API Tests
```bash
# Test content creation
curl -X POST http://localhost/api/content \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "uuid-here",
    "title": "API Test Content",
    "platform": "instagram",
    "description": "Test content via API"
  }'

# Test content listing with filters
curl "http://localhost/api/content?client_id=uuid-here&status=review&platform=facebook"
```

### Magic Link API Tests
```bash
# Test magic link creation
curl -X POST http://localhost/api/magic-links \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "uuid-here",
    "scopes": ["view", "comment", "approve"],
    "expires_at": "2025-09-15T23:59:59Z"
  }'

# Test magic link access
curl "http://localhost/magic/uuid-token-here"
```

## Performance Testing

### Database Query Performance
```bash
# Check for N+1 queries
php artisan telescope:install
php artisan migrate

# Monitor queries during content listing
# Ensure eager loading for relationships:
# ContentItem::with(['client', 'user', 'comments'])->get()
```

### Load Testing
```bash
# Use Apache Bench for basic load testing
ab -n 1000 -c 10 http://localhost/magic/test-token

# Monitor response times should be < 1 second
```

## Integration Testing

### Trello Integration
```bash
# Test comment sync to Trello
php artisan tinker
> $comment = Comment::factory()->create()
> dispatch(new SyncCommentToTrello($comment))
> // Check Trello board for new card
```

### Email Notifications
```bash
# Test magic link email delivery
php artisan tinker
> $client = Client::first()
> $link = MagicLink::factory()->create(['client_id' => $client->id])
> Mail::send(new MagicLinkEmail($link))
> // Check mail logs or test mailbox
```

## Troubleshooting

### Common Issues

**Magic Link Not Working**:
- Check link expiration: `MagicLink::where('token', $token)->first()->expires_at`
- Verify client exists: `Client::find($client_id)`
- Check scopes: `$link->scopes`

**Trello Sync Failing**:
- Verify API credentials in `.env`
- Check network connectivity
- Review sync status: `TrelloCard::where('sync_status', 'failed')->get()`

**Calendar Not Loading**:
- Check database connections
- Verify content items have valid `scheduled_at` dates
- Check for JavaScript errors in browser console

### Debugging Commands
```bash
# Check system health
php artisan health:check

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check logs
tail -f storage/logs/laravel.log
```

## Development Workflow

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/ContentManagementTest.php

# Run with coverage
php artisan test --coverage
```

### Static Analysis
```bash
# Run PHPStan analysis
vendor/bin/phpstan analyse

# Run code style checks
vendor/bin/php-cs-fixer fix --dry-run
```

This quickstart guide ensures all core system functionality works as expected and provides a foundation for ongoing development and testing.