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
    protected $tagsUrl;

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

    public function getRegistryInfo()
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

        // Capture the URL for this registry
        $this->tagsUrl = self::BASE_DOMAIN . $registry['tags_path'];

        return $data;
    }

    public function getImageList()
    {
        $url = $this->getRegistryUrl();
        $data = $this->curl($url);
        print_r($data);

        return $data;
    }

    public function getAllImages($maxCalls = 20)
    {
        // Call getImageList repeatedly until no more data is returned
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
        return $this->tagsUrl;
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
