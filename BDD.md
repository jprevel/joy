# BDD Test Scenarios (Gherkin)

## Magic Link Access

```gherkin
Feature: Magic link access
  Scenario: Client opens valid link
    Given a client has a magic link with comment and approve permissions
    When they open it
    Then they see the monthly calendar and timeline list
    And they can comment and approve items

  Scenario: Expired link
    Given a magic link expired yesterday
    When opened
    Then an expiry message is shown
```

## Calendar and Timeline Views

```gherkin
Feature: Calendar + timeline views
  Scenario: Calendar monthly view
    Given multiple variants scheduled in September
    When the calendar is viewed
    Then items are shown in a grid for each day

  Scenario: Timeline chronological list
    Given multiple variants with dates
    When the timeline view is opened
    Then items are listed in ascending order by default
```

## Comment Integration with Trello

```gherkin
Feature: Comments → Trello
  Scenario: Comment on mapped item
    Given a variant mapped to Trello card X
    When client adds a comment
    Then the same comment appears on card X

  Scenario: Comment on unmapped item
    Given a variant without a Trello card
    When client comments
    Then a new Trello card is created and the comment posted
```

## Approval Workflow

```gherkin
Feature: Approvals
  Scenario: Approve a variant
    Given client has approve permissions
    When they click Approve
    Then the variant status becomes Approved

  Scenario: Concept full approval
    Given all variants in a concept are approved
    Then the concept status is Approved
```

## Audit Trail

```gherkin
Feature: Audit trail
  Scenario: Log approval
    Given a client approves a variant
    Then the audit trail records approver and timestamp
```