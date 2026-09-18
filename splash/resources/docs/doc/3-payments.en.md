---
lang: en
permalink: docs/payments
title: User Payments
description: Default payment method and bank account, and a dedicated bank account for each payment method.
updated: 2026-09-18
---

### Select bank account

Since v1.4 of Splash Module for Dolibarr, it is possible to select, for each active payment method,
the bank account you want to use.

![Bank account selected for each payment method](../assets/img/screenshot_7.png)

#### Default payment method

When an invoice payment is imported, if no valid payment method is given, Splash will use this
default payment method to create the payment.

#### Default bank account

When an invoice payment is imported, if no specific bank account is given, Splash will use this
default value.

#### Bank account per method

For each **active** payment method, select the target bank account to use.
