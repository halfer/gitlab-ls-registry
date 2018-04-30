Building Phars
===

I use [Box](https://github.com/box-project/box2) to build the Phars.

To install the Box binary, use the instructions they supply:

    curl -LSs https://box-project.github.io/box2/installer.php | php

From the root of the project, you can then do:

    ./box build -c builds/box-0.1.1.json

The Box project itself appears to have been abandoned, and good-quality forks seem
to exist. However the old Box project is well starred on GitHub, and works fine for
me.
