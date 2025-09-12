# Joy - Content Calendar Management System

This directory contains the project documentation for Joy, a content calendar management system for MajorMajor Digital Marketing.

## Documentation Structure

- [Functional Requirements (FRD)](./FRD.md) - Detailed functional requirements and system specifications
- [BDD Test Scenarios](./BDD.md) - Behavior-driven development scenarios in Gherkin format
- [User Stories](./user-stories.md) - User-centered requirements and acceptance criteria
- [Entity Relationship Diagram (ERD)](./ERD.md) - Database schema and entity relationships

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