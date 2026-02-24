Feature: Authentication
  As a user of Joy
  I need to log in with the correct role-based routing
  So that I see the appropriate dashboard

  Scenario: Admin logs in successfully
    Given I am on the login page
    When I enter valid admin credentials
    And I click "Login"
    Then I should be redirected to "/admin"

  Scenario: Agency user logs in successfully
    Given I am on the login page
    When I enter valid agency credentials
    And I click "Login"
    Then I should be redirected to "/calendar/agency"

  Scenario: Client user logs in successfully
    Given I am on the login page
    When I enter valid client credentials
    And I click "Login"
    Then I should be redirected to "/calendar/client"

  Scenario: Invalid credentials show error
    Given I am on the login page
    When I enter invalid credentials
    And I click "Login"
    Then I should see an error message
    And I should remain on the login page

  Scenario: Unauthenticated user is redirected to login
    Given I am not logged in
    When I visit "/"
    Then I should be redirected to "/login"

  Scenario: User logs out
    Given I am logged in as an admin
    When I click "Logout"
    Then I should be redirected to "/login"
    And my session should be destroyed

  Scenario: Magic link access for client
    Given a valid magic link exists for client "Acme Corp"
    When I visit the magic link URL
    Then I should see the client content calendar for "Acme Corp"

  Scenario: Expired magic link shows error
    Given an expired magic link exists
    When I visit the magic link URL
    Then I should see an error page
