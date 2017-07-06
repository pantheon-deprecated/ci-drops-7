Feature: Update database
  In order to know that update.php is working
  As a website user
  I need to be able to run database updates

  @api
  Scenario: Control to make sure updatedb can be run when there are no updates pending
    Given I am logged in as a user with the "administrator" role
    And I am on "/update.php"
    Then I should see "Drupal database update"
    When I press "Continue"
    Then I should see "No pending updates."

  # Install xmlsitemap 7.x-2.2.  This module already has an update
  # available (7.x-2.3, or later), and after updating there will be at least
  # one update hook to run.  This test is a little fragile, as a future minor
  # update of Drupal might break version 7.x-2.2. Given that changes to Drupal 7
  # are now infrequent and usually minor, this is probably unlikely, though.
  @api
  Scenario: Determine whether a module can be installed and updated with its update_N hooks
    Given I am logged in as a user with the "administrator" role
    And I have run the drush command 'drush dl ctools-7.x-1.0'
    And I am on "/admin/modules"
    Then I should see "Chaos Tools"
    When I check the box "Chaos Tools"
    And I press "Save Configuration"
    Then I should see "The configuration options have been saved."
    When I am on "/admin/modules/update"
    Then I should see "7.x-1.0"
    When I check the box "edit-projects-ctools"
    And I press "Download these updates"
    And I wait for the progress bar to finish
    Then I should see "Updates downloaded successfully"
    When I press "Continue"
    And I wait for the progress bar to finish
    Then I should see "Update was completed successfully"
    When I follow "Run database updates"
    Then I should see "Drupal database update"
    When I press "Continue"
    Then I should see "Increase the length of the ctools_object_cache.obj column"
    When I follow "Apply pending updates"
    And I wait for the progress bar to finish
    Then I should see "Updates were attempted"
    # TODO: find some text that would always appear if there were an error

  @api
  Scenario: Ensure that the previous test finished in maintenance mode, then turn maintenance mode off
    Given I am logged in as a user with the "administrator" role
    And I am on "/"
    Then I should see "Operating in maintenance mode."
    When I have run the drush command 'drush vset maintenance_mode 0'
    And I have run the drush command 'cc all'
    And I am on "/"
    Then I should not see "Operating in maintenance mode."
