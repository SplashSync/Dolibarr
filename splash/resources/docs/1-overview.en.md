---
lang: en
permalink: overview
title: Connect Dolibarr to all your applications
description: Automatically synchronize companies, products, stocks, orders and invoices between Dolibarr and your other applications with Splash Sync.
updated: 2026-09-18
translation:
    from:        fr
    source_hash: 0c35bfb2
    mode:        llm
---

Online store, point of sale, CRM: each of your applications holds a piece of your business.
The Splash module makes **Dolibarr the hub** of all this data and keeps it continuously in sync,
with no double entry and no manual export. :rocket:

### Why Splash for Dolibarr?

#### :shopping_cart: All your sales in one place

Orders and invoices from your online store, your point of sale or any other connected application
land directly in Dolibarr, whatever the sales channel.

#### :package: Accurate stocks, everywhere

A stock updated in Dolibarr is updated on all your sales channels too: no more orders for
out-of-stock products. Manage a global stock, or the stock of each warehouse separately.

#### :busts_in_silhouette: One customer record, always up to date

With the Splash Linker, the profiles of a same customer spread across your applications are
identified and merged. A change made in one place is applied everywhere, from the CRM to the
store.

#### :bar_chart: Financial management that fills itself

Orders, invoices, credit notes and payments are imported automatically, with the right VAT rates
and the right bank accounts. Your financial follow-up becomes simpler... and effortless.

### What gets synchronized

| Dolibarr object | What is supported |
|---|---|
| Companies | Customers and prospects, customer and accounting codes, address |
| Contacts | Contacts and delivery addresses |
| Products | Catalog, prices and multi-prices, stocks, images, variants, translations |
| Customer orders | Lines, statuses, delivery address, number and PDF of the related invoice |
| Customer invoices | Lines, VAT, payments |
| Customer credit notes | Lines, VAT, refunds |

### Built for real-life use

- :zap: **Frictionless import**: guest orders attached to a default customer, customers recognized
  by their email, products identified by their reference (SKU).
- :receipt: **VAT under control**: VAT codes are recognized and, when missing, resolved from the
  rate, according to your Dolibarr dictionary.
- :house: **Clean addresses**: addresses entered on several lines are split automatically for the
  applications expecting them in several fields.
- :globe_with_meridians: **Multilingual**: product labels and descriptions synchronized in all your
  languages.
- :lock: **Your rules apply**: the module acts on behalf of a dedicated user and follows the
  Dolibarr rights policy.

### Compatibility

- Dolibarr **14 to 24**
- PHP **7.4** or later
- An active Splash Sync account

> [!TIP]
> Installation and configuration only take a few minutes: follow the **Getting started** section
> of this documentation.

### Free and open to contributions

The module is open source and its code is public on
[github.com/SplashSync/Dolibarr](https://github.com/SplashSync/Dolibarr): all contributions are
welcome!
