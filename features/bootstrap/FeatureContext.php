<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Task;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements SnippetAcceptingContext
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $deleteStack = [];

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $i = 0;

        while (! $this->app && $i++ < 3) {
            try {
                $this->app = new Application(Application::ENV_TEST);
            }
            catch (\Exception $ex) {
                usleep(20000 * $i);
            }
        }
    }

    /**
     * @Given There is a task :name with status :status
     */
    public function thereIsATaskWithStatus($name, $status)
    {
        $task = new Task();
        $task
            ->setName($name)
            ->setJobId('Null');

        $this->app['orm.em']->persist($task);
        $this->app['orm.em']->flush();

        $this->deleteStack[] = $task;
    }

    /**
     * @Given I do an AJAX request
     */
    public function iDoAnAJAXRequest()
    {
        $this->getSession('default')->setRequestHeader('Content-Type', 'application/json');
        $this->getSession('default')->setRequestHeader('Accept', 'application/json');
    }

    /**
     * @Given I am logged in as an admin
     */
    public function iAmAnAdminUser()
    {
        $this->getSession('default')->getDriver()->getClient()->followRedirects(true);
        /** @var \Alchemy\Phrasea\Model\Manipulator\UserManipulator $userManipulator */
        $userManipulator = $this->app['manipulator.user'];
        $user = $userManipulator->createUser('admin', 'test', 'admin@admin.dev', true);

        $this->deleteStack[] = function () use ($userManipulator, $user) {
            $userManipulator->delete($user);
        };

        $this->visitPath('/login/?redirect=/');

        $page = $this->getSession('default')->getPage();

        $page->fillField('login', 'admin');
        $page->fillField('password', 'test');

        /** @var \Behat\Mink\Element\NodeElement $form */
        $form = $page->find('named', [ 'id_or_name', 'loginForm' ]);
        $form->submit();
    }

    /**
     * @When I visit :url
     */
    public function iVisit($url)
    {
        $this->visitPath($url);
    }

    /**
     * @When I submit to :url with:
     */
    public function iSubmitToWith($url, TableNode $table)
    {
        $params = $table->getRowsHash();

        /** @var \Behat\Mink\Driver\Goutte\Client $client */
        $client = $this->getSession('default')->getDriver()->getClient();

        $client->request('POST', $url, $params);
    }
    /**
     * @When I submit to :url
     */
    public function iSubmitTo($url)
    {
        /** @var \Behat\Mink\Driver\Goutte\Client $client */
        $client = $this->getSession('default')->getDriver()->getClient();

        $client->request('POST', $url, []);
    }

    /**
     * @Given I dont follow redirects
     */
    public function iVisitWithoutRedirect()
    {
        $this->getSession('default')->getDriver()->getClient()->followRedirects(false);
    }

    /**
     * @When I visit the :urlType URL of named task :task
     */
    public function iVisitTheTasksLogUrl($urlType, $taskName)
    {
        $this->iDoAnAJAXRequest();
        $this->visitPath('/admin/task-manager/tasks');

        $actualTask = null;
        $actualJson = json_decode($this->getSession('default')->getPage()->getContent(), true);

        foreach ($actualJson as $task) {
            if (isset($task['name']) && $task['name'] == $taskName) {
                $actualTask = $task;
            }
        }

        if (! $actualTask) {
            throw new \RuntimeException('Task not found in response.');
        }

        $taskUrl = $actualTask['urls'][$urlType];

        $this->iVisit($taskUrl);
    }

    /**
     * @Then I should receive HTTP status: :expectedStatus
     */
    public function iShouldReceiveHttpStatus($expectedStatus)
    {
        $this->assertResponseStatus($expectedStatus);
    }


    /**
     * @Then I should be redirected to: :expectedUrl
     */
    public function iShouldBeRedirectedTo($expectedUrl)
    {
        if ($this->getSession('default')->getStatusCode() !== 302) {
            $this->printLastResponse();
        }

        $this->assertSession()->responseHeaderMatches('Location', $expectedUrl);
    }

    /**
     * @Then I should receive a JSON array containing at least:
     */
    public function iShouldReceiveAJsonArrayContainingAtLeast(TableNode $tableNode)
    {
        $expectedEntries = $tableNode->getHash();
        $actualJson = json_decode($this->getSession('default')->getPage()->getContent(), true);

        $this->assertResponseStatus(200);

        foreach ($expectedEntries as $expectedEntry) {
            $found = false;

            foreach ($actualJson as $entry) {
                $intersection = array_intersect_assoc($expectedEntry, $entry);
                $diff = array_diff_assoc($intersection, $expectedEntry);

                if (! empty($intersection) && empty($diff)) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                throw new \RuntimeException('Missing entry: ' . json_encode($expectedEntry));
            }
        }
    }

    /**
     * @AfterScenario
     */
    public function after($event)
    {
        foreach ($this->deleteStack as $deleteTask) {
            if (is_callable($deleteTask)) {
                $deleteTask();
                continue;
            }

            $this->app['orm.em']->remove($deleteTask);
        }

        $this->app['orm.em']->flush();
    }
}
