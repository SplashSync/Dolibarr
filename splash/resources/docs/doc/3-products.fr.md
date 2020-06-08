---
lang: fr
permalink: docs/products
title:  Catalogue Produits
---


### Gestion du Catalogue Produits

Splash dispose de beaucoup de fonctionnalités dédiées à la gestion du catlogue produit.

La gestion des stocks et des prix de vente sont des point très sensibles.
Prennez le temps de bien comprendre à quoi servent les différents paramètres afin de les configurer correctement. 

#### Entrepôt utilisé pour les mouvements de stocks

Afin de gérer correctement vos stocks, vous devez indiquer à Splash quel entrepôt utiliser pour les corrections de stocks.

<div class="callout-block callout-warning">
    <div class="icon-holder">
        <i class="fas fa-exclamation-circle"></i>
    </div>
    <div class="content">
        <h4 class="callout-title">Attention</h4>
        <p>A minima, vous devez créer un entrepôt, même si vous n'avez qu'un seul lieu de stockage.</p>
    </div>
</div>

#### Entrepôt par défaut pour les configurations produits

Lors de la création de produits, Splash peu les configurer afin qu'ils soient associés l'entrepôt de votre choix. 

#### Multi-Prix: Prix par défaut utilisée par le module

Si vous utilisés la fonction multiprix de Dolibarr, vous devez indiquer à Splash quel niveau de prix utiliser comme prix par défaut.

Les autres niveaux de prix seront eux aussi accéssibles, mais dans des champs supplémentaires 
qu'il vous faudra connecter manuellement depuis votre compte Splash.

#### [Expert] Gérez vos Stocks Entrepôt par Entrepôt

**Cette fonction requiert l'activation du mode "Expert"** 

Si vous travaillez avec plusieurs Entrepôts, ce mode vous permettra d'accéder indépendamment aux stocks de chaque entrepôts.

Un champ sera créé pour chaque entrepôt, il faudra ensuite le configurer sur votre compte Splash.

<div class="callout-block callout-success">
    <div class="icon-holder">
        <i class="fas fa-thumbs-up"></i>
    </div>
    <div class="content">
        <h4 class="callout-title">Gestion Multi-sites</h4>
        <p>Il est désormais possible de gérér séparement les stocks de vos sites de E-Commerce et de vos points de vente.</p>
    </div>
</div>
 
Le stock réel de vos produits, champs générique et connecté automatiquement, sera désormais en lécture seule. 
Vous pourrez le lire, mais pas le modifier.