Feature: Trello Integration
  As an admin
  I need to connect clients to Trello boards
  So that content items sync between Joy and Trello

  Background:
    Given I am logged in as an admin
    And client "Acme Corp" exists

  Scenario: Create Trello integration
    When I visit "/admin/trello/create"
    And I fill in the Trello API credentials
    And I select client "Acme Corp"
    And I click "Save"
    Then a Trello integration should be created for "Acme Corp"

  Scenario: Test Trello connection
    Given a Trello integration exists for "Acme Corp"
    When I click "Test Connection"
    Then I should see a success message

  Scenario: Sync Trello cards
    Given a Trello integration exists for "Acme Corp" with cards on the board
    When I click "Sync"
    Then Trello cards should be imported as content items
    And the sync timestamp should be updated

  Scenario: Toggle integration on/off
    Given an active Trello integration exists for "Acme Corp"
    When I toggle the integration off
    Then the integration should be inactive
    And syncs should not run for "Acme Corp"

  Scenario: Delete Trello integration
    Given a Trello integration exists for "Acme Corp"
    When I delete the integration
    Then the Trello integration should be removed
    And existing synced content items should remain
