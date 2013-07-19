#tdt-UI

This is a user interface created for The DataTank. It has the following functions:
- Manage resources
- Manage routes
- Manage users
- Add jobs to tdt/input
- Test mapping files

- - -

## Installation

The first step is to install The DataTank. If you want to use The DataTank Installer, continue with [setup using the installer](https://github.com/oSoc13/tdt-UI/tree/development#setup-using-the-installer).
If you want to install The DataTank using composer, follow the instructions in [setup using composer](https://github.com/oSoc13/tdt-UI/tree/development#setup-using-composer).

### Setup using the installer

Download [tdt/installer](https://github.com/oSoc13/tdt-Installer) to install The DataTank. Before you continue to follow the instructions provided on that page, go to the settings folder and set these properties in tdt-start.json:

```json
	"link" : "https://github.com/oSoc13/tdt-start.git",
  	"zip" : "https://github.com/oSoc13/tdt-start/archive/master.zip",
  	"zipdirname" : "tdt-start-master"
```

After this, follow the complete installation process described [here](https://github.com/oSoc13/tdt-Installer).

Finally, you need to [configure tdt/start](https://github.com/oSoc13/tdt-UI/tree/development#configure-tdtstart).

### Setup using composer

Create a new project by cloning the tdt/start repository:
```bash
git clone https://github.com/oSoc13/tdt-start
```

Setup tdt/start, as explained in the "Getting started" section of the [github page](https://github.com/tdt/start#getting-started).

Now, the only thing left to do, is to [configure tdt/start](https://github.com/oSoc13/tdt-UI/tree/development#configure-tdtstart).

## Configure tdt/start

Now you need to adapt the configuration of tdt/start, to make everything in the UI work.

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

Now update your project (in the tdt/start folder):

```bash
composer update
```

If anything goes wrong with this command, try to run it as superuser (because the folders may be owned by the php user).