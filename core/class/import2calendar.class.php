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

use log;
use calendar;
use calendar_event;
use utils;
use plugin;
use message;

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
  public static function cron10() {}
  */

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

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      return "les infos essentiel de mon plugin";
   }
   */

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
  public function postInsert()
  {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate()
  {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate()
  {
    //  self::parseIcal($this->getId());
    self::parseIcal($this->getId());
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave()
  {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave()
  {
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove()
  {
  }

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
    $eqlogic = eqLogic::byId($eqlogicId);
    // création du calendrier si inexistant
    $calendarEqId = self::calendarCreate($eqlogic);
    // récupèration des valeurs communes à tous les évènement    
    $icon = $eqlogic->getConfiguration('icon');
    // récupèration du fichier ical
    $icalConfig = $eqlogic->getConfiguration('ical');
    $file = ($icalConfig != "") ? $icalConfig : $eqlogic->getConfiguration('icalAuto');
    // Open file in read-only mode and place file pointer at the beginning 
    $fh = fopen($file, 'r');
    // Check if fopen() was successful
    if ($fh === false) {
      log::add(__CLASS__, 'error', 'Impossible d\'ouvrir le fichier ical => ' . $file);
      return null;
    }
    // Read the whole file into a string
    $icalData = stream_get_contents($fh);
    // Check if stream_get_contents() was successful
    if ($icalData === false) {
      log::add(__CLASS__, 'error', 'Impossible de parser le fichier ical => ' . $file);
      return null;
    }

    // Close the file handle
    fclose($fh);

    log::add(__CLASS__, 'debug', '01 => ICAL = ' . json_encode($icalData));
    // parser le fichier ical 
    $events = self::parse_icalendar_file($icalData);

    log::add(__CLASS__, 'debug', '02 => EVENTS = ' . json_encode($events));

    foreach ($events as $event) {
      // Vérifier si le fichier iCal provient d'Airbnb
      if (strpos($icalConfig, 'airbnb') !== false) {
        log::add(__CLASS__, 'debug', '03 => modification horaire AirBnB');
        // Mettre à jour l'heure de début sur 16:00:00
        if (isset($event['start_date'])) {
          $startDateTime = new DateTime($event['start_date']);
          $startDateTime->setTime(16, 0, 0);
          $event['start_date'] = $startDateTime->format('Y-m-d H:i:s');
        }
        // Mettre à jour l'heure de fin sur 10:00:00
        if (isset($event['end_date'])) {
          $endDateTime = new DateTime($event['end_date']);
          $endDateTime->setTime(10, 0, 0);
          $event['end_date'] = $endDateTime->format('Y-m-d H:i:s');
        }
      }

      log::add(__CLASS__, 'warning', "Event options : " . json_encode($event));
      $color = self::getColors($eqlogicId, $event['summary']);
      $allCmdStart = self::getActionCmd($eqlogicId, $event['summary'], 'starts');
      $allCmdEnd = self::getActionCmd($eqlogicId, $event['summary'], 'ends');
      $repeat = null;
      $until = null;
      if (!is_null($event['rrule'])) {
        $repeat = self::parseEventRrule($event['rrule'], $event['start_date']);
        if (!is_null($event['rrule']['UNTIL'])) {
          $until = self::formatDate($event['rrule']['UNTIL']);
        }
        if (!is_null($event['rrule']['COUNT'])) {
          $until = self::formatCount($event);
        }
      }
      // Nettoyer le nom de l'événement && de la description
      $eventName = self::emojiClean($event['summary']);
      $note = self::emojiClean($event['description']);
      // Vérifier la valeur de $until
      if (is_null($until)) {
        $options[] = [
          "id" => "",
          "eqLogic_id" => $calendarEqId,
          "import2calendar" => $eqlogicId,
          "cmd_param" => [
            "eventName" => $eventName['htmlFormat'],
            "icon" => $icon,
            "color" => $color["background"],
            "text_color" => $color["texte"],
            "start" => $allCmdStart,
            "end" => $allCmdEnd,
            "note" => $note['htmlFormat'],
            "location" => $event['location'],
          ],
          "startDate" => $event['start_date'],
          "endDate" => $event['end_date'],
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
              "icon" => $icon,
              "color" => $color["background"],
              "text_color" => $color["texte"],
              "start" => $allCmdStart,
              "end" => $allCmdEnd,
              "note" => $note['htmlFormat'],
              "location" => $event['location'],
            ],
            "startDate" => $event['start_date'],
            "endDate" => $event['end_date'],
            "until" => $until,
            "repeat" => $repeat
          ];
        }
      }
    }

    log::add(__CLASS__, 'debug', "Event options : " . json_encode($options));
    self::saveDB($calendarEqId, $options);
    self::cleanDB($calendarEqId, $options);
    $calendarEqlogic = eqLogic::byId($calendarEqId);
    $calendarEqlogic->refreshWidget();

    return $calendarEqId;
  }
  private static function parse_icalendar_file($icalFile)
  {
    $events = [];
    $lines = preg_split('/\r?\n/', $icalFile);
    $event = [];
    $description = '';
    log::add(__CLASS__, 'debug', "parse_icalendar_file : " . json_encode($lines));
    foreach ($lines as $line) {
      if (strpos($line, 'BEGIN:VEVENT') === 0) {
        $event = [];
      } elseif (strpos($line, 'END:VEVENT') === 0) {
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
            if (!isset($event['summary'])) {
              $event['summary'] = "Aucun nom";
            }
            // Vérifier si l'événement a une date de fin ou si la date de fin est identique à la date de début, lui donner une date de fin par défaut
            if (!isset($event['end_date']) || $event['start_date'] === $event['end_date']) {
              $startDateTime = new DateTime($event['start_date']);
              $endDateTime = clone $startDateTime;
              $endDateTime->add(new DateInterval('PT30M'));
              $event['end_date'] = $endDateTime->format('Y-m-d H:i:s');
            }

            // Ajouter l'évenement à la liste
            $events[] = $event;
          }
        }
      } elseif (strpos($line, 'DTSTART') === 0) {
        $event['start_date'] = self::formatDate(substr($line, strlen('DTSTART:')));
        // ajouter gestion des timezones
      } elseif (strpos($line, 'DTEND') === 0) {
        $event['end_date'] = self::formatDate(substr($line, strlen('DTEND:')));
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
      } elseif (strpos($line, 'RRULE') === 0) {
        $rrule = substr($line, strlen('RRULE:'));
        $rrule_params = explode(';', $rrule);
        foreach ($rrule_params as $param) {
          list($key, $value) = explode('=', $param);
          $event['rrule'][$key] = $value;
        }
      }
    }

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

      $dayOfWeek = strtolower(date('l', strtotime($startDate)));
      // Convertir l'unité de répètition et la fréquence de répètition
      $icalUnit = $rrule['FREQ'];
      $frequence = $rrule['INTERVAL'] ?? 1;
      $unit = 'days';
      $mode = "simple";
      $position = "first";
      if ($icalUnit === 'DAILY') $unit = 'days';
      elseif ($icalUnit === 'MONTHLY') $unit = 'month';
      elseif ($icalUnit === 'YEARLY') $unit = 'years';
      elseif ($icalUnit === 'WEEKLY') {
        $unit = 'days';
        $frequence = 1;
      }

      if (isset($rrule['BYDAY'])) {
        $daysArray = explode(",", $rrule['BYDAY']);
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
        $dayIndex = array_search(ucfirst(strtolower($matches[2])), $daysOfWeek);
        if ($dayIndex !== false) {
          $excludeDay[$dayIndex + 1] = "1";
        }
      } else {
        $excludeDay = ["1" => "1", "2" => "1", "3" => "1", "4" => "1", "5" => "1", "6" => "1", "7" => "1"];
      }

      $repeat =  [
        'includeDate' => '',
        'includeDateFromCalendar' => '',
        'includeDateFromEvent' => '',
        'excludeDate' => '',
        'excludeDateFromCalendar' => '',
        'excludeDateFromEvent' => '',
        'enable' => '1',
        'mode' => $mode,
        'positionAt' => $position,
        'day' => $dayOfWeek,
        'freq' => $frequence,
        'unite' => $unit,
        'excludeDay' => $excludeDay,
        'nationalDay' => 'all',
      ];
    }
    return $repeat;
  }
  private static function formatDate($dateString)
  {
    // remplace 
    $dateString = self::convertTimezone($dateString);
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
    return $dateTime->format("Y-m-d H:i:s");
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
      default:
        $endDate = null;
        break;
    }
    log::add(__CLASS__, 'debug', "Until count : " . json_encode($endDate));
    return $endDate->format("Y-m-d H:i:s");
  }

  public static function cleanDB($calendarEqId, $options)
  {
    $inDB = self::calendarGetEventsByEqId($calendarEqId);
    // Enregistrer les options dans le calendrier
    foreach ($inDB as $existingOption) {
      $isPresentInOptions = false;

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

      if (!$isPresentInOptions) {
        $eventId = $existingOption['id'];
        log::add(__CLASS__, 'debug', "Event : " . $existingOption['id'] . " est supprimé.");
        self::calendarRemove($eventId);
      }
    }
  }
  public static function saveDB($calendarEqId, $options)
  {
    $inDB = self::calendarGetEventsByEqId($calendarEqId);
    // Enregistrer les options dans le calendrier
    foreach ($options as $option) {
      $isDuplicate = false;

      foreach ($inDB as $existingOption) {
        if (
          $option['cmd_param']['eventName'] == $existingOption['cmd_param']['eventName'] &&
          $option['startDate'] == $existingOption['startDate'] &&
          $option['endDate'] == $existingOption['endDate']
        ) {
          // Vérifier si l'un des paramètres (start, end, color, text_color ou icon) est différent
          $paramsToCheck = ['start', 'end', 'color', 'icon', 'text_color', "colors", "note", "location"];
          $isDifferent = false;
          foreach ($paramsToCheck as $param) {
            if ($option['cmd_param'][$param] != $existingOption['cmd_param'][$param]) {
              log::add(__CLASS__, 'debug', "Event existant, changement : " . $param);
              $isDifferent = true;
              break;
            }
          }

          if ($option['until'] != $existingOption['until']) {
            log::add(__CLASS__, 'debug', "Event existant, changement until ");
            $isDifferent = true;
          }

          if ($option['repeat'] != $existingOption['repeat']) {
            log::add(__CLASS__, 'debug', "Event existant, changement repeat");
            $isDifferent = true;
          }

          if ($isDifferent) {
            $option['id'] = $existingOption['id'];
          } else {
            $isDuplicate = true;
          }
          break;
        }
      }

      if (!$isDuplicate) {
        self::calendarSave($option);
        log::add(__CLASS__, 'info', "Event existant, les modifications sont sauvegardées.");
      } else {
        log::add(__CLASS__, 'debug', "Event existant, aucune modification.");
      }
    }
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
          log::add(__CLASS__, 'info', 'Le calendrier :b:' . $name . '-ical:/b: existe dans le plugin Agenda. Mise à jour des évènements.');
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
        log::add(__CLASS__, 'info', 'Conversion du calendrier iCal :b:' . $name . ':/b: dans le plugin Agenda.');
      }


      return $calendarEqId;
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "Le plugin agenda n'est pas installé ou activé.");
    }
  }


  public static function calendarSave($option)
  {

    if (self::testPlugin()) {
      $event = null;
      if (!empty($option['id'])) {
        $event = calendar_event::byId($option['id']);
        log::add(__CLASS__, 'debug', "Evènement mis à jour : " . json_encode($option));
      }
      if (!is_object($event)) {
        $event = new calendar_event();
        log::add(__CLASS__, 'debug', "Evènement créé : " . json_encode($option));
      }
      utils::a2o($event, jeedom::fromHumanReadable($option));

      $event->save();
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "Le plugin agenda n'est pas installé ou activé.");
    }
  }

  public static function calendarUpdate($id, $option)
  {
  }
  public static function calendarRemove($id)
  {
    if (self::testPlugin()) {
      log::add("calendar", 'debug', 'calendar_event::remove ' . $id);

      $event = calendar_event::byId($id);
      if (is_object($event)) {
        $event->remove();
        log::add(__CLASS__, 'debug', "Event id : " . $id . ", suppression éffectué.");
      } else {
        log::add(__CLASS__, 'debug', "Aucun event ne correspond à l'id : " . $id . ", suppression impossible.");
      }
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "Le plugin agenda n'est pas installé ou activé.");
    }
  }

  public static function calendarGetEventsByEqId($calendarEqId)
  {
    if (self::testPlugin()) {
      $getAllEvents = calendar_event::getEventsByEqLogic($calendarEqId);

      if (count($getAllEvents) <= 0) {
        log::add(__CLASS__, 'debug', "Aucun calendrier correspondant à : " . $calendarEqId);
      } else {
        $result = array();
        foreach ($getAllEvents as $event) {
          $result[] = utils::o2a($event);
        }
      }
      return $result;
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'error', "Le plugin agenda n'est pas installé ou activé.");
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
      "Reserved",
      "Full moon",
      "Last quarter",
      "New moon",
      "First quarter",
      "\, "
    );
    $french = array(
      "Non disponible",
      "Réservé",
      "Pleine lune",
      "Dernier quartier",
      "Nouvelle lune",
      "Premier quartier",
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
    $result["background"] = $eqlogic->getConfiguration('color');
    $result["texte"] = $eqlogic->getConfiguration('text_color');
    $colors = $eqlogic->getConfiguration('colors');

    foreach ($colors[0] as $color) {
      if (strpos(strtolower($name), strtolower($color['colorName'])) !== false) {
        $result["background"] = $color['colorBackground'];
        $result["texte"] = $color['colorText'];
        return $result;
      }
    }
    return $result;
  }

  private static function getActionCmd($eqlogicId, $name, $type)
  {
    $eqlogic = eqLogic::byId($eqlogicId);
    $actions = $eqlogic->getConfiguration($type)[0];

    foreach ($actions as $action) {
      if ((strpos(strtolower($name), strtolower($action['cmdEventName'])) !== false) || ($action['cmdEventName'] === "")) {
        $result[] = $action;
      }
    }
    return $result;
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
    log::add(__CLASS__, 'debug', "Création d'un nouveau équipement.");
    $eqExist = FALSE;

    $name = $options['name'];
    $object = $options['roomId'];

    $allCalendar = import2calendar::byLogicalId('ical', 'import2calendar', true);
    foreach ($allCalendar as $cal) {
      if ($name === $cal->getname()) {
        $eqExist = TRUE;
        $calendarEqId = $cal->getId();
        log::add(__CLASS__, 'debug', "Equipement agenda mis à jour.");
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
      log::add(__CLASS__, 'debug', "Equipement agenda créé");
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

  public function execute($_options = array())
  {
  }

  /*     * **********************Getteur Setteur*************************** */
}
