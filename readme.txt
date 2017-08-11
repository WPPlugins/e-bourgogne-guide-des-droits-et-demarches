=== e-bourgogne Guide des Droits et Démarches===
Contributors: 
Tags: e-bourgogne, bourgogne
Requires at least: 3.3
Tested up to: 4.3.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Permet d'intégrer les Guides des Droits et Démarches (GDD) e-bourgogne à vos pages.

== Description ==

Ce plugin vous permettra d'intégrer directement dans vos pages les Guides du service "Mon Service Public->Mon guide des démarches administratives" d'[e-bourgogne](www.e-bourgogne.fr).

== Installation ==

1. Installez le plugin depuis l'outil de gestion des extensions de Wordpress et activez-le. Vous pouvez également installer le plugin manuellement en extrayant les fichiers du plugin dans le dossier `wp-content/plugins/e-bourgogne-gdd`. 
1. Allez dans le menu de configuration "e-bourgogne" (ou "e-bourgogne->Guide des Droits et Démarches" si vous avez déjà installé un autre module e-bourgogne) et renseignez votre clé d'API e-bourgogne dans le champ prévu à cette effet (veuillez contacter l'administrateur de votre organisme pour connaître votre clé) . Si un autre module e-bourgogne est déjà configuré, la même clé sera utilisée pour tout les modules.
1. Configurez l'affichage de vos guides sur la page de configuration du module.

_Note_ : les modules e-bourgogne nécessitent l'activation de l'extension `cURL` pour PHP


== Frequently Asked Questions ==

= Les liens à l'intérieur du guide ne fonctionnent pas, que faire ? =
Si les permaliens de votre site Wordpress sont configurés au format simple (ex : http://monsite.fr/?p=42), les liens disponibles au sein des guides ne seront pas fonctionnels. Pour que ceux-ci fonctionnent, vos permaliens doivent être configuré suivant un autre format.

= Comment configurer un Guide pour mon site ? =
Pour configurer un guide, vous devez vous rendre dans le panneau de configuration du plugin"e-bourgogne" (ou "e-bourgogne->Guide des Droits et Démarches" si vous avez déjà installé un autre module e-bourgogne). Dans ce panneau, vous verrez l'outil de configuration, où vous pourrez créer des configurations ou en modifier des existantes. Pour créer une nouvelle configuration : 

1. Cliquez sur "Nouveau" en haut de la liste à droite. 
1. Choisissez un nom pour votre configuration, un type de flux et le niveau d'arborescence du Guide que vous souhaitez afficher (cliquez simplement sur le niveau ou le Guide choisi). 
1. Si vous le souhaitez, ajoutez l'URL d'une feuille de style CSS à appliquer au Guide. 
1. Sauvegardez votre configuration en cliquant sur "Enregistrer la configuration". 

Vous pouvez reprendre une configuration existante en cliquant sur son nom dans la liste à gauche. Pour afficher une configuration, voir "Comment afficher un Guide sur ma page".

= Comment afficher un Guide sur ma page ? =
Avant de pouvoir afficher un Guide, vous devez l'avoir configuré (voir "Comment configurer un Guide pour mon site"). Lors de la création ou de l'édition d'un nouvel article (ou d'une nouvelle page), cliquez sur le bouton "Ajouter un GDD" (situé au dessus de l'éditeur). Une fenêtre s'ouvre dans laquelle vous pouvez choisir le Guide préalablement configuré que vous voulez insérer dans votre page. Validez la sélection et enregistrer l'article. En visitant la page, vous devriez voir votre Guide affiché. *Attention*, il est fortement déconseillé d'afficher plusieurs Guides sur une même page.

= L'un de mes guides ne s'affiche pas, que faire ? =
Vérifiez que la configuration du Guide en question n'a pas été supprimée dans le panneau de configuration du plugin Guide d'e-bourgogne. Vérifiez également que le Guide que vous tentez d'afficher est toujours disponible dans le service "Mon Service Public->Mon guide des démarches administratives" d'[e-bourgogne](www.e-bourgogne.fr).

= Je souhaite afficher plusieurs Guides sur ma page, est-ce possible ? =
Il est fortement déconseillé d'intégrer plusieurs Guide sur une même page, que ce soit dans des articles différents ou dans un même article. Cela reste néanmoins possible, mais peut conduire à des comportements anormaux des Guides.

= Où puis-je récupérer ma clé d'API ? =
La clé d'API permettant d'accéder aux services e-bourgogne via les plugins doit vous être fournie par l'administrateur de votre organisme. Elle doit ensuite être renseignée dans le panneau de configuration du plugin e-bourgogne. Note : la clé est partagée par tout les plugins e-bourgogne ; si plusieurs plugins e-bourgogne sont installés, modifier la clé pour l'un d'entre eux la modifiera pour tout les autres également.

= Comment savoir si ma clé d'API est correcte ? =
Lorsque vous affichez le panneau de configuration de votre plugin e-bourgogne, vous verrez un champ "Clé d'API" en haut de la page. Inscrivez-y votre clé, puis cliquez sur "Enregistrer la clé". Si celle-ci est valide, vous verrez une coche verte apparaître à côté du champ. Dans le cas contraire, un message vous indiquera que votre clé est incorrecte.


== Changelog ==

= 1.0.1 =
* Ajout d'un avertissement en cas d'absence de cURL PHP

= 1.0 =
* Ajout de l'outil de configuration de l'affichage d'un Guide
* Ajout du bouton d'intégration des guides