# Joy - MajorMajor suite of applications

This directory contains the project documentation for Joy, a suite of client and internal facing tools for MajorMajor Digital Marketing.

## Documentation Structure

- this app is written with spec-driven-development. We are using Spec Kit framework from GitHub. The documentation files live in the /specs folder.

## Project Overview

Joy is a web application built with the TALL stack (Tailwind, Alpine.js, Laravel, Livewire) + Filament for managing client content calendars. The system provides:

- Monthly calendar and timeline views for content management
- Magic link sharing for client access without login
- Client review, commenting, and approval workflows
- Integration with Trello for comment synchronization
- Multi-platform content variants (Facebook, Instagram, LinkedIn, Blog)

## Key Features

- **Role-based access**: Admin, Agency Team, and Client roles with appropriate permissions
- **Workspace isolation**: Each client gets their own workspace
- **Magic link authentication**: Secure, time-limited access for clients
- **Real-time synchronization**: Comments sync to Trello cards automatically
- **Audit trail**: Complete activity logging for compliance and tracking
- **Responsive design**: Works across desktop and mobile devices

## Technical Stack

- **Frontend**: Tailwind CSS, Alpine.js, Livewire
- **Backend**: Laravel with Filament admin panel
- **Database**: MySQL/PostgreSQL (Laravel compatible)
- **Integrations**: Trello API, Slack (via Trello)
- **Deployment**: Web-based responsive application

## Development Constitution
**MANDATORY TDD**: All features must follow strict Test-Driven Development.

#### Rules:

1. **NEW TEST FILES by permission only**
   - Do not create new `*Test.php` files unless you ask expressed permission with a reason why.

2. **ALL TESTS MUST PASS** - Zero tolerance for failing tests
   - All existing tests must pass (excluding incomplete tests)
   - When tests fail, assume that the test is correct and the implementation is the issue. Do not change tests just to make them pass.
