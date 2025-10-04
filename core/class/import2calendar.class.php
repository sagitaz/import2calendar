<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*
* @package   sagitaz/import2calendar
* @author    sagitaz
* @copyright 2024 sagitaz
* @create    2024-03-20
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class import2calendar extends eqLogic
{
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */
  /**
   * Fonction exécutée automatiquement pour mettre à jour les calendriers
   * Parcourt tous les équipements actifs du plugin et vérifie si une mise à jour est nécessaire selon le cron configuré
   *
   * @return void
   */
  public static function update()
  {
		$eqLogics = self::byType(__CLASS__, true);
    foreach ($eqLogics as $eqLogic) {
      $autorefresh = $eqLogic->getConfiguration('autorefresh');
      if ($autorefresh != '') {
        try {
          $c = new Cron\CronExpression(checkAndFixCron($autorefresh), new Cron\FieldFactory);
          if ($c->isDue()) {
            // Mettre à jour les commandes d'agenda pour afficher les événements du jour et du lendemain
            if ($eqLogic->getIsEnable() == 1) {
              $calendarEqId = self::parseIcal($eqLogic->getId());
              //si parseicalr retourne null on quitte la fonction
              if ($calendarEqId != null) {
              $calendar = calendar::byId($calendarEqId);
              self::majCmdsAgenda($calendar);
              } 
            }
          }
        } catch (Exception $exc) {
          log::add(__CLASS__, 'error', $eqLogic->getHumanName() . ' : Invalid cron expression : ' . $autorefresh);
        }
      }
    }
    
  }
  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron5() {}
  */


  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  */

  /**
   * Fonction exécutée automatiquement tous les jours par Jeedom
   * Met à jour les commandes d'agenda pour afficher les événements du jour et du lendemain
   *
   * @return void
   */
  public static function cronDaily()
  {
    // On récupère tous les calendriers créés par le plugin
    $allCalendar = calendar::byLogicalId('import2calendar', 'calendar', true);
    foreach ($allCalendar as $calendar) {
      self::majCmdsAgenda($calendar);
    }
  }

  /**
   * Met à jour les commandes d'agenda pour un calendrier donné
   * Crée et met à jour les commandes pour afficher les événements d'aujourd'hui et de demain
   *
   * @param object $calendar Objet calendrier à mettre à jour
   * @return void
   */
  private static function majCmdsAgenda($calendar)
  {
    // Récupération des informations du calendrier
    $id = $calendar->getid();
    $name = $calendar->getName();
    // Récupération des événements existants dans la base de données
    $inDB = self::calendarGetEventsByEqId($id);
    $eventsYesterday = [];
    $eventsToday = [];
    $eventsTomorrow = [];
    $eventsJ2 = [];
    $eventsJ3 = [];
    $eventsJ4 = [];
    $eventsJ5 = [];
    $eventsJ6 = [];
    $eventsJ7 = [];

    // Créer les objets DateTime pour aujourd'hui et demain
    $now = new DateTime();
    $yesterday = clone $now;
    $today = clone $now;
    $tomorrow = clone $now;
    $j2 = clone $now;
    $j3 = clone $now;
    $j4 = clone $now;
    $j5 = clone $now;
    $j6 = clone $now;
    $j7 = clone $now;

    // Normaliser les dates en conservant le même objet DateTime
    $yesterday->modify('-1 day')->setTime(0, 0);
    $today->setTime(0, 0);
    $tomorrow->modify('+1 day')->setTime(0, 0);
    $j2->modify('+2 days')->setTime(0, 0);
    $j3->modify('+3 days')->setTime(0, 0);
    $j4->modify('+4 days')->setTime(0, 0);
    $j5->modify('+5 days')->setTime(0, 0);
    $j6->modify('+6 days')->setTime(0, 0);
    $j7->modify('+7 days')->setTime(0, 0);

    foreach ($inDB as $event) {
      log::add('import2calendar_checkEvent' . $id, 'info', '╔═══════ Vérification des événements en cours ═══════');
      log::add('import2calendar_checkEvent' . $id, 'info', '║ Nom: ' . $event['cmd_param']['eventName']);
      log::add('import2calendar_checkEvent' . $id, 'info', '║ Répétition activée: ' . $event['repeat']['enable']);
      log::add('import2calendar_checkEvent' . $id, 'info', '║ Jour: ' . $event['repeat']['day']);
      log::add('import2calendar_checkEvent' . $id, 'info', '║ Date de début: ' . $event['startDate']);
      // Vérifier pour hier
      $yesterdayEvents = self::checkEventForDate($event, $yesterday);
      if ($yesterdayEvents !== null) {
        $eventsYesterday = array_merge($eventsYesterday, $yesterdayEvents);
        $eventsYesterday = array_unique($eventsYesterday);
      }

      // Vérifier pour aujourd'hui
      $todayEvents = self::checkEventForDate($event, $today);
      if ($todayEvents !== null) {
        $eventsToday = array_merge($eventsToday, $todayEvents);
        $eventsToday = array_unique($eventsToday);
      }

      // Vérifier pour demain
      $tomorrowEvents = self::checkEventForDate($event, $tomorrow);
      if ($tomorrowEvents !== null) {
        $eventsTomorrow = array_merge($eventsTomorrow, $tomorrowEvents);
        $eventsTomorrow = array_unique($eventsTomorrow);
      }

      // Vérifier pour les jours suivants
      $j2Events = self::checkEventForDate($event, $j2);
      $j3Events = self::checkEventForDate($event, $j3);
      $j4Events = self::checkEventForDate($event, $j4);
      $j5Events = self::checkEventForDate($event, $j5);
      $j6Events = self::checkEventForDate($event, $j6);
      $j7Events = self::checkEventForDate($event, $j7);

      if ($j2Events !== null) {
        $eventsJ2 = array_merge($eventsJ2, $j2Events);
        $eventsJ2 = array_unique($eventsJ2);
      }
      if ($j3Events !== null) {
        $eventsJ3 = array_merge($eventsJ3, $j3Events);
        $eventsJ3 = array_unique($eventsJ3);
      }
      if ($j4Events !== null) {
        $eventsJ4 = array_merge($eventsJ4, $j4Events);
        $eventsJ4 = array_unique($eventsJ4);
      }
      if ($j5Events !== null) {
        $eventsJ5 = array_merge($eventsJ5, $j5Events);
        $eventsJ5 = array_unique($eventsJ5);
      }
      if ($j6Events !== null) {
        $eventsJ6 = array_merge($eventsJ6, $j6Events);
        $eventsJ6 = array_unique($eventsJ6);
      }
      if ($j7Events !== null) {
        $eventsJ7 = array_merge($eventsJ7, $j7Events);
        $eventsJ7 = array_unique($eventsJ7);
      }
    }

    // Créer les commandes pour les événements d'hier
    $cmd = self::createCmd($id, 'yesterday_events', 'Hier');
    $cmd->save();
    if (!empty($eventsYesterday)) {
      $cmd->event(implode(', ', $eventsYesterday));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }

    log::add('import2calendar_checkEvent' . $id, 'info', '╔═══════ Début du bilan ═══════');
    log::add('import2calendar_checkEvent' . $id, 'info', '║ Calendrier : :b:' . $name . ':/b:, Events hier : ' . implode(', ', $eventsYesterday));

    // Créer les commandes pour les événements d'aujourd'hui
    $cmd = self::createCmd($id, 'today_events', 'Aujourd\'hui');
    $cmd->save();

    if (!empty($eventsToday)) {
      $cmd->event(implode(', ', $eventsToday));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_checkEvent' . $id, 'info', '║ Calendrier : :b:' . $name . ':/b:, Events aujourd\'hui : ' . implode(', ', $eventsToday));

    // Créer les commandes pour les événements de demain
    $cmd = self::createCmd($id, 'tomorrow_events', 'Demain');
    $cmd->save();
    if (!empty($eventsTomorrow)) {
      $cmd->event(implode(', ', $eventsTomorrow));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_checkEvent' . $id, 'info', '║ Calendrier : :b:' . $name . ':/b:, Events demain : ' . implode(', ', $eventsTomorrow));

    // Créer les commandes pour les événements des jours suivants
    $cmd = self::createCmd($id, 'j2_events', 'aprés demain');
    $cmd->save();
    if (!empty($eventsJ2)) {
      $cmd->event(implode(', ', $eventsJ2));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_checkEvent' . $id, 'info', '║ Calendrier : :b:' . $name . ':/b:, Events aprés demain : ' . implode(', ', $eventsJ2));

    $cmd = self::createCmd($id, 'j3_events', 'J+3');
    $cmd->save();
    if (!empty($eventsJ3)) {
      $cmd->event(implode(', ', $eventsJ3));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_checkEvent' . $id, 'info', '║ Calendrier : :b:' . $name . ':/b:, Events J+3 : ' . implode(', ', $eventsJ3));

    $cmd = self::createCmd($id, 'j4_events', 'J+4');
    $cmd->save();
    if (!empty($eventsJ4)) {
      $cmd->event(implode(', ', $eventsJ4));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_checkEvent' . $id, 'info', '║ Calendrier : :b:' . $name . ':/b:, Events J+4 : ' . implode(', ', $eventsJ4));

    $cmd = self::createCmd($id, 'j5_events', 'J+5');
    $cmd->save();
    if (!empty($eventsJ5)) {
      $cmd->event(implode(', ', $eventsJ5));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_checkEvent' . $id, 'info', '║ Calendrier : :b:' . $name . ':/b:, Events J+5 : ' . implode(', ', $eventsJ5));

    $cmd = self::createCmd($id, 'j6_events', 'J+6');
    $cmd->save();
    if (!empty($eventsJ6)) {
      $cmd->event(implode(', ', $eventsJ6));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_checkEvent' . $id, 'info', '║ Calendrier : :b:' . $name . ':/b:, Events J+6 : ' . implode(', ', $eventsJ6));

    $cmd = self::createCmd($id, 'j7_events', 'J+7');
    $cmd->save();
    if (!empty($eventsJ7)) {
      $cmd->event(implode(', ', $eventsJ7));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_checkEvent' . $id, 'info', '║ Calendrier : :b:' . $name . ':/b:, Events J+7 : ' . implode(', ', $eventsJ7));
    log::add('import2calendar_checkEvent' . $id, 'info', '╚════════ Fin du bilan ═══════');
  }

  /**
   * Vérifie si un événement est actif pour une date donnée
   *
   * @param array $event L'événement à vérifier
   * @param DateTime $checkDate La date à vérifier
   * @return array|null Liste des événements trouvés ou null si aucun
   */
  private static function checkEventForDate($event, $checkDate)
  {
    // Initialisation des variables
    $eventName = html_entity_decode($event['cmd_param']['eventName'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $startDateTime = new DateTime($event["startDate"]);
    $endDateTime = new DateTime($event["endDate"]);
    $checkDateTime = new DateTime($checkDate->format('Y-m-d'));
    $id = $event['eqLogic_id'];
    // durée de l'événement en jours
    $duration = $endDateTime->diff($startDateTime)->days;
    $isMultiDays = $duration > 0;

    log::add('import2calendar_checkEvent' . $id, 'debug', '╔═════ Vérification événement ═════');
    log::add('import2calendar_checkEvent' . $id, 'debug', '║ Nom: ' . $eventName);
    log::add('import2calendar_checkEvent' . $id, 'debug', '║ Durée: ' . $duration . ' jours');

    // Créer le début et la fin de la journée à vérifier
    $checkDateStart = clone $checkDate;
    $checkDateEnd = clone $checkDate;
    $checkDateStart->setTime(0, 0, 0);
    $checkDateEnd->setTime(23, 59, 59);
    $checkDateStr = $checkDate->format('Y-m-d');

    log::add('import2calendar_checkEvent' . $id, 'debug', '║ Date vérifiée: du ' . $checkDateStart->format('Y-m-d H:i:s') . ' au ' . $checkDateEnd->format('Y-m-d H:i:s'));
    log::add('import2calendar_checkEvent' . $id, 'debug', '║ Plage: du ' . $startDateTime->format('Y-m-d H:i:s') . ' au ' . $endDateTime->format('Y-m-d H:i:s'));

    // 1️⃣ Vérifier les dates incluses/exclues en priorité
    $includedDates = !empty($event["repeat"]["includeDate"]) ? array_map('trim', explode(",", $event["repeat"]["includeDate"])) : [];
    $excludedDates = !empty($event["repeat"]["excludeDate"]) ? array_map('trim', explode(",", $event["repeat"]["excludeDate"])) : [];
    log::add('import2calendar_checkEvent' . $id, 'debug', '║ Dates exclues: ' . json_encode($excludedDates));
    log::add('import2calendar_checkEvent' . $id, 'debug', '║ Dates incluses: ' . json_encode($includedDates));

    if ($isMultiDays) {
      // Pour les événements multi-jours, on vérifie si la période complète est incluse/exclue
      $periodStart = clone $checkDate;
      $periodEnd = clone $checkDate;

      // On recule jusqu'au début de la période
      while ((int)$periodStart->format('N') !== (int)array_search("1", $event["repeat"]["excludeDay"])) {
        $periodStart->modify('-1 day');
      }

      // On avance jusqu'à la fin de la période
      $periodEnd = clone $periodStart;
      $periodEnd->modify('+' . $duration . ' days');

      // Vérifier si une des dates de la période est explicitement incluse
      $currentDate = clone $periodStart;
      while ($currentDate <= $periodEnd) {
        if (in_array($currentDate->format('Y-m-d'), $includedDates)) {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✓ Période explicitement incluse');
          return [$eventName];
        }
        $currentDate->modify('+1 day');
      }

      // Vérifier si une des dates de la période est explicitement exclue
      $currentDate = clone $periodStart;
      while ($currentDate <= $periodEnd) {
        if (in_array($currentDate->format('Y-m-d'), $excludedDates)) {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ Période explicitement exclue');
          log::add('import2calendar_checkEvent' . $id, 'debug', '╠═════ Fin de la vérification ════════════════════');
          return null;
        }
        $currentDate->modify('+1 day');
      }
    } else {
      // Pour les événements d'un seul jour, on vérifie simplement la date
      if (in_array($checkDateStr, $includedDates)) {
        log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✓ Date explicitement incluse');
        return [$eventName];
      }

      if (in_array($checkDateStr, $excludedDates)) {
        log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ Date explicitement exclue');
        log::add('import2calendar_checkEvent' . $id, 'debug', '╠═════ Fin de la vérification ════════════════════');
        return null;
      }
    }

    // 2️⃣ Vérification des événements non récurrents
    if (!$event["repeat"]["enable"]) {
      log::add('import2calendar_checkEvent' . $id, 'debug', '║ Type: Événement non récurrent');
      // L'événement chevauche la période si:
      // - L'événement commence avant la fin de la journée vérifiée ET
      // - L'événement finit après le début de la journée vérifiée
      if ($startDateTime <= $checkDateEnd && $endDateTime >= $checkDateStart) {
        log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✓ Date validée');
        return [$eventName];
      }
      log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ Date hors plage');
      log::add('import2calendar_checkEvent' . $id, 'debug', '╠═════ Fin de la vérification ════════════════════');
      return null;
    } else {
      // 3️⃣ Vérification des événements récurrents
      log::add('import2calendar_checkEvent' . $id, 'debug', '║ Type: Événement récurrent');

      // Vérifier jusqu'à quelle date la récurrence est valide
      if (isset($event["until"]) && !empty($event["until"])) {
        $untilDate = new DateTime($event["until"]);
        if ($checkDateTime > $untilDate) {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ La date ' . $checkDateTime->format('Y-m-d') . ' est après la fin de récurrence (' . $untilDate->format('Y-m-d') . ')');
          return null;
        }
      }


      // Pour les événements multi-jours, calculer l'occurrence
      if ($isMultiDays) {
        // Trouver le début de l'occurrence
        $occurrenceStart = clone $checkDateTime;
        while ((int)$occurrenceStart->format('N') !== (int)array_search("1", $event["repeat"]["excludeDay"])) {
          $occurrenceStart->modify('-1 day');
        }

        // Calculer la fin de l'occurrence
        $occurrenceEnd = clone $occurrenceStart;
        $occurrenceEnd->modify('+' . $duration . ' days');

        // Vérifier si la date est dans la plage de l'occurrence
        if ($checkDateTime < $occurrenceStart || $checkDateTime > $occurrenceEnd) {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ Date ' . $checkDateTime->format('Y-m-d') . ' hors de la période du ' .
            $occurrenceStart->format('Y-m-d') . ' au ' . $occurrenceEnd->format('Y-m-d'));
          return null;
        }
        log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✓ Date dans la période du ' .
          $occurrenceStart->format('Y-m-d') . ' au ' . $occurrenceEnd->format('Y-m-d'));

        // Vérifier la fréquence par rapport au début de l'occurrence
        $difference = 0;
        $difference = self::calculateDateDifference($startDateTime, $occurrenceStart, $event["repeat"]["unite"]);
        if ($difference === -1) {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ La date ne correspond pas au format attendu pour cette unité');
          return null;
        }

        if ($difference % $event["repeat"]["freq"] !== 0) {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ Écart de ' . $difference . ' ' .
            $event["repeat"]["unite"] . ' ne correspond pas à la fréquence de ' .
            $event["repeat"]["freq"] . ' ' . $event["repeat"]["unite"]);
          return null;
        }
        log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✓ Fréquence de répétition validée (' .
          $difference . ' ' . $event["repeat"]["unite"] . ')');


        // Vérifier les semaines paires/impaires
        if ($event["repeat"]["nationalDay"] === "onlyEven" || $event["repeat"]["nationalDay"] === "onlyOdd") {
          $occurrenceWeekNum = (int)$occurrenceStart->format('W');
          $isEvenWeek = $occurrenceWeekNum % 2 === 0;

          $typeRecurrence = ($event["repeat"]["nationalDay"] === "onlyEven") ? "paire" : "impaire";
          if (($event["repeat"]["nationalDay"] === "onlyEven" && !$isEvenWeek) ||
            ($event["repeat"]["nationalDay"] === "onlyOdd" && $isEvenWeek)
          ) {
            log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ La semaine ' . $occurrenceWeekNum . ' est ' .
              ($isEvenWeek ? 'paire' : 'impaire') . ' alors que l\'évènement est prévu les semaines ' . $typeRecurrence . 's');
            return null;
          }
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✓ La semaine ' . $occurrenceWeekNum . ' est bien ' .
            ($isEvenWeek ? 'paire' : 'impaire') . ' comme attendu');
        }

        // Vérifier si la date est dans la plage de l'occurrence
        if ($checkDateTime >= $occurrenceStart && $checkDateTime <= $occurrenceEnd) {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✓ Occurrence validée');
          return [$eventName];
        }

        return null;
      } else {
        // Pour les événements d'un seul jour
        $difference = 0;
        $difference = self::calculateDateDifference($startDateTime, $checkDateTime, $event["repeat"]["unite"]);
        log::add('import2calendar_checkEvent' . $id, 'debug', '║ Différence (' . $difference . ')');
        log::add('import2calendar_checkEvent' . $id, 'debug', '║ Fréquence (' . $event["repeat"]["freq"] . ')');

        if ($difference < 0) {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ La date ne correspond pas au format attendu pour cette unité');
          return null;
        }

        if ($difference % $event["repeat"]["freq"] !== 0) {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ Écart de ' . $difference . ' ' .
            $event["repeat"]["unite"] . ' ne correspond pas à la fréquence de ' .
            $event["repeat"]["freq"] . ' ' . $event["repeat"]["unite"]);
          return null;
        }
        // vérifier que le jour n'est pas exclu
        $dayOfWeek = $checkDateTime->format('N'); // 1 (lundi) à 7 (dimanche)

        if (!isset($event["repeat"]["excludeDay"][$dayOfWeek]) || $event["repeat"]["excludeDay"][$dayOfWeek] != "1") {
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ Jour NON autorisé (' . $dayOfWeek . ')');
          return null;
        }
        // Vérifier les semaines paires/impaires
        if ($event["repeat"]["nationalDay"] === "onlyEven" || $event["repeat"]["nationalDay"] === "onlyOdd") {
          $weekNum = (int)$checkDateTime->format('W');
          $isEvenWeek = $weekNum % 2 === 0;
          $typeRecurrence = ($event["repeat"]["nationalDay"] === "onlyEven") ? "paire" : "impaire";

          if (($event["repeat"]["nationalDay"] === "onlyEven" && !$isEvenWeek) ||
            ($event["repeat"]["nationalDay"] === "onlyOdd" && $isEvenWeek)
          ) {
            log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✗ La semaine ' . $weekNum . ' est ' .
              ($isEvenWeek ? 'paire' : 'impaire') . ' alors que l\'évènement est prévu les semaines ' . $typeRecurrence . 's');
            return null;
          }
          log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✓ La semaine ' . $weekNum . ' est bien ' .
            ($isEvenWeek ? 'paire' : 'impaire') . ' comme attendu');
        }

        log::add('import2calendar_checkEvent' . $id, 'debug', '║ ✓ Occurrence validée (fréquence et semaine)');
        return [$eventName];
      }
    }
  }

  /**
   * Calcule la différence entre deux dates selon l'unité spécifiée
   *
   * @param DateTime $date1 Première date
   * @param DateTime $date2 Seconde date
   * @param string $unit Unité (days, month, years)
   * @return int Différence entre les deux dates dans l'unité spécifiée
   */ private static function calculateDateDifference($date1, $date2, $unit)
  {
    switch ($unit) {
      case 'days':
        // Ignorer l'heure, ne comparer que la date
        $date1Only = clone $date1;
        $date2Only = clone $date2;
        $date1Only->setTime(0, 0, 0);
        $date2Only->setTime(0, 0, 0);
        //     log::add('import2calendar_calculateDateDifference', 'debug', '║ Date 1: ' . $date1Only->format('Y-m-d H:i:s'));
        //     log::add('import2calendar_calculateDateDifference', 'debug', '║ Date 2: ' . $date2Only->format('Y-m-d H:i:s'));
        //    log::add('import2calendar_calculateDateDifference', 'debug', '║ Différence: ' . $date1Only->diff($date2Only)->format('%r%a'));
        return (int)$date1Only->diff($date2Only)->format('%r%a'); // %r pour le signe

      case 'month':
        // Doit tomber le même jour du mois
        if ($date1->format('d') !== $date2->format('d')) {
          return -1;
        }
        return (($date1->format('Y') - $date2->format('Y')) * 12) +
          ($date1->format('n') - $date2->format('n'));

      case 'years':
        // Doit tomber le même jour et le même mois
        if ($date1->format('m-d') !== $date2->format('m-d')) {
          return -1;
        }
        return $date1->format('Y') - $date2->format('Y');

      default:
        return 0;
    }
  }


  /**
   * Crée ou met à jour une commande pour l'équipement
   *
   * @param int $eqLogicId Identifiant de l'équipement
   * @param string $logicalId Identifiant logique de la commande
   * @param string $name Nom de la commande
   * @return cmd Objet commande créé ou mis à jour
   */
  private static function createCmd($eqLogicId, $logicalId, $name)
  {
    $eqLogic = eqLogic::byId($eqLogicId);
    $cmd = $eqLogic->getCmd(null, $logicalId);

    if (!is_object($cmd)) {
      $cmd = new calendarCmd();
      $cmd->setLogicalId($logicalId);
      $cmd->setEqLogic_id($eqLogicId);
      $cmd->setName($name);
      $cmd->setType('info');
      $cmd->setSubType('string');
      $cmd->setIsVisible(0);
      $cmd->setIsHistorized(0);
      $cmd->save();
    }
    return $cmd;
  }
  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */

  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */


  public static function getConfigForCommunity()
  {
    $system = system::getOsVersion();

    $CommunityInfo = "```\n";
    $CommunityInfo = $CommunityInfo . 'Debian : ' . $system . "\n";
    $CommunityInfo = $CommunityInfo . 'Plugin : ' . config::byKey('pluginVersion', 'import2calendar') . "\n";
    $CommunityInfo = $CommunityInfo . "```";
    return $CommunityInfo;
  }


  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert()
  {
    if ($this->getConfiguration('color') === '') {
      $this->setConfiguration('color', '#1212EF');
    }

    if ($this->getConfiguration('text_color') === '') {
      $this->setConfiguration('text_color', '#FFFFFF');
    }
    $this->setIsEnable(1);
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {}

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {}

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate()
  {
    if ($this->getIsEnable() == 0) {
      return;
    }
    $calendarEqId = self::parseIcal($this->getId());
    //si parseicalr retourne null on quitte la fonction
    if ($calendarEqId == null) {
      return;
    }
    $calendar = calendar::byId($calendarEqId);
    self::majCmdsAgenda($calendar);
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {}

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave()
  {
    $cmd = $this->getCmd(null, 'refresh');
    if (!is_object($cmd)) {
      $cmd = new import2calendarCmd();
      $cmd->setLogicalId('refresh');
      $cmd->setName(__('Rafraichir', __FILE__));
      $cmd->setType('action');
      $cmd->setSubType('other');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();
    }
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {}

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove()
  {
    message::add(__CLASS__, __("Vous venez de supprimer l'équipement ical :b:" . $this->getName() . ":/b: ,l'agenda associé n'est pas supprimé dans le plugin agenda(calendar).", __FILE__), null, null);
    log::add(__CLASS__, 'warning', "Vous venez de supprimer l'équipement ical :b:" . $this->getName() . ":/b: ,l'agenda associé n'est pas supprimé dans le plugin agenda(calendar).");
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*     * **********************Getteur Setteur*************************** */
  private static function downloadIcal($url, $destinationPath, $timeout = 30, $retries = 3, $delay = 1)
  {
    $tmpFile = $destinationPath . '.new';

    for ($i = 0; $i < $retries; $i++) {
      $fp = fopen($tmpFile, 'w+');
      if (!$fp) {
        log::add(__CLASS__, 'error', "║ Impossible d’ouvrir le fichier temporaire : $tmpFile");
        return false;
      }

      $ch = curl_init();
      curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'PHP-cURL-ical/1.0'
      ]);

      $start = microtime(true);
      $success = curl_exec($ch);
      $end = microtime(true);

      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlError = curl_error($ch);
      curl_close($ch);
      fclose($fp);

      $duration = number_format($end - $start, 3);

      if ($success && $httpCode >= 200 && $httpCode < 300) {
        $newHash = self::getCleanIcalHash($tmpFile);
        log::add(__CLASS__, 'debug', "║ Fichier iCal temporaire (hash filtré : $newHash)");

        $existingHash = file_exists($destinationPath) ? self::getCleanIcalHash($destinationPath) : null;
        log::add(__CLASS__, 'debug', "║ Fichier iCal existant (hash filtré : $existingHash)");

        if ($newHash === $existingHash) {
          unlink($tmpFile);
          log::add(__CLASS__, 'info', "║ Hash du fichier iCal inchangé, téléchargement ignoré.");
          return null;
        }

        rename($tmpFile, $destinationPath);
        log::add(__CLASS__, 'info', "║ Fichier iCal mis à jour en $duration s (hash modifié : $newHash)");
        return true;
      }

      log::add(__CLASS__, 'warning', sprintf(
        'Échec tentative %d : HTTP %s, cURL : %s, durée : %ss',
        $i + 1,
        $httpCode ?: 'inconnu',
        $curlError ?: 'aucune',
        $duration
      ));

      if ($i < $retries - 1) {
        sleep($delay);
      }
    }

    log::add(__CLASS__, 'error', "║ Échec du téléchargement après $retries tentatives.");
    return false;
  }

 private static function getCleanIcalHash($filePath)
  {
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $filtered = [];

    foreach ($lines as $line) {
      $line = trim($line);

      if (
        stripos($line, 'DTSTAMP:') === 0 ||
        stripos($line, 'PRODID:') === 0 ||
        stripos($line, 'CREATED:') === 0 ||
        stripos($line, 'LAST-MODIFIED:') === 0
      ) {
        continue;
      }

      $filtered[] = $line;
    }

    return sha1(implode("\n", $filtered));
  }

  public static function parseIcal($eqlogicId)
  {
    log::add(__CLASS__, 'debug', '╔════════════ :fg-warning:START PARSE ICAL:/fg:');
    $options = [];
    $events = [];
    $eqlogic = eqLogic::byId($eqlogicId);
    // création du calendrier si inexistant
    $calendarEqId = self::calendarCreate($eqlogic);
    // récupèration des valeurs communes à tous les évènement    
    $icon = $eqlogic->getConfiguration('icon');
    $startTime = $eqlogic->getConfiguration('startTime');
    $endTime = $eqlogic->getConfiguration('endTime');
    // récupèration du fichier ical
    $icalConfig = $eqlogic->getConfiguration('ical');
    $file = ($icalConfig != "") ? $icalConfig : $eqlogic->getConfiguration('icalAuto');
    // Remplacer 'webcal://' par 'https://' si nécessaire
    $file = str_replace('webcal://', 'https://', $file);

    //$localFile = "/tmp/calendar.ics";
    // Chemin du répertoire contenant les fichiers
    $folder = dirname(__FILE__, 3) . '/data/calendar/';
    // Vérification si le répertoire existe 
    if (!is_dir($folder)) {
      log::add(__CLASS__, 'error', 'Le répertoire n\'existe pas : ' . $folder);
      log::add(__CLASS__, 'debug', '╚════════════ :fg-warning:END PARSE ICAL:/fg: ');
      ajax::error('Le répertoire n\'existe pas : ' . $folder);
      return null;
    }
    // $icalData = self::getIcalDataWithCurl($file);
    $localFile = $folder . $eqlogic->getId() . '.ics';
    $icalChanged = self::downloadIcal($file, $localFile);

    if ($icalChanged === false) {
      log::add(__CLASS__, 'error', '║ Impossible de télécharger le fichier iCal => ' . $file);
      log::add(__CLASS__, 'debug', '╚════════════ :fg-warning:END PARSE ICAL:/fg: ');
      return null;
    }

    if ($icalChanged === null) {
      log::add(__CLASS__, 'info', '║ Le fichier iCal n’a pas changé, pas besoin de le parser.');
      log::add(__CLASS__, 'debug', '╚════════════ :fg-warning:END PARSE ICAL:/fg: ');
      return null;
    }

    if ($icalChanged === true) {
      $icalData = file_get_contents($localFile); // Le fichier a été mis à jour, on le lit maintenant en local

      if ($icalData === false) {
        log::add(__CLASS__, 'error', '║ Impossible de lire le fichier ical local => ' . $localFile);
        log::add(__CLASS__, 'debug', '╚════════════ :fg-warning:END PARSE ICAL:/fg: ');
        return null;
      }

      // Parser le fichier uniquement si on a un nouveau contenu
      $events = self::parse_icalendar_file($icalData);
    }

    log::add(__CLASS__, 'debug', '║ EVENTS = ' . json_encode($events));
    $n = 1;
    foreach ($events as $event) {

      if (!is_null($startTime) && $startTime != "") {
        log::add(__CLASS__, 'debug', '║ modification horaire de début d\'èvénement');
        if (isset($event['start_date'])) {
          $startDateTime = new DateTime($event['start_date']);
          $startDateTime->setTime((int)$startTime, 0, 0);
          $event['start_date'] = $startDateTime->format('Y-m-d H:i:s');
        }
      }
      if (!is_null($endTime) && $endTime != "") {
        log::add(__CLASS__, 'debug', '║ modification horaire de fin d\'èvénement');
        if (isset($event['end_date'])) {
          $endDateTime = new DateTime($event['end_date']);
          $endDateTime->setTime((int)$endTime, 0, 0);
          $event['end_date'] = $endDateTime->format('Y-m-d H:i:s');
        }
      }


      log::add(__CLASS__, 'debug', "╠═ Event " . $n . ": " . json_encode($event));
      $color = self::getColors($eqlogicId, $event['summary']);
      $allCmdStart = self::getActionCmd($eqlogicId, $event['summary'], 'starts');
      $allCmdEnd = self::getActionCmd($eqlogicId, $event['summary'], 'ends');
      $startDate = self::changeDate($eqlogicId, $event['summary'], $event['start_date'], "startEvent");
      $endDate = self::changeDate($eqlogicId, $event['summary'], $event['end_date'], "endEvent");
      $repeat = [
        "includeDate" => "",
        "includeDateFromCalendar" => "",
        "excludeDate" => "",
        "excludeDateFromCalendar" => "",
        "enable" => "0",
        "mode" => "simple",
        "positionAt" => "first",
        "day" => "monday",
        "freq" => 0,
        "unite" => "days",
        "excludeDay" => [
          "1" => "1",
          "2" => "1",
          "3" => "1",
          "4" => "1",
          "5" => "1",
          "6" => "1",
          "7" => "1"
        ],
        "nationalDay" => "all"
      ];
      $until = null;
      if (isset($event['rrule']) && !is_null($event['rrule'])) {
        $repeat = self::parseEventRrule($event['rrule'], $event['start_date']);

        // Priorité à UNTIL si présent
        if (isset($event['rrule']['UNTIL']) && !is_null($event['rrule']['UNTIL'])) {
          $until = self::formatDate($event['rrule']['UNTIL']);
        }
        // Si UNTIL n'est pas défini, on vérifie COUNT
        elseif (isset($event['rrule']['COUNT']) && !is_null($event['rrule']['COUNT'])) {
          $until = self::formatCount($event);
        }
      }

      // rechercher si des caractères non voulu existent dans le nom de l'événement
      // les supprimer et ajouter un log si c'est le cas
      $replace = ["\\", '"'];
      $summary = str_replace($replace, "", $event['summary']);
      if ($summary != $event['summary']) {
        log::add(__CLASS__, 'warning', "║ Caractères non autorisés détectés dans le nom de l'événement : " . $event['summary'] . " => renommé en : " . $summary);
      }
      // Nettoyer le nom de l'événement && de la description
      $eventName = self::emojiClean($summary);
      $note = ''; // Valeur par défaut pour éviter les erreurs
      $location = ''; // Valeur par défaut pour éviter les erreurs

      if (isset($event['location']) && !is_null($event['location'])) {
        $location = self::emojiClean($event['location']);
        $location = str_replace("LANGUAGE=fr:", "", $location);
      }

      if (isset($event['description']) && !is_null($event['description'])) {
        $note = self::emojiClean($event['description']);
      }

      // Vérifier la valeur de $until
      if (is_null($until)) {
        $options[] = [
          "id" => "",
          "eqLogic_id" => $calendarEqId,
          "import2calendar" => $eqlogicId,
          "cmd_param" => [
            "eventName" => $eventName['htmlFormat'],
            "eventFullName" => $eventName['htmlFormat'],
            "icon" => $icon,
            "color" => $color["background"],
            "text_color" => $color["texte"],
            "start" => $allCmdStart,
            "end" => $allCmdEnd,
            "transparent" => "0",
            "noDisplayOnDashboard" => "0",
            "note" => isset($note['htmlFormat']) ? $note['htmlFormat'] : '', // Vérification supplémentaire
            "location" => isset($location['htmlFormat']) ? $location['htmlFormat'] : '',
            "uid" => isset($event['uid']) ? $event['uid'] : '',
            "recurrenceId" => isset($event['recurrenceId']) ? $event['recurrenceId'] : '',
            "exdate" => isset($event['exdate']) ? $event['exdate'] : '',
          ],
          "startDate" => $startDate,
          "endDate" => $endDate,
          "until" => $until,
          "repeat" => $repeat
        ];
      } else {
        $withinRetention = self::isWithinRetentionPeriod($until);

        if ($withinRetention) {
          $options[] = [
            "id" => "",
            "eqLogic_id" => $calendarEqId,
            "import2calendar" => $eqlogicId,
            "cmd_param" => [
              "eventName" => $eventName['htmlFormat'],
              "eventFullName" => $eventName['htmlFormat'],
              "icon" => $icon,
              "color" => $color["background"],
              "text_color" => $color["texte"],
              "start" => $allCmdStart,
              "end" => $allCmdEnd,
              "transparent" => "0",
              "noDisplayOnDashboard" => "0",
              "note" => isset($note['htmlFormat']) ? $note['htmlFormat'] : '', // Vérification supplémentaire
              "location" => isset($location['htmlFormat']) ? $location['htmlFormat'] : '',
              "uid" => isset($event['uid']) ? $event['uid'] : '',
              "recurrenceId" => isset($event['recurrenceId']) ? $event['recurrenceId'] : '',
              "exdate" => isset($event['exdate']) ? $event['exdate'] : '',
            ],
            "startDate" => $startDate,
            "endDate" => $endDate,
            "until" => $until,
            "repeat" => $repeat
          ];
        }
      }
      $n++;
    }

    log::add(__CLASS__, 'debug', "║ OPTIONS : " . json_encode($options));
    self::saveDB($calendarEqId, $options);
    self::cleanDB($calendarEqId, $options);
    $calendarEqlogic = eqLogic::byId($calendarEqId);
    $calendarEqlogic->refreshWidget();

    log::add(__CLASS__, 'debug', '╚════════════ :fg-warning:END PARSE ICAL:/fg: ');
    return $calendarEqId;
  }
  private static function parse_icalendar_file($icalFile)
  {
    $events = [];
    $icalFile = str_replace("\r\n ", "", $icalFile);
    $lines = preg_split('/\r?\n/', $icalFile);

    $event = [];
    $description = '';
    $exdates = "";
    $dtStart = "";
    $dtEnd = "";
    $dtEqual = "";
    $formattedDates = [];
    $inAlarm = false;

    // Extraire le PRODID
    preg_match('/PRODID:(.*?)\r?\n/i', $icalFile, $matches);
    $prodId = isset($matches[1]) ? trim($matches[1]) : 'Unknown';
    log::add(__CLASS__, 'debug', '║ PRODID = ' . $prodId);

    $n = 0;

    foreach ($lines as $line) {
      // Ignorer les lignes si on est dans une section VALARM
      if (strpos($line, 'BEGIN:VALARM') === 0) {
        $inAlarm = true;
        continue;
      }
      if (strpos($line, 'END:VALARM') === 0) {
        $inAlarm = false;
        continue;
      }
      if ($inAlarm) {
        continue; // Ignorer cette ligne si on est dans une section VALARM
      }

      if (strpos($line, 'BEGIN:VEVENT') === 0) {
        $event = [];
        $exdates = [];
      } elseif (strpos($line, 'END:VEVENT') === 0) {
        if (!empty($exdates)) {
          $event['exdate'] = $exdates;
        }

        $addEvent = false;
        if (isset($event['rrule'])) {
          $addEvent = true;
        }
        if (isset($event['start_date'])) {
          // Vérifier si la date de début est dans la période de rétention configurée
          $startWithinRetention = self::isWithinRetentionPeriod($event['start_date']);

          // Vérifier si une date de fin est définie pour l'événement
          if (isset($event['end_date'])) {
            // Vérifier si la date de fin est dans la période de rétention configurée
            $endWithinRetention = self::isWithinRetentionPeriod($event['end_date']);
          }

          if ($startWithinRetention || $endWithinRetention || $addEvent) {
            // Vérifier si l'évènement à un nom sinon lui en donner un par default
            if (!isset($event['summary']) || trim($event['summary']) === '') {
              $event['summary'] = "Aucun nom";
            }

            // Vérifier si l'événement a une date de fin ou si la date de fin est identique à la date de début, lui donner une date de fin par défaut
            if (!isset($event['end_date']) || $event['start_date'] === $event['end_date']) {
              $startDateTime = new DateTime($event['start_date']);
              $endDateTime = clone $startDateTime;
              //     $endDateTime->add(new DateInterval('PT30M'));
              $event['end_date'] = $endDateTime->format('Y-m-d 23:59:59');
            }

            // Ajouter l'évenement à la liste
            $events[] = $event;
          }
        }
      } elseif (strpos($line, 'DTSTART') === 0) {
        $dtStart = substr($line, strlen('DTSTART:'));
        //  log::add(__CLASS__, 'debug', "║ Evènement " . $n . ", débute à : " . json_encode($dtStart));

        $n++;
        $event['start_date'] = self::formatDate($dtStart);
        // ajouter gestion des timezones
      } elseif (strpos($line, 'DTEND') === 0) {
        $dtEnd = substr($line, strlen('DTEND:'));
        // log::add(__CLASS__, 'debug', "║ Date END 00 : " . json_encode($dtEnd));
        $dtEqual = ($dtStart === $dtEnd) ? 1 : 0;
        //  log::add(__CLASS__, 'debug', "║ Date Identique : " . json_encode($dtEqual));

        // Vérifier si c'est un événement Airbnb et ajuster la date de fin
        if (stripos($prodId, 'Airbnb') !== false || stripos($prodId, 'booking') !== false) {
          // Nettoyer la date si elle contient VALUE=DATE:
          if (strpos($dtEnd, 'VALUE=DATE:') !== false) {
            $dtEnd = substr($dtEnd, strlen('VALUE=DATE:'));
          }
          $tempDate = new DateTime($dtEnd);
          $dtEnd = $tempDate->format('Y-m-d 23:59:59');
          log::add(__CLASS__, 'debug', "║ Calendrier Airbnb ou Booking détecté - Date de fin ajustée à 23:59:59");
        }

        $event['end_date'] = self::formatDate($dtEnd, 'Y-m-d H:i:s', 1, $dtEqual);
        //  log::add(__CLASS__, 'debug', "║ Date END 01 : " . json_encode($event['end_date']));
        // ajouter gestion des timezones
      } elseif (strpos($line, 'SUMMARY') === 0) {
        $summary = self::translateName(substr($line, strlen('SUMMARY:')));
        // Attribuer le champ 'SUMMARY' ou un nom par défaut si 'SUMMARY' n'est pas trouvé
        $event['summary'] = $summary ? $summary : "Aucun nom";
      } elseif (strpos($line, 'DESCRIPTION') === 0) {
        $description = substr($line, strlen('DESCRIPTION:'));
        $event['description'] = $description;
      } elseif (strpos($line, 'LOCATION') === 0) {
        $location = substr($line, strlen('LOCATION:'));
        $location = str_replace("\,", ",", $location);
        $event['location'] = $location;
      } elseif (strpos($line, 'UID') === 0) {
        $uid = substr($line, strlen('UID:'));
        $event['uid'] = $uid;
      } elseif (strpos($line, 'RECURRENCE-ID') === 0) {
        $recurrenceId = self::formatDate(substr($line, strlen('RECURRENCE-ID:')), 'Y-m-d');
        $event['recurrenceId'] = $recurrenceId;
      } elseif (strpos($line, 'EXDATE') === 0) {
        // Gérer plusieurs lignes d'EXDATE
        $dates = explode(",", substr($line, strlen('EXDATE:')));
        foreach ($dates as $date) {
          $exdates[] = self::formatDate($date, 'Y-m-d');
        }
      } elseif (strpos($line, 'RRULE') === 0) {
        $rrule = substr($line, strlen('RRULE:'));
        $rrule_params = explode(';', $rrule);
        foreach ($rrule_params as $param) {
          list($key, $value) = explode('=', $param);
          $event['rrule'][$key] = $value;
        }
      }
    }

    // log::add(__CLASS__, 'debug', "║ events after parse : " . json_encode($events));
    return $events;
  }

  /**
   * Vérifie si une date donnée est dans la période de rétention configurée par l'utilisateur
   * La période peut être configurée entre 3 et 31 jours. Si non configurée, la valeur par défaut est 3 jours.
   *
   * @param string $date La date à vérifier au format Y-m-d H:i:s
   * @return boolean true si la date est dans la période de rétention, false sinon
   */
  private static function isWithinRetentionPeriod($date)
  {
    // Récupération de la période configurée (entre 3 et 31 jours)
    $numberOfDays = config::byKey('numberOfDays', 'import2calendar');
    if ($numberOfDays == '') {
      $numberOfDays = 3; // Valeur par défaut
      config::save('numberOfDays', 3, 'import2calendar');
    } else if ($numberOfDays < 3) {
      $numberOfDays = 3; // Minimum 3 jours
      config::save('numberOfDays', 3, 'import2calendar');
    } else if ($numberOfDays > 31) {
      $numberOfDays = 31; // Maximum 31 jours
      config::save('numberOfDays', 31, 'import2calendar');
    }

    $currentTime = time();
    $timestamp = strtotime($date);
    $retentionPeriodInSeconds = $numberOfDays * 24 * 60 * 60;

    if (($currentTime - $timestamp) < $retentionPeriodInSeconds) {
      return true;
    }
    return false;
  }

  private static function parseEventRrule($rrule, $startDate)
  {
    if (isset($rrule)) {
      //  log::add(__CLASS__, 'debug', "║ rrule : " . json_encode($rrule));
      $dayOfWeek = strtolower(date('l', strtotime($startDate)));
      // Convertir l'unité de répètition et la fréquence de répètition
      $icalUnit = $rrule['FREQ'];
      $frequence = $rrule['INTERVAL'] ?? 1;
      $nationalDay = "all";
      $includeDate = "";
      $excludeDate = "";
      $enable = 1;
      $byDay = 0;
      $unit = 'days';
      $mode = "simple";
      $position = "first";

      // Si c'est une récurrence mensuelle avec BYDAY spécifiant une position
      if (
        $icalUnit === 'MONTHLY' && isset($rrule['BYDAY']) &&
        preg_match('/^(-?\d+)([A-Z]{2})$/', $rrule['BYDAY'])
      ) {
        $currentDate = new DateTime($startDate);
        $endFiveYears = clone $currentDate;
        $endFiveYears->modify('+5 years');
        $excludePeriods = [];

        // Mapper les jours suivants pour chaque jour
        $nextDayMap = [
          'MO' => 'tuesday',
          'TU' => 'wednesday',
          'WE' => 'thursday',
          'TH' => 'friday',
          'FR' => 'saturday',
          'SA' => 'sunday',
          'SU' => 'monday'
        ];

        // Mapper les jours précédents pour chaque jour
        $previousDayMap = [
          'MO' => 'sunday',
          'TU' => 'monday',
          'WE' => 'tuesday',
          'TH' => 'wednesday',
          'FR' => 'thursday',
          'SA' => 'friday',
          'SU' => 'saturday'
        ];

        do {
          // Extraire la position et le jour de BYDAY
          preg_match('/^(-?\d+)([A-Z]{2})$/', $rrule['BYDAY'], $matches);
          $weekday = $matches[2];

          // Début de la période d'exclusion au jour suivant
          $exclusionStart = clone $currentDate;
          $exclusionStart->modify('next ' . $nextDayMap[$weekday]);

          // Calculer la date de la prochaine occurrence
          $nextOccurrence = clone $currentDate;
          $nextOccurrence->modify('+' . $frequence . ' months');

          // Si la prochaine occurrence dépasse les 5 ans, on arrête
          if ($nextOccurrence > $endFiveYears) {
            break;
          }

          // Fin de la période d'exclusion au jour précédent
          $exclusionEnd = clone $nextOccurrence;
          $exclusionEnd->modify('previous ' . $previousDayMap[$weekday]);

          // Ajouter la période d'exclusion
          $excludePeriods[] = $exclusionStart->format('Y-m-d') . ':' . $exclusionEnd->format('Y-m-d');

          // Passer à la prochaine occurrence
          $currentDate = clone $nextOccurrence;
        } while ($currentDate < $endFiveYears);

        // Joindre les périodes d'exclusion
        $excludeDate = implode(',', $excludePeriods);
      }
      if ($icalUnit === 'DAILY') $unit = 'days';
      elseif ($icalUnit === 'MONTHLY') $unit = 'month';
      elseif ($icalUnit === 'YEARLY') $unit = 'years';
      elseif ($icalUnit === 'WEEKLY') {
        $unit = 'days';
        // si frequence est 1 alors on laisse 1, si frequence est 2 alors on met 1 et on exclue le type de semaine pair ou impair, si 3 alors on multiplis par 7 jours.
        if ($frequence == 2) {
          // Déterminer si la semaine est paire ou impaire
          $weekNumber = date('W', strtotime($startDate));
          $nationalDay = ($weekNumber % 2 == 0) ? "onlyEven" : "onlyOdd";
          $frequence = 1;
        } elseif ($frequence > 2) {
          $frequence = $frequence * 7;
          // lister les jours sur 6 mois
          $includeDate = self::occurrenceMultipleWeek($rrule, $startDate);
          $enable = 0;
        } else {
          $frequence = 1;
          $byDay = isset($rrule['BYDAY']) ? 0 : 1;
        }
      }

      if (isset($rrule['BYDAY']) || $byDay == 1) {
        // Si BYDAY est vide, utiliser le jour de startDate
        if (empty($rrule['BYDAY'])) {
          //   $dayOfWeek = date('D', strtotime($startDate));
          $daysArray = [strtoupper(substr($dayOfWeek, 0, 2))];
          log::add(__CLASS__, 'debug', "║ BYDAY est vide, on défini le jour a celui de startDate : " . json_encode($dayOfWeek));
          //   $rrule['BYDAY'] = $dayOfWeek;
        } else {
          $daysArray = explode(",", $rrule['BYDAY']);
        }
        $excludeDay = [];
        $daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

        foreach ($daysOfWeek as $key => $day) {
          $excludeDay[$key + 1] = (in_array(strtoupper(substr($day, 0, 2)), $daysArray)) ? "1" : "0";
        }

        if (isset($rrule['BYDAY']) && preg_match('/^(-?\d+)([A-Za-z]{2})$/', $rrule['BYDAY'], $matches)) {
          $mode = "advance";
          if ($matches[1] === "-1") {
            $position = "last";
          } elseif ($matches[1] === "1") {
            $position = "first";
          } elseif ($matches[1] === "2") {
            $position = "second";
          } elseif ($matches[1] === "3") {
            $position = "third";
          } elseif ($matches[1] === "4") {
            $position = "fourth";
          }
        }
        // On exclut le jour correspondant à la position spécifiée
        if (isset($matches[2])) {
          $dayIndex = array_search(ucfirst(strtolower($matches[2])), $daysOfWeek);
          if ($dayIndex !== false) {
            $excludeDay[$dayIndex + 1] = "1";
          }
        }
      } else {
        $excludeDay = ["1" => "1", "2" => "1", "3" => "1", "4" => "1", "5" => "1", "6" => "1", "7" => "1"];
      }

      $repeat =  [
        'includeDate' => $includeDate,
        'includeDateFromCalendar' => '',
        'includeDateFromEvent' => '',
        'excludeDate' => $excludeDate,
        'excludeDateFromCalendar' => '',
        'excludeDateFromEvent' => '',
        'enable' => $enable,
        'mode' => $mode,
        'positionAt' => $position,
        'day' => $dayOfWeek,
        'freq' => $frequence,
        'unite' => $unit,
        'excludeDay' => $excludeDay,
        'nationalDay' => $nationalDay,
      ];
    }
    return $repeat;
  }

  // fonctions pour les occurence de plus de 2 semaines
  private static function occurrenceMultipleWeek($rrule, $startDate)
  {

    // Dates de début et de fin sur un an
    $startDate = new DateTime($startDate);
    if (!empty($rrule['UNTIL'])) {
      $endDate = new DateTime($rrule['UNTIL']);
    } else {
      $endDate = (new DateTime('now'))->modify('+1 year');
    }

    // Configuration de l'intervalle et des jours de la semaine
    $intervalWeeks = (int)$rrule['INTERVAL'];
    $daysOfWeek = explode(',', $rrule['BYDAY']);
    $dayMap = [
      "MO" => "Monday",
      "TU" => "Tuesday",
      "WE" => "Wednesday",
      "TH" => "Thursday",
      "FR" => "Friday",
      "SA" => "Saturday",
      "SU" => "Sunday"
    ];

    // Fonction pour générer les occurrences
    $occurrenceDates = [];
    $currentDate = clone $startDate;

    // S'assurer que la partie heure est à 00:00:00 pour éviter les erreurs
    $currentDate->setTime(0, 0);

    while ($currentDate <= $endDate) {
      foreach ($daysOfWeek as $dayCode) {
        $occurrence = (clone $currentDate)->modify($dayMap[$dayCode]);
        if ($occurrence >= $currentDate && $occurrence <= $endDate) {
          $occurrenceDates[] = $occurrence->format('Y-m-d');
        }
      }
      // Passer à la prochaine série de jours dans 7 semaines
      $currentDate->modify("+$intervalWeeks week");
    }
    // Supprimer le premier jour de la liste des occurrences
    array_shift($occurrenceDates);
    // Trier les dates et les convertir en une chaîne de caractères
    sort($occurrenceDates);
    $dates = implode(',', $occurrenceDates);
    // Affichage des résultats
    return $dates;
  }
  private static function formatDate($dateString, $format = 'Y-m-d H:i:s', $end = 0, $dtEqual = 0)
  {
    // remplace 
    $firstDateString = str_replace('"', '', $dateString);
    $dateString = self::convertTimezone($firstDateString);
    if ($firstDateString != $dateString) {
      log::add(__CLASS__, 'debug', "║ Date depart : " . json_encode($firstDateString));
      log::add(__CLASS__, 'debug', "║ Date convertie : " . json_encode($dateString));
    }
    $hasTimeinfo = self::hasTimeInfo($dateString);
    // Extraire le fuseau horaire de la date s'il est présent
    if (strpos($dateString, "TZID=") !== false) {
      $timezone = substr($dateString, strpos($dateString, "=") + 1, strpos($dateString, ":") - strpos($dateString, "=") - 1);
      $dateString = substr($dateString, strpos($dateString, ":") + 1);
      $dateTime = new DateTime($dateString, new DateTimeZone($timezone));
    } elseif (strpos($dateString, "VALUE=DATE:") !== false) {
      // Pour les dates sans indication de fuseau horaire
      $dateString = substr($dateString, strlen("VALUE=DATE:"));
      $dateTime = new DateTime($dateString);
    } else {
      // Pour les dates en format UTC
      $dateTime = new DateTime($dateString);
    }
    // fuseau horaire de Jeedom
    $jeedomTimezone = config::byKey('timezone');
    $dateTime->setTimezone(new DateTimeZone($jeedomTimezone));
    // Formater la date selon le format spécifié
    $date = $dateTime->format($format);
    //   log::add(__CLASS__, 'debug', "║ Date : " . json_encode($date));
    // Vérifier et corriger l'heure de fin
    if (($end == 1) && ($hasTimeinfo == 0) && ($dtEqual == 0)) {
      $dateTime = new DateTime($date);
      // Vérifier si l'heure est minuit (00:00:00)
      if ($dateTime->format('H:i:s') === '00:00:00') {
        $dateTime->modify('-1 minute');
        $date = $dateTime->format($format);
      }
    }
    return $date;
  }

  private static function hasTimeInfo($date)
  {
    // Vérifier si la chaîne contient "T" suivi de chiffres pour heures, minutes, ou secondes
    return preg_match('/T\d{2}/', $date) ? 1 : 0;
  }

  private static function formatCount($event)
  {
    // Date de début de la répétition
    $startDate = new DateTime($event['start_date']);

    // Nombre d'occurrences pour la répétition
    $occurrences = intval($event['rrule']['COUNT']);

    // Calculer la date de fin en ajoutant le nombre d'occurrences à la date de début, en fonction de la fréquence de répétition
    switch ($event['rrule']['FREQ']) {
      case 'DAILY':
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P' . ($occurrences) . 'D'));
        break;
      case 'WEEKLY':
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P' . ($occurrences) . 'W'));
        break;
      case 'MONTHLY':
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P' . ($occurrences) . 'M'));
        break;
      case 'YEARLY':
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P' . ($occurrences) . 'Y'));
        break;
      default:
        $endDate = null;
        break;
    }
    // log::add(__CLASS__, 'debug', "║ Until count : " . json_encode($endDate));
    return $endDate->format("Y-m-d H:i:s");
  }

  public static function cleanDB($calendarEqId, $options)
  {
    $inDB = self::calendarGetEventsByEqId($calendarEqId);
    // Enregistrer les options dans le calendrier
    if (is_array($inDB) && !empty($inDB)) {
      foreach ($inDB as $existingOption) {
        $isPresentInOptions = false;

        // On regarde si l'option existe dans la liste d'options
        if (is_array($options) && !empty($options)) {
          foreach ($options as $option) {
            if (
              $existingOption['cmd_param']['eventName'] == $option['cmd_param']['eventName'] &&
              $existingOption['startDate'] == $option['startDate'] &&
              $existingOption['endDate'] == $option['endDate']
            ) {
              $isPresentInOptions = true;
              break;
            }
          }
        }

        if (!$isPresentInOptions) {
          $eventId = $existingOption['id'];
          log::add(__CLASS__, 'debug', "║ Event : " . $existingOption['id'] . " est supprimé.");
          self::calendarRemove($eventId);
        }
      }
    }
  }
  public static function saveDB($calendarEqId, $options)
  {
    // Vérifier si les options sont un tableau non vide
    if (is_array($options) && !empty($options)) {
      // Récupérer les événements existants pour l'ID de calendrier donné

      foreach ($options as $option) {

        log::add(__CLASS__, 'debug', '╠════════════ :b:START OPTIONS : ' . html_entity_decode($option['cmd_param']['eventName'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . ':/b: ');

        // Gestion des dates d'exclusion (exdate)
        self::handleExdate($option);

        // Gestion des événements récurrents (recurrenceId)
        if (!is_null($option['cmd_param']['recurrenceId'])) {
          self::handleRecurrence($option, $calendarEqId);
        }

        // Comparaison et détection des duplicatas
        $existingEventId = self::isDuplicateEvent($option, $calendarEqId);
        if ($existingEventId === true) {
          log::add(__CLASS__, 'debug', '║ Aucune modification sur les options de cet évènement.');
          log::add(__CLASS__, 'debug', '╠════════════ END OPTIONS ');
          continue; // Sauter cet événement s'il est un duplicata
        } elseif ($existingEventId !== false) {
          // Si une différence est détectée, ajouter l'ID existant à l'option
          $option['id'] = $existingEventId;
        }

        // log::add(__CLASS__, 'debug', '║ OPTIONS ══ ' . json_encode($option));
        // Comparer les dates inclus et exclus
        $cleanOption = self::cleanDate($option);
        log::add(__CLASS__, 'debug', '║ OPTIONS ══ ' . json_encode($cleanOption));
        // Sauvegarder l'événement s'il n'est pas un duplicata
        self::calendarSave($cleanOption);
        log::add(__CLASS__, 'debug', '╠════════════ END OPTIONS ');
      }
    }
  }
  private static function cleanDate($option)
  {
    // Récupérer les listes de dates
    $includeDates = explode(',', $option['repeat']['includeDate']);
    $excludeDates = explode(',', $option['repeat']['excludeDate']);

    // Trouver les dates en double
    $commonDates = array_intersect($includeDates, $excludeDates);

    // Supprimer ces dates des deux tableaux
    $includeDates = array_diff($includeDates, $commonDates);
    $excludeDates = array_diff($excludeDates, $commonDates);

    // Réaffecter les valeurs nettoyées
    $option['repeat']['includeDate'] = implode(',', $includeDates);
    $option['repeat']['excludeDate'] = implode(',', $excludeDates);

    return $option;
  }

  private static function handleExdate(&$option)
  {
    // Si exdate est présent et non nul
    if (!is_null($option['cmd_param']['exdate'])) {
      // S'assurer que exdate est un tableau
      if (!is_array($option['cmd_param']['exdate'])) {
        $option['cmd_param']['exdate'] = [$option['cmd_param']['exdate']]; // Transformer en tableau si nécessaire
      }
      if (!isset($option['repeat'])) {
        $option['repeat'] = [];
      }
      $exdate = implode(',', $option['cmd_param']['exdate']);
      if ($exdate != "") {
        // Mise à jour de la date d'exclusion et suppression d'exdate après traitement
        $option['repeat']['excludeDate'] = $exdate;
        //     log::add(__CLASS__, 'debug', '║ :b:'. $option['cmd_param']['eventName'].':/b: handleExdate 01  : ' . $option['repeat']['excludeDate']);
      }
    }
  }

  private static function handleRecurrence($option, $calendarEqId)
  {
    $uid = $option['cmd_param']['uid'];
    $recurrenceId = $option['cmd_param']['recurrenceId'];
    $inDB = self::calendarGetEventsByEqId($calendarEqId);
    if (is_array($inDB) && !empty($inDB)) {
      foreach ($inDB as &$existingOption) {
        if (
          isset($existingOption['cmd_param']['uid'], $existingOption['repeat']['enable']) &&
          $existingOption['cmd_param']['uid'] == $uid &&
          $existingOption['repeat']['enable'] == 1
        ) {
          if (!empty($recurrenceId)) {
            $oldExcludeDate = $existingOption['repeat']['excludeDate'];

            $existingExclusions = explode(',', $oldExcludeDate);

            if (!in_array($recurrenceId, $existingExclusions)) {

              $existingExclusions[] = $recurrenceId;
            }

            $existingOption['repeat']['excludeDate'] = implode(',', $existingExclusions);

            if ($oldExcludeDate != $existingOption['repeat']['excludeDate']) {
              log::add(__CLASS__, 'debug', '║ Mise à jour des dates exclues sur le calendrier d\'origine avec les dates suivantes : ' . $existingOption['repeat']['excludeDate']);
              self::calendarSave($existingOption);
            } else {
              log::add(__CLASS__, 'debug', '║ Aucun changement sur les dates exclues. ');
            }
          }
        }
      }
    }
  }





  // $existingOption = calendar_event::byId($existingOption['id']);
  private static function isDuplicateEvent(&$option, $calendarEqId)
  {
    $inDB = self::calendarGetEventsByEqId($calendarEqId);
    if (is_array($inDB) && !empty($inDB)) {
      foreach ($inDB as $existingOption) {
        if (
          isset($option['cmd_param'], $existingOption['cmd_param']) &&
          $option['cmd_param']['eventName'] == $existingOption['cmd_param']['eventName'] &&
          $option['startDate'] == $existingOption['startDate'] &&
          $option['endDate'] == $existingOption['endDate']
        ) {
          // Vérifier si un changement existe dans les paramètres de l'événement, y compris 'exdate'
          if (self::isEventDifferent($option, $existingOption)) {
            // Retourner l'ID de l'événement existant s'il y a une différence
            return $existingOption['id'];
          }
          return true; // Duplicata détecté
        }
      }
    }
    return false; // Pas de duplicata
  }

  private static function isEventDifferent($option, $existingOption)
  {

    // Liste des paramètres à vérifier pour détecter les changements dans cmd_param
    $paramsToCheck = ['start', 'end', 'color', 'icon', 'text_color', 'colors', 'note', 'location', 'uid', 'recurrenceId', 'exdate'];
    foreach ($paramsToCheck as $param) {
      // Comparer les valeurs des paramètres si elles existent
      $optionValue = $option['cmd_param'][$param] ?? null;
      $existingOptionValue = $existingOption['cmd_param'][$param] ?? null;

      if ($optionValue != $existingOptionValue) {
        log::add(__CLASS__, 'debug', '║ Différence sur : ' . $param);
        return true; // Différence détectée
      }
    }

    // Liste des paramètres à vérifier pour détecter les changements dans repeat
    $paramsToCheck = ['day', 'excludeDay'];
    foreach ($paramsToCheck as $param) {
      // Comparer les valeurs des paramètres si elles existent
      $optionValue = $option['repeat'][$param] ?? null;
      $existingOptionValue = $existingOption['repeat'][$param] ?? null;

      if ($optionValue != $existingOptionValue) {
        log::add(__CLASS__, 'debug', '║ Différence sur : ' . $param);
        return true; // Différence détectée
      }
    }

    return false; // Aucune différence détectée
  }


  public static function calendarCreate($eqlogic)
  {
    if (self::testPlugin()) {
      $eqExist = FALSE;
      $name = $eqlogic->getName();
      $object = $eqlogic->getObject_id();

      // on vérifie si un calendrier existe déjà dans le plugin Agenda
      $allCalendar = calendar::byLogicalId('ical2calendar', 'calendar', true);
      foreach ($allCalendar as $cal) {
        $cal->setLogicalId(__('import2calendar', __FILE__));
        $cal->save();
      }
      $allCalendar = calendar::byLogicalId('import2calendar', 'calendar', true);
      foreach ($allCalendar as $cal) {
        if ($name . '-ical' === $cal->getname()) {
          $eqExist = TRUE;
          $calendarEqId = $cal->getId();
          $calendar = calendar::byId($calendarEqId);
          log::add(__CLASS__, 'debug', '║ Le calendrier :b:' . $name . '-ical:/b: existe dans le plugin Agenda. Mise à jour des évènements.');
        }
      }
      // s'il n'exista pas, on le créé
      if (!$eqExist) {
        $calendar = new calendar();
        $calendar->setObject_id($object);
        $calendar->setIsEnable(1);
        $calendar->setIsVisible(1);
        $calendar->setLogicalId(__('import2calendar', __FILE__));
        $calendar->setEqType_name('calendar');
        $calendar->setName(__($name . '-ical', __FILE__));
        $calendar->save();
        $calendarEqId = $calendar->getId();
        log::add(__CLASS__, 'info', '║ Conversion du calendrier iCal :b:' . $name . ':/b: dans le plugin Agenda.');
      }


      return $calendarEqId;
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "║ Le plugin agenda n'est pas installé ou activé.");
    }
  }


  public static function calendarSave($option)
  {
    if (self::testPlugin()) {
      $event = null;
      if (!empty($option['id'])) {
        $event = calendar_event::byId($option['id']);
        log::add(__CLASS__, 'debug', "║ Evènement :b:" . $option['cmd_param']['eventName'] . ":/b: (" . $option['id'] . ") mis à jour.");
      }
      if (!is_object($event)) {
        $event = new calendar_event();
        log::add(__CLASS__, 'debug', "║ Evènement :b:" . $option['cmd_param']['eventName'] . ":/b: créé.");
      }
      utils::a2o($event, jeedom::fromHumanReadable($option));

      $event->save();
      return $option;
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "║ Le plugin agenda n'est pas installé ou activé.");
    }
  }

  public static function calendarRemove($id)
  {
    if (self::testPlugin()) {
      log::add("calendar", 'debug', '║ calendar_event::remove ' . $id);

      $event = calendar_event::byId($id);
      if (is_object($event)) {
        $event->remove();
        log::add(__CLASS__, 'debug', "║ Event id : " . $id . ", suppression éffectué.");
      } else {
        log::add(__CLASS__, 'debug', "║ Aucun event ne correspond à l'id : " . $id . ", suppression impossible.");
      }
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "║ Le plugin agenda n'est pas installé ou activé.");
    }
  }

  public static function calendarGetEventsByEqId($calendarEqId)
  {
    $result = [];
    if (self::testPlugin()) {
      $getAllEvents = calendar_event::getEventsByEqLogic($calendarEqId);

      if (count($getAllEvents) <= 0) {
        log::add(__CLASS__, 'debug', "║ Aucun calendrier correspondant à : " . $calendarEqId);
      } else {
        $result = [];
        foreach ($getAllEvents as $event) {
          $result[] = utils::o2a($event);
        }
      }
      return $result;
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "║ Le plugin agenda n'est pas installé ou activé.");
    }
  }

  private static function testPlugin()
  {
    $result = FALSE;
    $test = plugin::byId('calendar');
    if ($test->isActive()) {
      $result = TRUE;
    }
    return $result;
  }

  private static function translateName($name)
  {
    $english = array(
      "Airbnb (Not available)",
      "CLOSED - Not available",
      "Reserved",
      "Full moon",
      "Last quarter",
      "New moon",
      "First quarter",
      "LANGUAGE=fr:",
      "\, "
    );
    $french = array(
      "Airbnb - Non disponible",
      "Booking - Non disponible",
      "Réservé",
      "Pleine lune",
      "Dernier quartier",
      "Nouvelle lune",
      "Premier quartier",
      "",
      ", "
    );

    $result = str_replace($english, $french, $name);

    return $result;
  }
  private static function emojiClean($string)
  {
    // Convertir les séquences d'échappement Unicode en caractères UTF-8
    $string_utf8 = json_decode('"' . $string . '"');

    // Convertir en format HTML
    $result['htmlFormat'] = mb_convert_encoding($string_utf8, 'HTML-ENTITIES', 'UTF-8');

    return $result;
  }
  private static function getColors($eqlogicId, $name)
  {
    $eqlogic = eqLogic::byId($eqlogicId);

    // Définir des couleurs par défaut si aucune couleur n'est trouvée dans la configuration
    $defaultBackground = '#581845';
    $defaultText = '#FFFFFF';

    // Récupérer les couleurs définies dans la configuration, ou utiliser les couleurs par défaut
    $result = [
      "background" => $eqlogic->getConfiguration('color') ?: $defaultBackground,
      "texte" => $eqlogic->getConfiguration('text_color') ?: $defaultText
    ];

    // Récupérer les configurations des couleurs
    $colors = $eqlogic->getConfiguration('colors');
    $nameLower = strtolower($name); // Convertir le nom en minuscule une seule fois

    // Vérifier que colors n'est pas vide et itérer dessus
    if (!empty($colors[0])) {
      foreach ($colors[0] as $color) {
        $colorName = strtolower($color['colorName'] ?? ''); // Gérer les cas où colorName est vide ou absent
        if ($colorName !== '' && strpos($nameLower, $colorName) !== false) {
          // Si une correspondance est trouvée, on met à jour les couleurs et on retourne le résultat
          $result["background"] = $color['colorBackground'] ?: $defaultBackground;
          $result["texte"] = $color['colorText'] ?: $defaultText;
          return $result;
        }
      }
    }

    // Retourner les couleurs (soit par défaut, soit personnalisées)
    return $result;
  }

  private static function getActionCmd($eqlogicId, $name, $type)
  {
    // Récupérer l'objet eqLogic et les actions
    $eqlogic = eqLogic::byId($eqlogicId);
    $actions = $eqlogic->getConfiguration($type)[0];

    $allNames = [];
    $result = [];
    $nameLower = strtolower($name); // Pour éviter de répéter strtolower()

    // Première boucle : On collecte tous les cmdEventName non "all" et "others" en minuscules
    foreach ($actions as $action) {
      $cmdEventName = strtolower($action['cmdEventName'] ?? '');
      if (
        $cmdEventName !== "all" && $cmdEventName !== "others" && $cmdEventName !== ''
      ) {
        $allNames[] = $cmdEventName;
      }
    }

    // Deuxième boucle : On traite chaque action en fonction de cmdEventName
    foreach ($actions as $action) {
      $cmdEventName = strtolower($action['cmdEventName'] ?? '');

      // Si cmdEventName est vide ou égale à "all", on ajoute l'action
      if ($cmdEventName === '' || $cmdEventName === "all") {
        $result[] = $action;
        continue; // On passe à l'action suivante
      }

      // Si cmdEventName est "others" et si name ne contient aucun des noms dans allNames, on ajoute l'action
      if ($cmdEventName === "others") {
        $matchFound = false;
        foreach ($allNames as $allowedName) {
          if (strpos($nameLower, $allowedName) !== false) {
            $matchFound = true;
            break;
          }
        }
        if (!$matchFound) {
          $result[] = $action;
        }
        continue;
      }

      // Si cmdEventName correspond à name, on ajoute l'action
      if (
        $cmdEventName !== '' && strpos($nameLower, $cmdEventName) !== false
      ) {
        $result[] = $action;
      }
    }

    return $result;
  }


  private static function changeDate($eqlogicId, $name, $date, $type)
  {
    // Récupérer l'objet eqLogic
    $eqlogic = eqLogic::byId($eqlogicId);

    // Récupérer les configurations de couleurs
    $colors = $eqlogic->getConfiguration('colors');
    $nameLower = strtolower($name); // Mettre le nom en minuscule une fois pour éviter des appels répétés

    // Vérifier que colors n'est pas vide et itérer dessus
    if (!empty($colors[0])) {
      foreach ($colors[0] as $color) {
        $colorName = strtolower($color['colorName'] ?? ''); // Gérer les cas où colorName est vide ou absent
        if (
          $colorName !== '' && strpos($nameLower, $colorName) !== false
        ) {
          // Vérifier le type d'événement et ajuster la date
          if (
            $type === "startEvent" && !empty($color['startEvent'])
          ) {
            // On modifie la date pour startEvent
            $date = date("Y-m-d H:i:s", strtotime($date) - ($color['startEvent'] * 3600));
          } elseif (
            $type === "endEvent" && !empty($color['endEvent'])
          ) {
            // On modifie la date pour endEvent
            $date = date("Y-m-d H:i:s", strtotime($date) + ($color['endEvent'] * 3600));
          }
          return $date;
        }
      }
    }
    return $date;
  }

  private static function convertTimezone($timezone)
  {
    $timezones = array(
      'Afghanistan Standard Time' => 'Asia/Kabul',
      'Alaskan Standard Time' => 'America/Anchorage',
      'Aleutian Standard Time' => 'America/Adak',
      'Arab Standard Time' => 'Asia/Riyadh',
      'Arabic Standard Time' => 'Asia/Baghdad',
      'Arabian Standard Time' => 'Asia/Dubai',
      'Argentina Standard Time' => 'America/Buenos_Aires',
      'Atlantic Standard Time' => 'America/Halifax',
      'Australia/Darwin' => 'AUS Central Standard Time',
      'Australia/Brisbane' => 'E. Australia Standard Time',
      'Australia/Hobart' => 'Tasmania Standard Time',
      'Australia/Perth' => 'W. Australia Standard Time',
      'Australia/Sydney' => 'AUS Eastern Standard Time',
      'Azerbaijan Standard Time' => 'Asia/Baku',
      'Azores Standard Time' => 'Atlantic/Azores',
      'Bahia Standard Time' => 'America/Bahia',
      'Bangladesh Standard Time' => 'Asia/Dhaka',
      'Belarus Standard Time' => 'Europe/Minsk',
      'Canada Central Standard Time' => 'America/Regina',
      'Cape Verde Standard Time' => 'Atlantic/Cape_Verde',
      'Caucasus Standard Time' => 'Asia/Yerevan',
      'Cen. Australia Standard Time' => 'Australia/Adelaide',
      'Central America Standard Time' => 'America/Guatemala',
      'Central Asia Standard Time' => 'Asia/Almaty',
      'Central Brazilian Standard Time' => 'America/Cuiaba',
      'Central Europe Standard Time' => 'Europe/Budapest',
      'Central Pacific Standard Time' => 'Pacific/Guadalcanal',
      'Central Standard Time' => 'America/Chicago',
      'Central Standard Time (Mexico)' => 'America/Mexico_City',
      'China Standard Time' => 'Asia/Shanghai',
      'Cuba Standard Time' => 'America/Havana',
      'Customized Time Zone' => 'Europe/Paris',
      'Dateline Standard Time' => 'Etc/GMT+12',
      'E. Africa Standard Time' => 'Africa/Nairobi',
      'E. Australia Standard Time' => 'Australia/Brisbane',
      'E. Europe Standard Time' => 'Europe/Chisinau',
      'E. South America Standard Time' => 'America/Sao_Paulo',
      'Eastern Standard Time' => 'America/New_York',
      'Eastern Standard Time (Mexico)' => 'America/Cancun',
      'Easter Island Standard Time' => 'Pacific/Easter',
      'Ekaterinburg Standard Time' => 'Asia/Yekaterinburg',
      'Egypt Standard Time' => 'Africa/Cairo',
      'Fiji Standard Time' => 'Pacific/Fiji',
      'FLE Standard Time' => 'Europe/Kiev',
      'Georgian Standard Time' => 'Asia/Tbilisi',
      'GMT Standard Time' => 'Europe/London',
      'Greenland Standard Time' => 'America/Godthab',
      'Greenwich Standard Time' => 'Atlantic/Reykjavik',
      'GTB Standard Time' => 'Europe/Bucharest',
      'Haiti Standard Time' => 'America/Port-au-Prince',
      'Hawaiian Standard Time' => 'Pacific/Honolulu',
      'India Standard Time' => 'Asia/Calcutta',
      'Iran Standard Time' => 'Asia/Tehran',
      'Israel Standard Time' => 'Asia/Jerusalem',
      'Jordan Standard Time' => 'Asia/Amman',
      'Kamchatka Standard Time' => 'Asia/Kamchatka',
      'Kaliningrad Standard Time' => 'Europe/Kaliningrad',
      'Korea Standard Time' => 'Asia/Seoul',
      'Libya Standard Time' => 'Africa/Tripoli',
      'Magallanes Standard Time' => 'America/Punta_Arenas',
      'Marquesas Standard Time' => 'Pacific/Marquesas',
      'Mauritius Standard Time' => 'Indian/Mauritius',
      'Middle East Standard Time' => 'Asia/Beirut',
      'Montevideo Standard Time' => 'America/Montevideo',
      'Morocco Standard Time' => 'Africa/Casablanca',
      'Myanmar Standard Time' => 'Asia/Rangoon',
      'N. Central Asia Standard Time' => 'Asia/Novosibirsk',
      'Namibia Standard Time' => 'Africa/Windhoek',
      'Nepal Standard Time' => 'Asia/Katmandu',
      'New Zealand Standard Time' => 'Pacific/Auckland',
      'Newfoundland Standard Time' => 'America/St_Johns',
      'North Asia East Standard Time' => 'Asia/Irkutsk',
      'North Asia Standard Time' => 'Asia/Krasnoyarsk',
      'Pacific SA Standard Time' => 'America/Santiago',
      'Pacific Standard Time' => 'America/Los_Angeles',
      'Pacific Standard Time (Mexico)' => 'America/Tijuana',
      'Pakistan Standard Time' => 'Asia/Karachi',
      'Paraguay Standard Time' => 'America/Asuncion',
      'Romance Standard Time' => 'Europe/Paris',
      'Russian Standard Time' => 'Europe/Moscow',
      'SA Eastern Standard Time' => 'America/Cayenne',
      'SA Pacific Standard Time' => 'America/Bogota',
      'SA Western Standard Time' => 'America/La_Paz',
      'Saint Pierre Standard Time' => 'America/Miquelon',
      'Samoa Standard Time' => 'Pacific/Apia',
      'SE Asia Standard Time' => 'Asia/Bangkok',
      'South Africa Standard Time' => 'Africa/Johannesburg',
      'Sri Lanka Standard Time' => 'Asia/Colombo',
      'Sudan Standard Time' => 'Africa/Khartoum',
      'Syria Standard Time' => 'Asia/Damascus',
      'Taipei Standard Time' => 'Asia/Taipei',
      'Tasmania Standard Time' => 'Australia/Hobart',
      'Tocantins Standard Time' => 'America/Araguaina',
      'Tokyo Standard Time' => 'Asia/Tokyo',
      'Turkey Standard Time' => 'Europe/Istanbul',
      'Turks And Caicos Standard Time' => 'America/Grand_Turk',
      'UTC-12' => 'Etc/GMT-12', // Ligne de changement de date
      'UTC-11' => 'Pacific/Midway', // Samoa, Niue
      'UTC-10' => 'Pacific/Honolulu', // Hawaï
      'UTC-09' => 'America/Anchorage', // Alaska
      'UTC-08' => 'America/Los_Angeles', // Pacifique (Californie, Vancouver)
      'UTC-07' => 'America/Denver', // Montagnes Rocheuses (Colorado)
      'UTC-06' => 'America/Chicago', // Centre (Texas, Mexique central)
      'UTC-05' => 'America/New_York', // Est (New York, Québec)
      'UTC-04' => 'America/Santiago', // Chili, Venezuela
      'UTC-03' => 'America/Argentina/Buenos_Aires', // Argentine, Brésil Est
      'UTC-02' => 'Atlantic/South_Georgia', // Atlantique Sud
      'UTC-01' => 'Atlantic/Azores', // Açores
      'UTC+01' => 'Europe/Paris', // France, Allemagne, Espagne
      'UTC+02' => 'Europe/Athens', // Grèce, Roumanie, Égypte
      'UTC+03' => 'Europe/Moscow', // Moscou, Arabie Saoudite
      'UTC+04' => 'Asia/Dubai', // Dubaï, Azerbaïdjan
      'UTC+05' => 'Asia/Karachi', // Pakistan, Ouzbékistan
      'UTC+06' => 'Asia/Dhaka', // Bangladesh, Bhoutan
      'UTC+07' => 'Asia/Bangkok', // Thaïlande, Vietnam
      'UTC+08' => 'Asia/Singapore', // Singapour, Chine, Hong Kong
      'UTC+09' => 'Asia/Tokyo', // Japon, Corée du Sud
      'UTC+10' => 'Australia/Sydney', // Australie (Sydney)
      'UTC+11' => 'Pacific/Noumea', // Nouvelle-Calédonie
      'UTC+12' => 'Pacific/Auckland', // Nouvelle-Zélande, Fidji
      'UTC+13' => 'Pacific/Tongatapu', // Tonga
      'UTC+14' => 'Pacific/Kiritimati', // Îles de la Ligne (Kiribati)
      'UTC'    => 'UTC', // Temps Universel Coordonné
      'Ulaanbaatar Standard Time' => 'Asia/Ulaanbaatar',
      'Venezuela Standard Time' => 'America/Caracas',
      'Vladivostok Standard Time' => 'Asia/Vladivostok',
      'W. Central Africa Standard Time' => 'Africa/Lagos',
      'W. Europe Standard Time' => 'Europe/Berlin',
      'West Asia Standard Time' => 'Asia/Tashkent',
      'West Bank Standard Time' => 'Asia/Hebron',
      'West Pacific Standard Time' => 'Pacific/Port_Moresby',
      'Yakutsk Standard Time' => 'Asia/Yakutsk'
    );
    foreach ($timezones as $key => $value) {
      $timezone = str_replace($key, $value, $timezone);
    }
    return $timezone;
  }

  public static function createEqI2C($options)
  {
    log::add(__CLASS__, 'debug', "║ Création d'un nouveau équipement.");
    $eqExist = FALSE;

    $name = $options['name'];
    $object = $options['roomId'];

    $allCalendar = import2calendar::byLogicalId('ical', 'import2calendar', true);
    foreach ($allCalendar as $cal) {
      if ($name === $cal->getname()) {
        $eqExist = TRUE;
        $calendarEqId = $cal->getId();
        log::add(__CLASS__, 'debug', "║ Equipement agenda mis à jour.");
      }
    }

    $import2calendar = import2calendar::byId($calendarEqId);
    // s'il n'exista pas, on le créé
    if (!$eqExist) {
      $import2calendar = new import2calendar();
      $import2calendar->setName(__($name, __FILE__));
      $import2calendar->setObject_id($object);
      $import2calendar->setLogicalId(__('ical', __FILE__));
      $import2calendar->setEqType_name('import2calendar');
      $import2calendar->setIsVisible(1);
      log::add(__CLASS__, 'debug', "║ Equipement agenda créé");
    }
    $import2calendar->setIsEnable($options['enable']);
    $import2calendar->setConfiguration("ical", $options['icalUrl']);
    $import2calendar->setConfiguration("icalAuto", $options['icalGeneral']);
    $import2calendar->setConfiguration("icon", $options['icon']);
    $import2calendar->setConfiguration("color", $options['backgroundColor']);
    $import2calendar->setConfiguration("text_color", $options['textColor']);
    $import2calendar->setConfiguration("autorefresh", $options['cron']);
    $import2calendar->setConfiguration("starts", [$options['startActions']]);
    $import2calendar->setConfiguration("ends", [$options['endActions']]);
    $import2calendar->save();

    $eqlogicId = $import2calendar->getId();
    $calendarEqId = self::parseIcal($eqlogicId);
    //si parseicalr retourne null on quitte la fonction
    if ($calendarEqId == null) {
      return;
    }

    return $calendarEqId;
  }
  public static function getPluginVersion()
  {
    $pluginVersion = '0.0.0';
    try {
      if (!file_exists(dirname(__FILE__) . '/../../plugin_info/info.json')) {
        log::add('frigate', 'warning', '[Plugin-Version] fichier info.json manquant');
      }
      $data = json_decode(
        file_get_contents(dirname(__FILE__) . '/../../plugin_info/info.json'),
        true
      );
      if (!is_array($data)) {
        log::add('import2calendar', 'warning', '[Plugin-Version] Impossible de décoder le fichier info.json');
      }
      try {
        $pluginVersion = $data['pluginVersion'];
      } catch (\Exception $e) {
        log::add('import2calendar', 'warning', '[Plugin-Version] Impossible de récupérer la version du plugin');
      }
    } catch (\Exception $e) {
      log::add('import2calendar', 'debug', '[Plugin-Version] Get ERROR :: ' . $e->getMessage());
    }
    log::add('import2calendar', 'info', '[Plugin-Version] PluginVersion :: ' . $pluginVersion);
    return $pluginVersion;
  }
}
class import2calendarCmd extends cmd
{
  /*     * *************************Attributs****************************** */


  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS

     */
  public function dontRemoveCmd()
  {
    return true;
  }
  public function execute($_options = array())
  {
    $i2c = $this->getEqLogic();
    if ($this->getLogicalId() == 'refresh') {
      $i2c->save();
    }
  }

  /*     * **********************Getteur Setteur*************************** */
}
