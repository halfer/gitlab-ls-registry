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
    protected $resultsPerPage = 10;

    // Internals
    protected $curl;
    protected $registryInfo = [];
    protected $imageInfo = [];

    public function __construct($userName, $projectName, $privateToken = null)
    {
        $this->userName = $userName;
        $this->projectName = $projectName;
        $this->privateToken = $privateToken;
    }

    /**
     * Sets the current page number
     *
     * @todo Throw error if 0 or less than zero
     *
     * @param integer $pageNo
     * @return $this
     */
    public function setPageNo($pageNo)
    {
        $this->pageNo = (int) $pageNo;

        return $this;
    }

    /**
     * Sets the number of results to return per page
     *
     * @todo Throw error if 0 or less than zero
     *
     * @param integer $resultsPerPage
     * @return $this
     */
    public function setResultsPerPage($resultsPerPage)
    {
        $this->resultsPerPage = (int) $resultsPerPage;

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

    /**
     * Returns the currently known information about the registry
     *
     * @return array
     */
    public function getRegistryInfo()
    {
        return $this->registryInfo;
    }

    /**
     * Does a curl fetch of the image list data
     */
    public function fetchImageList()
    {
        $url = $this->getRegistryUrl() . 
            '&page=' . $this->getPageNo() .
            '&per_page=' . $this->getResultsPerPage();
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
     * @todo Throw an error if we reach the max calls without breaking
     *
     * @param int $maxCalls
     */
    public function fetchAllImages($maxCalls = 20)
    {
        $imageInfo = [];
        for($page = 1; $page <= $maxCalls; $page++)
        {
            $this
                ->setPageNo($page)
                ->fetchImageList();
            $slice = $this->getImageList();
            $imageInfo = array_merge($imageInfo, $slice);

            // Know when to quit
            if (count($slice) < $this->getResultsPerPage())
            {
                break;
            }
        }

        // Overwrite the last slice with the whole lot
        $this->imageInfo = $imageInfo;

        return $this;
    }

    /**
     * Sorting system for registry data
     *
     * @todo Implement asc/desc sorting
     * @todo Throw an error if the specified key does not exist on either side
     *
     * @param string $key
     * @param boolean $ascending
     */
    public function sortImageList($key, $ascending = true)
    {
        usort($this->imageInfo, function($a, $b) use ($key)
        {
            $va = isset($a[$key]) ? $a[$key] : null;
            $vb = isset($b[$key]) ? $b[$key] : null;
            if ($va == $vb)
            {
                return 0;
            }

            return ($va < $vb) ? -1 : 1;
        });

        return $this;
    }

    /**
     * Filter existing results using this key-value pair
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function matchFilter($key, $value)
    {
        $imageInfo = array_filter(
            $this->imageInfo,
            function(array $entry) use ($key, $value)
            {
                $thisValue = $entry[$key];

                return $thisValue === $value;
            }
        );

        // Renumber the indexes (it feels too flakey to let users rely on it)
        $this->imageInfo = array_values($imageInfo);

        return $this;
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

    /**
     * Gets the number of images in the image list
     *
     * @return integer
     */
    public function getImageCount()
    {
        return count($this->getImageList());
    }

    /**
     * Performs an internal curl operation
     *
     * @param string $url
     * @param boolean $convertJson
     * @return string|array
     */
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

    protected function getPageNo()
    {
        return $this->pageNo;
    }

    protected function getResultsPerPage()
    {
        return $this->resultsPerPage;
    }
}
