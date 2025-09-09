# Joy

> Content Calendar Management System for MajorMajor Digital Marketing

Joy is a comprehensive web application built with the TALL stack (Tailwind, Alpine.js, Laravel, Livewire) + Filament that enables digital marketing agencies to manage client content calendars with seamless client collaboration and approval workflows.

## 🚀 Features

### Content Management
- **Monthly Calendar View**: Visual grid layout showing scheduled content by date
- **Timeline View**: Chronological list view with ascending/descending sort options
- **Multi-Platform Support**: Facebook, Instagram, LinkedIn, and Blog variants with platform-specific icons
- **Concept-Based Organization**: Group related platform variants under unified concepts

### Client Collaboration
- **Magic Link Access**: Secure, time-limited client access without requiring login
- **Review & Comment System**: Clients can provide feedback directly on content items
- **Approval Workflow**: Simple approve/reject functionality with status rollup
- **Real-time Synchronization**: Comments automatically sync to Trello cards

### Integration & Automation
- **Trello Integration**: Automatic comment synchronization and card creation
- **Slack Notifications**: Via existing Trello-Slack integration
- **Background Job Processing**: Reliable sync with retry mechanisms
- **Audit Trail**: Complete activity logging for compliance and tracking

## 👥 User Roles

### Admin (MajorMajor)
- Manage clients and team members (CRUD operations)
- Configure global settings and workspace ownership
- Access complete audit trail across all workspaces

### Agency Team (MajorMajor)
- Create and edit content concepts and variants
- Manage content status and scheduling
- Generate and manage client magic links
- View client comments and approval status

### Client (Workspace-specific)
- Access content via magic links
- Review scheduled content in calendar and timeline views
- Add comments and approve content items
- No account registration required

## 📊 Technical Architecture

### Technology Stack
- **Frontend**: Tailwind CSS, Alpine.js, Livewire
- **Backend**: Laravel with Filament admin panel
- **Database**: MySQL/PostgreSQL (Laravel compatible)
- **Integrations**: Trello API
- **Deployment**: Responsive web application

### Database Schema
The system uses a workspace-isolated architecture with the following key entities:
- **ClientWorkspace**: Isolated environments per client
- **Concept**: Content ideas with multiple platform variants
- **Variant**: Platform-specific content with scheduling and media
- **MagicLink**: Secure client access tokens
- **AuditLog**: Complete activity tracking

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

The project includes comprehensive test coverage with:
- **BDD Scenarios**: Gherkin-format behavior specifications
- **User Story Testing**: Acceptance criteria validation
- **Integration Testing**: Trello sync and magic link functionality
- **Audit Trail Validation**: Complete activity logging verification

## 📚 Documentation

- **[Functional Requirements (FRD)](./FRD.md)**: Detailed system specifications and requirements
- **[User Stories](./user-stories.md)**: User-centered requirements and acceptance criteria
- **[BDD Test Scenarios](./BDD.md)**: Behavior-driven development scenarios in Gherkin format
- **[Entity Relationship Diagram (ERD)](./ERD.md)**: Database schema and entity relationships

## 🎯 Success Metrics

- Faster client approval cycles
- High adoption rate of magic link access
- Reliable Trello synchronization (>99% uptime)
- Reduced email-based content review processes

## 🚫 Version 1 Limitations

The following features are intentionally out of scope for the initial release:
- Direct publishing to social platforms
- Content import/export functionality
- Advanced tagging and UTM parameters
- Multi-step approval workflows
- Content filtering (planned for future versions)

## 🔮 Future Enhancements

- Advanced content filtering and search
- Direct platform publishing integration
- Enhanced analytics and reporting
- Multi-language support
- Advanced approval workflow customization

## 🏢 About MajorMajor Digital Marketing

Joy is designed specifically for MajorMajor Digital Marketing's content workflow, featuring custom branding on all client-facing interfaces and seamless integration with existing Trello-based project management processes.
