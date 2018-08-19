<?php
/**
 * Some experimental (non-working) code to delete images from GitLab remotely
 *
 * Currently getting a 301 response.
 */

require_once __DIR__ . '/vendor/autoload.php';
#require_once __DIR__ . '/builds/gitlab-ls-registry-0.1.2.phar';

use DockerLs\Registry\GitLab;

/**
 * Fill in configuration in these files:
 *
 * .config_project
 * .config_token
 * .config_username
 */
$token = getConfigValue('token');
$username = getConfigValue('username');
$project = getConfigValue('project');

$gitLab = new GitLab($username, $project, $token);

$hash = isset($argv[1]) ? $argv[1] : null;
if (!$hash)
{
    echo "Error: no image hash supplied\n";
    exit(1);
}

$gitLab
    ->setDebugMode(true)
    ->fetchRegistryInfo()
    ->setResultsPerPage(20)
    ->fetchAllImages()
    ->matchFilter('revision', $hash)
    ->calculateImageAges();

// See if the filtering worked
#print_r($list->getImageList());

// Now try a deletion
$gitLab->deleteImages('registry.gitlab.com');

function getConfigValue($key)
{
    $value = file_get_contents(__DIR__ . '/.config_' . $key);
    $trimmed = trim($value);

    return $trimmed;
}
