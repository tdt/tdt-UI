#tdt-UI

This is a user interface created for The DataTank. It has the following functions:
- Manage resources
- Manage routes
- Manage users
- Add jobs to tdt/input
- Test mapping files

- - -

## Configuration in tdt/start

Create a new project by cloning the tdt/start repository:
```bash
git clone https://github.com/oSoc13/tdt-start
```

Configure tdt/start, as explained in the "Getting started" section of the [github page](https://github.com/tdt/start#getting-started).
Alter composer.json and add, on the root level (the same level as "require"):

```json
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/oSoc13/tdt-core.git"
        }
    ]
```

Then add to requirements:

```json
"tdt/core"      : "dev-development",
"tdt/input"     : "dev-development",
"tdt/ui"        : "dev-development"
```

Now update your project:

```bash
composer update
```