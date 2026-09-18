---
lang: fr
permalink: start/configure
title: Configurer le module Splash
description: Connectez le module à votre compte Splash et définissez ses paramètres par défaut.
updated: 2026-09-18
---

Une fois activé, le module doit être connecté à votre compte Splash et recevoir quelques valeurs
par défaut. Comptez quelques minutes :stopwatch:

Pour ouvrir sa configuration, cliquez sur l'icône de réglage du module Splash dans
**Configuration > Modules / Applications**.

### Connectez-vous à votre compte Splash

Commencez par créer les clés d'accès de votre serveur : dans votre espace Splash, allez dans
**Serveurs** > **Ajouter un serveur**, puis notez l'identifiant du serveur et sa clé de cryptage.

![Ajout d'un serveur sur l'espace Splash](../assets/img/screenshot_2.png)

Saisissez ensuite ces deux clés dans le bloc **Paramètres généraux** de la configuration du module.

> [!IMPORTANT]
> Copiez les clés telles quelles, sans espace en trop ni caractère oublié : un seul caractère
> erroné empêche toute connexion.

![Clés Splash dans la configuration du module](../assets/img/screenshot_3.png)

### Définissez les paramètres par défaut

Le bloc **Paramètres Locaux** regroupe les valeurs utilisées chaque fois qu'un objet est créé ou
modifié sans valeur explicite.

![Paramètres par défaut du module](../assets/img/screenshot_4.png)

#### Langue par défaut

La langue utilisée par le module pour communiquer avec le serveur Splash.

#### Utilisateur par défaut

L'utilisateur au nom duquel le module exécute toutes ses actions.

> [!TIP]
> Créez un utilisateur dédié à Splash : le module applique la politique de droits de Dolibarr,
> cet utilisateur doit donc disposer des droits sur tous les objets que vous voulez synchroniser.

#### Entrepôt, compte bancaire et mode de paiement par défaut

Les valeurs utilisées lorsque l'autre application n'en fournit aucune.

### Vérifiez les résultats des self-tests

À chaque enregistrement de la configuration, le module vérifie vos paramètres et s'assure que la
communication avec Splash fonctionne.

> [!WARNING]
> Tous les tests doivent être au vert : un self-test en échec signifie que le serveur ne peut pas
> synchroniser.

![Résultats des self-tests](../assets/img/screenshot_5.png)
