
### Configurer les d'imports

Depuis la version 1.4 de Splash Module pour Dolibarr, un blocs de configuration regroupe tous les paramètres d'import des Commandes & Factures.

![](https://splashsync.github.io/Dolibarr/img/screenshot_6.png)

### Détection des taux de TVA (NEW) 

Lors de l'importation de lignes de commandes et de factures, le module Spalsh peut désormais identifier le taux de taxe de la ligne à l'aide d'un code de taxe partagé.

Cette fonctionnalité est utile pour les pays qui ont des taux de TVA multiples ou complexes (Canada).

**Comment le configurer ?**
  
Tout d'abord, vous devez créer, sur chaque serveur, les mêmes codes pour les taux de TVA. Pour Dolibarr, cette configuration est disponible dans **Paramètres >> Dictionnaire >> Taux de TVA**

Avec Dolibarr, le nom du taux de TVA est "Code", cette valeur est vide par défaut. Généralement, vous pouvez utiliser les codes utilisés par votre E-Commerce.

![](https://splashsync.github.io/Dolibarr/img/screenshot_8.png)

**Comment ça marche ?**

Si vous regardez les données qui sont maintenant disponibles pour les objets Commandes & Factures, vous verrez un nouveau champ appelé "Taux de TVA".

![](https://splashsync.github.io/Dolibarr/img/screenshot_9.png)

Lorsque Splash importe une Commande ou une Facture, si le code indiqué se trouve dans votre Dictionnaire Dolibarr, Splash configurera ce Taux de TVA pour créer cette ligne de produits.

**Limites**

Jusqu'à présent, seule une partie de nos modules est compatible avec cette fonctionnalité.

Pour utiliser cette fonctionnalité, vous devez vous assurer que les codes de TVA sont **strictement** similaires sur toutes les applications connectées.


### Import des commandes invités (NEW)

**Pourquoi ?**

La plupart des plateformes E-Commerce modernes offrent désormais aux clients la possibilité de passer une commande sans créer de compte client.
Du côté de l'ERP, il n'est pas possible de créer une commande (ou une facture) sans pointer vers un client.
Pour résoudre ce problème, nous avons développé une fonctionnalité spécifique.

**Qu'est-ce l'on fait ?**

Lorsque vous activez **Import de Commandes et de Factures en mode Invité**, Splash supprime le drapeau **Requis** pour les clients. 
Ainsi, le server transmettra toutes les nouvelles commandes et factures à Dolibarr.
Peu importe si un client est défini ou non.

Dans ce mode, toute Commande (ou Facture) qui n'a pas de client défini sera attachée à un client prédéfini.

**Configuration**

Pour utiliser ce mode, activez simplement la fonction et sélectionnez le client par défaut à utiliser. Nous recommandons fortement la création d'un client dédié.

**Détection d'email**

Cette fonctionnalité supplémentaire peut être utilisée pour détecter des clients déjà connus en utilisant leur Email si fourni par le serveur.
Si l'email donné appartient à un tier existant, la commande sera attachée à ce client et non au client par défaut.