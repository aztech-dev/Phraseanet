# features/task_manager.feature
Feature: task_manager
  In order to manipulate tasks
  As an admin
  I need to be able to manipulate the registered tasks in the admin panel

  Scenario: Visit tasks panel root should redirect to task list
    Given There is a task 'Test' with status 'stopped'
    And I am logged in as an admin
    And I dont follow redirects
    When I visit '/admin/task-manager/'
    Then I should be redirected to: '/\/admin\/task-manager\/tasks/'

  Scenario: Visit tasks panel should return a 200 response
    Given There is a task 'Test' with status 'stopped'
    And I am logged in as an admin
    When I visit '/admin/task-manager/tasks'
    Then I should receive HTTP status: 200

  Scenario: Listing tasks in JSON returns available tasks
    Given There is a task 'Test' with status 'stopped'
    And I am logged in as an admin
    And I do an AJAX request
    When I visit '/admin/task-manager/tasks'
    Then I should receive a JSON array containing at least:
      | name  | state   |
      | Test  | started |

  Scenario: Creating a task should register a new task
    Given I am logged in as an admin
    And I do an AJAX request
    And I dont follow redirects
    When I submit to '/admin/task-manager/tasks/create' with:
      | name     | value                                   |
      | job-name | Alchemy\Phrasea\TaskManager\Job\NullJob |
    Then I should be redirected to: '/\/admin\/task-manager\/task\/([0-9]+)/'

  Scenario: Creating a task with an invalid job name should fail
    Given I am logged in as an admin
    And I do an AJAX request
    And I dont follow redirects
    When I submit to '/admin/task-manager/tasks/create' with:
      | name     | value                                   |
      | job-name | invalid-name |
    Then I should receive HTTP status: 400

  Scenario: Starting the scheduler redirects to admin panel
    Given I am logged in as an admin
    And I dont follow redirects
    When I submit to '/admin/task-manager/scheduler/start'
    Then I should be redirected to: '/\/admin\/task-manager\/tasks/'

  Scenario: Stopping the scheduler redirects to admin panel
    Given I am logged in as an admin
    And I dont follow redirects
    When I submit to '/admin/task-manager/scheduler/stop'
    Then I should be redirected to: '/\/admin\/task-manager\/tasks/'

  Scenario: Getting the scheduler log returns a 200 response
    Given I am logged in as an admin
    And I dont follow redirects
    When I visit '/admin/task-manager/scheduler/log'
    Then I should receive HTTP status: 200

  Scenario: Getting a task log returns a 200 response
    Given There is a task 'Test' with status 'stopped'
    And I am logged in as an admin
    When I visit the 'log' URL of named task 'Test'
    Then I should receive HTTP status: 200

  Scenario: Deleting a task removes it from the listed tasks
    Given There is a task 'Delete me' with status 'stopped'
    And I am logged in as an admin
    When I delete
