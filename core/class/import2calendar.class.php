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
    $this->setConfiguration('color', '#1212EF');
    $this->setConfiguration('text_color', '#FFFFFF');
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

    $icalConfig = $eqlogic->getConfiguration('ical');
    $file = ($icalConfig != "") ? $icalConfig : $eqlogic->getConfiguration('icalAuto');

    $icon = $eqlogic->getConfiguration('icon');
    $color = $eqlogic->getConfiguration('color');
    $textColor = $eqlogic->getConfiguration('text_color');
    $allCmdStart = $eqlogic->getConfiguration('starts')[0];
    $allCmdEnd = $eqlogic->getConfiguration('ends')[0];
    // création du calendrier si inexistant
    $calendarEqId = self::calendarCreate($eqlogic);
    $icalData = file_get_contents($file);
    $icalEvents = explode("BEGIN:VEVENT", $icalData);
    array_shift($icalEvents);

    $options = [];

    foreach ($icalEvents as $icalEvent) {
      $lines = preg_split('/\r?\n/', $icalEvent);
      log::add(__CLASS__, 'info', "ICAL : " . json_encode($lines));

      $parsedEvent = [];

      foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
          list($key, $value) = explode(':', $line, 2);
          $parsedEvent[$key] = $value;
          // Trouver les clés pour la date de début et de fin
          if (strpos($key, 'DTSTART') !== false) {
            $startDateKey = $key;
          }
          if (strpos($key, 'DTEND') !== false) {
            $endDateKey = $key;
          }
        }
      }

      if (!isset($endDateKey)) {
        $endDateKey = $startDateKey;
      }

      $summary = $parsedEvent["SUMMARY"];
      $startDate = $parsedEvent[$startDateKey];
      $start = self::convertDate($startDate);

      $endDate = $parsedEvent[$endDateKey];
      if ($startDate === $endDate) {
        $end = self::convertDate($endDate, "235900Z");
      } else {
        $end = self::convertDate($endDate);
      }


      $currentTime = time();
      $endTimestamp = strtotime($end);

      if ($endTimestamp > $currentTime) {
        $options[] = [
          "id" => "",
          "eqLogic_id" => $calendarEqId,
          "import2calendar" => $eqlogicId,
          "cmd_param" => [
            "eventName" => $summary ?? "Aucun nom",
            "icon" => $icon,
            "color" => $color,
            "text_color" => $textColor,
            "start" => $allCmdStart,
            "end" => $allCmdEnd,
          ],
          "startDate" => $start,
          "endDate" => $end,
        ];
      }
    }

    self::saveDB($calendarEqId, $options);
    self::cleanDB($calendarEqId, $options);
    $calendarEqlogic = eqLogic::byId($calendarEqId);
    $calendarEqlogic->refreshWidget();
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
          $paramsToCheck = ['start', 'end', 'color', 'icon', 'text_color'];
          $isDifferent = false;
          foreach ($paramsToCheck as $param) {
            if ($option['cmd_param'][$param] != $existingOption['cmd_param'][$param]) {
              $isDifferent = true;
              break;
            }
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
      } else {
        log::add(__CLASS__, 'debug', "Event existant, n'est pas sauvegardé.");
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
      $allCalendar = calendar::byLogicalId('import2calendar', 'calendar', true);
      foreach ($allCalendar as $cal) {
        if ($name . '-ical' === $cal->getname()) {
          $eqExist = TRUE;
          $calendarEqId = $cal->getId();
        }
      }
      // s'il n'exista pas, on le créé
      if (!$eqExist) {
        $calendar = new calendar();
        $calendar->setName(__($name . '-ical', __FILE__));
        $calendar->setIsEnable(1);
        $calendar->setIsVisible(1);
        $calendar->setLogicalId(__('import2calendar', __FILE__));
        $calendar->setEqType_name('calendar');
        $calendar->setObject_id($object);
        $calendar->save();
        $calendarEqId = $calendar->getId();
        log::add(__CLASS__, 'info', 'Conversion du calendrier iCal :b:' . $name . ':/b: dans le plugin Agenda.');
      }

      return $calendarEqId;
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'debug', "Le plugin agenda n'est pas installé ou activé.");
    }
  }


  public static function calendarSave($option)
  {

    if (self::testPlugin()) {
      $event = null;
      if (!empty($option['id'])) {
        $event = calendar_event::byId($option['id']);
      }
      if (!is_object($event)) {
        $event = new calendar_event();
      }
      utils::a2o($event, jeedom::fromHumanReadable($option));

      $event->save();
      log::add(__CLASS__, 'debug', "Event sauvegardé : " . json_encode($option));
    } else {
      message::add(__CLASS__, __("Le plugin agenda n'est pas installé ou activé.", __FILE__), null, null);
      log::add(__CLASS__, 'debug', "Le plugin agenda n'est pas installé ou activé.");
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
      log::add(__CLASS__, 'debug', "Le plugin agenda n'est pas installé ou activé.");
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
      log::add(__CLASS__, 'debug', "Le plugin agenda n'est pas installé ou activé.");
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

  private static function convertDate($date, $defaultTime = "000000Z")
  {
    // Si la date ne comporte pas d'heures, on utilise celle par default
    if (strpos($date, 'T') === false) {
      $date .= 'T' . $defaultTime;
      $date = str_replace('\r', '', $date);
      $timestamp = strtotime($date);
      if ($defaultTime === "000000Z") {
        return date("Y-m-d 00:00:00", $timestamp);
      } else {
        return date("Y-m-d 23:59:00", $timestamp);
      }
    } else {
      $timestamp = strtotime($date);
      return date("Y-m-d H:i:s", $timestamp);
    }

    // Convertir la date au format MySQL
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
