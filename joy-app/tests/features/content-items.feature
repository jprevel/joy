Feature: Content Items
  As a user
  I need to create, edit, and manage content items
  So that client content flows through the approval pipeline

  Background:
    Given I am logged in as an admin
    And client "Acme Corp" exists

  Scenario: Create a content item
    When I visit the add content page
    And I fill in "Title" with "February Social Post"
    And I select client "Acme Corp"
    And I set the scheduled date
    And I click "Save"
    Then I should see "February Social Post" on the calendar

  Scenario: Edit a content item
    Given content item "February Social Post" exists for "Acme Corp"
    When I edit "February Social Post"
    And I change "Title" to "Updated Social Post"
    And I click "Save"
    Then I should see "Updated Social Post" on the calendar

  Scenario: Change content item status
    Given content item "February Social Post" exists with status "draft"
    When I change its status to "pending_review"
    Then the content item should have status "pending_review"
    And an audit log entry should be created

  Scenario: Content review by date
    Given multiple content items exist for date "2026-02-24"
    When I visit "/calendar/review/2026-02-24"
    Then I should see all content items for that date
    And I should be able to approve or reject each item

  Scenario: Client views content via magic link
    Given content item "February Social Post" exists for "Acme Corp"
    And a valid magic link exists for "Acme Corp"
    When the client visits the magic link
    Then they should see "February Social Post"
