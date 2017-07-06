<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\MinkContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Define application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements Context, SnippetAcceptingContext {
  /**
   * Initializes context.
   * Every scenario gets its own context object.
   *
   * @param array $parameters
   *   Context parameters (set them in behat.yml)
   */
  public function __construct(array $parameters = []) {
    // Initialize your context here
  }

  /** @var \Drupal\DrupalExtension\Context\MinkContext */
  private $minkContext;
  /** @BeforeScenario */
  public function gatherContexts(BeforeScenarioScope $scope)
  {
      $environment = $scope->getEnvironment();
      $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

//
// Place your definition and hook methods here:
//
//  /**
//   * @Given I have done something with :stuff
//   */
//  public function iHaveDoneSomethingWith($stuff) {
//    doSomethingWith($stuff);
//  }
//

    /**
     * Fills in form field with specified id|name|label|value
     * Example: And I enter the value of the env var "TEST_PASSWORD" for "edit-account-pass-pass1"
     *
     * @Given I enter the value of the env var :arg1 for :arg2
     */
    public function fillFieldWithEnv($value, $field)
    {
        $this->minkContext->fillField($field, getenv($value));
    }

    /**
     * @Given /^I wait for the batch job to finish$/
     * Wait until the id="updateprogress" element is gone,
     * or timeout after 3 minutes (180,000 ms).
     */
    public function iWaitForTheBatchJobToFinish() {
      $this->getSession()->wait(180000, 'jQuery("#updateprogress").length === 0');
    }

    /**
     * @Given I wait for the progress bar to finish
     */
    public function iWaitForTheProgressBarToFinish() {
      $this->iFollowMetaRefresh();
    }

    /**
     * @Given I follow meta refresh
     *
     * https://www.drupal.org/node/2011390
     */
    public function iFollowMetaRefresh() {
      if ($url = $this->getRefreshURL()) {
        print "redirecting to $url\n";
        $this->getSession()->visit($url);
        print "here is the new content:\n";
        $content = $this->getSession()->getPage()->getContent();
        print "No refresh url found!\n";
        print $content;
      }
      else {
        $content = $this->getSession()->getPage()->getContent();
        print "No refresh url found!\n";
        print $content;
      }
    }

    protected function getRefreshURL() {
      if ($refresh = $this->getSession()->getPage()->find('css', 'meta[http-equiv="Refresh"]')) {
        $content = $refresh->getAttribute('content');
        $url = str_replace('0; URL=', '', $content);
        return $url;
      }
      // Ugh. If there is no http-equiv refresh, extract the URI from the Javascript.
      $content = $this->getSession()->getPage()->getContent();
      if (preg_match('#jQuery\.extend.*"uri":"([^"]*)#', $content, $matches)) {
        $url = json_decode('"' . $matches[1] . '"');
        return $url;
      }
      return false;
    }

    /**
     * @Given I have wiped the site
     */
    public function iHaveWipedTheSite()
    {
        $site = getenv('TERMINUS_SITE');
        $env = getenv('TERMINUS_ENV');

        passthru("terminus --yes env:wipe {$site}.{$env}");
    }

    /**
     * @Given I have reinstalled :arg1
     */
    public function iHaveReinstalled($arg1)
    {
        $site = getenv('TERMINUS_SITE');
        $env = getenv('TERMINUS_ENV');
        $password = getenv('ADMIN_PASSWORD');

        $replacements = [
          '{site-name}' => $site,
          '{env}' => $env,
        ];

        $arg1 = str_replace(array_keys($replacements), array_values($replacements), $arg1);

        $cmd = "terminus --yes drush {$site}.{$env} -- site-install standard --yes --site-name=\"$arg1\" --account-name=admin";
        if (!empty($password)) {
          $cmd .= " --account-pass='$password'";
        }

        passthru($cmd);
    }

    /**
     * @Given I have run the drush command :arg1
     */
    public function iHaveRunTheDrushCommand($arg1)
    {
        $return = '';
        $output = array();
        exec("terminus  --yes drush {$site}.{$env} -- $arg1", $output, $return);
        // echo $return;
        // print_r($output);

    }

    /**
     * @Given I have committed my changes with comment :arg1
     */
    public function iHaveCommittedMyChangesWithComment($arg1)
    {
        $site = getenv('TERMINUS_SITE');
        $env = getenv('TERMINUS_ENV');

        passthru("terminus --yes env:commit {$site}.{$env} --message='$arg1'");
    }

    /**
     * @Given I have exported configuration
     */
    public function iHaveExportedConfiguration()
    {
        $return = '';
        $output = array();
        exec("terminus --yes drush {$site}.{$env} -- config-export -y", $output, $return);
    }

    /**
     * @Given I wait :seconds seconds
     */
    public function iWaitSeconds($seconds)
    {
        sleep($seconds);
    }

    /**
     * @Given I wait :seconds seconds or until I see :text
     */
    public function iWaitSecondsOrUntilISee($seconds, $text)
    {
        $errorNode = $this->spin( function($context) use($text) {
            $node = $context->getSession()->getPage()->find('named', array('content', $text));
            if (!$node) {
              return false;
            }
            return $node->isVisible();
        }, $seconds);

        // Throw to signal a problem if we were passed back an error message.
        if (is_object($errorNode)) {
          throw new Exception("Error detected when waiting for '$text': " . $errorNode->getText());
        }
    }

    // http://docs.behat.org/en/v2.5/cookbook/using_spin_functions.html
    // http://mink.behat.org/en/latest/guides/traversing-pages.html#selectors
    public function spin ($lambda, $wait = 60)
    {
        for ($i = 0; $i <= $wait; $i++)
        {
            if ($i > 0) {
              sleep(1);
            }

            $debugContent = $this->getSession()->getPage()->getContent();
            file_put_contents("/tmp/mink/debug-" . $i, "\n\n\n=================================\n$debugContent\n=================================\n\n\n");

            try {
                if ($lambda($this)) {
                    return true;
                }
            } catch (Exception $e) {
                // do nothing
            }

            // If we do not see the text we are waiting for, fail fast if
            // we see a Drupal 8 error message pane on the page.
            $node = $this->getSession()->getPage()->find('named', array('content', 'Error'));
            if ($node) {
              $errorNode = $this->getSession()->getPage()->find('css', '.messages--error');
              if ($errorNode) {
                return $errorNode;
              }
              $errorNode = $this->getSession()->getPage()->find('css', 'main');
              if ($errorNode) {
                return $errorNode;
              }
              return $node;
            }
        }

        $backtrace = debug_backtrace();

        throw new Exception(
            "Timeout thrown by " . $backtrace[1]['class'] . "::" . $backtrace[1]['function'] . "()\n" .
            $backtrace[1]['file'] . ", line " . $backtrace[1]['line']
        );

        return false;
    }
}
