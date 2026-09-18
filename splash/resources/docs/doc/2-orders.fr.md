---
lang: fr
permalink: docs/orders
title: Commandes & Factures
description: Paramètres d'import des commandes et factures, détection des taux de TVA et commandes invités.
updated: 2026-09-18
---

### Configurer les imports

Depuis la version 1.4 du module Splash pour Dolibarr, un bloc de configuration regroupe tous les
paramètres d'import des commandes et factures.

![Paramètres d'import des commandes & factures](../assets/img/screenshot_6.png)

### Détection des taux de TVA

Lors de l'importation de lignes de commandes et de factures, le module Splash peut identifier le
taux de taxe de la ligne à l'aide d'un code de taxe partagé.

Cette fonctionnalité est utile pour les pays qui ont des taux de TVA multiples ou complexes
(Canada).

#### Comment le configurer ?

Tout d'abord, vous devez créer, sur chaque serveur, les mêmes codes pour les taux de TVA. Pour
Dolibarr, cette configuration est disponible dans
**Configuration > Dictionnaires > Taux de TVA ou de Taxes de Ventes**.

Avec Dolibarr, le nom du taux de TVA est « Code », cette valeur est vide par défaut. Généralement,
vous pouvez utiliser les codes utilisés par votre e-commerce.

![Dictionnaire des taux de TVA dans Dolibarr](../assets/img/screenshot_8.png)

#### Comment ça marche ?

Si vous regardez les données disponibles pour les objets Commandes & Factures, vous verrez un champ
appelé « Taux de TVA ».

![Champ Taux de TVA sur les objets commandes & factures](../assets/img/screenshot_9.png)

Lorsque Splash importe une commande ou une facture, si le code indiqué se trouve dans votre
dictionnaire Dolibarr, Splash utilise ce taux de TVA pour créer la ligne de produits.

#### Limites

Jusqu'à présent, seule une partie de nos modules est compatible avec cette fonctionnalité.

> [!IMPORTANT]
> Pour utiliser cette fonctionnalité, vous devez vous assurer que les codes de TVA sont
> **strictement** identiques sur toutes les applications connectées.

### Import des commandes invités

#### Pourquoi ?

La plupart des plateformes de e-commerce modernes offrent désormais aux clients la possibilité de
passer une commande sans créer de compte client. Du côté de l'ERP, il n'est pas possible de créer
une commande (ou une facture) sans pointer vers un client. Pour résoudre ce problème, nous avons
développé une fonctionnalité spécifique.

#### Que fait-elle ?

Lorsque vous activez **Import de Commandes et de Factures en mode Invité**, Splash supprime le
drapeau **Requis** sur le client. Ainsi, le serveur transmettra toutes les nouvelles commandes et
factures à Dolibarr, qu'un client soit défini ou non.

Dans ce mode, toute commande (ou facture) qui n'a pas de client défini sera attachée à un client
prédéfini.

#### Configuration

Pour utiliser ce mode, activez simplement la fonction et sélectionnez le client par défaut à
utiliser.

> [!TIP]
> Nous recommandons fortement la création d'un client dédié.

#### Détection par e-mail

Cette fonctionnalité supplémentaire permet de détecter des clients déjà connus à l'aide de leur
e-mail, s'il est fourni par le serveur. Si l'e-mail appartient à un tiers existant, la commande sera
attachée à ce client et non au client par défaut.
