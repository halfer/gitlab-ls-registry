gitlab-ls-registry
===

This is a simple PHP console command to fetch useful Docker registry data from GitLab. I
wrote it because I couldn't figure out how to do this (a) the Docker way, or (b) using
the official GitLab API.

It is not finished, but the classes that do exist are pretty self-explanatory. Use
an IDE to get auto-complete on the `GitLab` class.

Initialisation
---

To create a new instance of the registry lister, run this:

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
        ->setResultsPerPage(5)
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

Improvements
---

* Matching filter on any image key
* A console binary with switches
* Autoloading of main classes
