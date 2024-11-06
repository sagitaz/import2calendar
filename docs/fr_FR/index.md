# Plugin import2calendar

Le plugin sert à importer un calendrier au format Ical dans le plugin Agenda officiel Jeedom (calendar).

**Attention : aucune modification du ical n'est possible, on récupère les infos du ical pour les envoyer au plugin Agenda de Jeedom. Ne faite aucune modification sur l'agenda créé dans le plugin Agenda, elles seraient supprimées au prochain update de votre ical.**

La configuration de celui-ci est très simple.

# Créer un équipement
Commencer par ajouter un équipement et choisir son nom
### Paramètre d'import
- **ical** : indiquer l'URL du fichier ical à convertir.
- **heure de début forcées** : choisir une heure de début d'évènement pour tous ceux du calendrier. Par défault ce seront les heures de début de l'évènement enregistré dans l'ical.
- **heure de fin forcées** : choisir une heure de fin d'évènement pour tous ceux du calendrier. Par défault ce seront les heures de fin de l'évènement enregistré dans l'ical.
- **ical auto** : vacances Française et jours fériés. Si sélectionné alors ne rien indiquer dans la zone ical.
- **cron** : choisir le temps de rafraîchissement voulu pour le calendrier.

### Paramètres d'affichage
- **icône** : l'icône qui sera appliquée à chaque event.
- **couleur de fond** : couleur de fond par défault pour chaque event.
- **couleur de texte** : couleur de texte par défault pour chaque event.

### Personnalisation des évènements
Ici vous pouvez choisir de personnaliser certain évènement de votre ical.

- Couleur du fond et couleur du texte.

Ici par default, rien n'est modifié, ces options permettent de modifier l'heure de début et de fin pour par exemple anticipé les actions programmées.
- Heure de début : l'évènement verra son heure de début commencer X h avant.
- Heure de fin : l'évènement verra son heure de fin finir X h après.

### Actions de début et de fin
Pour tous les événements de votre calendrier seront ajoutées les actions définies ici.
Vous pouvez réorganiser les actions en glisser/déposer.


![Configurations des actions](../images/import2calendar-config-actions.png)

Vous pouvez indiquer dans la case **nom**, l'évènement pour lequel l'action est prévu.

**1** et **2** - Laisser vide ou mettre **all** pour que l'action soit ajoutée à tous les évènement de l'agenda.
**4** - Mettre **others** pour que l'action soit ajoutée à tous les évènement de l'agenda sauf ceux pour lesquels une action personnalisée est prévu.
**3** et **5** - Mettre le **nom de l'évènement** pour que l'action ne soit ajoutée que pour eux.


Vous pouvez maintenant cliquer sur **sauvegarder**.
L'agenda correspondant sera créé dans le plugin agenda.

# Édition d'un équipement
Si vous modifiez une des options suivantes :
- icône
- couleur de fond
- couleur de texte
- actions de début
- actions de fin

Les events seront modifiés dans l'agenda (calendar)

# Gestion des événements
A chaque sauvegarde ou à chaque fois que le cron défini parse l'ical alors si un événement n'est plus dans l'ical, il est supprimé de l'agenda.
Les événements passés de plus de 3 jours ne sont pas importés et seront supprimés au fur et à mesure.

# Occurrences
Les règles défini dans votre ical sont convertit au format Jeedom Agenda. Je n'ai pas tester toutes les possibilités, si jamais certaines ne passent pas, merci de joindre la ligne du log import2calendar : **event options** (mettre vos log en warning ou debug).

Les évènements présent dans l'occurence reste visible sur le calendrier tant que l'ocurrence est valide.

Exemple : 1 évènement tous les 5 jours du 01-03-2024 au 24-11-2024. Tous les évènements sont visibles sur le calendrier jusqu'au 27-11-2024 (3 jours après la fin de l'occurence).

## ical <-> jeedom
Pour certaine occurence, il ne sera pas possible de convertir au format Jeedom, il faut donc adapter vos calendrier.
C'est le cas par exemple sur ceci :
- mardi et mercredi toutes les 3 semaines
Pour que cela soit remonté dans jeedom il vous faut créer :
- mardi toute les 3 semaines
- mercredi toutes les 3 semaines

# JeeMate
- la description et le lieu seront visible dans l'agenda importé dans JeeMate.

# Attention
- le nom de l'agenda créé est le même que celui de l'équipement + "-ical"
- la pièce sera identique

# Support
- Community Jeedom
- Discord JeeMate
