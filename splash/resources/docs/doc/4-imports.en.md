---
lang: en
permalink: docs/imports
title: Data imports
description: Since Dolibarr 24, imported data is natively synchronized by Splash, provided you choose the right import mode.
updated: 2026-10-05
---

The Dolibarr import assistant lets you create or update companies, contacts, products, orders or
invoices in bulk, from a CSV or Excel file.

Since **Dolibarr 24**, these imports are synchronized **natively** by Splash: each imported line is
sent to your other applications, exactly like a manual entry. Only one condition: choose the right
import mode. :white_check_mark:

### Choose the secured mode

In **Tools > Import > New Import**, at the targeted fields step, select in **Import mode**:

**Secured mode (slower) - applies all automatic actions on each imported line**

![Import mode selection in the import assistant](../assets/img/screenshot_10.png)

It is the mode offered by default: just leave it as is.

> [!WARNING]
> In **fast mode**, Dolibarr runs no automatic action on imported lines: Splash is not notified of
> the changes, and the imported data is not synchronized.

> [!NOTE]
> The import simulation never runs automatic actions: this is expected, nothing is written to the
> database yet. Synchronization happens during the final import.

### Importing large volumes?

The secured mode is slower, as each line triggers the same processing as a manual entry. For very
large files, split them into several imports rather than switching to fast mode.

> [!TIP]
> The default mode can be changed by your Dolibarr administrator. If the assistant offers the fast
> mode, simply switch back to the secured mode before running the import.

### Dolibarr versions before 24

Before version 24, the Dolibarr import assistant writes directly to the database, without running
any automatic action: Splash does not see the imported data.

These records will only be synchronized on their next update in Dolibarr. If you import often,
upgrading to Dolibarr 24 is the simplest way to benefit from it.
