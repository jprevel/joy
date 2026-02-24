Feature: User & Role Management
  As an admin
  I need to manage users and their roles
  So that access control is properly enforced

  Background:
    Given I am logged in as an admin

  Scenario: Create a new user with admin role
    When I create a user with email "newadmin@test.com" and role "admin"
    Then the user should exist with role "admin"
    And they should be able to access "/admin"

  Scenario: Create a new user with agency role
    When I create a user with email "agency@test.com" and role "agency"
    Then the user should exist with role "agency"
    And they should be routed to "/calendar/agency" on login

  Scenario: Edit user profile
    Given user "agency@test.com" exists
    When I edit the user's name to "Updated Name"
    And I click "Save"
    Then the user's name should be "Updated Name"

  Scenario: Soft-delete a user
    Given user "agency@test.com" exists
    When I delete the user
    Then the user should have a deleted_at timestamp
    And they should not appear in the active users list
    And they should not be able to log in

  Scenario: Role-based access — admin sees all clients
    Given I am logged in as an admin
    When I visit the content calendar
    Then I should see content from all clients

  Scenario: Role-based access — agency sees assigned clients only
    Given I am logged in as an agency user assigned to "Acme Corp"
    When I visit the content calendar
    Then I should only see content for "Acme Corp"

  Scenario: User edits own profile
    Given I am logged in as an agency user
    When I visit "/profile"
    And I update my display name
    Then my profile should be updated
