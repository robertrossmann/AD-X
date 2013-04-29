#Usage

First create a config.neon file, with the following contents :

```
main: Laravel
source: /the/path/to/source/files
destination: /where/you/want/the/api
exclude: *stub.php
templateConfig: /the/path/to/this/themes/config.neon
```

Replace the URLs with suitable locations.

Now you can generate with :

```
apigen --config config.neon
```

Enjoy!
