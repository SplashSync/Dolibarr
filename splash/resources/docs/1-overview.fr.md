---
lang: fr
permalink: overview
title: Connectez Dolibarr à toutes vos applications
description: Synchronisez automatiquement tiers, produits, stocks, commandes et factures entre Dolibarr et vos autres applications grâce à Splash Sync.
updated: 2026-09-18
---

Boutique en ligne, caisse, CRM : chacune de vos applications détient une partie de votre activité.
Le module Splash fait de **Dolibarr le point central** de toutes ces données et les garde
synchronisées en permanence, sans ressaisie ni export manuel. :rocket:

### Pourquoi Splash pour Dolibarr ?

#### :shopping_cart: Toutes vos ventes au même endroit

Les commandes et factures de votre site e-commerce, de votre point de vente ou de toute autre
application connectée arrivent directement dans Dolibarr, quel que soit le canal de vente.

#### :package: Des stocks justes, partout

Un stock mis à jour dans Dolibarr l'est aussi sur tous vos canaux de vente : fini les commandes de
produits en rupture. Gérez un stock global, ou les stocks de chaque entrepôt séparément.

#### :busts_in_silhouette: Une seule fiche client, toujours à jour

Grâce au Linker Splash, les profils d'un même client répartis dans vos applications sont
identifiés et fusionnés. Une modification faite à un endroit est répercutée partout, du CRM à la
boutique.

#### :bar_chart: Une gestion financière qui se remplit toute seule

Commandes, factures, avoirs et paiements sont importés automatiquement, avec les bons taux de TVA
et les bons comptes bancaires. Votre suivi financier devient plus simple... et sans effort.

### Ce qui est synchronisé

| Objet Dolibarr | Ce qui est pris en charge |
|---|---|
| Tiers | Clients et prospects, codes client et comptable, adresse |
| Contacts | Contacts et adresses de livraison |
| Produits | Catalogue, prix et multi-prix, stocks, images, variantes, traductions |
| Commandes clients | Lignes, statuts, adresse de livraison, numéro et PDF de la facture associée |
| Factures clients | Lignes, TVA, paiements |
| Avoirs clients | Lignes, TVA, remboursements |

### Pensé pour vos usages réels

- :zap: **Import sans friction** : commandes « invité » rattachées à un client par défaut, client
  reconnu par son e-mail, produits identifiés par leur référence (SKU).
- :receipt: **TVA maîtrisée** : les codes TVA sont reconnus et, à défaut, retrouvés à partir du
  taux, selon votre dictionnaire Dolibarr.
- :house: **Adresses propres** : les adresses saisies sur plusieurs lignes sont découpées
  automatiquement pour les applications qui les attendent en plusieurs champs.
- :globe_with_meridians: **Multilingue** : libellés et descriptions produits synchronisés dans
  toutes vos langues.
- :lock: **Vos règles s'appliquent** : le module agit au nom d'un utilisateur dédié et respecte la
  politique de droits de Dolibarr.

### Compatibilité

- Dolibarr **14 à 24**
- PHP **7.4** ou plus récent
- Un compte Splash Sync actif

> [!TIP]
> Installation et configuration ne prennent que quelques minutes : suivez la rubrique
> **Démarrage** de cette documentation.

### Libre et ouvert aux contributions

Le module est open source et son code est public sur
[github.com/SplashSync/Dolibarr](https://github.com/SplashSync/Dolibarr) : toutes les contributions
sont les bienvenues !
