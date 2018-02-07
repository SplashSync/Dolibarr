
## Configuration

### Activez le Module 
La configuration du module est disponible dans la configuration de Dolibarr **Configuration >> Modules >> Interfaces >> Splash** 

![](https://splashsync.github.io/Dolibarr/img/screenshot_1.png)


### Connectez vous à votre compte Splash

D'abord, vous devez créer des clés d'accès pour votre module sur notre site. Pour ce faire, sur votre compte Splash, allez sur ** Serveurs ** >> ** Ajoutez un serveur ** et notez vos clés d'identification et de cryptage qui vous seront données.

![](https://splashsync.github.io/Dolibarr/img/screenshot_2.png)

Ensuite, entrez les clés de la configuration du module (attention à ne pas oublier de caractère).

![](https://splashsync.github.io/Dolibarr/img/screenshot_3.png)

### Configurer les paramètres par défaut

Pour fonctionner correctement, le module a besoin de quelques paramètres. 
Ces valeurs par défaut seront utilisées lors de la création / modification des objets.

![](https://splashsync.github.io/Dolibarr/img/screenshot_4.png)

##### Langue par défaut
Sélectionnez la langue par défaut à utiliser pour la communication avec les serveurs de Splash.

##### User par défaut
Sélectionnez l'utilisateur qui sera utilisé pour toutes les actions exécutées par le module Splash.
Nous recommandons fortement la création d'un utilisateur **dédié** pour Splash.
Soyez conscient que le module Splash prends en compte la configuration des droits des utilisateurs, cet utilisateur doit donc disposer des droit appropriés pour interagir avec Dolibarr.

##### Entrepôt / Compte bancaire / Méthode de Paiement
Définissez ces valeurs à utiliser si aucune valeur n'est spécifiée. 

### Vérifiez les résultats des Self-Tests

Chaque fois que vous mettez à jour votre configuration, le module vérifiera vos paramètres et vous assurera que la communication avec Splash fonctionne bien.
Assurez-vous que tous les tests sont passés ... c'est critique!

![](https://splashsync.github.io/Dolibarr/img/screenshot_5.png)