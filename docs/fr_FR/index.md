# Plugin import2calendar

Le plugin sert à importer un calendrier au format Ical dans le plugin Agenda officiel Jeedom (calendar).

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
Les événements passés ne sont pas importés et seront supprimés au fur et à mesure
# Attention
- le nom de l'agenda créé est le même que celui de l'équipement + "-ical"
- la pièce sera identique
- Ne jamais modifier l'agenda créé dans le plugin agenda