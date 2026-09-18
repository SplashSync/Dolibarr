---
lang: en
permalink: start/configure
title: Configure the Splash module
description: Connect the module to your Splash account and set its default parameters.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 9558c324
    mode:        llm
---

Once enabled, the module needs to be connected to your Splash account and given a few default
values. Count a few minutes :stopwatch:

To open its configuration, click the settings icon of the Splash module in
**Setup > Modules/Applications**.

### Connect to your Splash account

Start by creating the access keys of your server: on your Splash workspace, go to **Servers** >
**Add a server**, then note the server identifier and its encryption key.

![Adding a server on Splash workspace](../assets/img/screenshot_2.png)

Then enter these two keys in the **Main Parameters** block of the module configuration.

> [!IMPORTANT]
> Copy the keys as they are, without any extra space or missing character: a single wrong
> character prevents any connection.

![Splash keys in module configuration](../assets/img/screenshot_3.png)

### Set the default parameters

The **Local Parameters** block holds the values used whenever an object is created or updated
without an explicit value.

![Module default parameters](../assets/img/screenshot_4.png)

#### Default language

The language the module uses to communicate with the Splash server.

#### Default user

The user on whose behalf the module runs all its actions.

> [!TIP]
> Create a dedicated user for Splash: the module applies the Dolibarr rights policy, so this user
> must hold the rights on every object you want to synchronize.

#### Default warehouse, bank account and payment method

The values used when the other application provides none.

### Check the self-tests results

Each time you save the configuration, the module checks your parameters and makes sure that
communication with Splash works.

> [!WARNING]
> All tests must be green: a failed self-test means the server cannot synchronize.

![Self-tests results](../assets/img/screenshot_5.png)
