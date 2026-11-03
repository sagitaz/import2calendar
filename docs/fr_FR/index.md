# Plugin import2calendar

Le plugin sert à importer un calendrier au format Ical dans le plugin Agenda officiel Jeedom (calendar), ce dernier doit donc être installé et configuré.
## Prérequis
- Jeedom 4.4 minimum, Debian 11 minimum
- Plugin Agenda, installé et activé
- Plugin import2calendar

## Attention
**Aucune modification du ical n'est possible, on récupère les infos du ical pour les envoyer au plugin Agenda de Jeedom. Ne faites aucune modification sur l'agenda créé dans le plugin Agenda, elles seraient supprimées au prochain update de votre ical.**


# <u>Configuration</u>

## Configuration du plugin
- **Nombre de jours passé de retention** : durée pendant laquelle un évènement passé reste dans l'agenda, de 3 à 31 jours (3 par défaut).
- **commande jours suivants** : ajoute les commandes des jours à venir (voir la section Plugin Agenda) à tous les agendas du plugin Agenda, y compris ceux qui n'ont pas été créés par import2calendar.

## Créer un équipement
Commencer par ajouter un équipement et choisir son nom
### Paramètre d'import
- **ical** : indiquer l'URL du fichier ical à convertir.
- **Heures de début forcées** : choisir une heure de début d'évènement pour tous ceux du calendrier. Par défaut, ce seront les heures de début de l'évènement enregistré dans l'ical.
- **Heures de fin forcées** : choisir une heure de fin d'évènement pour tous ceux du calendrier. Par défaut, ce seront les heures de fin de l'évènement enregistré dans l'ical.
- **ICAL général** : vacances scolaires françaises et jours fériés. Si sélectionné alors ne rien indiquer dans la zone ical.
- **Auto-actualisation** : choisir le cron de rafraîchissement voulu pour le calendrier.

### Paramètres d'affichage
- **icône** : l'icône qui sera appliquée à chaque event.
- **couleur de fond** : couleur de fond par défaut pour chaque event.
- **couleur de texte** : couleur de texte par défaut pour chaque event.

### Personnalisation des évènements
Ici vous pouvez choisir de personnaliser certains évènements de votre ical.

- Couleur du fond et couleur du texte.

Ici par défaut, rien n'est modifié, ces options permettent de modifier l'heure de début et de fin pour par exemple, anticiper les actions programmées.
- Début : l'évènement commencera X minutes avant son heure de début (de 0 à 360).
- Fin : l'évènement finira X minutes après son heure de fin (de 0 à 360).

### Actions de début et de fin
Pour tous les évènements de votre calendrier seront ajoutées les actions définies ici.
Vous pouvez réorganiser les actions en glisser/déposer.


![Configurations des actions](../images/import2calendar_screenshot03.png)

Vous pouvez indiquer dans la case **nom**, l'évènement pour lequel l'action est prévue. 
- Accepte un nom partiel
- Ne tient pas compte des majuscules

**1** et **2** - Laisser vide ou mettre **all** pour que l'action soit ajoutée à tous les évènements de l'agenda.
**4** - Mettre **others** pour que l'action soit ajoutée à tous les évènements de l'agenda sauf ceux pour lesquels une action personnalisée est prévue.
**3** et **5** - Mettre le **nom de l'évènement** pour que l'action ne soit ajoutée que pour eux.


Vous pouvez maintenant cliquer sur **sauvegarder**.
L'agenda correspondant sera créé dans le plugin agenda.

Exemple :

Ici, nous voyons les évènements dans le plugin Agenda, on voit par ailleurs que j’ai personnalisé la couleur également.

![Agenda](../images/Agenda-exemple.png)

La configuration dans le plugin import2calendar

![Couleurs](../images/personnalisation-couleurs.png)

![Actions](../images/personnalisation-actions.png)

On retourne sur l'agenda pour vérifier les actions
![Agenda vérification](../images/import2calendarActions.gif)

## Édition d'un équipement
Si vous modifiez une des options suivantes :
- icône
- couleur de fond
- couleur de texte
- personnalisation des évènements (couleurs, début et fin)
- heures de début et de fin forcées
- actions de début
- actions de fin

Les events seront modifiés dans l'agenda (calendar) dès la sauvegarde de l'équipement.

## Gestion des événements
L'ical est traité à chaque sauvegarde de l'équipement et à chaque exécution de sa commande **Rafraichir**. Le cron défini, lui, ne le traite que si l'ical a changé.
Le 1er de chaque mois, ainsi qu'après chaque mise à jour du plugin, tous les agendas sont traités à nouveau au passage suivant de leur cron. Un équipement sans cron ne l'est qu'à sa prochaine sauvegarde.
Si un événement n'est plus dans l'ical, il est supprimé de l'agenda.
Les événements passés depuis plus de 3 jours (durée réglable dans la configuration du plugin) ne sont pas importés et seront supprimés au fur et à mesure.

## Occurrences
Les règles définies dans votre ical sont converties au format Jeedom Agenda. Je n'ai pas testé toutes les possibilités, si jamais certaines ne passent pas, merci de joindre la ligne du log import2calendar : **START OPTIONS** (mettre vos logs en debug).


Les évènements présents dans l'occurrence restent visibles sur le calendrier tant que l'occurrence est valide.

Exemple : 1 évènement tous les 5 jours du 01-03-2024 au 24-11-2024. Tous les évènements sont visibles sur le calendrier jusqu'au 27-11-2024 (3 jours après la fin de l'occurrence, avec la durée par défaut).

## Plugin Agenda
Dans votre agenda, les commandes infos sur les événements à venir seront ajoutées. Elles sont mises à jour chaque nuit, ou à la demande avec le bouton **Mise à jour J+** de la page du plugin.
Vous aurez donc 9 nouvelles commandes :
- hier
- aujourd'hui
- demain
- après demain
- j+3
- j+4
- j+5
- j+6
- j+7

## ical <-> jeedom
Certaines récurrences n'existent pas dans le plugin Agenda, par exemple :
- mardi et mercredi toutes les 3 semaines

Elles sont alors importées sous forme de dates, calculées sur un an et recalculées à chaque traitement de l'agenda, au plus tard le 1er de chaque mois. Un équipement sans cron doit donc être sauvegardé au moins une fois par an pour que ces dates ne s'épuisent pas.

# <u>JeeMate</u>
- la description et le lieu seront visibles dans l'agenda importé dans JeeMate.

# <u>Attention</u>
- le nom de l'agenda créé est le même que celui de l'équipement + "-ical" (il peut ensuite être renommé)
- la pièce sera identique

# <u>Support</u>
- Community Jeedom
- Discord JeeMate

# <u>Demande d'aide</u>
Afin de me simplifier la tâche lors du débug d'une erreur de conversion, je vous demanderai de créer un agenda de test avec seulement l'événement qui pose problème et de me donner un accès à cet ICAL.


# <u>Remerciements</u>
Le plugin et le support sont gratuits, vous souhaitez néanmoins m'offrir un café ou des couches pour bébé, je vous remercie par avance.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/C1C61AKVV7)