Feature: Slack Notifications
  As an admin
  I need to configure Slack notifications for clients
  So that teams are notified about content status changes

  Background:
    Given I am logged in as an admin
    And client "Acme Corp" exists

  Scenario: Configure Slack workspace
    When I set up a Slack workspace for "Acme Corp"
    And I provide the webhook URL and channel
    And I click "Save"
    Then the Slack workspace should be configured for "Acme Corp"

  Scenario: Notification on content status change
    Given Slack is configured for "Acme Corp"
    And content item "February Post" exists for "Acme Corp"
    When the status of "February Post" changes to "approved"
    Then a Slack notification should be sent to the configured channel
    And the notification should include the content title and new status

  Scenario: Notification on status update approval
    Given Slack is configured for "Acme Corp"
    And a submitted status update exists for "Acme Corp"
    When the status update is approved
    Then a Slack notification should be sent

  Scenario: Slack not configured — no notification sent
    Given Slack is NOT configured for "Acme Corp"
    When a content status changes
    Then no Slack notification should be attempted
    And no error should occur
