<?php
/**
 *
 * This file is part of Producer for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Producer;

use Producer\Repo\RepoInterface;

/**
 *
 * A container for all Producer objects.
 *
 * @package producer/producer
 *
 */
class ProducerContainer
{
    /**
     *
     * The user's home directory.
     *
     * @var string
     *
     */
    protected $homedir;

    /**
     *
     * The repository directory.
     *
     * @var string
     *
     */
    protected $repodir;

    /**
     *
     * A resource handle pointing to STDOUT.
     *
     * @var resource
     *
     */
    protected $stdout;

    /**
     *
     * A resource handle pointing to STDERR.
     *
     * @var resource
     *
     */
    protected $stderr;

    /**
     *
     * Constructor.
     *
     * @param string $homedir The user's home directory.
     *
     * @param string $repodir The repository directory.
     *
     * @param resource A resource handle pointing to STDOUT.
     *
     * @param resource A resource handle pointing to STDERR.
     *
     */
    public function __construct(
        $homedir,
        $repodir,
        $stdout = STDOUT,
        $stderr = STDERR
    ) {
        $this->homedir = $homedir;
        $this->repodir = $repodir;
        $this->logger = new Stdlog($stdout, $stderr);
    }

    /**
     *
     * Returns a new Command object.
     *
     * @param string $name The command name.
     *
     * @return Command\CommandInterface
     *
     */
    public function newCommand($name)
    {
        $name = trim($name);
        if (! $name || $name == 'help') {
            return new Command\Help($this->logger);
        }

        $class = "Producer\\Command\\" . ucfirst($name);
        if (! class_exists($class)) {
            throw new Exception("Command '$name' not found.");
        }

        $homefs = $this->newFsio($this->homedir);
        $repofs = $this->newFsio($this->repodir);
        $config = $this->newConfig($homefs, $repofs);

        $repo = $this->newRepo($repofs, $config);
        $api = $this->newApi($repo->getOrigin(), $config);

        return new $class($this->logger, $repo, $api, $config);
    }

    /**
     *
     * Returns a new filesystem I/O object.
     *
     * @param string $dir The root directory for the filesystem.
     *
     * @return Fsio
     *
     */
    protected function newFsio($root)
    {
        return new Fsio($root);
    }

    /**
     *
     * Returns a new Config object.
     *
     * @param Fsio $homefs
     *
     * @param Fsio $repofs
     *
     * @return Config
     *
     */
    protected function newConfig(Fsio $homefs, Fsio $repofs)
    {
        return new Config($homefs, $repofs);
    }

    /**
     *
     * Returns a new Repo object.
     *
     * @param Fsio $fsio A filesystem I/O object for the repository.
     *
     * @param Config $config Global and project configuration.
     *
     * @return RepoInterface
     *
     */
    protected function newRepo($fsio, Config $config)
    {
        if ($fsio->isDir('.git')) {
            return new Repo\Git($fsio, $this->logger, $config);
        };

        if ($fsio->isDir('.hg')) {
            return new Repo\Hg($fsio, $this->logger, $config);
        }

        throw new Exception("Could not find .git or .hg files.");
    }

    /**
     *
     * Returns a new Api object.
     *
     * @param string $origin The repository remote origin.
     *
     * @param Config $config A config object.
     *
     * @return RepoInterface
     *
     */
    protected function newApi($origin, Config $config)
    {
        switch (true) {

            case (strpos($origin, 'github.com') !== false):
                return new Api\Github(
                    $origin,
                    $config->get('github_username'),
                    $config->get('github_token')
                );

            case (strpos($origin, 'gitlab.com') !== false):
                return new Api\Gitlab(
                    $origin,
                    $config->get('gitlab_token')
                );

            case (strpos($origin, 'bitbucket.org') !== false):
                return new Api\Bitbucket(
                    $origin,
                    $config->get('bitbucket_username'),
                    $config->get('bitbucket_password')
                );

            default:
                throw new Exception("Producer will not work with {$origin}.");

        }
    }
}
