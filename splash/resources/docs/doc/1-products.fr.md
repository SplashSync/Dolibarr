---
lang: fr
permalink: docs/products
title: Catalogue Produits
description: Entrepôts, stocks et multi-prix, les paramètres du catalogue produits à bien configurer.
updated: 2026-09-18
---

Splash dispose de nombreuses fonctionnalités dédiées à la gestion du catalogue produits.

La gestion des stocks et des prix de vente sont des points très sensibles. Prenez le temps de bien
comprendre à quoi servent les différents paramètres afin de les configurer correctement.

### Entrepôt utilisé pour les mouvements de stocks

Afin de gérer correctement vos stocks, vous devez indiquer à Splash quel entrepôt utiliser pour les
corrections de stocks.

> [!WARNING]
> A minima, vous devez créer un entrepôt, même si vous n'avez qu'un seul lieu de stockage.

### Entrepôt par défaut pour les configurations produits

Lors de la création de produits, Splash peut les configurer afin qu'ils soient associés à l'entrepôt
de votre choix.

### Multi-prix : prix par défaut utilisé par le module

Si vous utilisez la fonction multi-prix de Dolibarr, vous devez indiquer à Splash quel niveau de
prix utiliser comme prix par défaut.

Les autres niveaux de prix seront eux aussi accessibles, mais dans des champs supplémentaires qu'il
vous faudra connecter manuellement depuis votre compte Splash.

### Gérez vos stocks entrepôt par entrepôt

> [!NOTE]
> Cette fonction requiert l'activation du mode **Expert**.

Si vous travaillez avec plusieurs entrepôts, ce mode vous permettra d'accéder indépendamment aux
stocks de chaque entrepôt.

Un champ sera créé pour chaque entrepôt, il faudra ensuite le configurer sur votre compte Splash.

> [!TIP]
> **Gestion multi-sites** : il est désormais possible de gérer séparément les stocks de vos sites
> de e-commerce et de vos points de vente.

Le stock réel de vos produits, champ générique et connecté automatiquement, sera désormais en
lecture seule : vous pourrez le lire, mais pas le modifier.
