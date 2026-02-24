Feature: Client Management
  As an admin
  I need to manage clients
  So that agency users and clients have proper access

  Background:
    Given I am logged in as an admin

  Scenario: View client list
    Given clients "Acme Corp" and "Globex" exist
    When I visit the admin clients page
    Then I should see "Acme Corp" in the client list
    And I should see "Globex" in the client list

  Scenario: Create a new client
    When I visit the create client page
    And I fill in "Name" with "New Client Inc"
    And I fill in the required spec fields
    And I click "Save"
    Then I should see "New Client Inc" in the client list

  Scenario: Edit client details
    Given client "Acme Corp" exists
    When I edit the client "Acme Corp"
    And I change "Name" to "Acme Corporation"
    And I click "Save"
    Then I should see "Acme Corporation" in the client list

  Scenario: Soft-delete a client
    Given client "Acme Corp" exists
    When I delete the client "Acme Corp"
    Then "Acme Corp" should not appear in the active client list
    And "Acme Corp" should still exist in the database with a deleted_at timestamp

  Scenario: Assign team to client
    Given client "Acme Corp" exists
    And team "Marketing Team" exists
    When I assign "Marketing Team" to "Acme Corp"
    Then "Acme Corp" should be associated with "Marketing Team"
