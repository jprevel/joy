# Joy

> Content Calendar & Client Management System for MajorMajor Digital Marketing

Joy is a comprehensive web application built with the TALL stack (Tailwind CSS 4, Alpine.js, Laravel 12, Livewire) + Filament that enables digital marketing agencies to manage client content calendars, track weekly client satisfaction metrics, and facilitate seamless client collaboration with approval workflows.

## 🚀 Features

### Content Management
- **Monthly Calendar View**: Visual grid layout showing scheduled content by date
- **Timeline View**: Chronological list view with ascending/descending sort options
- **Multi-Platform Support**: Facebook, Instagram, LinkedIn, and Blog variants with platform-specific icons
- **Concept-Based Organization**: Group related platform variants under unified concepts

### Statusfaction - Client Health Tracking
- **Weekly Status Reports**: Track client satisfaction and team health scores (1-10 scale)
- **Approval Workflow**: Agency submits status reports, admin approves for client visibility
- **Trend Visualization**: 5-week trend graphs using Chart.js for data insights
- **Week-Based Tracking**: Sunday to Saturday weekly cycles with one status per client per week
- **Client-Specific Views**: Dedicated status reporting interface with sidebar navigation

### Client Collaboration
- **Magic Link Access**: Secure, time-limited client access without requiring login
- **Review & Comment System**: Clients can provide feedback directly on content items
- **Content Approval Workflow**: Simple approve/reject functionality with status rollup
- **Real-time Synchronization**: Comments automatically sync to Trello cards

### Integration & Automation
- **Trello Integration**: Automatic comment synchronization and card creation
- **Slack Notifications**: Via existing Trello-Slack integration
- **Background Job Processing**: Reliable sync with retry mechanisms
- **Audit Trail**: Complete activity logging for compliance and tracking

## 👥 User Roles

The application uses **Spatie Laravel Permission** for comprehensive role and permission management.

### Admin
- Manage clients and team members (CRUD operations)
- Configure global settings and workspace ownership
- Access complete audit trail across all workspaces
- **Approve status reports** submitted by agency team
- Access Filament admin panel for system configuration
- View all client status trends and reports

### Agency
- Create and edit content concepts and variants
- Manage content status and scheduling
- Generate and manage client magic links
- View client comments and approval status
- **Submit weekly client status reports** (satisfaction & team health scores)
- Access Statusfaction reporting interface

### Client (Workspace-specific)
- Access content via magic links
- Review scheduled content in calendar and timeline views
- Add comments and approve content items
- No account registration required
- Magic link authentication only (no password needed)

## 📊 Technical Architecture

### Technology Stack
- **Frontend**: Tailwind CSS 4, Alpine.js, Livewire
- **Backend**: Laravel 12 with Filament admin panel
- **Database**: MySQL/PostgreSQL (Laravel compatible)
- **Authentication**: Spatie Laravel Permission for role/permission management
- **Session Management**: Extended 2-week session lifetime with graceful expiry handling
- **Data Visualization**: Chart.js for trend graphs and metrics
- **Testing**: PHPUnit for unit/feature tests, Playwright for E2E testing
- **Integrations**: Trello API
- **Deployment**: Responsive web application

### Database Schema
The system uses a workspace-isolated architecture with the following key entities:

**Content Management:**
- **ClientWorkspace**: Isolated environments per client
- **Concept**: Content ideas with multiple platform variants
- **Variant**: Platform-specific content with scheduling and media
- **MagicLink**: Secure client access tokens
- **AuditLog**: Complete activity tracking

**Status Reporting:**
- **ClientStatusUpdate**: Weekly client satisfaction and team health metrics
- **Team**: Organization of users into teams for client assignment
- Approval workflow fields (pending_approval, approved, approved_by, approved_at)
- Week-based tracking with week_start_date (Sunday-Saturday cycles)

## 🔄 Workflow

1. **Content Creation**: Agency team creates concepts with platform-specific variants
2. **Client Sharing**: Generate secure magic links with appropriate permissions (view/comment/approve)
3. **Client Review**: Clients access content calendar, add comments, and approve items
4. **Status Management**: Approvals roll up from variants to concepts (full/partial approval)
5. **Integration Sync**: Comments automatically sync to Trello with backlinks
6. **Audit Trail**: All activities logged for compliance and tracking

## 🔗 Magic Link Features

- **Scoped Permissions**: View-only, comment, or approve access
- **Optional Expiry**: Time-limited access for enhanced security
- **PIN Protection**: Additional security layer when needed
- **Revocation**: Links can be deactivated by agency team
- **Activity Tracking**: All link usage logged in audit trail

## 📋 Content Model

### Concept Level
- Title and internal notes
- Owner assignment (Agency Team member)
- Status tracking (Draft/In Review/Approved/Scheduled)
- Internal due dates
- Complete audit trail

### Variant Level
- Platform specification (FB/IG/LI/Blog)
- Scheduled publication date/time
- Content copy and media URLs
- Individual status tracking
- Client comments
- Trello card integration

## 🧪 Quality Assurance

### Test Suite Lock 🔒

**CRITICAL:** The test suite is **LOCKED** as of 2025-10-06 to ensure application stability.

#### Rules:
1. **42 Test Files Maximum** - No new test files without explicit approval
2. **Zero Failing Tests** - All tests must pass before any code changes ship
3. **Pre-Development Check** - Run `./scripts/test-lock.sh` before starting work
4. **23 Incomplete Tests** - Marked for future implementation, do not modify

#### Test Coverage:
- **Feature Tests**: Role-based access, authentication, status reporting workflows
- **E2E Tests**: Playwright tests for critical user journeys
- **BDD Scenarios**: Gherkin-format behavior specifications
- **Integration Testing**: Trello sync and magic link functionality
- **Audit Trail Validation**: Complete activity logging verification

Run the test lock script before making changes:
```bash
./scripts/test-lock.sh
```

## 📚 Documentation

- **[CLAUDE.md](./CLAUDE.md)**: Development constitution and AI assistant guidelines
- **[Functional Requirements (FRD)](./specs/FRD.md)**: Detailed system specifications and requirements
- **[User Stories](./user-stories.md)**: User-centered requirements and acceptance criteria
- **[BDD Test Scenarios](./BDD.md)**: Behavior-driven development scenarios in Gherkin format
- **[Entity Relationship Diagram (ERD)](./ERD.md)**: Database schema and entity relationships
- **Feature Specifications**: Located in `/specs/` directory with detailed requirements and implementation plans

## 🎯 Success Metrics

- Faster client approval cycles
- High adoption rate of magic link access
- Reliable Trello synchronization (>99% uptime)
- Reduced email-based content review processes

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ & npm
- MySQL/PostgreSQL
- Laravel 12

### Installation

1. **Clone and install dependencies:**
   ```bash
   cd joy-app
   composer install
   npm install
   ```

2. **Environment setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database setup:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Run development servers:**
   ```bash
   # Terminal 1: Laravel dev server
   php artisan serve

   # Terminal 2: Vite dev server
   npm run dev
   ```

5. **Run tests:**
   ```bash
   # Run test lock validation
   ./scripts/test-lock.sh

   # Run Playwright E2E tests
   npm run test:e2e
   ```

## 🚫 Current Limitations

The following features are intentionally out of scope for the current release:
- Direct publishing to social platforms
- Content import/export functionality
- Advanced tagging and UTM parameters
- Multi-client status comparison dashboard
- Content filtering (planned for future versions)

## 🔮 Future Enhancements

- Advanced content filtering and search
- Direct platform publishing integration
- Multi-client status comparison and benchmarking
- Enhanced analytics and reporting dashboard
- Multi-language support
- Advanced approval workflow customization
- Status report email notifications
- Custom metric definitions beyond satisfaction and team health

## 🏢 About MajorMajor Digital Marketing

Joy is designed specifically for MajorMajor Digital Marketing's content workflow, featuring custom branding on all client-facing interfaces and seamless integration with existing Trello-based project management processes.
