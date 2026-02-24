Feature: Content Calendar
  As a user
  I need to view and navigate the content calendar
  So that I can see scheduled content items by date

  Scenario: Admin views all clients' content
    Given I am logged in as an admin
    And content items exist for clients "Acme Corp" and "Globex"
    When I visit "/calendar/admin"
    Then I should see content from both "Acme Corp" and "Globex"

  Scenario: Agency user views only assigned clients
    Given I am logged in as an agency user assigned to "Acme Corp"
    And content items exist for clients "Acme Corp" and "Globex"
    When I visit "/calendar/agency"
    Then I should see content from "Acme Corp"
    And I should not see content from "Globex"

  Scenario: Client views only their own content
    Given I am logged in as a client user for "Acme Corp"
    When I visit "/calendar/client"
    Then I should only see content for "Acme Corp"

  Scenario: Navigate between months
    Given I am logged in as an admin
    When I visit the content calendar
    And I click the next month button
    Then I should see the next month's content

  Scenario: Filter by client
    Given I am logged in as an admin
    And content items exist for clients "Acme Corp" and "Globex"
    When I visit "/calendar/admin/client/1"
    Then I should only see content for that client
