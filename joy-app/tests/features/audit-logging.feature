Feature: Audit Logging
  As an admin
  I need to track all significant actions
  So that there is an accountable record of system activity

  Background:
    Given I am logged in as an admin

  Scenario: Actions generate audit log entries
    When I create a new client "Audit Test Client"
    Then an audit log entry should be created with action "create"
    And the entry should reference the client

  Scenario: View audit dashboard
    Given audit log entries exist
    When I visit "/admin/audit/dashboard"
    Then I should see a summary of recent activity
    And I should see activity statistics

  Scenario: Filter audit logs
    Given audit log entries exist for multiple users
    When I visit "/admin/audit"
    And I filter by user
    Then I should only see entries for that user

  Scenario: Export audit logs
    Given audit log entries exist
    When I click "Export"
    Then I should receive a downloadable file of audit logs

  Scenario: Cleanup old audit logs
    Given audit log entries older than 90 days exist
    When I run the audit log cleanup
    Then entries older than the retention period should be removed
    And recent entries should be preserved
