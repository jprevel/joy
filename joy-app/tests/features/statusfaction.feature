Feature: Statusfaction — Weekly Status Updates
  As an account manager or admin
  I need to create and manage weekly status updates for clients
  So that clients stay informed about project progress

  Background:
    Given I am logged in as an admin
    And client "Acme Corp" exists

  Scenario: Access statusfaction dashboard
    Given I have permission to access statusfaction
    When I visit "/statusfaction"
    Then I should see the status update dashboard

  Scenario: Create weekly status update
    When I create a new status update for "Acme Corp"
    And I fill in the update details
    And I click "Submit"
    Then a status update should be created for "Acme Corp"
    And its status should be "submitted"

  Scenario: Approve a status update
    Given a submitted status update exists for "Acme Corp"
    When I approve the status update
    Then its status should be "approved"
    And an audit log entry should be created

  Scenario: Reject a status update
    Given a submitted status update exists for "Acme Corp"
    When I reject the status update with reason "Needs more detail"
    Then its status should be "rejected"
    And the rejection reason should be recorded

  Scenario: View status history for a client
    Given multiple status updates exist for "Acme Corp"
    When I view the status history for "Acme Corp"
    Then I should see all status updates in chronological order

  Scenario: Non-authorized user cannot access statusfaction
    Given I am logged in as a client user
    When I visit "/statusfaction"
    Then I should see an authorization error
