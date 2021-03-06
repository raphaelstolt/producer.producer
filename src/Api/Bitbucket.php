<?php
/**
 *
 * This file is part of Producer for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Producer\Api;

use Producer\Exception;
use Producer\Repo\RepoInterface;

/**
 *
 * The BitBucket API.
 *
 * @package producer/producer
 *
 */
class Bitbucket extends AbstractApi
{
    /**
     *
     * Constructor.
     *
     * @param string $origin The repository remote origin.
     *
     * @param string $user The API username.
     *
     * @param string $pass The API password.
     *
     */
    public function __construct($origin, $user, $pass)
    {
        // set the HTTP object
        $this->setHttp("https://{$user}:{$pass}@api.bitbucket.org/2.0");

        // set the repo name
        $repoName = parse_url($origin, PHP_URL_PATH);
        if (substr($repoName, -4) == '.hg') {
            $repoName = substr($repoName, 0, -3);
        }
        $this->repoName = trim($repoName, '/');
    }

    /**
     *
     * Extracts the value elements from the API JSON result.
     *
     * @param mixed $json The API JSON result.
     *
     * @return mixed
     *
     */
    protected function httpValues($json)
    {
        return $json->values;
    }

    /**
     *
     * Returns a list of open issues from the API.
     *
     * @return array
     *
     */
    public function issues()
    {
        $issues = [];

        $yield = $this->httpGet(
            "/repositories/{$this->repoName}/issues",
            [
                'sort' => 'created_on'
            ]
        );

        foreach ($yield as $issue) {
            $issues[] = (object) [
                'title' => $issue->title,
                'number' => $issue->id,
                'url' => "https://bitbucket.org/{$this->repoName}/issues/{$issue->id}",
            ];
        }

        return $issues;
    }

    /**
     *
     * Submits a release to the API.
     *
     * @param RepoInterface $repo The repository.
     *
     * @param string $version The version number to release.
     *
     */
    public function release(RepoInterface $repo, $version)
    {
        $repo->tag($version, "Released $version");
        $repo->sync();
    }
}
