# Production Deployment Guide: Session Reliability Fix

**Date**: 2025-10-13
**Branch**: `main` (merged from `006-session-reliability`)
**Commit**: `909b55c`
**Priority**: P0 (Production Blocker Fix)

---

## 🎯 What This Deployment Fixes

**Problem**: Users encounter "419 Page Expired" errors when clicking logout after the app sits idle
**Solution**: Improved CSRF handling + GET logout route fallback
**Impact**: Zero downtime, backward compatible, immediate effect

---

## 📋 Pre-Deployment Checklist

Before deploying, ensure:

- [ ] You have SSH access to production server
- [ ] You have database backup (though no schema changes needed)
- [ ] You can run `php artisan` commands
- [ ] You can restart PHP-FPM/web server
- [ ] You have tested logout functionality in staging (optional but recommended)

---

## 🚀 Deployment Steps

### Step 1: Put Application in Maintenance Mode (Optional)

```bash
php artisan down --message="Deploying session fixes" --retry=60
```

**Note**: This is optional since the changes are non-breaking and can be deployed with zero downtime.

### Step 2: Pull Latest Code

```bash
# Navigate to application directory
cd /path/to/joy/joy-app

# Pull latest main branch
git pull origin main

# Verify you're on the right commit
git log --oneline -1
# Should show: 909b55c Merge Phase 1: Session Reliability - Eliminate 419 errors
```

### Step 3: Install Dependencies (if needed)

```bash
# Update composer dependencies (unlikely to have changes, but safe to run)
composer install --no-dev --optimize-autoloader

# No npm changes in this deployment, skip if not needed
# npm ci && npm run build
```

### Step 4: Clear Caches

```bash
# Clear all Laravel caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Step 5: Restart Services

```bash
# Restart PHP-FPM (command varies by system)
sudo systemctl restart php8.4-fpm
# OR
sudo systemctl restart php-fpm
# OR
sudo service php8.4-fpm restart

# Restart web server (if using nginx/apache)
sudo systemctl restart nginx
# OR
sudo systemctl restart apache2
```

### Step 6: Bring Application Back Up

```bash
php artisan up
```

---

## ✅ Post-Deployment Verification

### Immediate Checks (< 5 minutes)

1. **Login Test**
   ```bash
   # Access login page
   curl -I https://your-domain.com/login
   # Should return: 200 OK
   ```

2. **GET Logout Test**
   ```bash
   # Test new GET logout route (should redirect to login)
   curl -I https://your-domain.com/logout
   # Should return: 302 Redirect
   ```

3. **Dashboard Access Test**
   - Log in as admin user
   - Access dashboard
   - Click logout button
   - Should redirect to login with no errors

### Extended Monitoring (24-48 hours)

Monitor for these metrics:

1. **Error Logs**
   ```bash
   # Watch for 419 errors (should be zero)
   tail -f storage/logs/laravel.log | grep "419"
   ```

2. **Session Errors**
   ```bash
   # Watch for session invalidation errors
   tail -f storage/logs/laravel.log | grep "Session invalidation failed"
   ```

3. **User Feedback**
   - Monitor support tickets for logout issues
   - Check user reports of "Page Expired" errors

---

## 📊 Expected Results

### Before Deployment
- ❌ Users see "419 Page Expired" after idle sessions
- ❌ Logout fails randomly
- ❌ Confusing error messages

### After Deployment
- ✅ Zero "419 Page Expired" errors
- ✅ Logout always works (GET or POST)
- ✅ Friendly "Your session has expired" message
- ✅ 100% logout success rate

---

## 🔧 Technical Changes Deployed

### 1. Enhanced CSRF Exception Handler
**File**: `joy-app/bootstrap/app.php` (lines 28-44)

```php
// Handle CSRF token expiration (419 Page Expired error)
if ($exception instanceof \Illuminate\Session\TokenMismatchException && $request->expectsHtml()) {
    // Safely clear old session with try-catch for already-invalidated sessions
    try {
        if (session()->isStarted()) {
            session()->invalidate();
            session()->regenerateToken();
        }
    } catch (\Exception $e) {
        // Session already invalidated or corrupted - ignore and proceed
        \Log::info('Session invalidation failed during CSRF exception', [
            'exception' => $e->getMessage(),
            'url' => $request->url(),
        ]);
    }

    return redirect()->route('login')->with('status', 'Your session has expired. Please log in again.');
}
```

**What changed**:
- Added `session()->isStarted()` check
- Wrapped in try-catch for safety
- Added error logging
- Always redirects gracefully

### 2. GET Logout Route
**File**: `joy-app/routes/web.php` (line 18)

```php
// Alternative GET logout route (no CSRF required - useful when session expired)
Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get')->middleware('auth');
```

**What changed**:
- New GET route for logout
- No CSRF token required
- Falls back when session expired
- Uses same controller as POST logout

### 3. Automated Tests
**File**: `joy-app/tests/Feature/AdminContentManagementE2ETest.php` (lines 494-519)

Added 2 new tests:
- `get_logout_works_without_csrf_token()`
- `post_logout_works_with_valid_session()`

**Test results**:
- 265 tests passing (+2 new)
- 536 assertions (+6 new)
- 0 failures

---

## 🔄 Rollback Plan

If you encounter issues, rollback is simple:

### Option 1: Quick Rollback (Recommended)

```bash
# Put in maintenance mode
php artisan down

# Revert to previous commit
git checkout aa3a436  # Previous stable commit

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

# Restart services
sudo systemctl restart php-fpm
sudo systemctl restart nginx

# Bring back up
php artisan up
```

### Option 2: Git Revert

```bash
# Revert the merge commit
git revert 909b55c --no-edit

# Deploy reverted code
git push origin main

# Then follow deployment steps 3-6 above
```

**Note**: Rollback is safe because:
- No database migrations
- No schema changes
- No breaking changes
- Fully backward compatible

---

## 🐛 Troubleshooting

### Issue: "Class not found" errors

**Solution**:
```bash
composer dump-autoload
php artisan optimize:clear
```

### Issue: Routes not working

**Solution**:
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: Still seeing 419 errors

**Solution**:
1. Check PHP-FPM restarted: `sudo systemctl status php-fpm`
2. Check web server restarted: `sudo systemctl status nginx`
3. Clear browser cache and cookies
4. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Issue: Users can't logout

**Solution**:
1. Verify GET /logout route exists: `php artisan route:list | grep logout`
2. Should show both:
   - `POST /logout`
   - `GET /logout`
3. If not, run: `php artisan route:cache`

---

## 📝 Testing Scenarios (Optional Pre-Production)

### Scenario 1: Normal Logout
1. Log in to application
2. Click logout button immediately
3. **Expected**: Redirect to login page

### Scenario 2: Idle Session Logout
1. Log in to application
2. Wait 2+ hours (or simulate by expiring session in DB)
3. Click logout button
4. **Expected**: Redirect to login with "Your session has expired" message

### Scenario 3: GET Logout
1. Log in to application
2. Navigate to `/logout` in browser address bar
3. **Expected**: Logged out, redirect to login

### Scenario 4: Multiple Browser Tabs
1. Log in on Tab 1
2. Open Tab 2 with same session
3. Logout on Tab 1
4. Try to interact on Tab 2
5. **Expected**: Graceful redirect to login (no 419 error)

---

## 📊 Monitoring Checklist (First 48 Hours)

- [ ] Monitor error logs for 419 errors (should be zero)
- [ ] Check application logs for session errors
- [ ] Monitor user support tickets
- [ ] Track logout success rate
- [ ] Verify no increase in failed login attempts

---

## 🎉 Success Indicators

You'll know the deployment was successful when:

1. **Zero 419 errors** in logs
2. **No user complaints** about logout issues
3. **All tests passing** in production
4. **Smooth user experience** when sessions expire
5. **Monitoring shows** 100% logout success rate

---

## 📞 Support

If you encounter issues during deployment:

1. **Check this guide** for troubleshooting steps
2. **Review commit** 51c89a1 for technical details
3. **Check planning docs** in `specs/006-session-reliability/`
4. **Rollback if needed** using steps above

---

## 📚 Additional Resources

**Planning Documentation**:
- `specs/006-session-reliability/spec.md` - Feature specification
- `specs/006-session-reliability/plan.md` - Implementation plan
- `specs/006-session-reliability/quickstart.md` - Testing guide
- `specs/006-session-reliability/research.md` - Technical research

**Test Coverage**:
- Location: `joy-app/tests/Feature/AdminContentManagementE2ETest.php`
- Lines: 494-519
- Tests: 2 new tests, 6 assertions

---

## ✅ Deployment Complete

Once deployed successfully, mark these items:

- [ ] Code deployed to production
- [ ] Services restarted
- [ ] Caches cleared
- [ ] Basic testing completed
- [ ] Monitoring enabled
- [ ] Team notified
- [ ] Documentation updated

**Deployment Time Estimate**: 10-15 minutes
**Downtime**: Zero (or < 1 minute if using maintenance mode)
**Risk Level**: Low (no database changes, backward compatible)

---

**Deployed By**: _______________
**Date**: _______________
**Time**: _______________
**Notes**: _______________
