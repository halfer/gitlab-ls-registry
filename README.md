gitlab-ls-registry
===

This is a simple PHP console command to fetch useful Docker registry data from GitLab. I
wrote it because I couldn't figure out how to do this (a) the Docker way, or (b) using
the official GitLab API. They may be a better way to do this, let me know if so!

It is a work in progress, but the classes that do exist are pretty self-explanatory.
Use an IDE to get auto-complete on the `GitLab` class.

Private registries
---

If you have a private registry, you'll need to create a
[Personal Access Token](https://gitlab.com/profile/personal_access_tokens) in order
to access it. You'll need to give the token a name and an expiry date, and choose the
appropriate scopes. The `api` scope is fine, but if you're only using this token for
getting registry information, use `read_registry` instead.

For security reasons, this screen will only show you the token's value for as
long as it is visible - as soon as you move to another page on GitHub, you
will no longer let you view it. So, take a copy and put it somewhere safe.

Installation
---

This repo is not on Packagist, so you'll need to add a custom repo entry in your
`composer.json`. Something like this should be fine:

    {
        "require": {
            "halfer/gitlab-ls-registry": "dev-master"
        },
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/halfer/gitlab-ls-registry.git"
            }
        ]
    }

Then do `composer install` or `composer update` in the usual way.

Initialisation
---

To create a new instance of the registry lister, run this:

    // Set up the autoloaders
    require_once $root . '/vendor/autoload.php';

    $username = '<your-username>';
    $project = '<your-project>';
    $token = '<your-token>';
    $gitLab = new GitLab($username, $project, $token);

I've not tried it, but I think if you want to work with a public registry, you can
simply omit the token:

    $username = '<your-username>';
    $project = '<your-project>';
    $gitLab = new GitLab($username, $project);

Getting registry information
---

You can get some interesting registry metadata thus:

    $data = $gitLab
        ->fetchRegistryInfo()
        ->getRegistryInfo();
    print_r($data);

This will return data that looks like this:

    Array
    (
        [id] => 12345
        [path] => jonuser/my-wordpress
        [location] => registry.gitlab.com/jonuser/my-wordpress
        [tags_path] => /jonuser/my-wordpress/registry/repository/12345/tags?format=json
        [destroy_path] => /jonuser/my-wordpress/container_registry/12345.json
    )

Getting images information
---

Here's how to set a page size, retrieving the first available page of data, then sort
by a key:

    $data = $gitLab
        ->fetchRegistryInfo()
        ->setResultsPerPage(5)
        ->setPageNo(1)
        ->fetchImageList()
        ->sortImageList('total_size')
        ->getImageList();
    print_r($data);

Here is a shortcut method to step through several pages worth of results. In this case
you don't need to set a page number, as it will set this for you, starting at page 1:

    $data = $gitLab
        ->fetchRegistryInfo()
        ->setResultsPerPage(20)
        ->fetchAllImages()
        ->sortImageList('total_size')
        ->getImageList();
    print_r($data);

If successful, either approach will result in an array of arrays that look like this:

    [name] => v1.01
    [location] => registry.gitlab.com/jonuser/my-wordpress:v1.01
    [revision] => 8b6e7c5564c3256eefa894ed8303dd0bdc441fce998bd1853f857226ed929d8f
    [short_revision] => 8b6e7c556
    [total_size] => 32049316
    [created_at] => 2018-04-22T12:23:57.707+00:00
    [destroy_path] => /jonuser/my-wordpress/registry/repository/84654/tags/v1.01

Filtering image information
---

Image metadata can be filtered using an equality filter. For example, to fetch
images that match a specific hash:

    $data = $gitLab
        ->fetchRegistryInfo()
        ->setResultsPerPage(20)
        ->fetchAllImages()
        ->matchFilter('revision', '8b6edd0bdc441fce997c5564c3d8b6eefa894e72261853f85258303ded929d8f')
        ->getImageList();

Counting images
---

A useful check is to see if something exists already e.g. a tag. To do that, we can
use the count method:

    echo $gitLab
        ->fetchRegistryInfo()
        ->setResultsPerPage(20)
        ->fetchAllImages()
        ->matchFilter('name', 'v1.02')
        ->getImageCount();

This will output an integer, which can be grepped for 0 (i.e. does not exist).

Any of the keys in the metadata blocks can be used as a field to filter on.

Testing
---

This code was tested on the free version of [hosted GitLab](https://about.gitlab.com/pricing/#gitlab-com).
I would expect it to work on paid versions in the cloud as well. I have not tested this
on a self-hosted instance.

Possible improvements
---

* A descending sort mode
* A console binary with switches
* Ask GitLab how stable this API is :)
* Put onto Packagist if there's demand
