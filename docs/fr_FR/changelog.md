# Changelog plugin import2calendar

>**IMPORTANT**
S'il n'y a pas d'information sur la mise à jour, c'est que celle-ci concerne uniquement de la mise à jour de documentation, de traduction ou de texte.

# 06/10/2026 Beta 1.5.1
- Correction des évènements répétés toutes les deux semaines ou plus : dates décalées, jours manquants, décalage d'une semaine à partir de janvier 2027
- Les modifications d'une récurrence dans l'agenda d'origine sont reportées sur les évènements déjà importés
- La sauvegarde de l'équipement et la commande Rafraichir appliquent aussitôt couleurs, actions et décalages
- Tous les agendas sont retraités après la mise à jour du plugin, puis le 1er de chaque mois
- Un agenda dont l'import échoue est retenté au passage suivant de son cron
- Commandes des jours à venir : correction des évènements mensuels, annuels (anniversaires) et du type « 2e lundi du mois »
- Option « commande jours suivants » : un agenda créé dans le plugin Agenda ne bloque plus la mise à jour des autres
- Noms, notes et lieux complets pour les agendas Outlook et les ical aux lignes longues
- Le plugin ne plante plus quand le plugin Agenda est désactivé ou désinstallé
- Bouton « Mise à jour J+ » : les erreurs sont désormais affichées
- Correction de règles de récurrence invalides qui pouvaient bloquer l'import
- Traductions corrigées et complétées
- Documentation mise à jour

# 27/09/2026 Beta 1.5.0
- Début et fin des évènements personnalisés réglables à la minute (0 à 360 min)
- Actualisation des agendas beaucoup moins gourmande en ressources, surtout pour les agendas volumineux
- Un agenda en erreur ne bloque plus l'actualisation des agendas suivants
- Messages d'erreur plus précis dans les logs
- Correction des notes et lieux perdus lorsqu'ils contiennent une virgule ou un point-virgule
- Correction des récurrences mensuelles du type « 2e lundi » ou « dernier vendredi », absentes des commandes des jours à venir
- Correction de l'arrêt de l'import sur certains évènements récurrents
- Correction d'anciens évènements sans date de fin conservés à tort
- Correction de fuseaux horaires mal reconnus (Australie, Amérique du Sud, Mexique…) qui bloquaient l'import ou décalaient les horaires
- Un fuseau horaire inconnu n'interrompt plus l'import : celui de Jeedom est utilisé à la place
- Correction d'un agenda créé en double à chaque actualisation sur les Jeedom en italien, un message signale les doublons à supprimer

# 25/02/2026 Beta 1.4.8
- Correction bug sur la vérification de la date

# 05/02/2026 Beta 1.4.7
- Autorisation de changer le nom de l'agenda

# 04/10/2025 Stable 1.4.2
- Correction erreur sur double quotes dans le nom de l'évènement

# 16/06/2025 beta 1.4.1
- Correction bug sur Cron

# 23/05/2025 Stable 1.4.0
- Verion Jeedom minimum 4.4
- Version Debian minimum 11

# 07/05/2025 Beta 1.3.6
- Téléchargement du fichier ical avant traitement

# 25/04/2025 Beta 1.3.5
- Ajout d'un timeout pour les requêtes
- Ajout d'un retry pour les requêtes
- Correction calendrier Booking

# 09/04/2025 Beta 1.3.4
- Petites corrections

# 05/04/2025 Beta 1.3.3
- Correction bug ical2calendar sur les évènements j-1 à j+7

# 06/03/2025 Beta 1.2.8
- Correction bug ical2calendar
- Ajout des commandes j-1 ainsi que j+2 à j+7
- Traiter les occurences qui se répète suivant un jour et non suivant une date

# 21/02/2025 Beta 1.2.7
- Correction timezone
- Ajout commande raffraichir

# 21/02/2025 Beta 1.2.6
- Correction exclude date et include date
- Correction cmd aujourd'hui si evenement toutes les 2 semaines

# 11/02/2025 Beta 1.2.5
- Création de commandes "aujourd'hui' et "demain" dans le plugin Agenda pour les évènement de votre ical.
- Correction des dates journée
- Autres petites corrections

# 03/02/2025 Beta & Stable 1.2.0
- Correction si timezone est mal formaté

# 30/01/2025 Beta 1.1.9
- Correction sur evenement recurrent 1 semaine provenant d'un calendrier infomaniak

# 24/01/2025 Stable 1.1.8
- Correction de **others** sur les actions

# 09/01/2025 Beta 1.1.7
- Correction mise à jour calendrier avec date exclus

# 07/01/2024 Beta 1.1.6
- Correction heure de fin journée entière

# 06/11/2024 Stable 1.1.5
- Ajout des vacances scolaires DOM TOM

# 31/10/2024 Stable 1.1.4
- Correction frequence (voir doc)

# 21/10/2024 Stable 1.1.3
- Correction si l'évènement comporte une alarme, le titre et la description étaient modifiés.
- Correction de l'importation des liens webcal.

# 16/10/2024 Stable 1.1.2
- Correction sur la récupération des évènements sur plusieurs années

# 07/10/2024 Stable 1.1.1
- Correction si une virgule est présente dans le nom de l'évènement

# 03/10/2024 Stable 1.1.0
- Correction sur suppression ou déplacement d'evènement présent dans récurrence

# 01/10/2024 Beta 1.0.9
- Corrections de warning PHP
- Ajout numéro de version du plugin
- Correction sur maj de l'évènement

# 17/08/2024 Stable 1.0.8
- Traduction Anglais, Allemand, Espagnol, Italien, Portugais. merci @mips

# 06/05/2024 Stable 1.0.7
- Fix error setTime.

# 06/05/2024 Beta 1.0.6
- Ajout possibilité de forcer heure de début et de fin d'évènement.

# 01/05/2024 Beta 1.0.5
- Prise en compte des dates exclus dans les récurrences
- Prise en compte des dates modifiées dans les récurrences

# 29/04/2024 Stable 1.0.0
- Conversion des émojis en html (visible dans le nom de l'évènement et dans la description (JeeMate v3))
- Conversion des timezones au format Windows (style Romance Standard Time)
- Ajoût d'options pour les actions (all, others, évènement)
- Prise en compte du lieu (visible dans JeeMate v3)
- Possibilité de configurer un début et fin modifié.

# 25/04/2024 Beta 0.8.0
- Suppression des émojis dans le nom de l'évènement (erreur mySQL 22007)

# 25/04/2024 Stable 0.7.0
- Essaie pour remonter sur doc Jeedom

# 01/04/2024 Beta 0.6.0
- Ajout bouton documentation et changelog
- Ajout bouton vers discord (sur le discord JeeMate, 1 salon dédié)

# 30/03/2024 Stable 0.5.0
- première version Stable

# 29/03/2024 Beta 0.5.0
- correction couleur texte input en dark + petite modif visuel
- couleur personnalisée, ne pas tenir compte des majuscules et minuscules
- événements avec occurrence, si le dernier évènement de la série est plus vieux de 3 jours alors la série n'est pas affiché.

# 28/03/2024 Beta 0.4.0
- Correction timezone
- Prise en compte des descriptions (visible dans JeeMate)
- Gestion des récurrences
- Gestion de fin de récurrence, par date ou par nombre de répétition
- Gestion de couleurs spécifique pour certain évènement

# 12/03/2024 Beta 0.3.0
- Correction pour compatibilité Jeedom 4.3
- Couleur text et background défini par default

# 11/03/2024 Beta 0.1.0
- première version Beta