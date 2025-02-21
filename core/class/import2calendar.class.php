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

/*
use calendar;
use calendar_event;
use utils;
use plugin;
use message;
use calendarCmd;
*/

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
  public static function update()
  {
    foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
      $autorefresh = $eqLogic->getConfiguration('autorefresh');
      if ($autorefresh != '') {
        try {
          $c = new Cron\CronExpression(checkAndFixCron($autorefresh), new Cron\FieldFactory);
          if ($c->isDue()) {
            self::parseIcal($eqLogic->getId());
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

  public static function cronDaily()
  {
    // On récupère tous les calendrier créé par le plugin
    $allCalendar = calendar::byLogicalId('import2calendar', 'calendar', true);
    foreach ($allCalendar as $calendar) {
      self::majCmdsAgenda($calendar);
    }
  }

  private static function majCmdsAgenda($calendar)
  {
    $id = $calendar->getid();
    $name = $calendar->getName();
    $inDB = self::calendarGetEventsByEqId($id);
    $eventsToday = [];
    $eventsTomorrow = [];

    // Créer les objets DateTime pour aujourd'hui et demain
    $today = new DateTime();
    $tomorrow = (new DateTime())->modify('+1 day');

    // Normaliser les dates
    $today->setTime(0, 0, 0);
    $tomorrow->setTime(0, 0, 0);

    foreach ($inDB as $event) {
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
    }
    $cmd = self::createCmd($id, 'today_events', 'Aujourd\'hui');
    $cmd->save();

    if (!empty($eventsToday)) {
      $cmd->event(implode(', ', $eventsToday));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_cron', 'debug', 'Calendrier : :b:' . $name . ':/b:, Events aujourd\'hui : ' . implode(', ', $eventsToday));

    $cmd = self::createCmd($id, 'tomorrow_events', 'Demain');
    $cmd->save();
    if (!empty($eventsTomorrow)) {
      $cmd->event(implode(', ', $eventsTomorrow));
      $cmd->save();
    } else {
      $cmd->event('Aucun');
      $cmd->save();
    }
    log::add('import2calendar_cron', 'debug', 'Calendrier : :b:' . $name . ':/b:, Events demain : ' . implode(', ', $eventsTomorrow));
  }
  private static function checkEventForDate($event, DateTime $checkDate)
  {
    $events = [];
    $startDate = new DateTime($event['startDate']);
    $endDate = new DateTime($event['endDate']);

    // Normaliser les dates pour comparer uniquement les dates (sans les heures)
    $startDate->setTime(0, 0, 0);
    $endDate->setTime(23, 59, 59);
    $checkDate->setTime(0, 0, 0);

    // Vérifier si la date n'est pas exclue
    $excludeDates = explode(',', $event['repeat']['excludeDate']);
    $isExcluded = false;
    foreach ($excludeDates as $excludeDate) {
      if (!empty($excludeDate)) {
        $excludeDateTime = new DateTime($excludeDate);
        $excludeDateTime->setTime(0, 0, 0);
        if ($checkDate == $excludeDateTime) {
          $isExcluded = true;
          break;
        }
      }
    }

    if (!$isExcluded) {
      // Vérifier si l'événement est en cours
      if ($checkDate >= $startDate && $checkDate <= $endDate) {
        $events[] = html_entity_decode($event['cmd_param']['eventName'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
      }

      // Vérifier si la date est présente dans include
      $includeDates = explode(',', $event['repeat']['includeDate']);
      foreach ($includeDates as $includeDate) {
        if (!empty($includeDate)) {
          $includeDateTime = new DateTime($includeDate);
          $includeDateTime->setTime(0, 0, 0);
          if ($checkDate == $includeDateTime) {
            $events[] = html_entity_decode($event['cmd_param']['eventName'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
          }
        }
      }

      // Vérifier la récurrence si elle est activée
      if ($event['repeat']['enable'] == 1) {
        $freq = $event['repeat']['freq'];
        $unite = $event['repeat']['unite'];
        $excludeDay = $event['repeat']['excludeDay'];
        $nationalDayEvent = $event['repeat']['nationalDay'];

        // Déterminer si la semaine est paire ou impaire
        $weekNumber = date('W');
        $nationalDay = ($weekNumber % 2 == 0) ? "onlyEven" : "onlyOdd";

        // Vérifier si le jour n'est pas exclu et si la semaine correspond (si définie)
        $currentDayNum = $checkDate->format('N'); // 1 (lundi) à 7 (dimanche)
        if (
          $excludeDay[$currentDayNum] == "1" &&
          ($event['repeat']['nationalDay'] == "" || $event['repeat']['nationalDay'] == $nationalDay)
        ) {
          // Calculer la différence entre la date vérifiée et la date de début
          $interval = $startDate->diff($checkDate);
          $daysDiff = $interval->days;

          // Vérifier si la date correspond à la fréquence
          switch ($unite) {
            case 'days':
              if ($freq > 0 && ($daysDiff % $freq) == 0) {
                $events[] = html_entity_decode($event['cmd_param']['eventName'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
              }
              break;
            case 'month':
              // Si même jour du mois et la différence en mois est multiple de freq
              if ($startDate->format('d') == $checkDate->format('d')) {
                $monthsDiff = ($interval->y * 12) + $interval->m;
                if ($freq > 0 && ($monthsDiff % $freq) == 0) {
                  $events[] = html_entity_decode($event['cmd_param']['eventName'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
              }
              break;
          }
        }
      }
    }

    return !empty($events) ? $events : null;
  }


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
    $calendar = calendar::byId($calendarEqId);
    self::majCmdsAgenda($calendar);
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {}

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {}

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
  public static function parseIcal($eqlogicId)
  {
    log::add(__CLASS__, 'debug', '----------START PARSE ICAL----------');
    $options = [];
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

    $icalData = @file_get_contents($file);

    // Vérifier si file_get_contents a réussi
    if ($icalData === false) {
      log::add(__CLASS__, 'error', '| Impossible de parser le fichier ical => ' . $file);
      return null;
    }

    log::add(__CLASS__, 'debug', '| ICAL = ' . json_encode($icalData));
    // parser le fichier ical 
    $events = self::parse_icalendar_file($icalData);

    //  log::add(__CLASS__, 'debug', '| EVENTS = ' . json_encode($events));
    $n = 1;
    foreach ($events as $event) {

      if (!is_null($startTime) && $startTime != "") {
        log::add(__CLASS__, 'debug', '| modification horaire de début d\'èvénement');
        if (isset($event['start_date'])) {
          $startDateTime = new DateTime($event['start_date']);
          $startDateTime->setTime((int)$startTime, 0, 0);
          $event['start_date'] = $startDateTime->format('Y-m-d H:i:s');
        }
      }
      if (!is_null($endTime) && $endTime != "") {
        log::add(__CLASS__, 'debug', '| modification horaire de fin d\'èvénement');
        if (isset($event['end_date'])) {
          $endDateTime = new DateTime($event['end_date']);
          $endDateTime->setTime((int)$endTime, 0, 0);
          $event['end_date'] = $endDateTime->format('Y-m-d H:i:s');
        }
      }


      log::add(__CLASS__, 'debug', "| Event " . $n . ": " . json_encode($event));
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


      $summary = str_replace("\\", "", $event['summary']);
      // Nettoyer le nom de l'événement && de la description
      $eventName = self::emojiClean($summary);
      $note = ''; // Valeur par défaut pour éviter les erreurs

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
        $threeDaysAgo = self::threeDaysAgo($until);

        if ($threeDaysAgo) {
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

    //  log::add(__CLASS__, 'debug', "| Event options : " . json_encode($options));
    self::saveDB($calendarEqId, $options);
    self::cleanDB($calendarEqId, $options);
    $calendarEqlogic = eqLogic::byId($calendarEqId);
    $calendarEqlogic->refreshWidget();

    log::add(__CLASS__, 'debug', '----------END PARSE ICAL----------');
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
    log::add(__CLASS__, 'debug', '| PRODID = ' . $prodId);

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
          // Vérifier si la date de début est au maximum 3 jours avant aujourd'hui
          $threeDaysAgoStart = self::threeDaysAgo($event['start_date']);

          // Vérifier si une date de fin est définie pour l'événement
          if (isset($event['end_date'])) {
            // Vérifier si la date de fin est au maximum 3 jours avant aujourd'hui
            $threeDaysAgoEnd = self::threeDaysAgo($event['end_date']);
          }

          if ($threeDaysAgoStart || $threeDaysAgoEnd || $addEvent) {
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
        // log::add(__CLASS__, 'debug', "| Date START : " . json_encode($dtStart));
        $event['start_date'] = self::formatDate($dtStart);
        // ajouter gestion des timezones
      } elseif (strpos($line, 'DTEND') === 0) {
        $dtEnd = substr($line, strlen('DTEND:'));
        // log::add(__CLASS__, 'debug', "| Date END 00 : " . json_encode($dtEnd));
        $dtEqual = ($dtStart === $dtEnd) ? 1 : 0;
        //  log::add(__CLASS__, 'debug', "| Date Identique : " . json_encode($dtEqual));

        // Vérifier si c'est un événement Airbnb et ajuster la date de fin
        if (stripos($prodId, 'Airbnb') !== false) {
          // Nettoyer la date si elle contient VALUE=DATE:
          if (strpos($dtEnd, 'VALUE=DATE:') !== false) {
            $dtEnd = substr($dtEnd, strlen('VALUE=DATE:'));
          }
          $tempDate = new DateTime($dtEnd);
          $dtEnd = $tempDate->format('Y-m-d 23:59:59');
          log::add(__CLASS__, 'debug', "| Airbnb détecté - Date de fin ajustée à 23:59:59");
        }

        $event['end_date'] = self::formatDate($dtEnd, 'Y-m-d H:i:s', 1, $dtEqual);
        //  log::add(__CLASS__, 'debug', "| Date END 01 : " . json_encode($event['end_date']));
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

    // log::add(__CLASS__, 'debug', "| events after parse : " . json_encode($events));
    return $events;
  }

  private static function threeDaysAgo($date)
  {
    $numberOfDays = 3;
    $currentTime = time();
    $timestamp = strtotime($date);
    $threeDaysInSeconds = $numberOfDays * 24 * 60 * 60;

    if (($currentTime - $timestamp) < $threeDaysInSeconds) {
      return true;
    }
    return false;
  }

  private static function parseEventRrule($rrule, $startDate)
  {
    if (isset($rrule)) {
      //  log::add(__CLASS__, 'debug', "| rrule : " . json_encode($rrule));
      $dayOfWeek = strtolower(date('l', strtotime($startDate)));
      // Convertir l'unité de répètition et la fréquence de répètition
      $icalUnit = $rrule['FREQ'];
      $frequence = $rrule['INTERVAL'] ?? 1;
      $nationalDay = "all";
      $includeDate = "";
      $enable = 1;
      $byDay = 0;
      $unit = 'days';
      $mode = "simple";
      $position = "first";
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
          log::add(__CLASS__, 'debug', "| BYDAY est vide, on défini le jour a celui de startDate : " . json_encode($dayOfWeek));
          //   $rrule['BYDAY'] = $dayOfWeek;
        } else {
          $daysArray = explode(",", $rrule['BYDAY']);
        }
        $excludeDay = [];
        $daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

        foreach ($daysOfWeek as $key => $day) {
          $excludeDay[$key + 1] = (in_array(strtoupper(substr($day, 0, 2)), $daysArray)) ? "1" : "0";
        }

        if (preg_match('/^(-?\d+)([A-Za-z]{2})$/', $rrule['BYDAY'], $matches)) {
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
        'excludeDate' => '',
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
    //   log::add(__CLASS__, 'debug', "| repeat : " . json_encode($repeat));
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
    $dateString = str_replace('"', '', $dateString);
    $dateString = self::convertTimezone($dateString);
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
    //   log::add(__CLASS__, 'debug', "| Date : " . json_encode($date));
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
    log::add(__CLASS__, 'debug', "| Until count : " . json_encode($endDate));
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
          log::add(__CLASS__, 'debug', "| Event : " . $existingOption['id'] . " est supprimé.");
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

        log::add(__CLASS__, 'debug', '---------- START OPTIONS :b:' . html_entity_decode($option['cmd_param']['eventName'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . ':/b: ---------- ');

        // Gestion des dates d'exclusion (exdate)
        self::handleExdate($option);

        // Gestion des événements récurrents (recurrenceId)
        if (!is_null($option['cmd_param']['recurrenceId'])) {
          self::handleRecurrence($option, $calendarEqId);
        }

        // Comparaison et détection des duplicatas
        $existingEventId = self::isDuplicateEvent($option, $calendarEqId);
        if ($existingEventId === true) {
          log::add(__CLASS__, 'debug', '| Aucune modification sur les options de cet évènement.');
          log::add(__CLASS__, 'debug', '---------- END OPTIONS ---------- ');
          continue; // Sauter cet événement s'il est un duplicata
        } elseif ($existingEventId !== false) {
          // Si une différence est détectée, ajouter l'ID existant à l'option
          $option['id'] = $existingEventId;
        }

        log::add(__CLASS__, 'debug', 'OPTIONS ---------- ' . json_encode($option));
        // Comparer les dates inclus et exclus
        $cleanOption = self::cleanDate($option);
        log::add(__CLASS__, 'debug', 'OPTIONS ---------- ' . json_encode($cleanOption));
        // Sauvegarder l'événement s'il n'est pas un duplicata
        self::calendarSave($cleanOption);
        log::add(__CLASS__, 'debug', '---------- END OPTIONS ---------- ');
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
        //     log::add(__CLASS__, 'debug', '| :b:'. $option['cmd_param']['eventName'].':/b: handleExdate 01  : ' . $option['repeat']['excludeDate']);
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
              log::add(__CLASS__, 'debug', '| Mise à jour des dates exclues sur le calendrier d\'origine avec les dates suivantes : ' . $existingOption['repeat']['excludeDate']);
              self::calendarSave($existingOption);
            } else {
              log::add(__CLASS__, 'debug', '| Aucun changement sur les dates exclues. ');
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
        log::add(__CLASS__, 'debug', '| Différence sur : ' . $param);
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
        log::add(__CLASS__, 'debug', '| Différence sur : ' . $param);
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
          log::add(__CLASS__, 'debug', '| Le calendrier :b:' . $name . '-ical:/b: existe dans le plugin Agenda. Mise à jour des évènements.');
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
        log::add(__CLASS__, 'info', '| Conversion du calendrier iCal :b:' . $name . ':/b: dans le plugin Agenda.');
      }


      return $calendarEqId;
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "| Le plugin agenda n'est pas installé ou activé.");
    }
  }


  public static function calendarSave($option)
  {
    if (self::testPlugin()) {
      $event = null;
      if (!empty($option['id'])) {
        $event = calendar_event::byId($option['id']);
        log::add(__CLASS__, 'debug', "| Evènement :b:" . $option['cmd_param']['eventName'] . ":/b: (" . $option['id'] . ") mis à jour.");
      }
      if (!is_object($event)) {
        $event = new calendar_event();
        log::add(__CLASS__, 'debug', "| Evènement :b:" . $option['cmd_param']['eventName'] . ":/b: créé.");
      }
      utils::a2o($event, jeedom::fromHumanReadable($option));

      $event->save();
      return $option;
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "| Le plugin agenda n'est pas installé ou activé.");
    }
  }

  public static function calendarRemove($id)
  {
    if (self::testPlugin()) {
      log::add("calendar", 'debug', '| calendar_event::remove ' . $id);

      $event = calendar_event::byId($id);
      if (is_object($event)) {
        $event->remove();
        log::add(__CLASS__, 'debug', "| Event id : " . $id . ", suppression éffectué.");
      } else {
        log::add(__CLASS__, 'debug', "| Aucun event ne correspond à l'id : " . $id . ", suppression impossible.");
      }
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "| Le plugin agenda n'est pas installé ou activé.");
    }
  }

  public static function calendarGetEventsByEqId($calendarEqId)
  {
    $result = [];
    if (self::testPlugin()) {
      $getAllEvents = calendar_event::getEventsByEqLogic($calendarEqId);

      if (count($getAllEvents) <= 0) {
        log::add(__CLASS__, 'debug', "| Aucun calendrier correspondant à : " . $calendarEqId);
      } else {
        $result = [];
        foreach ($getAllEvents as $event) {
          $result[] = utils::o2a($event);
        }
      }
      return $result;
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "| Le plugin agenda n'est pas installé ou activé.");
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
      'Dateline Standard Time' => 'Etc/GMT+12',
      'UTC-11' => 'Etc/GMT+11',
      'Aleutian Standard Time' => 'America/Adak',
      'Hawaiian Standard Time' => 'Pacific/Honolulu',
      'Marquesas Standard Time' => 'Pacific/Marquesas',
      'Alaskan Standard Time' => 'America/Anchorage',
      'UTC-09' => 'Etc/GMT+9',
      'Pacific Standard Time (Mexico)' => 'America/Tijuana',
      'UTC-08' => 'Etc/GMT+8',
      'Pacific Standard Time' => 'America/Los_Angeles',
      'US Mountain Standard Time' => 'America/Phoenix',
      'Mountain Standard Time (Mexico)' => 'America/Chihuahua',
      'Mountain Standard Time' => 'America/Denver',
      'Central America Standard Time' => 'America/Guatemala',
      'Central Standard Time' => 'America/Chicago',
      'Easter Island Standard Time' => 'Pacific/Easter',
      'Central Standard Time (Mexico)' => 'America/Mexico_City',
      'Canada Central Standard Time' => 'America/Regina',
      'SA Pacific Standard Time' => 'America/Bogota',
      'Eastern Standard Time (Mexico)' => 'America/Cancun',
      'Eastern Standard Time' => 'America/New_York',
      'Haiti Standard Time' => 'America/Port-au-Prince',
      'Cuba Standard Time' => 'America/Havana',
      'US Eastern Standard Time' => 'America/Indianapolis',
      'Turks And Caicos Standard Time' => 'America/Grand_Turk',
      'Paraguay Standard Time' => 'America/Asuncion',
      'Atlantic Standard Time' => 'America/Halifax',
      'Venezuela Standard Time' => 'America/Caracas',
      'Central Brazilian Standard Time' => 'America/Cuiaba',
      'SA Western Standard Time' => 'America/La_Paz',
      'Pacific SA Standard Time' => 'America/Santiago',
      'Newfoundland Standard Time' => 'America/St_Johns',
      'Tocantins Standard Time' => 'America/Araguaina',
      'E. South America Standard Time' => 'America/Sao_Paulo',
      'SA Eastern Standard Time' => 'America/Cayenne',
      'Argentina Standard Time' => 'America/Buenos_Aires',
      'Greenland Standard Time' => 'America/Godthab',
      'Montevideo Standard Time' => 'America/Montevideo',
      'Magallanes Standard Time' => 'America/Punta_Arenas',
      'Saint Pierre Standard Time' => 'America/Miquelon',
      'Bahia Standard Time' => 'America/Bahia',
      'UTC-02' => 'Etc/GMT+2',
      'Azores Standard Time' => 'Atlantic/Azores',
      'Cape Verde Standard Time' => 'Atlantic/Cape_Verde',
      'UTC' => 'Etc/GMT',
      'GMT Standard Time' => 'Europe/London',
      'Greenwich Standard Time' => 'Atlantic/Reykjavik',
      'W. Europe Standard Time' => 'Europe/Berlin',
      'Central Europe Standard Time' => 'Europe/Budapest',
      'Romance Standard Time' => 'Europe/Paris',
      'Morocco Standard Time' => 'Africa/Casablanca',
      'W. Central Africa Standard Time' => 'Africa/Lagos',
      'Jordan Standard Time' => 'Asia/Amman',
      'GTB Standard Time' => 'Europe/Bucharest',
      'Middle East Standard Time' => 'Asia/Beirut',
      'Egypt Standard Time' => 'Africa/Cairo',
      'E. Europe Standard Time' => 'Europe/Chisinau',
      'Syria Standard Time' => 'Asia/Damascus',
      'West Bank Standard Time' => 'Asia/Hebron',
      'South Africa Standard Time' => 'Africa/Johannesburg',
      'FLE Standard Time' => 'Europe/Kiev',
      'Israel Standard Time' => 'Asia/Jerusalem',
      'Kaliningrad Standard Time' => 'Europe/Kaliningrad',
      'Sudan Standard Time' => 'Africa/Khartoum',
      'Libya Standard Time' => 'Africa/Tripoli',
      'Namibia Standard Time' => 'Africa/Windhoek',
      'Arabic Standard Time' => 'Asia/Baghdad',
      'Turkey Standard Time' => 'Europe/Istanbul',
      'Arab Standard Time' => 'Asia/Riyadh',
      'Belarus Standard Time' => 'Europe/Minsk',
      'Russian Standard Time' => 'Europe/Moscow',
      'E. Africa Standard Time' => 'Africa/Nairobi',
      'Iran Standard Time' => 'Asia/Tehran',
      'Arabian Standard Time' => 'Asia/Dubai',
      'Azerbaijan Standard Time' => 'Asia/Baku',
      'Mauritius Standard Time' => 'Indian/Mauritius',
      'Georgian Standard Time' => 'Asia/Tbilisi',
      'Caucasus Standard Time' => 'Asia/Yerevan',
      'Afghanistan Standard Time' => 'Asia/Kabul',
      'West Asia Standard Time' => 'Asia/Tashkent',
      'Pakistan Standard Time' => 'Asia/Karachi',
      'India Standard Time' => 'Asia/Calcutta',
      'Sri Lanka Standard Time' => 'Asia/Colombo',
      'Nepal Standard Time' => 'Asia/Katmandu',
      'Central Asia Standard Time' => 'Asia/Almaty',
      'Bangladesh Standard Time' => 'Asia/Dhaka',
      'Ekaterinburg Standard Time' => 'Asia/Yekaterinburg',
      'Myanmar Standard Time' => 'Asia/Rangoon',
      'SE Asia Standard Time' => 'Asia/Bangkok',
      'N. Central Asia Standard Time' => 'Asia/Novosibirsk',
      'China Standard Time' => 'Asia/Shanghai',
      'North Asia Standard Time' => 'Asia/Krasnoyarsk',
      'Singapore Standard Time' => 'Asia/Singapore',
      'W. Australia Standard Time' => 'Australia/Perth',
      'Taipei Standard Time' => 'Asia/Taipei',
      'Ulaanbaatar Standard Time' => 'Asia/Ulaanbaatar',
      'North Asia East Standard Time' => 'Asia/Irkutsk',
      'Tokyo Standard Time' => 'Asia/Tokyo',
      'Korea Standard Time' => 'Asia/Seoul',
      'Cen. Australia Standard Time' => 'Australia/Adelaide',
      'AUS Central Standard Time' => 'Australia/Darwin',
      'E. Australia Standard Time' => 'Australia/Brisbane',
      'AUS Eastern Standard Time' => 'Australia/Sydney',
      'West Pacific Standard Time' => 'Pacific/Port_Moresby',
      'Tasmania Standard Time' => 'Australia/Hobart',
      'Yakutsk Standard Time' => 'Asia/Yakutsk',
      'Central Pacific Standard Time' => 'Pacific/Guadalcanal',
      'Vladivostok Standard Time' => 'Asia/Vladivostok',
      'New Zealand Standard Time' => 'Pacific/Auckland',
      'UTC+12' => 'Etc/GMT-12',
      'Fiji Standard Time' => 'Pacific/Fiji',
      'Kamchatka Standard Time' => 'Asia/Kamchatka',
      'Tonga Standard Time' => 'Pacific/Tongatapu',
      'Samoa Standard Time' => 'Pacific/Apia',
    );
    foreach ($timezones as $key => $value) {
      $timezone = str_replace($key, $value, $timezone);
    }
    return $timezone;
  }
  public static function createEqI2C($options)
  {
    log::add(__CLASS__, 'debug', "| Création d'un nouveau équipement.");
    $eqExist = FALSE;

    $name = $options['name'];
    $object = $options['roomId'];

    $allCalendar = import2calendar::byLogicalId('ical', 'import2calendar', true);
    foreach ($allCalendar as $cal) {
      if ($name === $cal->getname()) {
        $eqExist = TRUE;
        $calendarEqId = $cal->getId();
        log::add(__CLASS__, 'debug', "| Equipement agenda mis à jour.");
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
      log::add(__CLASS__, 'debug', "| Equipement agenda créé");
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
      public function dontRemoveCmd() {
      return true;
      }
     */

  public function execute($_options = array()) {}

  /*     * **********************Getteur Setteur*************************** */
}
