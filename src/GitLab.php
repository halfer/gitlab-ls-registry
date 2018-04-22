<?php

/**
 * A class to get a registry listing in GitLab
 */

namespace DockerLs\Registry;

use DockerLs\Registry\Exceptions\RegistryNotFound;
use DockerLs\Registry\Exceptions\GeneralError;

class GitLab
{
    const BASE_DOMAIN = 'https://gitlab.com';

    // Settings
    protected $userName;
    protected $projectName;
    protected $privateToken;
    protected $pageNo = 1;
    protected $resultsPerPage;

    // Internals
    protected $curl;
    protected $registryInfo;
    protected $imageInfo;

    public function __construct($userName, $projectName, $privateToken = null)
    {
        $this->userName = $userName;
        $this->projectName = $projectName;
        $this->privateToken = $privateToken;
    }

    public function setPageNo($pageNo)
    {
        $this->pageNo = (int) $pageNo;

        return $this;
    }

    public function setResultsPerPage($resultsPerPage)
    {
        $this->resultsPerPage = $resultsPerPage;

        return $this;
    }

    public function fetchRegistryInfo()
    {
        $url = $this->getBaseUrl() . '/container_registry.json';
        $data = $this->curl($url);

        // We just find the first registry for now
        if (!isset($data[0]))
        {
            throw new RegistryNotFound('Could not find a registry for this user/project');
        }

        $registry = $data[0];
        if (!isset($registry['tags_path']))
        {
            throw new GeneralError('Cannot find the registry base URL');
        }

        // Capture the registry info
        $this->registryInfo = $registry;

        return $this;
    }

    // @todo Needs an exception if registryInfo is not set
    public function getRegistryInfo()
    {
        return $this->registryInfo;
    }

    /**
     * Does a curl fetch of the image list data
     *
     * @todo Needs a page number and page size integrating
     */
    public function fetchImageList()
    {
        $url = $this->getRegistryUrl();
        $imageInfo = $this->curl($url);

        if (!is_array($imageInfo))
        {
            throw new GeneralError("Cannot fetch an image list from the registry");
        }

        $this->imageInfo = $imageInfo;

        return $this;
    }

    /**
     * Calls getImageList repeatedly until no more data is returned
     *
     * (This is useful as I don't think the JSON endpoint supports sorting,
     * so we have to get everything and then sort it ourselves).
     *
     * @param int $maxCalls
     */
    public function retrieveAllImages($maxCalls = 20)
    {
        $data = [];
        for($page = 1; $page <= $maxCalls; $page++)
        {
            $this->setPageNo($page);
            $data = array_merge($data, $this->getImageList());
        }

        return $this;
    }

    public function sort($key)
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Gets the currently known image list data
     *
     * @return array
     */
    public function getImageList()
    {
        return $this->imageInfo;
    }

    protected function curl($url, $convertJson = true)
    {
        $curl = $this->getCurl();
        curl_setopt($curl, CURLOPT_URL, $url);
        $data = curl_exec($curl);

        if ($convertJson)
        {
            $data = json_decode($data, true);
        }

        return $data;
    }

    protected function getCurl()
    {
        if (!$this->curl)
        {
            $this->curl = $curl = curl_init();
            if ($privateToken = $this->getPrivateToken())
            {
                curl_setopt($curl, CURLOPT_HTTPHEADER, ["Private-Token:$privateToken", ]);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        }

        return $this->curl;
    }

    protected function getRegistryUrl()
    {
        $registryInfo = $this->getRegistryInfo();

        return self::BASE_DOMAIN . $registryInfo['tags_path'];
    }

    protected function getBaseUrl()
    {
        return self::BASE_DOMAIN . '/' . $this->getUserName() . '/' . $this->getProjectName();
    }

    protected function getUserName()
    {
        return $this->userName;
    }

    protected function getProjectName()
    {
        return $this->projectName;
    }

    protected function getPrivateToken()
    {
        return $this->privateToken;
    }
}
