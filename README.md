gitlab-ls-registry
===

[![Latest Stable Version](https://poser.pugx.org/halfer/gitlab-ls-registry/v/stable)](https://packagist.org/packages/halfer/gitlab-ls-registry)

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

Installation notes can be [found here](INSTALLATION.md).

Initialisation
---

To create a new instance of the registry lister, run this:

    // Set up the autoloaders
    require_once $root . '/vendor/autoload.php';

    use DockerLs\Registry\GitLab;

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

    $registry = $gitLab
        ->fetchRegistryInfo()
        ->getRegistryInfo();
    print_r($registry);

This will return data that looks like this:

    Array
    (
        [id] => 12345
        [path] => jonuser/my-wordpress
        [location] => registry.gitlab.com/jonuser/my-wordpress
        [tags_path] => /jonuser/my-wordpress/registry/repository/12345/tags?format=json
        [destroy_path] => /jonuser/my-wordpress/container_registry/12345.json
    )

Note that to fetch image data, it is mandatory to fetch registry information first. This
is necessary to get the URL in `tags_path`, which is used to query registry data.

Getting images information
---

Here's how to set a page size, retrieving the first available page of data, then sort
by a key:

    $images = $gitLab
        ->fetchRegistryInfo()
        ->setResultsPerPage(5)
        ->setPageNo(1)
        ->fetchImageList()
        ->sortImageList('total_size')
        ->getImageList();
    print_r($images);

Here is a shortcut method to step through several pages worth of results. In this case
you don't need to set a page number, as it will set this for you, starting at page 1:

    $images = $gitLab
        ->fetchRegistryInfo()
        ->setResultsPerPage(20)
        ->fetchAllImages()
        ->sortImageList('total_size')
        ->getImageList();
    print_r($images);

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

    $images = $gitLab
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

Getting ages of images
---

You might want to take an action based on the age of an image. For example, you might
wish to do a clean Docker build with no caching if your image is over a week old. You
can do that like so:

    $images = $gitLab
        ->fetchRegistryInfo()
        ->fetchAllImages()
        ->sortImageList('created_at')
        ->matchFilter('name', 'v1.02')
        ->calculateImageAges()
        ->getImageList();
    if ($images && isset($images[0]))
    {
        echo $images[0]['created_at_age'];
    }

This will return the usual image list format, plus a new key, containing the number
of whole days old the image is:

    [created_at_age] => 2

By default this calculates an age relative to now, but you can supply a custom `DateTime`
from which ages should be calculated if you wish.

The default unit for the age calculator is days. However, you can supply a custom
DateTimeInterval format string, for example if you wanted the age in hours instead.

Tips
---

If you want to debug the calls being made by curl, there's a debugging mode:

    $images = $gitLab
        ->setDebugMode(true)
        ->fetchRegistryInfo()
        ->setResultsPerPage(20)
        ->fetchAllImages();

The output will look a bit like this:

    Debug: calling https://gitlab.com/jonuser/my-wordpress/container_registry.json
    Debug: call took 35.351953 sec
    Debug: calling https://gitlab.com/jonuser/my-wordpress/registry/repository/12345/tags?format=json&page=1&per_page=20
    Debug: call took 10.322790 sec

In my experience, the cloud version of GitLab can be a bit slow. If you have a lot of
images, try increasing your `setResultsPerPage`, it defaults to just 10. This will reduce
the number of calls required. I don't know what the maximum number of results per page
is, but it could be found by some simple experimentation.

Testing
---

This code was tested on the free version of [hosted GitLab](https://about.gitlab.com/pricing/#gitlab-com).
I would expect it to work on paid versions in the cloud as well. I have not tested this
on a self-hosted instance.

The code relies on an internal API rather than the public one. I have asked [how stable this
API is](https://forum.gitlab.com/t/are-the-internal-gitlab-docker-registry-endpoints-stable/15503) i.e.
whether it can be relied upon.

This library will become redundant if GitLab add registry endpoints to their official
API. If that happens, there are established libraries that will be much better tested,
for example [this one](https://github.com/m4tthumphrey/php-gitlab-api).

Possible improvements
---

* A console binary with switches
* More filter devices (greater than, less than, between, contains, etc.)
* Some integration tests with a real GitLab account
* Add a live example that readers can examine and modify
* See if the `destroy_path` can be used to do registry and image delete operations?
* Allow users to implement their own curl interface using [HTTPlug](http://httplug.io/)
* Add a license for Packagist
