---
lang: fr
permalink: start/install
title: Installer le module Splash
description: Téléchargez le module, installez-le depuis l'interface de Dolibarr ou manuellement, puis activez-le.
updated: 2026-09-18
---

### Prérequis

- Dolibarr **14** ou plus récent
- PHP **7.4** ou plus récent
- Un compte Splash Sync actif

### Téléchargez le module

Téléchargez la dernière version du module directement depuis cette page : c'est le fichier
`module_splash-x.y.z.zip`.

Besoin d'une version précédente ? Toutes les versions publiées sont disponibles sur la
[page des versions GitHub](https://github.com/SplashSync/Dolibarr/releases).

> [!NOTE]
> Pour une installation depuis l'interface, ne décompressez pas l'archive : Dolibarr attend le
> fichier `.zip` tel quel.

### Installez depuis l'interface de Dolibarr

C'est la méthode la plus simple, sans accès au serveur.

1. Allez dans **Configuration > Modules / Applications**.
2. Ouvrez l'onglet **Déployer/Installer un module externe**.
3. Sélectionnez le fichier `module_splash-x.y.z.zip` et envoyez-le.

Dolibarr décompresse l'archive et installe le module dans son dossier `custom`.

> [!WARNING]
> L'installation depuis l'interface peut être bloquée :
> - **fichier trop volumineux** : l'archive pèse près de 2 Mo, soit la limite par défaut de PHP.
>   La taille maximale dépend de votre hébergeur (réglages PHP `upload_max_filesize` et
>   `post_max_size`) et de Dolibarr (**Configuration > Sécurité**, onglet **Fichiers**) ; la limite
>   en vigueur est affichée à côté du champ d'envoi ;
> - **installation de modules externes désactivée** : le fichier `installmodules.lock` est présent
>   dans le dossier de données de Dolibarr ; demandez à votre administrateur de le supprimer ;
> - **dossier racine alternatif non défini** : le dossier `custom` n'est pas déclaré dans le
>   fichier `conf.php` de Dolibarr.
>
> Dans tous les cas, vous pouvez aussi passer par l'installation manuelle.

### Ou installez manuellement

1. Décompressez l'archive.
2. Copiez le dossier `splash` dans le dossier `htdocs/custom` de Dolibarr, pour obtenir
   `htdocs/custom/splash`.

### Activez le module

Dans **Configuration > Modules / Applications**, le module Splash se trouve dans la famille
**Interfaces avec des systèmes externes** : activez-le.

![Module Splash dans la liste des modules Dolibarr](../assets/img/screenshot_1.png)

Le module est prêt : il ne reste plus qu'à le configurer.
