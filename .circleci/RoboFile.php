<?php

// @codingStandardsIgnoreStart

/**
 * Base tasks for setting up a module to test within a full Drupal environment.
 *
 * This file expects to be called from the root of a Drupal site.
 *
 * @class RoboFile
 * @codeCoverageIgnore
 */
class RoboFile extends \Robo\Tasks
{

    /**
     * The database URL.
     *
     * @var string
     */
    const DB_URL = 'mysql://root@127.0.0.1/drupal8';

    /**
     * Command to run unit tests.
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobRunUnitTests()
    {
        $collection = $this->collectionBuilder();
        $collection->addTask($this->installDependencies());
        $collection->addTask($this->installDrupal());
        $collection->addTaskList($this->runUnitTests());
        return $collection->run();
    }

    /**
     * Command to generate a coverage report.
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobGenerateCoverageReport()
    {
        $collection = $this->collectionBuilder();
        $collection->addTask($this->installDependencies());
        $collection->addTask($this->installDrupal());
        $collection->addTaskList($this->runUnitTestsWithCoverage());
        return $collection->run();
    }

    /**
     * Installs composer dependencies.
     *
     * @return \Robo\Contract\TaskInterface
     *   A task instance.
     */
    protected function installDependencies()
    {
        return $this->taskComposerInstall()
            ->optimizeAutoloader();
    }

    /**
     * Install Drupal.
     *
     * @return \Robo\Task\Base\Exec
     *   A task to install Drupal.
     */
    protected function installDrupal()
    {
        $task = $this->drush()
            ->args('site-install')
            ->option('verbose')
            ->option('yes')
            ->option('db-url', static::DB_URL, '=');
        return $task;
    }

    /**
     * Run unit tests.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runUnitTests()
    {
        $force = true;
        $tasks = [];
        $tasks[] = $this->taskFilesystemStack()
            ->copy('.circleci/config/phpunit.xml', 'web/core/phpunit.xml', $force)
            ->mkdir('artifacts/phpunit', 777);
        $tasks[] = $this->taskExecStack()
            ->dir('web')
            ->exec('../vendor/bin/phpunit -c core --debug --verbose --log-junit ../artifacts/phpunit/phpunit.xml modules/custom');
        return $tasks;
    }

    /**
     * Run unit tests and generates a code coverage report.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runUnitTestsWithCoverage()
    {
        $force = true;
        $tasks = [];
        $tasks[] = $this->taskFilesystemStack()
            ->copy('.circleci/config/phpunit.xml', 'web/core/phpunit.xml', $force)
            ->mkdir('artifacts/coverage-xml', 777)
            ->mkdir('artifacts/coverage-html', 777);
        $tasks[] = $this->taskExecStack()
            ->dir('web')
            ->exec('../vendor/bin/phpunit -c core --debug --verbose --coverage-xml ../artifacts/coverage-xml --coverage-html ../artifacts/coverage-html modules/custom');
        return $tasks;
    }

    /**
    cp .circleci/config/phpunit.xml web/core/
    mkdir -p artifacts/coverage-xml
    mkdir -p artifacts/coverage-html
    chmod -R 777 artifacts
    cd web
    timeout 60m sudo -E -u www-data ../vendor/bin/phpunit --verbose --debug -c core --coverage-xml ../artifacts/coverage-xml --coverage-html ../artifacts/coverage-html --testsuite nonfunctional modules/custom
    cd ../
    tar czf artifacts/coverage.tar.gz -C artifacts coverage-html coverage-xml
     */

    /**
     * Return drush with default arguments.
     *
     * @return \Robo\Task\Base\Exec
     *   A drush exec command.
     */
    protected function drush()
    {
        // Drush needs an absolute path to the docroot.
        $docroot = $this->getDocroot() . '/web';
        return $this->taskExec('vendor/bin/drush')
            ->option('root', $docroot, '=');
    }

    /**
     * Get the absolute path to the docroot.
     *
     * @return string
     */
    protected function getDocroot()
    {
        $docroot = (getcwd());
        return $docroot;
    }

}
