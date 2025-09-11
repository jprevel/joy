<div>
<!doctype html>
<html lang="en" class="h-full antialiased">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Joy Content Calendar</title>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- MajorMajor Brand Styles -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  
  <style>
    /* Ensure calendar cells don't clip content */
    .calendar-cell {
      overflow: visible !important;
    }
  </style>
</head>
<body class="with-stripe h-full bg-neutral-50 text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 selection:bg-indigo-200/60 selection:text-neutral-900">
  
  <!-- Role Testing Bar -->
  <div class="bg-yellow-100 dark:bg-yellow-900/30 border-b border-yellow-200 dark:border-yellow-800 px-4 py-2">
    <div class="mx-auto max-w-5xl flex items-center justify-between">
      <div class="flex items-center gap-2 text-sm">
        <span class="font-medium text-yellow-800 dark:text-yellow-200">Testing Mode:</span>
        <span class="px-2 py-1 bg-yellow-200 dark:bg-yellow-800 text-yellow-900 dark:text-yellow-100 rounded text-xs font-bold uppercase">
          {{ $currentRole }}
        </span>
        @php $currentUser = $this->getCurrentUserRole(); @endphp
        @if($currentUser)
          <span class="text-xs text-yellow-700 dark:text-yellow-300">
            ({{ $currentUser->name }})
          </span>
          <div class="text-xs text-yellow-600 dark:text-yellow-400">
            Permissions: 
            @if($this->hasPermission('view calendar')) ✓ View @endif
            @if($this->hasPermission('edit content')) ✓ Edit @endif
            @if($this->hasPermission('approve content')) ✓ Approve @endif
            @if($this->hasPermission('manage clients')) ✓ Manage @endif
            @if($this->hasPermission('manage system')) ✓ System @endif
          </div>
        @endif
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs text-yellow-700 dark:text-yellow-300">Switch Role:</span>
        <a href="{{ route('calendar.role', 'client') }}" 
           class="px-2 py-1 text-xs rounded transition {{ $currentRole === 'client' ? 'bg-yellow-300 dark:bg-yellow-700 font-bold' : 'bg-yellow-200 dark:bg-yellow-800 hover:bg-yellow-300 dark:hover:bg-yellow-700' }} text-yellow-900 dark:text-yellow-100">
          Client
        </a>
        <a href="{{ route('calendar.role', 'agency') }}" 
           class="px-2 py-1 text-xs rounded transition {{ $currentRole === 'agency' ? 'bg-yellow-300 dark:bg-yellow-700 font-bold' : 'bg-yellow-200 dark:bg-yellow-800 hover:bg-yellow-300 dark:hover:bg-yellow-700' }} text-yellow-900 dark:text-yellow-100">
          Agency
        </a>
        <a href="{{ route('calendar.role', 'admin') }}" 
           class="px-2 py-1 text-xs rounded transition {{ $currentRole === 'admin' ? 'bg-yellow-300 dark:bg-yellow-700 font-bold' : 'bg-yellow-200 dark:bg-yellow-800 hover:bg-yellow-300 dark:hover:bg-yellow-700' }} text-yellow-900 dark:text-yellow-100">
          Admin
        </a>
      </div>
    </div>
  </div>
  
  <div class="min-h-full py-10 px-4 md:px-8">
    <div class="mx-auto max-w-5xl space-y-6">
      <!-- Page Header -->
      <div class="border-b border-neutral-200 dark:border-neutral-800 pb-4">
        <div class="flex items-center gap-4">
          <img src="/MM_logo.png" alt="MajorMajor Logo" class="h-16 w-auto">
          <div>
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">
              {{ $client ? $client->name : 'Client' }} Content Calendar
              <span class="text-sm font-normal text-neutral-500 dark:text-neutral-400">
                ({{ ucfirst($currentRole) }} View)
              </span>
            </h1>
            @if($client && $client->description)
              <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">{{ $client->description }}</p>
            @endif
          </div>
        </div>
      </div>
      
      <!-- Calendar Controls -->
      <header class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-3">
          <h1 id="monthLabel" class="text-2xl font-semibold tracking-tight"></h1>
          <span id="todayBadge" class="hidden text-xs rounded-full px-2 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">Today</span>
        </div>
        <div class="flex items-center gap-2">
          @if($this->hasPermission('edit content'))
            <a href="{{ route('content.add', $currentRole) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Add Content
            </a>
          @endif
          <!-- View Toggle -->
          <div id="viewToggle" class="inline-flex rounded-md border border-neutral-300 dark:border-neutral-700 overflow-hidden">
            <button id="calendarBtn"
                    class="px-3 py-1.5 text-sm font-medium transition view-toggle-btn active"
                    title="Calendar view">
              Calendar
            </button>
            <button id="timelineBtn"
                    class="px-3 py-1.5 text-sm font-medium transition view-toggle-btn"
                    title="Timeline view">
              Timeline
            </button>
          </div>
          
          <button id="todayBtn"
                  class="rounded-md border border-neutral-300 dark:border-neutral-700 px-3 py-1.5 text-sm font-medium hover:bg-neutral-100 dark:hover:bg-neutral-800 transition"
                  title="Jump to current month (T)">
            Today
          </button>
          <div class="inline-flex rounded-md border border-neutral-300 dark:border-neutral-700 overflow-hidden">
            <button id="prevBtn"
                    class="px-3 py-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition"
                    title="Previous month (←)">
              <!-- chevron left -->
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <button id="nextBtn"
                    class="px-3 py-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition"
                    title="Next month (→)">
              <!-- chevron right -->
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </div>
      </header>

      <!-- Calendar View -->
      <div id="calendarView" class="view-content">
        <!-- Weekday headings -->
        <div class="grid grid-cols-7 rounded-lg bg-white dark:bg-neutral-800 ring-1 ring-black/5 dark:ring-white/5">
          <div class="col-span-7 grid grid-cols-7 text-xs uppercase tracking-wider text-neutral-500 dark:text-neutral-400 border-b border-neutral-100 dark:border-neutral-700">
            <!-- Sunday-first -->
            <div class="px-2 py-2 text-center">Sun</div>
            <div class="px-2 py-2 text-center">Mon</div>
            <div class="px-2 py-2 text-center">Tue</div>
            <div class="px-2 py-2 text-center">Wed</div>
            <div class="px-2 py-2 text-center">Thu</div>
            <div class="px-2 py-2 text-center">Fri</div>
            <div class="px-2 py-2 text-center">Sat</div>
          </div>

          <!-- Calendar grid -->
          <div id="grid"
               class="col-span-7 grid grid-cols-7 auto-rows-[minmax(96px,1fr)] md:auto-rows-[minmax(120px,1fr)]">
            <!-- days injected here -->
          </div>
        </div>

        <!-- Calendar Legend -->
        <div class="flex flex-wrap items-center gap-4 text-sm text-neutral-600 dark:text-neutral-300">
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded-md border-2 border-emerald-500"></span> Today
          </span>
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded-md bg-gradient-to-br from-yellow-100 to-yellow-200 border border-yellow-600 dark:from-yellow-800 dark:to-yellow-700"></span> Needs Review
          </span>
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded-md bg-gradient-to-br from-green-100 to-green-200 border border-green-600 dark:from-green-800 dark:to-green-700"></span> All Approved
          </span>
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded-md bg-neutral-100 dark:bg-neutral-800 opacity-70"></span> No Content
          </span>
        </div>
      </div>

      <!-- Timeline View -->
      <div id="timelineView" class="view-content hidden">
        <!-- Timeline Legend -->
        <div class="flex flex-wrap items-center gap-4 text-sm text-neutral-600 dark:text-neutral-300">
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded-md border-2 border-emerald-500"></span> Today
          </span>
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded-md bg-gradient-to-br from-yellow-100 to-yellow-200 border border-yellow-600 dark:from-yellow-800 dark:to-yellow-700"></span> Needs Review
          </span>
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded-md bg-gradient-to-br from-green-100 to-green-200 border border-green-600 dark:from-green-800 dark:to-green-700"></span> All Approved
          </span>
          <span class="inline-flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded-md bg-neutral-100 dark:bg-neutral-800 opacity-70"></span> No Content
          </span>
        </div>

        <!-- Timeline -->
        <section class="relative">
          <!-- vertical rail -->
          <div class="absolute left-6 top-0 bottom-0 w-px bg-neutral-200 dark:bg-neutral-700"></div>

          <ol id="timeline" class="space-y-3 fade-in" aria-label="Monthly timeline">
            <!-- items injected here -->
          </ol>
        </section>
      </div>
    </div>
  </div>

  <script>
    // --- Utilities -----------------------------------------------------------
    const pad = n => String(n).padStart(2, '0');
    const fmtKey = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

    const startOfMonth = (d) => new Date(d.getFullYear(), d.getMonth(), 1);
    const endOfMonth = (d) => new Date(d.getFullYear(), d.getMonth() + 1, 0);
    const startOfGrid = (d) => {
      const first = startOfMonth(d);
      const day = first.getDay(); // 0=Sun
      const gridStart = new Date(first);
      gridStart.setDate(first.getDate() - day);
      return gridStart;
    };
    const addDays = (d, n) => {
      const copy = new Date(d);
      copy.setDate(copy.getDate() + n);
      return copy;
    };

    // --- Real events data from Laravel ---
    // Map of ISO "YYYY-MM-DD" -> array of event objects
    let events = {};
    
    // Initialize events data
    function initializeEvents() {
        events = {};
        @php
            foreach ($contentItems as $contentItem) {
                if ($contentItem->scheduled_at) {
                    $date = $contentItem->scheduled_at->format('Y-m-d');
                    echo "events['$date'] = events['$date'] || [];\n";
                    echo "events['$date'].push({";
                    echo "title: '" . addslashes($contentItem->title) . "',";
                    echo "platform: '" . $contentItem->platform . "',";
                    echo "status: '" . $contentItem->status . "'";
                    echo "});\n";
                }
            }
        @endphp
    }
    
    // Initialize events on page load
    initializeEvents();

    // --- State ---------------------------------------------------------------
    const today = new Date();
    let view = new Date(today.getFullYear(), today.getMonth(), 1);
    let lastDir = 'none'; // 'left' or 'right' for slide direction
    let currentView = 'calendar'; // 'calendar' or 'timeline'

    // --- DOM refs ------------------------------------------------------------
    const grid = document.getElementById('grid');
    const timeline = document.getElementById('timeline');
    const label = document.getElementById('monthLabel');
    const todayBadge = document.getElementById('todayBadge');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const todayBtn = document.getElementById('todayBtn');
    const calendarBtn = document.getElementById('calendarBtn');
    const timelineBtn = document.getElementById('timelineBtn');
    const calendarView = document.getElementById('calendarView');
    const timelineView = document.getElementById('timelineView');

    // --- View Toggle ---------------------------------------------------------
    function switchView(newView) {
      currentView = newView;
      
      // Update button states
      document.querySelectorAll('.view-toggle-btn').forEach(btn => btn.classList.remove('active'));
      if (newView === 'calendar') {
        calendarBtn.classList.add('active');
        calendarView.classList.remove('hidden');
        timelineView.classList.add('hidden');
      } else {
        timelineBtn.classList.add('active');
        calendarView.classList.add('hidden');
        timelineView.classList.remove('hidden');
      }
      
      // Re-render the active view
      render();
    }

    // --- Render Calendar -----------------------------------------------------
    function renderCalendar() {
      // Build 6x7 grid (always 42 cells)
      const frag = document.createDocumentFragment();
      grid.innerHTML = '';

      const gridStart = startOfGrid(view);

      for (let i = 0; i < 42; i++) {
        const d = addDays(gridStart, i);
        const inMonth = d.getMonth() === view.getMonth();
        const isToday = fmtKey(d) === fmtKey(today);

        const list = events[fmtKey(d)] || [];
        const hasContent = list.length > 0;
        
        // Determine status for styling based on approval status
        let statusClass = '';
        if (hasContent) {
          // Check if ALL content items are approved/scheduled
          const allApproved = list.every(ev => 
            ev.status === 'Approved' || ev.status === 'Scheduled'
          );
          
          if (allApproved) {
            // All content is approved - show green
            statusClass = 'calendar-status-approved';
          } else {
            // Some content needs review - show amber/yellow
            statusClass = 'calendar-status-needs-review';
          }
        } else if (inMonth) {
          statusClass = 'calendar-no-content';
        }

        const cell = document.createElement('button');
        cell.type = 'button';
        cell.setAttribute('tabindex', hasContent ? '0' : '-1');
        
        // Enhanced aria-label with status information
        let ariaLabel = d.toDateString();
        if (hasContent) {
          const statusText = statusClass.includes('approved') ? 'All approved' : 
                           statusClass.includes('needs-review') ? 'Needs review' : 'Mixed status';
          ariaLabel += `, ${list.length} item${list.length !== 1 ? 's' : ''}, ${statusText}`;
        } else {
          ariaLabel += ', No content';
        }
        cell.setAttribute('aria-label', ariaLabel);
        
        cell.className = [
          "group relative flex flex-col items-start justify-start p-2 text-left focus-ring",
          "border border-transparent",
          inMonth ? "bg-white dark:bg-neutral-900" : "bg-neutral-50 dark:bg-neutral-800/40 text-neutral-400 dark:text-neutral-500",
          !hasContent ? "" : "hover:bg-neutral-50 dark:hover:bg-neutral-800",
          "transition",
          statusClass
        ].join(' ');

        // day number
        const num = document.createElement('div');
        num.className = "absolute top-1 left-1 text-lg font-semibold date-number";
        num.textContent = d.getDate();

        // today ring
        if (isToday) {
          const ring = document.createElement('div');
          ring.className = "absolute top-1 right-1 w-6 h-6 rounded-full border-2 border-emerald-500 pointer-events-none";
          cell.appendChild(ring);
        }

        // events dots
        if (list.length) {
          const dots = document.createElement('div');
          dots.className = "mt-auto flex gap-1";
          list.slice(0,3).forEach(ev => {
            const icon = createPlatformIcon(ev.platform, ev.title, ev.status);
            dots.appendChild(icon);
          });
          if (list.length > 3) {
            const more = document.createElement('span');
            more.className = "text-[10px] text-neutral-500 dark:text-neutral-400";
            more.textContent = `+${list.length-3}`;
            dots.appendChild(more);
          }
          cell.appendChild(dots);
        }

        // hover accent (only for cells with content)
        if (hasContent) {
          const hoverAccent = document.createElement('span');
          hoverAccent.className = "pointer-events-none absolute inset-0 rounded-md ring-1 ring-inset ring-transparent group-hover:ring-indigo-300/60 dark:group-hover:ring-indigo-500/40";
          cell.appendChild(hoverAccent);
        }

        cell.appendChild(num);

        // Add click handler to navigate to review page (only if has content)
        if (hasContent) {
          cell.addEventListener('click', () => {
            const dateStr = fmtKey(d);
            window.location.href = `/calendar/review/${dateStr}`;
          });

          // keyboard hint: Enter navigates to review page
          cell.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
              const dateStr = fmtKey(d);
              window.location.href = `/calendar/review/${dateStr}`;
              cell.classList.add('press');
              setTimeout(() => cell.classList.remove('press'), 120);
            }
          });
        } else {
          // For cells without content, show a tooltip or indication
          cell.addEventListener('click', (e) => {
            e.preventDefault();
            // Optional: show a brief message
            if (inMonth) {
              showNoContentMessage(cell);
            }
          });
        }


        frag.appendChild(cell);
      }

      // slide direction class
      if (lastDir === 'left') grid.classList.add('slide-in-left');
      if (lastDir === 'right') grid.classList.add('slide-in-right');
      if (lastDir === 'none') grid.classList.add('fade-in');

      grid.appendChild(frag);

      // Clean up animation classes after they play
      grid.addEventListener('animationend', () => {
        grid.classList.remove('slide-in-left', 'slide-in-right', 'fade-in');
      }, { once: true });
    }

    // --- Render Timeline -----------------------------------------------------
    function renderTimeline() {
      const locale = navigator.language || 'en-US';
      const start = startOfMonth(view);
      const end = endOfMonth(view);
      const daysInMonth = end.getDate();

      timeline.innerHTML = '';

      for (let day = 1; day <= daysInMonth; day++) {
        const d = new Date(view.getFullYear(), view.getMonth(), day);
        const key = fmtKey(d);
        const weekday = d.toLocaleString(locale, { weekday: 'short' });
        const isToday = fmtKey(d) === fmtKey(today);
        const list = events[key] || [];

        // <li> wrapper
        const li = document.createElement('li');
        li.className = "relative pl-16";

        // Platform icon on the rail
        if (list.length > 0) {
          const icon = createPlatformIcon(list[0].platform, list[0].title, list[0].status);
          icon.className = icon.className.replace('w-7 h-7', 'w-8 h-8');
          icon.style.position = 'absolute';
          icon.style.left = '1.5rem';
          icon.style.transform = 'translateX(-50%)';
          icon.style.top = '1rem';
          li.appendChild(icon);
        } else {
          // Empty state dot
          const dot = document.createElement('span');
          dot.className = "absolute left-6 -translate-x-1/2 top-5 w-8 h-8 rounded-full bg-neutral-300 dark:bg-neutral-600";
          li.appendChild(dot);
        }

        // Determine status for timeline card styling based on approval status
        const hasContent = list.length > 0;
        let timelineStatusClass = '';
        if (hasContent) {
          // Check if ALL content items are approved/scheduled
          const allApproved = list.every(ev => 
            ev.status === 'Approved' || ev.status === 'Scheduled'
          );
          
          if (allApproved) {
            // All content is approved - show green
            timelineStatusClass = 'calendar-status-approved';
          } else {
            // Some content needs review - show amber/yellow
            timelineStatusClass = 'calendar-status-needs-review';
          }
        } else {
          timelineStatusClass = 'calendar-no-content';
        }

        // Card
        const card = document.createElement('button');
        card.type = 'button';
        card.setAttribute('tabindex', hasContent ? '0' : '-1');
        card.className = [
          "w-full text-left rounded-xl border border-neutral-200 dark:border-neutral-700",
          "bg-white/80 dark:bg-neutral-900/80 backdrop-blur",
          hasContent ? "hover:bg-neutral-50 dark:hover:bg-neutral-800" : "",
          "transition focus-ring",
          // understated motion on hover (only for content)
          hasContent ? "hover:-translate-y-0.5 hover:shadow-sm" : "",
          isToday ? "ring-1 ring-emerald-400/60" : "ring-1 ring-transparent",
          timelineStatusClass
        ].join(' ');

        // Card inner layout
        const inner = document.createElement('div');
        inner.className = "p-4 flex items-start justify-between gap-4";

        // Date block (big number, small weekday)
        const dateBlock = document.createElement('div');
        dateBlock.className = "flex items-baseline gap-3";
        const dateNum = document.createElement('div');
        dateNum.className = "text-2xl font-semibold";
        dateNum.textContent = day;
        const wk = document.createElement('div');
        wk.className = "text-sm text-neutral-500 dark:text-neutral-400";
        wk.textContent = weekday;
        dateBlock.appendChild(dateNum);
        dateBlock.appendChild(wk);

        // Events summary
        const right = document.createElement('div');
        right.className = "flex-1 flex items-center justify-between gap-4";

        const summary = document.createElement('div');
        summary.className = "text-sm";
        if (list.length === 0) {
          summary.innerHTML = `<span class="text-neutral-500 dark:text-neutral-400">No events</span>`;
        } else {
          // up to 3 as chips, then +n
          const wrap = document.createElement('div');
          wrap.className = "flex flex-wrap items-center gap-2";
          list.slice(0,3).forEach(ev => {
            const chip = document.createElement('span');
            chip.className = "px-2 py-0.5 rounded-full text-xs bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200";
            chip.textContent = ev.title;
            wrap.appendChild(chip);
          });
          if (list.length > 3) {
            const more = document.createElement('span');
            more.className = "text-xs text-neutral-500 dark:text-neutral-400";
            more.textContent = `+${list.length-3} more`;
            wrap.appendChild(more);
          }
          summary.appendChild(wrap);
        }

        // Today badge (inline on the card, subtle)
        if (isToday) {
          const tb = document.createElement('span');
          tb.className = "ml-auto text-xs rounded-full px-2 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200";
          tb.textContent = "Today";
          right.appendChild(tb);
        }

        right.prepend(summary);

        inner.appendChild(dateBlock);
        inner.appendChild(right);

        card.appendChild(inner);
        li.appendChild(card);

        // interactions (only navigate if has content)
        if (hasContent) {
          card.addEventListener('click', () => {
            window.location.href = `/calendar/review/${key}`;
          });
          card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
              window.location.href = `/calendar/review/${key}`;
            }
          });
        } else {
          card.addEventListener('click', (e) => {
            e.preventDefault();
            showNoContentMessage(card);
          });
        }

        // mount
        timeline.appendChild(li);
      }

      // slide direction
      if (lastDir === 'left') timeline.classList.add('slide-in-left');
      if (lastDir === 'right') timeline.classList.add('slide-in-right');
      if (lastDir === 'none') timeline.classList.add('fade-in');
      timeline.addEventListener('animationend', () => {
        timeline.classList.remove('slide-in-left', 'slide-in-right', 'fade-in');
      }, { once: true });
    }

    // --- Render --------------------------------------------------------------
    function render() {
      // Header
      const locale = navigator.language || 'en-US';
      const mo = view.toLocaleString(locale, { month: 'long' });
      label.textContent = `${mo} ${view.getFullYear()}`;

      // Today badge if viewing current month
      if (view.getFullYear() === today.getFullYear() && view.getMonth() === today.getMonth()) {
        todayBadge.classList.remove('hidden');
      } else {
        todayBadge.classList.add('hidden');
      }

      // Render active view
      if (currentView === 'calendar') {
        renderCalendar();
      } else {
        renderTimeline();
      }
    }

    // --- Navigation ----------------------------------------------------------
    function go(deltaMonths) {
      const currentMonth = view.getMonth();
      view = new Date(view.getFullYear(), currentMonth + deltaMonths, 1);
      lastDir = deltaMonths > 0 ? 'right' : 'left';
      render();
      
      // Timeline-specific scroll behavior
      if (currentView === 'timeline') {
        if (view.getFullYear() === today.getFullYear() && view.getMonth() === today.getMonth()) {
          const todayIdx = today.getDate();
          const node = timeline.children[todayIdx - 1];
          if (node) node.scrollIntoView({ block: 'center', behavior: 'smooth' });
        } else {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      }
    }
    function goToday() {
      view = new Date(today.getFullYear(), today.getMonth(), 1);
      lastDir = 'none';
      render();
      
      // Timeline-specific scroll behavior
      if (currentView === 'timeline') {
        const node = timeline.children[today.getDate() - 1];
        if (node) node.scrollIntoView({ block: 'center', behavior: 'smooth' });
      }
    }

    // --- Event Listeners -----------------------------------------------------
    prevBtn.addEventListener('click', () => go(-1));
    nextBtn.addEventListener('click', () => go(1));
    todayBtn.addEventListener('click', goToday);
    calendarBtn.addEventListener('click', () => switchView('calendar'));
    timelineBtn.addEventListener('click', () => switchView('timeline'));

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.target !== document.body) return; // don't hijack when focused inside cells
      if (e.key === 'ArrowLeft') go(-1);
      if (e.key === 'ArrowRight') go(1);
      if (e.key.toLowerCase() === 't') goToday();
    });

    // Toggle button styles
    document.head.insertAdjacentHTML('beforeend', `
      <style>
        .view-toggle-btn {
          color: #6b7280;
        }
        .view-toggle-btn:hover {
          background-color: #f3f4f6;
        }
        .view-toggle-btn.active {
          background-color: #3b82f6;
          color: white;
        }
      </style>
    `);

    // Real data loaded from Laravel above - no demo data needed

    // Platform icon creation function
    function createPlatformIcon(platform, title, status = null) {
      const icon = document.createElement('span');
      let baseClasses = "w-7 h-7 rounded-full flex items-center justify-center text-white font-bold group-hover:scale-110 transition";
      
      // Add status border class if status is provided
      if (status) {
        if (status === 'Approved' || status === 'Scheduled') {
          baseClasses += " content-icon-approved";
        } else {
          baseClasses += " content-icon-needs-review";
        }
      }
      
      icon.className = baseClasses;
      
      // Create tooltip text with status if available
      let tooltipText = title;
      if (status) {
        tooltipText += ` - ${status}`;
      }
      icon.setAttribute('data-tooltip', tooltipText);
      
      // Add hover events for custom tooltip
      icon.addEventListener('mouseenter', showTooltip);
      icon.addEventListener('mouseleave', hideTooltip);
      icon.addEventListener('mousemove', positionTooltip);

      const platformLower = platform.toLowerCase();
      
      if (platformLower === 'facebook') {
        icon.style.backgroundColor = '#1877F2';
        icon.style.fontSize = '18px';
        icon.innerHTML = 'f';
      } else if (platformLower === 'instagram') {
        icon.style.background = 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)';
        icon.style.fontSize = '16px';
        icon.innerHTML = `<svg viewBox="0 0 24 24" width="16" height="16" fill="white">
          <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.646.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
        </svg>`;
      } else if (platformLower === 'linkedin') {
        icon.style.backgroundColor = '#0A66C2';
        icon.style.fontSize = '14px';
        icon.innerHTML = 'in';
      } else if (platformLower === 'twitter' || platformLower === 'x') {
        icon.style.backgroundColor = '#000000';
        icon.style.fontSize = '18px';
        icon.innerHTML = 'X';
      } else if (platformLower === 'blog') {
        icon.style.backgroundColor = '#3B82F6';
        icon.style.fontSize = '18px';
        icon.innerHTML = 'B';
      } else {
        // Default for unknown platforms
        icon.style.backgroundColor = '#6366F1';
        icon.style.fontSize = '18px';
        icon.innerHTML = platform.charAt(0).toUpperCase();
      }
      
      return icon;
    }

    // Tooltip functions
    let tooltip = null;

    function showTooltip(e) {
      const text = e.target.getAttribute('data-tooltip');
      if (!text) return;

      hideTooltip(); // Remove any existing tooltip

      tooltip = document.createElement('div');
      tooltip.className = 'fixed z-50 bg-gray-900 text-white text-xs px-2 py-1 rounded shadow-lg pointer-events-none';
      tooltip.textContent = text;
      document.body.appendChild(tooltip);

      positionTooltip(e);
    }

    function hideTooltip() {
      if (tooltip) {
        document.body.removeChild(tooltip);
        tooltip = null;
      }
    }

    function positionTooltip(e) {
      if (!tooltip) return;

      const rect = tooltip.getBoundingClientRect();
      const x = e.clientX - rect.width / 2;
      const y = e.clientY - rect.height - 8;

      tooltip.style.left = Math.max(5, Math.min(window.innerWidth - rect.width - 5, x)) + 'px';
      tooltip.style.top = Math.max(5, y) + 'px';
    }

    // No content message function
    function showNoContentMessage(cell) {
      const existingMessage = cell.querySelector('.no-content-message');
      if (existingMessage) return;

      const message = document.createElement('div');
      message.className = 'no-content-message absolute inset-0 flex items-center justify-center bg-neutral-900/80 text-white text-xs rounded-md pointer-events-none';
      message.textContent = 'No content';
      cell.appendChild(message);

      // Remove message after 1.5 seconds
      setTimeout(() => {
        if (message.parentNode) {
          message.parentNode.removeChild(message);
        }
      }, 1500);
    }

    // Initial render
    render();
  </script>
</body>
</html>
</div>