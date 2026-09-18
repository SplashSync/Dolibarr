---
lang: en
permalink: start/install
title: Install the Splash module
description: Download the module, install it from the Dolibarr interface or manually, then enable it.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 377a3cd1
    mode:        llm
---

### Requirements

- Dolibarr **14** or later
- PHP **7.4** or later
- An active Splash Sync account

### Download the module

Download the latest version of the module directly from this page: it is the
`module_splash-x.y.z.zip` file.

Need a previous version? All published versions are available on the
[GitHub releases page](https://github.com/SplashSync/Dolibarr/releases).

> [!NOTE]
> To install from the interface, do not unzip the archive: Dolibarr expects the `.zip` file as is.

### Install from the Dolibarr interface

The simplest method, with no server access required.

1. Go to **Setup > Modules/Applications**.
2. Open the **Deploy/install external app/module** tab.
3. Select the `module_splash-x.y.z.zip` file and upload it.

Dolibarr unzips the archive and installs the module in its `custom` folder.

> [!WARNING]
> Installing from the interface may be blocked:
> - **file too large**: the archive weighs nearly 2 MB, which is PHP's default limit. The maximum
>   size depends on your host (PHP `upload_max_filesize` and `post_max_size` settings) and on
>   Dolibarr (**Setup > Security**, **Files** tab); the limit in force is shown next to the upload
>   field;
> - **install of external modules disabled**: the `installmodules.lock` file is present in the
>   Dolibarr data folder; ask your administrator to remove it;
> - **alternative root directory not defined**: the `custom` folder is not declared in the
>   Dolibarr `conf.php` file.
>
> In all cases, you can use the manual installation instead.

### Or install manually

1. Unzip the archive.
2. Copy the `splash` folder into the Dolibarr `htdocs/custom` folder, to get
   `htdocs/custom/splash`.

### Enable the module

In **Setup > Modules/Applications**, the Splash module is listed in the **Interfaces with external
systems** family: enable it.

![Splash module in the Dolibarr modules list](../assets/img/screenshot_1.png)

The module is ready: all that is left is to configure it.
