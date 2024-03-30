# Plugin import2calendar

Le plugin sert à importer un calendrier au format Ical dans le plugin Agenda officiel Jeedom (calendar).

**Attention : aucune modification du ical est possible, on récupère les infos du ical pour les envoyer au plugin Agenda de Jeedom. Ne faite aucune modification sur l'agenda créé dans le plugin Agenda, elles seraient supprimée au prochain update de votre ical.**


La configuration de celui-ci est très simple.

# Créer un équipement
Commencer par ajouter un équipement et choisir son nom
### Paramètre d'import
- **ical** : indiquer l'URL du fichier ical à convertir
- **cron** : choisir le temps de rafraîchissement voulu pour le calendrier

### Paramètres d'affichage
- **icône** : l'icône qui sera appliquée à chaque event
- **couleur de fond** : couleur de fond pour chaque event
- **couleur de texte** : couleur de texte pour chaque event

### Couleurs des évènements
Ici vous pouvez choisir de personnaliser la couleur pour certain évènement de votre ical.
### Actions de début et de fin
Pour tous les événements de votre calendrier seront ajoutées les actions définies ici.
Vous pouvez réorganiser les actions en glisser/déposer

**Il n'est pas possible de définir des actions différentes pour chaque événement.**

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

# Attention
- le nom de l'agenda créé est le même que celui de l'équipement + "-ical"
- la pièce sera identique
- Ne jamais modifier l'agenda créé dans le plugin agenda

# Support
- Community Jeedom
- Discord JeeMate