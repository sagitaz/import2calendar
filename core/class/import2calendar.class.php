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
require_once __DIR__ . '/frigate_events.class.php';

class frigate extends eqLogic
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

  // Fonction exécutée à l'installation du plugin
  public static function setConfig()
  {
    // Configuration par défaut
    if (!config::byKey('URL', 'frigate')) {
      config::save('URL', '', 'frigate');
    }
    if (!config::byKey('port', 'frigate')) {
      config::save('port', '5000', 'frigate');
    }
    if (!config::byKey('recovery_days', 'frigate')) {
      config::save('recovery_days', '7', 'frigate');
    }
    if (!config::byKey('remove_days', 'frigate')) {
      config::save('remove_days', '7', 'frigate');
    }
    if (!config::byKey('datas_weight', 'frigate')) {
      config::save('datas_weight', '500', 'frigate');
    }
    if (!config::byKey('refresh_snapshot', 'frigate')) {
      config::save('refresh_snapshot', '5', 'frigate');
    }
    if (!config::byKey('cron', 'frigate')) {
      config::save('cron', '5', 'frigate');
    }
    if (!config::byKey('cron::run', 'frigate')) {
      config::save('cron::run', 0, 'frigate');
    }
    if (!config::byKey('excludeBackup', 'frigate')) {
      config::save('excludeBackup', 1, 'frigate');
    }
    // seulement si mqtt2 est installé
    if (class_exists('mqtt2')) {
      if (!config::byKey('topic', 'frigate')) {
        config::save('topic', 'frigate', 'frigate');
      }
      if (!config::byKey('presetMax', 'frigate')) {
        config::save('presetMax', '0', 'frigate');
      }
    }
    if (!config::byKey('event::displayVideo', 'frigate')) {
      config::save('event::displayVideo', true, 'frigate');
    }
    if (!config::byKey('event::confirmDelete', 'frigate')) {
      config::save('event::confirmDelete', true, 'frigate');
    }
  }
  // configuration par defaut des crons
  public static function setConfigCron()
  {
    // cron par défaut
    if (!config::byKey('functionality::cron::enable', 'frigate')) {
      config::save('functionality::cron::enable', 0, 'frigate');
    }
    if (!config::byKey('functionality::cron5::enable', 'frigate')) {
      config::save('functionality::cron5::enable', 0, 'frigate');
    }
    if (!config::byKey('functionality::cron10::enable', 'frigate')) {
      config::save('functionality::cron10::enable', 0, 'frigate');
    }
    if (!config::byKey('functionality::cron15::enable', 'frigate')) {
      config::save('functionality::cron15::enable', 0, 'frigate');
    }
    if (!config::byKey('functionality::cron30::enable', 'frigate')) {
      config::save('functionality::cron30::enable', 0, 'frigate');
    }
    if (!config::byKey('functionality::cronHourly::enable', 'frigate')) {
      config::save('functionality::cronHourly::enable', 0, 'frigate');
    }
    if (!config::byKey('functionality::cronDaily::enable', 'frigate')) {
      config::save('functionality::cronDaily::enable', 1, 'frigate');
    }
  }

  private static function execCron($frequence)
  {
    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:START CRON:/fg: ════════════════════════");
    log::add(__CLASS__, 'debug', "║ Exécution du cron : {$frequence}");
    if (config::byKey("cron::run", 'frigate')) {
      log::add(__CLASS__, 'debug', "║ Un cron est deja en cours d'exécution, on n'exécute pas de nouveau.");
      config::save('cron::run', 0, 'frigate');
      log::add(__CLASS__, 'debug', "╚════════════════════════ END CRON ════════════════════════");
      return;
    }
    config::save('cron::run', 1, 'frigate');
    // Si on utilise MQTT2, les crons 1, 5, 10 et 15 ne sont pas utilisés
    if (class_exists('mqtt2')) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['launchable'] === 'ok' && (
        $frequence === "functionality::cron::enable" ||
        $frequence === "functionality::cron5::enable" ||
        $frequence === "functionality::cron10::enable" ||
        $frequence === "functionality::cron15::enable")) {
        log::add(__CLASS__, 'debug', "║ Les crons 1, 5, 10 et 15 sont désactivés avec MQTT et ne sont pas utilisés.");
        config::save('cron::run', 0, 'frigate');
        log::add(__CLASS__, 'debug', "╚════════════════════════ END CRON ═══════════════════");
        return;
      }
    }

    // Exécution des autres fréquences et nettoyage
    self::cleanFolderData();
    self::cleanAllOldestFiles();

    // Si la fréquence n'est pas parmi les crons désactivés, exécuter cleanByType
    if (!($frequence === "functionality::cron::enable" ||
      $frequence === "functionality::cron5::enable" ||
      $frequence === "functionality::cron10::enable" ||
      $frequence === "functionality::cron15::enable")) {
      self::cleanByType();
      self::cleanByType("update");
    }

    // Exécution des actions si Frigate est disponible
    $frigate = frigate::byLogicalId('eqFrigateEvents', 'frigate');
    if (!empty($frigate) && config::byKey($frequence, 'frigate', 0) == 1) {
      $cmd = $frigate->getCmd(null, 'info_Cron');
      $execute = "1";
      // Vérification si la commande existe
      if (is_object($cmd)) {
        $execute = $cmd->execCmd();
      }

      if ($execute == "1") {
        self::getEvents();
        self::getStats();
      }
    }

    config::save('cron::run', 0, 'frigate');
    log::add(__CLASS__, 'debug', "╚════════════════════════ END CRON ═══════════════════");
  }


  private static function isFrigateServerAvailable()
  {
    $urlFrigate = self::getUrlFrigate();
    if ($urlFrigate === false) {
      return false;
    }

    $ch = curl_init("http://" . $urlFrigate . "/api/version");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300 && !empty($result);
  }

  // Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron()
  {
    if (!self::isFrigateServerAvailable()) {
      log::add(__CLASS__, 'warning', "Le serveur Frigate n'est pas disponible. Cron non exécuté.");
      return;
    }
    self::checkFrigateStatus();
    self::execCron('functionality::cron::enable');
  }
  // Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5()
  {
    if (!self::isFrigateServerAvailable()) {
      log::add(__CLASS__, 'warning', "Le serveur Frigate n'est pas disponible. Cron5 non exécuté.");
      return;
    }
    self::checkFrigateStatus();
    self::execCron('functionality::cron5::enable');
  }
  // Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10()
  {
    if (!self::isFrigateServerAvailable()) {
      log::add(__CLASS__, 'warning', "Le serveur Frigate n'est pas disponible. Cron10 non exécuté.");
      return;
    }
    self::checkFrigateStatus();
    self::execCron('functionality::cron10::enable');
  }
  // Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15()
  {
    if (!self::isFrigateServerAvailable()) {
      log::add(__CLASS__, 'warning', "Le serveur Frigate n'est pas disponible. Cron15 non exécuté.");
      return;
    }
    self::checkFrigateStatus();
    self::execCron('functionality::cron15::enable');
  }
  // Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30()
  {
    if (!self::isFrigateServerAvailable()) {
      log::add(__CLASS__, 'warning', "Le serveur Frigate n'est pas disponible. Cron30 non exécuté.");
      return;
    }
    self::checkFrigateStatus();
    self::execCron('functionality::cron30::enable');
  }
  // Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly()
  {
    if (!self::isFrigateServerAvailable()) {
      log::add(__CLASS__, 'warning', "Le serveur Frigate n'est pas disponible. CronHourly non exécuté.");
      return;
    }
    self::checkFrigateStatus();
    self::execCron('functionality::cronHourly::enable');
  }
  // Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily()
  {
    if (!self::isFrigateServerAvailable()) {
      log::add(__CLASS__, 'warning', "Le serveur Frigate n'est pas disponible. CronDaily non exécuté.");
      return;
    }
    self::checkFrigateStatus();
    self::checkFrigateVersion();
    self::execCron('functionality::cronDaily::enable');
  }


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


  // Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
  // en lors de la création semi-automatique d'un post sur le forum community
  public static function getConfigForCommunity()
  {
    $system = system::getOsVersion();

    $CommunityInfo = "```\n";
    $CommunityInfo = $CommunityInfo . 'URL : ' . self::getUrlFrigate() . "\n";
    $CommunityInfo = $CommunityInfo . 'MQTT topic : ' . config::byKey('topic', 'frigate') . "\n";
    $CommunityInfo = $CommunityInfo . 'Debian : ' . $system . "\n";
    $CommunityInfo = $CommunityInfo . 'Frigate : ' . config::byKey('frigate_version', 'frigate') . "\n";
    $CommunityInfo = $CommunityInfo . 'Plugin : ' . config::byKey('pluginVersion', 'frigate') . "\n";
    $CommunityInfo = $CommunityInfo . "```";
    return $CommunityInfo;
  }


  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {}

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {}

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {}

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {}

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave()
  {
    $url = config::byKey('URL', 'frigate');
    // on nettoie l'url si elle contient http:// ou https://
    $url = preg_replace('#^https?://#', '', $url);
    config::save('URL', $url, 'frigate');
    $port = config::byKey('port', 'frigate');


    if ($this->getLogicalId() != 'eqFrigateStats' || $this->getLogicalId() != 'eqFrigateEvents') {
      if ($this->getConfiguration('ptz') == '') {
        $this->setConfiguration('ptz', '0');
      }
      // on verifie le preset et on le save
      $preset = ($this->getConfiguration('presetMax') <= "10") ? $this->getConfiguration('presetMax') : "10";
      $this->setConfiguration('presetMax', $preset);

      $name = $this->getConfiguration('name');
      $bbox = $this->getConfiguration('bbox', 0);
      $timestamp = $this->getConfiguration('timestamp', 1);
      $zones = $this->getConfiguration('zones', 0);
      $mask = $this->getConfiguration('mask', 0);
      $motion = $this->getConfiguration('motion', 0);
      $regions = $this->getConfiguration('regions', 0);
      $quality = $this->getConfiguration('quality', 70);

      $urlLatest = "http://" . $url . ":" . $port . "/api/" . $name . "/latest.jpg?timestamp=" . $timestamp . "&bbox=" . $bbox . "&zones=" . $zones . "&mask=" . $mask . "&motion=" . $motion . "&regions=" . $regions;
      $img = urlencode($urlLatest);
      $this->setConfiguration('img', $img);

      // maj lien et cmd snapshot
      $urlStream = "";
      $cmd = cmd::byEqLogicIdCmdName($this->getId(), "SNAPSHOT LIVE");
      if (is_object($cmd)) {
        $urlStream = $cmd->execCmd();

        if ($this->getConfiguration('urlStream') == '' || $this->getConfiguration('urlStream') != $urlStream) {
          $urlJeedom = network::getNetworkAccess('external');
          if ($urlJeedom == "") {
            $urlJeedom = network::getNetworkAccess('internal');
          }
          $urlStream = "/plugins/frigate/core/ajax/frigate.proxy.php?url=" . $img;
          $this->setConfiguration('urlStream', $urlStream);
          if (is_object($cmd)) {
            $cmd->event($urlJeedom . $urlStream);
            // $cmd->save();
          }
        }
      }

      // maj lien et cmd rtsp    
      $rtspStream = "";
      $cmd = cmd::byEqLogicIdCmdName($this->getId(), "RTSP");
      if (is_object($cmd)) {
        $rtspStream = $cmd->execCmd();

        $rtsp = $this->getConfiguration('cameraStreamAccessUrl');
        if ($rtsp == '' || $rtsp != $rtspStream) {
          if ($rtsp == '') {
            $rtsp = 'rtsp://' . $url . ':8554/' . $this->getConfiguration('name');
          }

          $this->setConfiguration('cameraStreamAccessUrl', $rtsp);

          if (is_object($cmd)) {
            $cmd->event($rtsp);
            // $cmd->save();
          }
        }
      }
    }
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {}

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {}

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove()
  {
    $name = $this->getConfiguration('name');
    $events = frigate_events::all();
    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:START REMOVE EQLOGIC:/fg: ═══════════════════");
    foreach ($events as $event) {
      if ($event->getCamera() == $name) {
        $event->setIsFavorite(0);
        $event->save();
        $eventId = $event->getEventId();
        self::cleanDbEvent($eventId);
      }
    }
    log::add(__CLASS__, 'debug', "╚════════════════════════ END REMOVE EQLOGIC ═══════════════════");
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


  // Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard')
  {
    $type = "";
    $logicalId = $this->getLogicalId();
    if (strpos($logicalId, "eqFrigateCamera_") !== false) {
      $type = "camera";
    }

    // panel ou dashbord
    $panel = false;
    if ($_version == 'panel') {
      $panel = true;
      $_version = 'dashboard';
    }

    if ($type == 'camera') {
      $replace = $this->preToHtml($_version);
      if (!is_array($replace)) {
        return $replace;
      }
      $version = jeedom::versionAlias($_version);

      $replace['#cameraEqlogicId#'] = $this->getLogicalId();
      $replace['#cameraName#'] = $this->getConfiguration("name");
      $replace['#imgUrl#'] = $this->getConfiguration("img");
      $replace['#refresh#'] = config::byKey('refresh_snapshot', 'frigate') * 1000;


      $replace['#actions#'] = '';
      // Commandes recording
      if (is_object($this->getCmd('action', 'action_start_recordings')) && is_object($this->getCmd('action', 'action_stop_recordings'))) {
        $on = $this->getCmd("action", 'action_start_recordings');
        $off = $this->getCmd("action", 'action_stop_recordings');
        $etat = $this->getCmd("info", 'info_recordings');
        if ($on->getIsVisible() == 1 && $off->getIsVisible() == 1) {
          if ($etat->execCmd() == 0) {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-video iconActionOff' . $this->getId() . '" title="recording ON" onclick="execAction(' . $on->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          } else {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-video iconAction' . $this->getId() . '" title="recording OFF" onclick="execAction(' . $off->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          }
        }
      }
      // Commandes snapshot
      if (is_object($this->getCmd('action', 'action_start_snapshots')) && is_object($this->getCmd('action', 'action_stop_snapshots'))) {
        $on = $this->getCmd("action", 'action_start_snapshots');
        $off = $this->getCmd("action", 'action_stop_snapshots');
        $etat = $this->getCmd("info", 'info_snapshots');
        if ($on->getIsVisible() == 1 && $off->getIsVisible() == 1) {
          if ($etat->execCmd() == 0) {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-camera iconActionOff' . $this->getId() . '" title="snapshot ON" onclick="execAction(' . $on->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          } else {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-camera iconAction' . $this->getId() . '" title="snapshot OFF" onclick="execAction(' . $off->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          }
        }
      }
      // commandes détections
      if (is_object($this->getCmd('action', 'action_start_detect')) && is_object($this->getCmd('action', 'action_stop_detect'))) {
        $on = $this->getCmd("action", 'action_start_detect');
        $off = $this->getCmd("action", 'action_stop_detect');
        $etat = $this->getCmd("info", 'info_detect');
        if ($on->getIsVisible() == 1 && $off->getIsVisible() == 1) {
          if ($etat->execCmd() == 0) {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-user-shield iconActionOff' . $this->getId() . '" title="detection ON" onclick="execAction(' . $on->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          } else {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-user-shield iconAction' . $this->getId() . '" title="detection OFF" onclick="execAction(' . $off->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          }
        }
      }
      // commandes audio
      if (is_object($this->getCmd('action', 'action_start_audio')) && is_object($this->getCmd('action', 'action_stop_audio'))) {
        $on = $this->getCmd("action", 'action_start_audio');
        $off = $this->getCmd("action", 'action_stop_audio');
        $etat = $this->getCmd("info", 'info_audio');
        if ($on->getIsVisible() == 1 && $off->getIsVisible() == 1) {
          if ($etat->execCmd() == 0) {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-volume-off iconActionOff' . $this->getId() . '" title="audio ON" onclick="execAction(' . $on->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          } else {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-volume-down iconAction' . $this->getId() . '" title="audio OFF" onclick="execAction(' . $off->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          }
        }
      }
      // commantes motions
      $replace['#detectNow#'] = "";
      $cmds = $this->getCmd('info');
      foreach ($cmds as $cmd) {
        if (strpos($cmd->getLogicalId(), 'info_detect_') === 0 && $cmd->getLogicalId() != 'info_detect_all') {
          $icon = $cmd->getDisplay("icon", "fas fa-exclamation-circle");
          $icon = preg_replace('/<i class="([^"]+)"><\/i>/', '$1', $icon);
          // Vérifier si la commande est visible
          if ($cmd->getIsVisible() == 1) {
            $value = $cmd->execCmd(); // Exécuter la commande et obtenir la valeur

            // Si la valeur est égale à 1, ajouter l'icône à l'affichage
            if ($value == 1) {
              $replace['#detectNow#'] .= '<div class="btn-detect">';
              $replace['#detectNow#'] .= '<i class="' . $icon . ' iconDetect' . $this->getId() . '"></i>';
              $replace['#detectNow#'] .= '</div>';
            }
          }
        }
      }


      if (is_object($this->getCmd('action', 'action_start_motion')) && is_object($this->getCmd('action', 'action_stop_motion'))) {
        $on = $this->getCmd("action", 'action_start_motion');
        $off = $this->getCmd("action", 'action_stop_motion');
        $etat = $this->getCmd("info", 'info_motion');
        if ($on->getIsVisible() == 1 && $off->getIsVisible() == 1) {
          if ($etat->execCmd() == 0) {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-male iconActionOff' . $this->getId() . '" title="motion ON" onclick="execAction(' . $on->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          } else {
            $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
            $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-walking iconAction' . $this->getId() . '" title="motion OFF" onclick="execAction(' . $off->getId() . ')"></i>';
            $replace['#actions#'] = $replace['#actions#'] . '</div>';
          }
        }
      }

      // commandes make snapshot
      if (is_object($this->getCmd('action', 'action_make_api_event'))) {
        $make_snapshot = $this->getCmd("action", 'action_make_api_event');
        if ($make_snapshot->getIsVisible() == 1) {
          $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
          $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-camera-retro iconActionOff' . $this->getId() . '" title="' . __("Créer un évènement", __FILE__) . '" onclick="execAction(' . $make_snapshot->getId() . ')"></i>';
          $replace['#actions#'] = $replace['#actions#'] . '</div>';
        }
      }

      // commandes create capture
      if (is_object($this->getCmd('action', 'action_create_snapshot'))) {
        $make_snapshot = $this->getCmd("action", 'action_create_snapshot');
        if ($make_snapshot->getIsVisible() == 1) {
          $replace['#actions#'] = $replace['#actions#'] . '<div class="btn-icon">';
          $replace['#actions#'] = $replace['#actions#'] . '<i class="fas fa-image iconActionOff' . $this->getId() . '" title="' . __("Créer une capture", __FILE__) . '" onclick="execAction(' . $make_snapshot->getId() . ')"></i>';
          $replace['#actions#'] = $replace['#actions#'] . '</div>';
        }
      }

      // commandes dispo sur la modale
      $replace['#actionsModal#'] = $replace['#actions#'];
      $replace['#ptzWidget#'] = "";
      $replace['#ptzZoom#'] = "";


      if (
        is_object($this->getCmd('action', 'action_ptz_down')) ||
        is_object($this->getCmd('action', 'action_ptz_up')) ||
        is_object($this->getCmd('action', 'action_ptz_left')) ||
        is_object($this->getCmd('action', 'action_ptz_right')) ||
        is_object($this->getCmd('action', 'action_ptz_stop'))
      ) {
        $replace['#ptzWidget#'] = '<div class="circle-overlay"></div>';
      }

      // commandes PTZ down
      if (is_object($this->getCmd('action', 'action_ptz_down'))) {
        $down = $this->getCmd("action", 'action_ptz_down');
        if ($down->getIsVisible() == 1) {
          // config pour le widget
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<div class="btn-ptz-down">';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<i class="fas fa-caret-down iconPTZdown' . $this->getId() . '" title="PTZ DOWN" onclick="execAction(' . $down->getId() . ')"></i>';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '</div>';
          // config pour la modal
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<div class="btn-icon">';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<i class="fas fa-chevron-circle-down iconActionOff' . $this->getId() . '" title="PTZ DOWN" onclick="execAction(' . $down->getId() . ')"></i>';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '</div>';
        }
      }

      // commandes PTZ up
      if (is_object($this->getCmd('action', 'action_ptz_up'))) {
        $up = $this->getCmd("action", 'action_ptz_up');
        if ($up->getIsVisible() == 1) {
          // config pour le widget
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<div class="btn-ptz-up">';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<i class="fas fa-caret-up iconPTZup' . $this->getId() . '" title="PTZ UP" onclick="execAction(' . $up->getId() . ')"></i>';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '</div>';
          // config pour la modal
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<div class="btn-icon">';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<i class="fas fa-chevron-circle-up iconActionOff' . $this->getId() . '" title="PTZ UP" onclick="execAction(' . $up->getId() . ')"></i>';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '</div>';
        }
      }

      // commandes PTZ left
      if (is_object($this->getCmd('action', 'action_ptz_left'))) {
        $left = $this->getCmd("action", 'action_ptz_left');
        if ($left->getIsVisible() == 1) {
          // config pour le widget
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<div class="btn-ptz-left">';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<i class="fas fa-caret-left iconPTZleft' . $this->getId() . '" title="PTZ LEFT" onclick="execAction(' . $left->getId() . ')"></i>';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '</div>';
          // config pour la modal
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<div class="btn-icon">';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<i class="fas fa-chevron-circle-left iconActionOff' . $this->getId() . '" title="PTZ LEFT" onclick="execAction(' . $left->getId() . ')"></i>';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '</div>';
        }
      }

      // commandes PTZ right
      if (is_object($this->getCmd('action', 'action_ptz_right'))) {
        $right = $this->getCmd("action", 'action_ptz_right');
        if ($right->getIsVisible() == 1) {
          // config pour le widget
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<div class="btn-ptz-right">';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<i class="fas fa-caret-right iconPTZright' . $this->getId() . '" title="PTZ RIGHT" onclick="execAction(' . $right->getId() . ')"></i>';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '</div>';
          // config pour la modal
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<div class="btn-icon">';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<i class="fas fa-chevron-circle-right iconActionOff' . $this->getId() . '" title="PTZ RIGHT" onclick="execAction(' . $right->getId() . ')"></i>';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '</div>';
        }
      }

      // commandes PTZ stop
      if (is_object($this->getCmd('action', 'action_ptz_stop'))) {
        $stop = $this->getCmd("action", 'action_ptz_stop');
        if ($stop->getIsVisible() == 1) {
          // config pour le widget
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<div class="btn-ptz-stop">';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '<i class="fas fa-stop iconPTZstop' . $this->getId() . '" title="PTZ STOP" onclick="execAction(' . $stop->getId() . ')"></i>';
          $replace['#ptzWidget#'] = $replace['#ptzWidget#'] . '</div>';
          // config pour la modal
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<div class="btn-icon">';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<i class="fas fa-stop-circle iconActionOff' . $this->getId() . '" title="PTZ STOP" onclick="execAction(' . $stop->getId() . ')"></i>';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '</div>';
        }
      }

      // commandes PTZ zoom in
      if (is_object($this->getCmd('action', 'action_ptz_zoom_in'))) {
        $zoom_in = $this->getCmd("action", 'action_ptz_zoom_in');
        if ($zoom_in->getIsVisible() == 1) {
          // config pour le widget
          $replace['#ptzZoom#'] = $replace['#ptzZoom#'] . '<div class="btn-ptz-zoom-in">';
          $replace['#ptzZoom#'] = $replace['#ptzZoom#'] . '<i class="fas fa-plus iconZoomIn' . $this->getId() . '" title="PTZ ZOOM IN" onclick="execAction(' . $zoom_in->getId() . ')"></i>';
          $replace['#ptzZoom#'] = $replace['#ptzZoom#'] . '</div>';
          // config pour la modal
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<div class="btn-icon">';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<i class="fas fa-plus-circle iconActionOff' . $this->getId() . '" title="PTZ ZOOM IN" onclick="execAction(' . $zoom_in->getId() . ')"></i>';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '</div>';
        }
      }

      // commandes PTZ zoom out
      if (is_object($this->getCmd('action', 'action_ptz_zoom_out'))) {
        $zoom_out = $this->getCmd("action", 'action_ptz_zoom_out');
        if ($zoom_out->getIsVisible() == 1) {
          // config pour le widget
          $replace['#ptzZoom#'] = $replace['#ptzZoom#'] . '<div class="btn-ptz-zoom-out">';
          $replace['#ptzZoom#'] = $replace['#ptzZoom#'] . '<i class="fas fa-minus iconZoomOut' . $this->getId() . '" title="PTZ ZOOM OUT" onclick="execAction(' . $zoom_out->getId() . ')"></i>';
          $replace['#ptzZoom#'] = $replace['#ptzZoom#'] . '</div>';
          // config pour la modal
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<div class="btn-icon">';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '<i class="fas fa-minus-circle iconActionOff' . $this->getId() . '" title="PTZ ZOOM OUT" onclick="execAction(' . $zoom_out->getId() . ')"></i>';
          $replace['#actionsModal#'] = $replace['#actionsModal#'] . '</div>';
        }
      }



      $replace['#actionsPreset#'] = '';
      $hasPresets = false; // Variable pour vérifier si des presets sont disponibles

      // Créer la structure HTML du select
      $selectHtml = '<div class="btn-icon">';
      $selectHtml .= '<select class="preset-select' . $this->getId() . '" id="presetSelect' . $this->getId() . '" onchange="execSelectedPreset' . $this->getId() . '()">';
      $selectHtml .= '<option value="" disabled selected hidden>{{action}}</option>';
      // Boucle sur les presets disponibles
      for ($i = 0; $i <= 10; $i++) {
        $presetCmd = 'action_preset_' . $i;
        if (is_object($this->getCmd('action', $presetCmd))) {
          $preset = $this->getCmd("action", $presetCmd);
          if ($preset->getIsVisible() == 1) {
            $hasPresets = true; // Des presets sont disponibles
            $selectHtml .= '<option value="' . $preset->getId() . '">' . $preset->getName() . '</option>';
          }
        }
      }
      // Boucle pour ajouter les commandes HTTP
      $httpCommands = cmd::byEqLogicIdAndLogicalId($this->getId(), "action_http", true);
      foreach ($httpCommands as $httpCmd) {
        if ($httpCmd && $httpCmd->getIsVisible() == 1) {
          $hasPresets = true; // Des presets sont disponibles
          $selectHtml .= '<option value="' . $httpCmd->getId() . '">' . $httpCmd->getName() . '</option>';
        }
      }

      $selectHtml .= '</select>';
      $selectHtml .= '</div>';

      // Fermer le select si des presets sont disponibles
      if ($hasPresets) {
        $replace['#actionsModal#'] = $replace['#actionsModal#'] . $selectHtml;
        $replace['#actionsPreset#'] = $selectHtml;
      }





      if (!$panel) {
        $html = template_replace($replace, getTemplate('core', $version, 'widgetCamera', __CLASS__));
        $html = translate::exec($html, 'plugins/frigate/core/template/' . $version . '/widgetCamera.html');
        $html = $this->postToHtml($_version, $html);
        cache::set('widgetCamera' . $_version . $this->getId(), $html, 0);
        return $html;
      } else {
        $html = template_replace($replace, getTemplate('core', $version, 'widgetPanel', __CLASS__));
        $html = translate::exec($html, 'plugins/frigate/core/template/' . $version . '/widgetPanel.html');
        $html = $this->postToHtml($_version, $html);
        cache::set('widgetPanel' . $_version . $this->getId(), $html, 0);
        return $html;
      }
    } else {
      return parent::toHtml($_version);
    }
  }


  /*     * **********************Getteur Setteur*************************** */
  private static function getTopic()
  {
    return config::byKey('topic', 'frigate');
  }

  public static function getUrlFrigate()
  {
    $url = config::byKey('URL', 'frigate');
    if ($url == "") {
      log::add(__CLASS__, 'error', "║ Erreur: L'URL ne peut être vide.");
      return false;
    }
    $port = config::byKey('port', 'frigate');
    if ($port == "") {
      log::add(__CLASS__, 'error', "║ Erreur: Le port ne peut être vide");
      return false;
    }
    $urlFrigate = $url . ":" . $port;
    return $urlFrigate;
  }

  public static function addMessages()
  {
    message::add('frigate', __("Merci d'avoir installé le plugin. Pour toutes les demandes d'aide, veuillez contacter le support sur Discord ou sur Community.", __FILE__), null, null);
    $system = system::getOsVersion();
    if (version_compare($system, "11", "<")) {
      message::add('frigate', __("Attention, vous utilisez la version " . $system . " de Debian, aucun support n'est disponible. La version 11 de Debian est recommandée.", __FILE__), null, null);
    }
    $jeedom = jeedom::version();
    if (version_compare($jeedom, "4.4", "<")) {
      message::add('frigate', __("Attention, vous utilisez la version " . $jeedom . " de Jeedom. La version 4.4.x de Jeedom est recommandée.", __FILE__), null, null);
    }
  }

  public static function publish_camera_message(string $camera, string $subTopic, string $payload)
  {
    self::publish_message("{$camera}/{$subTopic}", $payload);
  }

  public static function publish_message(string $subTopic, string $payload)
  {
    log::add(__CLASS__, 'debug', "║ publish_message : " . self::getTopic() . "/{$subTopic} avec payload : {$payload}");
    mqtt2::publish(self::getTopic() . "/{$subTopic}", $payload);
  }

  private static function getcURL($function, $url, $params = null, $decodeJson = true, $method = 'GET')
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
      if ($method !== 'DELETE') {
        $jsonParams = json_encode($params);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParams);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Content-Type: application/json',
          'Content-Length: ' . strlen($jsonParams)
        ]);
      }

      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    $data = curl_exec($ch);

    if (curl_errno($ch)) {
      log::add(__CLASS__, 'error', "║ Erreur getcURL (" . $method . "): " . curl_error($ch));
      return null;
    }
    curl_close($ch);
    $response = $decodeJson ? json_decode($data, true) : $data;
    log::add(__CLASS__, 'debug', "║ " . $function . " : requête " . $method . " exécutée.");
    return $response;
  }

  private static function postcURL($function, $url, $params = null, $decodeJson = true)
  {
    return self::getcURL($function, $url, $params, $decodeJson, 'POST');
  }

  private static function putcURL($function, $url, $params = null, $decodeJson = true)
  {
    return self::getcURL($function, $url, $params, $decodeJson, 'PUT');
  }

  private static function deletecURL($url)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $data = curl_exec($ch);

    if (curl_errno($ch)) {
      log::add(__CLASS__, 'error', "║ Erreur: deletecURL" . curl_error($ch));
      die();
    }
    curl_close($ch);
    $response = json_decode($data, true);
    log::add(__CLASS__, 'debug', "║ Suppression sur le serveur Frigate : " . json_encode($response));
    return $response;
  }

  public static function getStats()
  {
    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:START STATS:/fg: ═══════════════════");
    $urlfrigate = self::getUrlFrigate();
    $resultURL = $urlfrigate . "/api/stats";
    $stats = self::getcURL("Stats", $resultURL);
    if ($stats == null) {
      log::add(__CLASS__, 'error', "║ Erreur: Impossible de récupérer les stats de Frigate.");
      log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-warning:ERREURS:/fg: ═══════════════════");
      return;
    }
    self::majStatsCmds($stats);
    log::add(__CLASS__, 'debug', "╚════════════════════════ END STATS ═══════════════════");
  }

  public static function getPresets($camera)
  {
    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:START IMPORT PRESETS:/fg: ═══════════════════");
    $urlfrigate = self::getUrlFrigate();
    $resultURL = $urlfrigate . "/api/" . $camera . "/ptz/info";
    $presets = self::getcURL("Presets", $resultURL);
    if ($presets == null) {
      log::add(__CLASS__, 'error', "║ Erreur: Impossible de récupérer les presets de Frigate.");
      log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-warning:ERREURS:/fg: ═══════════════════");
      return;
    }
    log::add(__CLASS__, 'debug', "╚════════════════════════ END IMPORT PRESET ═══════════════════");
    return $presets;
  }

  public static function createEvent($camera, $label, $video = 1, $duration = 20, $score = 30, $subLabel = '')
  {
    $urlfrigate = self::getUrlFrigate();
    $resultURL = $urlfrigate . "/api/events/" . $camera . "/" . rawurlencode($label) . "/create";

    $score = max(0, min(100, floatval($score)));
    $score = $score / 100;
    $duration = floatval($duration);
    $includeRecording = ($video == 1);

    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:START CREATE EVENT:/fg: ═══════════════════");
    log::add(__CLASS__, 'debug', "║ label : {$label}");
    log::add(__CLASS__, 'debug', "║ score : {$score}");
    log::add(__CLASS__, 'debug', "║ duration : {$duration}");
    log::add(__CLASS__, 'debug', "║ video : {$video}");
    log::add(__CLASS__, 'debug', "║ include_recording : " . ($includeRecording ? "true" : "false"));
    log::add(__CLASS__, 'debug', "║ sub_label : {$subLabel}");

    $params = [
      'source_type' => 'api',
      'sub_label' => $subLabel,
      'score' => $score,
      'duration' => $duration,
      'include_recording' => $includeRecording
    ];
    $response = self::postcURL("CreateEvent", $resultURL, $params);

    log::add(__CLASS__, 'debug', "╚════════════════════════ END CREATE EVENT ═══════════════════");
    return $response;
  }

  // Méthodes de modification du fichier de configuration par API
  // Attention : Redémarrage Frigate nécessaire pour prise en compte
  // TODO : Ajouter des méthodes appelant cette méthode pour une modification de paramètres du fichier de configuration
  public static function saveConfig($config)
  {
    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:START SAVE CONFIG:/fg: ═══════════════════");
    $urlfrigate = self::getUrlFrigate();
    $resultURL = $urlfrigate . "/api/config/set?{$config}";

    log::add(__CLASS__, 'debug', "║ url : {$resultURL}");
    $response = self::putcURL("saveConfig", $resultURL); //, $params);

    event::add('frigate::config', array('message' => 'api_config_update', 'type' => 'config'));

    log::add(__CLASS__, 'debug', "╚════════════════════════ END SAVE CONFIG ═══════════════════");
    return $response;
  }

  public static function saveCameraConfig($camera, $config)
  {
    $response = self::saveConfig("cameras.{$camera}.{$config}");

    return $response;
  }

  // Méthodes de modification du fichier de configuration pour une caméra par API 
  // Attention : Redémarrage Frigate nécessaire pour prise en compte
  // TODO : Ajouter d'autres méthodes pour différents paramètres supplémentaires pour une caméra
  public static function enableCamera($camera, $enable)
  {
    $enabled = $enable == 1 ? 'true' : 'false';
    $response = self::saveCameraConfig($camera, "enabled={$enabled}");

    return $response;
  }

  public static function getLogs($service)
  {
    $urlfrigate = self::getUrlFrigate();
    $resultURL = $urlfrigate . "/api/logs/" . $service;
    $logs = self::getcURL("Logs", $resultURL, null, false);
    if ($logs == null) {
      log::add(__CLASS__, 'error', "║ Erreur: Impossible de récupérer les logs de Frigate.");
      log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-warning:ERREURS:/fg: ═══════════════════");
      return;
    }
    return $logs;
  }

  public static function getEvent($id = null, $type = 'end')
  {
    if ($id == null) return;

    self::getEvents(false, array(), $type, $id);
  }

  public static function getEvents($mqtt = false, $events = array(), $type = 'end', $id = null, $recoveryDays = null)
  {
    if ($id !== null) {
      $urlFrigate = self::getUrlFrigate();
      $resultURL = "{$urlFrigate}/api/events/{$id}";
      $event = self::getcURL("ManualEvent", $resultURL);
      // Traiter un évènement
      $events = array($event);
    } else if (!$mqtt) {
      $urlFrigate = self::getUrlFrigate();
      $resultURL = "{$urlFrigate}/api/events";
      $events = self::getcURL("Events", $resultURL);
      if ($events == null) {
        log::add(__CLASS__, 'error', "║ Erreur: Impossible de récupérer les événements de Frigate.");
        log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-warning:ERREURS:/fg: ═══════════════════");
        return;
      }
      // Traiter les evenements du plus ancien au plus recent
      $events = array_reverse($events);
    }
    if ($recoveryDays != 1) {
      // Nombre de jours a filtrer et enregistrer en DB
      $recoveryDays = config::byKey('recovery_days', 'frigate');
      if (empty($recoveryDays)) {
        $recoveryDays = 7;
      }
    }
    // vérification de la taille du dossier et nettoyage
    self::cleanFolderDataIfFull();

    $filteredRecoveryEvents = array_filter($events, function ($event) use ($recoveryDays) {
      return $event['start_time'] >= time() - $recoveryDays * 86400;
    });
    $filteredRecoveryEvents = array_values($filteredRecoveryEvents);

    foreach ($filteredRecoveryEvents as $event) {
      $frigate = frigate_events::byEventId($event['id']);

      log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:START EVENT:/fg: ═══════════════════");

      $infos = self::getEventinfos($mqtt, $event, false, $type);

      if (!$frigate) {
        log::add(__CLASS__, 'debug', "║ Events (type=" . $type . ") => " . json_encode($event));
        $box = $event['data']['box'] ?? "null";

        $frigate = new frigate_events();
        $frigate->setBox($box);
        $frigate->setCamera($event['camera']);
        $frigate->setData($event['data']);
        $frigate->setLasted($infos["image"]);
        $frigate->setHasClip($infos["hasClip"]);
        $frigate->setClip($infos["clip"]);
        $frigate->setHasSnapshot($infos["hasSnapshot"]);
        $frigate->setSnapshot($infos["snapshot"]);
        $frigate->setStartTime($infos['startTime']);
        $frigate->setEndTime($infos["endTime"]);
        // $frigate->setFalsePositive($event['false_positive']);
        $frigate->setEventId($event['id']);
        $frigate->setLabel($infos['label']);
        $frigate->setPlusId($event['plus_id']);
        $frigate->setRetain($event['retain_indefinitely']);
        $frigate->setSubLabel($event['sub_label']);
        $frigate->setThumbnail($infos["thumbnail"]);
        $frigate->setTopScore($infos["topScore"]);
        $frigate->setScore($infos["score"]);
        $frigate->setZones($infos['zones']);
        $frigate->setType($type);
        $frigate->setIsFavorite(0);
        $frigate->save();
        self::majEventsCmds($frigate);
        log::add(__CLASS__, 'debug', "║ Evénement Frigate créé et sauvegardé, event ID: " . $event['id']);
      } else {

        if (is_array($frigate) && !empty($frigate)) {
          $frigate = $frigate[0];
        }

        $updated = false;

        $fieldsToUpdate = [
          'EndTime' => $infos["endTime"],
          'HasClip' => $infos["hasClip"],
          'Clip' => $infos["clip"],
          'HasSnapshot' => $infos["hasSnapshot"],
          'Snapshot' => $infos["snapshot"],
          'Box' => $event['data']['box'] ?? null,
          'Camera' => $event['camera'],
          // 'FalsePositive' => $event['false_positive'],
          'Label' => $infos['label'],
          'PlusId' => $event['plus_id'],
          'SubLabel' => $event['sub_label'],
          'Thumbnail' => $infos["thumbnail"],
          'Type' => $type,
          'TopScore' => $infos["topScore"],
          'Score' => $infos["score"],
          'Zones' => $infos['zones']
        ];

        foreach ($fieldsToUpdate as $field => $value) {
          $getMethod = 'get' . $field;
          $setMethod = 'set' . $field;
          //$currentValue = is_string($frigate->$getMethod()) ? json_decode($frigate->$getMethod(), true) : $frigate->$getMethod();
          $currentValue = $frigate->$getMethod();
          //$newValue = is_string($value) ? json_decode($value, true) : $value;
          $newValue = $value;

          // soucis sur maj Box, "[]" != []
          if ($field == 'Box') {
            if ($value !== null) {
              $newValue = json_encode($value);
            }
            // log::add(__CLASS__, 'debug', "║ BOX, ancienne valeur: " . $currentValue . ", nouvelle valeur: " . $newValue);
          }

          if ((is_null($currentValue) || $currentValue === '' || $currentValue != $newValue) && !is_null($newValue) && $newValue !== '') {
            log::add(__CLASS__, 'debug', "║ Mise à jour du champ '$field' pour event ID: " . $event['id'] . ". ancienne valeur: " . json_encode($currentValue) . ", nouvelle valeur: " . json_encode($newValue));
            $frigate->$setMethod($newValue);
            $updated = true;
            if ($field == 'Type' && $newValue != $frigate->getType()) {
              $infos = self::getEventinfos($mqtt, $event, true);
              $frigate->setSnapshot($infos["snapshot"]);
              $frigate->setClip($infos["clip"]);
              log::add(__CLASS__, 'debug', "║ Mise à jour forcé des champs snapshot et clip pour event ID: " . $event['id']);
              $frigate->save();
            }
          }
        }

        if ($updated) {
          $frigate->setData($event['data']);
          log::add(__CLASS__, 'debug', "║ Mise à jour du champ data pour event ID: " . $event['id']);
          $frigate->save();
          self::majEventsCmds($frigate);
          log::add(__CLASS__, 'debug', "║ Evénement Frigate mis à jour et sauvegardé, event ID: " . $event['id']);
        } else {
          log::add(__CLASS__, 'debug', "║ Pas de mise à jour pour event ID: " . $event['id']);
        }
      }
      log::add(__CLASS__, 'debug', "╚════════════════════════ END EVENT ═══════════════════");
    }
  }

  private static function eventAdd($event, $eqLogicId)
  {

    $date = date("d-m-Y H:i:s", $event->getStartTime());
    $duree = round($event->getEndTime() - $event->getStartTime(), 0);
    $box = $event->getBox();
    $boxArray = is_array($box) ? $box : json_decode($box, true);
    $data = $event->getData();
    $dataArray = is_array($data) ? $data : json_decode($data, true);

    $result = array(
      "id" => $event->getId(),
      "img" => $event->getLasted(),
      "camera" => $event->getCamera(),
      "label" => $event->getLabel(),
      "box" => $boxArray,
      "date" => $date,
      "duree" => $duree,
      "startTime" => $event->getStartTime(),
      "endTime" => $event->getEndTime(),
      "snapshot" => $event->getSnapshot(),
      "clip" => $event->getClip(),
      "thumbnail" => $event->getthumbnail(),
      "hasSnapshot" => $event->getHasSnapshot(),
      "hasClip" => $event->getHasClip(),
      "eventId" => $event->getEventId(),
      "score" => $event->getScore(),
      "top_score" => $event->getTopScore(),
      "type" => $event->getType(),
      "isFavorite" => $event->getIsFavorite() ?? 0,
      "zones" => $event->getZones() ?? '',
      "description" => $dataArray['description'] ?? ''
    );


    event::add(
      'frigate::event',
      [
        'pluginId' => 'frigate',
        'type' => 'pluginEvent',
        'value' => [
          'eqlogicId' => $eqLogicId,
          'value' => $result
        ]
      ]
    );
  }

  public static function getEventInfos($mqtt, $event, $force = false, $type = "end")
  {
    $dir = dirname(__FILE__, 3) . "/data/" . $event['camera'];
    $sleep = config::byKey('sleep', 'frigate');
    if (!isset($sleep) || $sleep < 0 || $sleep > 10) {
      $sleep = 5;
    } else {
      $sleep = intval($sleep);
    }
    // Fonction de vérification et téléchargement
    sleep($sleep);
    $img = self::processMedia($dir, $event['id'], '_thumbnail.jpg', $event['camera'], 1);
    $snapshot = self::processSnapshot($dir, $event, $force);
    log::add(__CLASS__, 'debug', "║ Snapshot: " . json_encode($snapshot));
    $clip = self::processClip($dir, $event, $type, $force);
    self::processPreview($dir, $event);

    // Gestion du end_time
    $endTime = !empty($event['end_time']) ? ceil($event['end_time']) : 0;

    // Calcul des scores
    $newTopScore = round(($mqtt ? $event['top_score'] : $event['data']['top_score']) * 100, 0);
    $newScore = round(($mqtt ? $event['score'] : $event['data']['score']) * 100, 0);

    // Calcul des zones
    $newZones = isset($event['zones']) && is_array($event['zones']) && !empty($event['zones'])
      ? implode(', ', $event['zones'])
      : "";

    // Retour des infos
    return array(
      "image" => !empty($img) ? $img : "",
      "thumbnail" => !empty($img) ? $img : "",
      "snapshot" => isset($snapshot['url']) ? $snapshot['url'] : "",
      "hasSnapshot" => isset($snapshot['has']) ? $snapshot['has'] : 0,
      "clip" => isset($clip['url']) ? $clip['url'] : "",
      "hasClip" => isset($clip['has']) ? $clip['has'] : 0,
      "startTime" => isset($event['start_time']) && is_numeric($event['start_time']) && ceil($event['start_time']) > 0 ? ceil($event['start_time']) : (isset($event['start_time']) ? $event['start_time'] : ""),
      "endTime" => is_numeric($endTime) ? $endTime : "",
      "topScore" => is_numeric($newTopScore) ? $newTopScore : "",
      "score" => is_numeric($newScore) ? $newScore : "",
      "zones" => $newZones,
      "label" => isset($event['label']) ? self::cleanLabel($event['label']) : ""
    );
  }


  private static function processMedia($dir, $id, $suffix, $camera, $isThumbnail = 0)
  {
    $filePath = $dir . '/' . $id . $suffix;
    if (!file_exists($filePath)) {
      log::add(__CLASS__, 'debug', "║ Fichier non trouvé: $filePath, téléchargement");
      $img = self::saveURL($id, $isThumbnail ? null : "snapshot", $camera, $isThumbnail);
      return $img == "error" ? "null" : "/plugins/frigate/data/" . $camera . "/" . $id . $suffix;
    }
    return "/plugins/frigate/data/" . $camera . "/" . $id . $suffix;
  }

  private static function processSnapshot($dir, $event, $force)
  {
    if (!file_exists($dir . '/' . $event['id'] . '_snapshot.jpg') || $force) {
      log::add(__CLASS__, 'debug', "║ Fichier snapshot non trouvé: " . $dir . '/' . $event['id'] . '_snapshot.jpg');
      if ($event['has_snapshot'] == "true") {
        log::add(__CLASS__, 'debug', "║ Has Snapshot: true, téléchargement");
        $snapshot = self::saveURL($event['id'], "snapshot", $event['camera']);
        return ['url' => $snapshot == "error" ? "null" : $snapshot, 'has' => ($snapshot != "error") ? 1 : 0];
      }
      log::add(__CLASS__, 'debug', "║ Has Snapshot: false, téléchargement annulé");
    }
    return ['url' => "/plugins/frigate/data/" . $event['camera'] . "/" . $event['id'] . '_snapshot.jpg', 'has' => 1];
  }

  private static function processClip($dir, $event, $type, $force)
  {
    if ($type != "end") {
      log::add(__CLASS__, 'debug', "║ Pas de clip, le type n'est pas 'end' " . json_encode($event));
      return ['url' => "null", 'has' => 0];
    }

    if (!file_exists($dir . '/' . $event['id'] . '_clip.mp4') || $force) {
      log::add(__CLASS__, 'debug', "║ Fichier clip non trouvé: " . $dir . '/' . $event['id'] . '_clip.mp4');
      if ($event['has_clip'] == "true") {
        $clip = self::saveURL($event['id'], "clip", $event['camera']);
        if ($clip == "error") return ['url' => "null", 'has' => 0];

        $duration = self::getVideoDuration($dir . '/' . $event['id'] . '_clip.mp4');
        if ($duration !== false) {
          log::add(__CLASS__, 'debug', "║ La durée de la video est de " . gmdate("H:i:s", $duration));
        }
        return ['url' => $clip, 'has' => 1];
      } else {
        log::add(__CLASS__, 'debug', "║ Has Clip: false, telechargement annulé");
        return ['url' => "null", 'has' => 0];
      }
    }
  }
  private static function processPreview($dir, $event)
  {
    if (!file_exists($dir . '/' . $event['id'] . '_preview.gif')) {
      log::add(__CLASS__, 'debug', "║ Fichier preview non trouvé: " . $dir . '/' . $event['id'] . '_preview.gif');
      $preview = self::saveURL($event['id'], "preview", $event['camera']);
      return ['url' => $preview == "error" ? "null" : $preview, 'has' => $preview != "error"];
    }
    return "/plugins/frigate/data/" . $event['camera'] . "/" . $event['id'] . '_preview.gif';
  }
  private static function cleanLabel($label)
  {
    return $label;
  }


  public static function getVideoDuration($filePath)
  {
    $cmd = "ffmpeg -i " . escapeshellarg($filePath) . " 2>&1";
    $output = shell_exec($cmd);

    if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $output, $matches)) {
      $hours = $matches[1];
      $minutes = $matches[2];
      $seconds = $matches[3];
      $duration = ($hours * 3600) + ($minutes * 60) + $seconds;

      return $duration; // Durée en secondes
    }

    return false; // En cas d'erreur
  }
  // Fonction de nettoyage du dossier data, suppression de tous les fichiers n'ayant pas d'event associé en DB Jeedom
  // Exécution en cronDaily
  public static function cleanFolderData()
  {
    // Nettoyage du dossier des images caméra
    $folder = dirname(__FILE__, 3) . "/data";

    if (file_exists($folder)) {
      // Parcourt récursivement tous les fichiers et dossiers
      foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile()) {
          $path = $file->getPathname();
          // Vérifiez que le fichier est dans un sous-dossier de /data
          if (strpos($path, $folder . DIRECTORY_SEPARATOR) === 0 && $path !== $folder . DIRECTORY_SEPARATOR . basename($path) && strpos($path, $folder . DIRECTORY_SEPARATOR . 'snapshots' . DIRECTORY_SEPARATOR) === false) {
            $id = self::extractID($file->getFilename());
            // Vérifier si l'id existe dans la base de données
            $frigate = frigate_events::byEventId($id);
            if (!$frigate) {
              log::add(__CLASS__, 'debug', "║ Fichier " . $path . " non trouvé en database.");
              if (unlink($path)) {
                log::add(__CLASS__, 'debug', "║ Suppresion reussie: " . $path);
              } else {
                log::add(__CLASS__, 'error', "║ Suppresion echouée: " . $path);
              }
            }
          }
        }
      }
    } else {
      log::add(__CLASS__, 'error', "║ Dossier inexistant: " . $folder);
    }
  }


  // nettoyer la DB de tous les fichiers dont la date de creation est supérieure au nombre de jours configurer
  // Exécution en cronDaily
  public static function cleanAllOldestFiles()
  {
    $days = config::byKey('remove_days', 'frigate', "7");
    $recoveryDays = config::byKey('recovery_days', 'frigate', "7");
    if (!is_numeric($days) || $days <= 0) {
      log::add(__CLASS__, 'error', "║ Configuration invalide pour 'remove_days': " . $days . " Cela doit être un nombre positif.");
      return;
    }
    if ($days < $recoveryDays) {
      log::add(__CLASS__, 'warning', "║ 'remove_days' doit être supérieur à 'recovery_days'");
      $days = $recoveryDays;
    }
    log::add(__CLASS__, 'info', "║ Nettoyage des fichiers datant de plus de " . $days . " jours.");

    $events = frigate_events::getOldestNotFavorites($days);

    if (!empty($events)) {
      foreach ($events as $event) {
        $eventId = $event->getEventId();

        log::add(__CLASS__, 'info', "║ Nettoyage de l'événement ID: " . $eventId);

        $result = self::cleanDbEvent($eventId);

        if ($result) {
          log::add(__CLASS__, 'info', "║ Événement ID: " . $eventId . " nettoyé avec succès.");
        } else {
          log::add(__CLASS__, 'error', "║ Échec du nettoyage de l'événement ID: " . $eventId);
        }
      }
    } else {
      log::add(__CLASS__, 'info', "║ Aucun événement trouvé datant de plus de " . $days . " jours.");
    }
  }

  public static function cleanByType($type = "new")
  {
    $events = frigate_events::byType($type);

    if (!empty($events)) {
      foreach ($events as $event) {
        $eventId = $event->getEventId();

        log::add(__CLASS__, 'info', "║ Nettoyage de l'événement ID: " . $eventId . " il est de type: " . $type);

        $result = self::cleanDbEvent($eventId);

        if ($result) {
          log::add(__CLASS__, 'info', "║ Événement ID: " . $eventId . " nettoyé avec succès.");
        } else {
          log::add(__CLASS__, 'error', "║ Échec du nettoyage de l'événement ID: " . $eventId);
        }
      }
    }
  }

  public static function cleanOldestFile()
  {
    $events = [];
    $events = frigate_events::getOldestNotFavorite();
    if (!empty($events)) {
      foreach ($events as $event) {
        self::cleanDbEvent($event->getEventId());
      }
    }
  }

  // Supprime le plus vieux si dossier plein
  public static function cleanFolderDataIfFull()
  {
    $maxSize = config::byKey('datas_weight', 'frigate');
    $size = self::getFolderSize();
    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:START CLEAN:/fg: ═══════════════════");
    log::add(__CLASS__, 'debug', "║ Taille du dossier : " . $size);
    log::add(__CLASS__, 'debug', "║ Taille maximale du dossier : " . $maxSize);

    while ($size > $maxSize) {
      log::add(__CLASS__, 'debug', "║ Le dossier est plein, nettoyage du fichier le plus ancien");
      self::cleanOldestFile();
      $size = self::getFolderSize();
      log::add(__CLASS__, 'debug', "║ Nouvelle taille du dossier : " . $size);
    }

    if ($size <= $maxSize) {
      log::add(__CLASS__, 'debug', "║ Le dossier n'est pas plein");
    }
    log::add(__CLASS__, 'debug', "╚════════════════════════ END CLEAN ═══════════════════");
  }


  // Fonction qui calcule la taille du dossier data
  public static function getFolderSize()
  {
    $folder = dirname(__FILE__, 3) . "/data";
    $size = 0;
    // Parcourt récursivement tous les fichiers et dossiers
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)) as $file) {
      $size += $file->getSize(); // Ajoute la taille du fichier
    }
    $size = round($size / 1024 / 1024, 2);
    return $size;
  }

  public static function extractID($filename)
  {
    // Utiliser une expression régulière pour extraire l'ID du nom de fichier
    if (preg_match(
      '/^\d+\.\d+-[a-z0-9]+/',
      $filename,
      $matches
    )) {
      return $matches[0];
    }
    return null;
  }

  public static function cleanDbEvent($id)
  {
    $frigate = frigate_events::byEventId($id);
    if (!empty($frigate) && isset($frigate[0])) {
      $frigate = $frigate[0];
      // Vérifier si le fichier est un favori
      $isFavorite = $frigate->getIsFavorite() ?? 0;
      if (
        $isFavorite == 1
      ) {
        log::add(__CLASS__, 'debug', "║ Événement " . $frigate->getEventId() . " est un favori, il ne doit pas être supprimé de la base de données.");
      } else {
        // Recherche si clip et snapshot existent dans le dossier de sauvegarde
        $clip = dirname(__FILE__, 3) . "/data/" . $frigate->getCamera() . "/" . $frigate->getEventId() . "_clip.mp4";
        $snapshot = dirname(__FILE__, 3) . "/data/" . $frigate->getCamera() . "/" . $frigate->getEventId() . "_snapshot.jpg";
        $thumbnail = dirname(__FILE__, 3) . "/data/" . $frigate->getCamera() . "/" . $frigate->getEventId() . "_thumbnail.jpg";
        $preview = dirname(__FILE__, 3) . "/data/" . $frigate->getCamera() . "/" . $frigate->getEventId() . "_preview.gif";

        if (file_exists($clip)) {
          unlink($clip);
          log::add(__CLASS__, 'debug', "║ Clip MP4 supprimé pour l'événement " . $frigate->getEventId());
        }
        if (file_exists($snapshot)) {
          unlink($snapshot);
          log::add(__CLASS__, 'debug', "║ Snapshot JPG supprimé pour l'événement " . $frigate->getEventId());
        }
        if (file_exists($thumbnail)) {
          unlink($thumbnail);
          log::add(__CLASS__, 'debug', "║ Miniature JPG supprimée pour l'événement " . $frigate->getEventId());
        }
        if (file_exists($preview)) {
          unlink($preview);
          log::add(__CLASS__, 'debug', "║ GIF supprimé pour l'événement " . $frigate->getEventId());
        }

        $frigate->remove();
        log::add(__CLASS__, 'debug', "║ Événement " . $frigate->getEventId() . " supprimé de la base de données.");
      }
      return true;
    } else {
      return false;
    }
  }

  public static function deleteEvents($ids)
  {
    foreach ($ids as $id) {
      self::deleteEvent($id);
    }
    return true;
  }
  public static function deleteEvent($id, $all = false)
  {
    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:SUPPRESSION EVENEMENT:/fg: ═══════════════════");
    $frigate = frigate_events::byEventId($id);
    if (!empty($frigate) && isset($frigate[0])) {
      $isFavorite = $frigate[0]->getIsFavorite() ?? 0;
      if ($isFavorite == 1) {
        log::add(__CLASS__, 'debug', "║ Evènement " . $frigate[0]->getEventId() . " est un favori, il ne doit pas être supprimé de la DB.");
        message::add('frigate', __("L'évènement est un favori, il ne peut pas être supprimé de la DB.", __FILE__), null, null);
        return "Error 01";
      }

      $urlfrigate = self::getUrlFrigate();
      $resultURL = $urlfrigate . "/api/events/" . $id;

      if ($all) {
        self::deletecURL($resultURL);
        self::cleanDbEvent($id);
      } else {
        self::cleanDbEvent($id);
      }
      log::add(__CLASS__, 'debug', "╚════════════════════════════════════════════════════════");
      return "OK";
    } else {
      log::add(__CLASS__, 'debug', "╚════════════════════════════════════════════════════════");
      return "Error 02";
    }
  }
  public static function showEvents()
  {
    $result = [];
    $events = frigate_events::all();

    foreach ($events as $event) {
      $date = date("d-m-Y H:i:s", $event->getStartTime());
      $duree = round($event->getEndTime() - $event->getStartTime(), 0);
      $box = $event->getBox();
      $boxArray = is_array($box) ? $box : json_decode($box, true);
      $data = $event->getData();
      $dataArray = is_array($data) ? $data : json_decode($data, true);

      $result[] = array(
        "id" => $event->getId(),
        "img" => $event->getLasted(),
        "camera" => $event->getCamera(),
        "label" => $event->getLabel(),
        "box" => $boxArray,
        "date" => $date,
        "duree" => $duree,
        "startTime" => $event->getStartTime(),
        "endTime" => $event->getEndTime(),
        "snapshot" => $event->getSnapshot(),
        "clip" => $event->getClip(),
        "thumbnail" => $event->getthumbnail(),
        "hasSnapshot" => $event->getHasSnapshot(),
        "hasClip" => $event->getHasClip(),
        "eventId" => $event->getEventId(),
        "score" => $event->getScore(),
        "top_score" => $event->getTopScore(),
        "type" => $event->getType(),
        "isFavorite" => $event->getIsFavorite() ?? 0,
        "zones" => $event->getZones() ?? '',
        "description" => $dataArray['description'] ?? ''
      );
    }

    if (isset($result)) {
      usort($result, 'frigate::orderByDate');
    }

    return $result;
  }

  private static function orderByDate($a, $b)
  {
    $dateA = new DateTime($a['date']);
    $dateB = new DateTime($b['date']);
    return $dateB <=> $dateA;
  }

  private static function orderByScore($a, $b)
  {
    return $b['top_score'] <=> $a['top_score'];
  }

  public static function generateAllEqs()
  {

    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:CREATION DES EQUIPEMENTS:/fg: ═══════════════════");
    $urlfrigate = self::getUrlFrigate();
    if (empty($urlfrigate)) {
      log::add(__CLASS__, 'error', "║ Impossible de récupérer l'URL de Frigate.");
      log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-warning:ERREURS DANS LA CONFIGURATION:/fg: ═══════════════════");
      return false;
    }
    // récupérer le json de configuration
    $configurationArray = self::jsonFromUrl("http://" . $urlfrigate . "/api/config");
    if ($configurationArray == null) {
      log::add(__CLASS__, 'error', "║ Impossible de récupérer le fichier de configuration de Frigate.");
      log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-warning:ERREURS DANS LA CONFIGURATION:/fg: ═══════════════════");
      return false;
    }
    log::add(__CLASS__, 'debug', "║ Fichier de configuration : " . json_encode($configurationArray));

    frigate::generateEqEvents($configurationArray);
    frigate::generateEqStats();
    frigate::generateEqCameras($configurationArray);

    log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-success:FIN CREATION DES EQUIPEMENTS:/fg: ═══════════════════");
    return true;
  }
  public static function generateEqCameras($configurationArray)
  {

    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-success:CREATION DES CAMERAS:/fg: ═══════════════════");
    $urlfrigate = self::getUrlFrigate();
    $mqttCmds = isset($configurationArray['mqtt']['host']) && !empty($configurationArray['mqtt']['host']);
    $addToName = "";
    $create = 1;
    //  $stats = self::getcURL("create eqCameras", $resultURL);
    $defaultRoom = intval(config::byKey('parentObject', 'frigate', '', true));
    $n = 0;

    foreach ($configurationArray['cameras'] as $cameraName => $cameraConfig) {
      $exist = 0;
      $eqlogics = eqLogic::byObjectId($defaultRoom, false);
      foreach ($eqlogics as $eqlogic) {
        $name = $eqlogic->getName();
        // Utilisation de strcasecmp pour une comparaison insensible à la casse
        if (strcasecmp($name, $cameraName) === 0) {
          $exist = 1;
          break;
        }
      }
      if ($exist) {
        log::add(__CLASS__, 'debug', "║ L'équipement : " . json_encode($cameraName) . " existe dans la pièce : " . jeeObject::byId($defaultRoom)->getName());
        $addToName = " by frigate plugin";
      }
      // Recherche équipement caméra
      $frigate = eqLogic::byLogicalId("eqFrigateCamera_" . $cameraName, "frigate");
      if (!is_object($frigate)) {
        $n++;
        $urlLatest = "http://" . $urlfrigate . "/api/" . $name . "/latest.jpg?timestamp=0&bbox=0&zones=0&mask=0&motion=0&regions=0";
        $img = urlencode($urlLatest);

        $frigate = new frigate();
        $frigate->setName($cameraName . $addToName);
        $frigate->setEqType_name("frigate");
        $frigate->setConfiguration("name", $cameraName);
        $frigate->setConfiguration('panel', 0);
        $frigate->setConfiguration('ptz', 0);
        $frigate->setConfiguration('preset_max', 0);
        $frigate->setConfiguration('userName', "");
        $frigate->setConfiguration('password', "");
        $frigate->setConfiguration('bbox', 0);
        $frigate->setConfiguration('timestamp', 0);
        $frigate->setConfiguration('zones', 0);
        $frigate->setConfiguration('mask', 0);
        $frigate->setConfiguration('motion', 0);
        $frigate->setConfiguration('regions', 0);
        $frigate->setConfiguration('img', $img);
        $frigate->setConfiguration('cameraStreamAccessUrl', 'rtsp://' . $urlfrigate . ':8554/' . $cameraName);
        $frigate->setConfiguration('urlStream', "/plugins/frigate/core/ajax/frigate.proxy.php?url=" . $img);
        if ($defaultRoom) $frigate->setObject_id($defaultRoom);
        $frigate->setIsEnable(1);
        $frigate->setIsVisible(1);
        log::add(__CLASS__, 'debug', "║ L'équipement : " . json_encode($cameraName . $addToName) . " est créé.");
      } else {
        log::add(__CLASS__, 'debug', "║ L'équipement : " . json_encode($cameraName) . " n'est pas créé.");
      }
      $frigate->setLogicalId("eqFrigateCamera_" . $cameraName);
      $frigate->save();
      // commandes identique pour toutes les caméras
      log::add(__CLASS__, 'debug', "║ Création des commandes générales pour : " . json_encode($cameraName));
      self::createCamerasCmds($frigate->getId());
      // commandes MQTT s'il est configuré
      if ($mqttCmds) {
        log::add(__CLASS__, 'debug', "║ Création des commandes MQTT pour : " . json_encode($cameraName));
        $value["detect"] = isset($cameraConfig['detect']['enabled']) ? $cameraConfig['detect']['enabled'] : $configurationArray['detect']['enabled'];
        $value["recordings"] = isset($cameraConfig['record']['enabled']) ? $cameraConfig['record']['enabled'] : $configurationArray['record']['enabled'];
        $value["snapshots"] = isset($cameraConfig['snapshots']['enabled']) ? $cameraConfig['snapshots']['enabled'] : $configurationArray['snapshots']['enabled'];
        $value["motion"] = isset($cameraConfig['motion']['enabled']) ? $cameraConfig['motion']['enabled'] : $configurationArray['motion']['enabled'];

        self::createMqttCmds($frigate->getId(), $value);

        // verifier les objects configurés en détection
        $objectsGeneral = $configurationArray['objects']['track'];
        $objectsCamera = $cameraConfig['objects']['track'];
        // fusionner les 2 tableaux
        $objects = array_merge($objectsGeneral, $objectsCamera);
        // supprimer les entrées identique :
        $objects = array_unique($objects);
        // créé les commandes 
        foreach ($objects as $object) {
          self::createObjectDetectorCmd($frigate->getId(), $object);
        }
        // commande PTZ si onvif est configuré
        if (isset($cameraConfig['onvif']['host']) && !empty($cameraConfig['onvif']['host']) && $cameraConfig['onvif']['host'] !== '0.0.0.0') {
          log::add(__CLASS__, 'debug', "║ Création des commandes PTZ pour : " . json_encode($cameraName));
          self::createPTZcmds($frigate->getId());
          self::createPresetPTZcmds($frigate->getId());
          $frigate->setConfiguration("ptz", 1);
          $frigate->save();
        }
      }
      // commandes audio s'il est configuré
      $isAudioEnabledGlobally  = isset($configurationArray['audio']['enabled']) && !empty($configurationArray['audio']['enabled']);
      $isAudioEnabledForCamera = isset($cameraConfig['audio']['enabled_in_config']) && !empty($cameraConfig['audio']['enabled_in_config']);
      if ($isAudioEnabledGlobally  || $isAudioEnabledForCamera) {
        log::add(__CLASS__, 'debug', "║ Création des commandes audio pour : " . json_encode($cameraName));

        $valueAudio = $isAudioEnabledForCamera ? $cameraConfig['audio']['enabled'] : $configurationArray['audio']['enabled'];

        self::createAudioCmds($frigate->getId(), $valueAudio);
      }
    }
    message::add('frigate', 'Frigate : ' . $n . ' caméras créées, les commandes, évènements et statistiques sont mises à jour. Veuillez patienter...');
    // commandes de statisque
    self::getStats();
    // commandes des events
    self::getEvents(false, array(), 'end', null, 1);
    message::add('frigate', 'Mise à jour des commandes, évènements et statistiques terminée.');

    log::add(__CLASS__, 'debug', "╚════════════════════════ END CREATION DES CAMERAS ═══════════════════");
    return $n;
  }

  public static function restartFrigate()
  {
    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-warning:RESTART FRIGATE:/fg: ═══════════════════");
    self::publish_message('restart', '');
    log::add(__CLASS__, 'debug', "╚════════════════════════════════════════════════════════════");
  }
  public static function generateEqEvents($configurationArray)
  {
    $frigate = frigate::byLogicalId('eqFrigateEvents', 'frigate');
    $defaultRoom = intval(config::byKey('parentObject', 'frigate', '', true));
    if (!is_object($frigate)) {
      $frigate = new frigate();
      $frigate->setName('Events');
      $frigate->setEqType_name("frigate");
      $frigate->setLogicalId("eqFrigateEvents");
      if ($defaultRoom) $frigate->setObject_id($defaultRoom);
      $frigate->setIsEnable(1);
      $frigate->setIsVisible(1);
      $frigate->save();
    }
    // création des commandes d'activation des cron
    frigate::setCmdsCron();
    // création des commandes détection objects        
    $objectsGeneral = $configurationArray['objects']['track'];
    $objectsCamera = []; // tableau pour stocker les objets détectés des caméras

    // Parcourir toutes les caméras
    foreach ($configurationArray['cameras'] as $cameraId => $cameraConfig) {
      if (isset($cameraConfig['objects']['track'])) {
        $objectsCamera = array_merge($objectsCamera, $cameraConfig['objects']['track']);
      }
    }

    // Fusionner les objets généraux et ceux des caméras
    $objects = array_merge($objectsGeneral, $objectsCamera);

    // Supprimer les entrées en double
    $objects = array_unique($objects);

    // Créer les commandes pour chaque objet
    foreach ($objects as $object) {
      self::createObjectDetectorCmd($frigate->getId(), $object);
    }

    // Sauvegarder la configuration mise à jour
    $frigate->setConfiguration("objects", $objects);
    $frigate->save();
  }

  public static function generateEqStats()
  {
    $frigate = frigate::byLogicalId('eqFrigateStats', 'frigate');
    $defaultRoom = intval(config::byKey('parentObject', 'frigate', '', true));
    // créer l'équipement s'il n'existe pas.
    if (!is_object($frigate)) {
      $frigate = new frigate();
      $frigate->setName('Statistiques');
      $frigate->setEqType_name("frigate");
      $frigate->setLogicalId("eqFrigateStats");
      if ($defaultRoom) $frigate->setObject_id($defaultRoom);
      $frigate->setIsEnable(1);
      $frigate->setIsVisible(1);
      $frigate->save();
    }

    // Création de la commande restart Frigate si elle n'existe pas
    $eqlogicId = $frigate->getId();
    self::createEqStatsCmd($eqlogicId);
  }
  private static function createCmd($eqLogicId, $name, $subType, $unite, $logicalId, $genericType, $isVisible = 1, $infoCmd = null, $historized = 0, $type = "info")
  {
    $cmd = cmd::byEqLogicIdCmdName($eqLogicId, $name);

    if (!is_object($cmd)) {
      $cmd = new frigateCmd();
      $cmd->setLogicalId($logicalId);
      $cmd->setEqLogic_id($eqLogicId);
      $cmd->setName($name);
      $cmd->setType($type);
      $cmd->setSubType($subType);
      $cmd->setGeneric_type($genericType);
      $cmd->setIsVisible($isVisible);
      $cmd->setIsHistorized($historized);
      $cmd->setUnite($unite);
      if (is_object($infoCmd) && $type == 'action') {
        $cmd->setValue($infoCmd->getId());
        $cmd->setTemplate('dashboard', 'core::toggle');
        $cmd->setTemplate('mobile', 'core::toggle');
      }
      // $cmd->save();
    }
    return $cmd;
  }
  public static function createAndRefreshURLcmd($eqlogicId, $url)
  {
    $cmd = self::createCmd($eqlogicId, "URL", "string", "", "info_url", "");
    // $cmd->save();
    $cmd->event($url);
    // $cmd->save();
  }

  public static function createAudioCmds($eqlogicId, $value = 0)
  {
    $infoCmd = self::createCmd($eqlogicId, "audio Etat", "binary", "", "info_audio", "JEEMATE_CAMERA_AUDIO_STATE", 0);
    //On vérifie la valeur présente et mets à jour que dans le cas ou elle est différente
    $currentState = $infoCmd->execCmd();
    if ($currentState !== $value) {
      $infoCmd->event($value);
    }
    // $infoCmd->save();

    // commande action
    $cmd = self::createCmd($eqlogicId, "audio off", "other", "", "action_stop_audio", "JEEMATE_CAMERA_AUDIO_SET_OFF", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "audio on", "other", "", "action_start_audio", "JEEMATE_CAMERA_AUDIO_SET_ON", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "audio toggle", "other", "", "action_toggle_audio", "JEEMATE_CAMERA_AUDIO_SET_TOGGLE", 0, $infoCmd, 0, "action");
    // $cmd->save();
  }
  public static function createCamerasCmds($eqlogicId)
  {
    $eqlogic = eqLogic::byId($eqlogicId);
    // Récupération des URLs externes et internes
    $urlJeedom = network::getNetworkAccess('external');
    if ($urlJeedom == "") {
      $urlJeedom = network::getNetworkAccess('internal');
    }
    $url = config::byKey('URL', 'frigate');
    $port = config::byKey('port', 'frigate');
    $name = $eqlogic->getConfiguration('name');

    $cmd = self::createCmd($eqlogicId, "Créer un évènement", "message", "", "action_make_api_event", "CAMERA_TAKE", 1, null, 0, "action");
    // $cmd->save();
    $infoCmd = self::createCmd($eqlogicId, "URL image", "string", "", "info_url_capture", "", 0, null, 0);
    // $infoCmd->save();
    $cmd = self::createCmd($eqlogicId, "Capturer une image", "other", "", "action_create_snapshot", "", 1, $infoCmd, 0, "action");
    // $cmd->save();

    // commande des liens rtsp et snapshot live
    $infoCmd = self::createCmd($eqlogicId, "RTSP", "string", "", "link_rtsp", "", 0, null, 0);
    // $infoCmd->save();
    $value = $infoCmd->execCmd();
    if (!isset($value) || $value == null || $value == '') {
      $link = $eqlogic->getConfiguration("cameraStreamAccessUrl");
      $infoCmd->event($link);
      // $infoCmd->save();
    }
    $infoCmd = self::createCmd($eqlogicId, "SNAPSHOT LIVE", "string", "", "link_snapshot", "CAMERA_URL", 0, null, 0);
    $infoCmd->setGeneric_type("CAMERA_URL");
    // $infoCmd->save();
    $value = $infoCmd->execCmd();
    if (!isset($value) || $value == null || $value == '') {
      $link = $urlJeedom . "/plugins/frigate/core/ajax/frigate.proxy.php?url=http://" . $url . ":" . $port . "/api/" . $name . "/latest.jpg";
      $infoCmd->event($link);
      // $infoCmd->save();
    }

    // commande action enable/disable camera
    $infoCmd = self::createCmd($eqlogicId, "(Config) Etat activation caméra", "binary", "", "enable_camera", "", 0);
    // $infoCmd->save();
    $value = $infoCmd->execCmd();
    if (!isset($value) || $value == null || $value == '') {
      $infoCmd->event(1);
      // $infoCmd->save();
    }
    $cmd = self::createCmd($eqlogicId, "(Config) Désactiver caméra", "other", "", "action_disable_camera", "", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "(Config) Activer caméra", "other", "", "action_enable_camera", "", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "(Config) Inverser activation caméra", "other", "", "action_toggle_camera", "", 0, $infoCmd, 0, "action");
    // $cmd->save();
  }

  public static function createObjectDetectorCmd($eqlogicId, $object)
  {
    $infoCmd = self::createCmd($eqlogicId, "Détection " . $object, "binary", "", "info_detect_" . $object, "JEEMATE_CAMERA_DETECT_EVENT_STATE", 0);
    // $infoCmd->save();
    $infoCmd = self::createCmd($eqlogicId, "Détection tout", "binary", "", "info_detect_all", "JEEMATE_CAMERA_DETECT_EVENT_STATE", 0);
    // $infoCmd->save();
  }

  public static function createMQTTcmds($eqlogicId, $value)
  {
    $infoCmd = self::createCmd($eqlogicId, "detect Etat", "binary", "", "info_detect", "JEEMATE_CAMERA_DETECT_STATE", 0);
    //On vérifie la valeur présente et mets à jour que dans le cas ou elle est différente
    $currentState = $infoCmd->execCmd();
    $stateValue = $value["detect"];
    if ($currentState !== $stateValue) {
      $infoCmd->event($stateValue);
    }
    // $infoCmd->save();

    // commande action
    $cmd = self::createCmd($eqlogicId, "detect off", "other", "", "action_stop_detect", "JEEMATE_CAMERA_DETECT_SET_OFF", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "detect on", "other", "", "action_start_detect", "JEEMATE_CAMERA_DETECT_SET_ON", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "detect toggle", "other", "", "action_toggle_detect", "JEEMATE_CAMERA_DETECT_SET_TOGGLE", 0, $infoCmd, 0, "action");
    // $cmd->save();


    $infoCmd = self::createCmd($eqlogicId, "recordings Etat", "binary", "", "info_recordings", "JEEMATE_CAMERA_NVR_STATE", 0);
    //On vérifie la valeur présente et mets à jour que dans le cas ou elle est différente
    $currentState = $infoCmd->execCmd();
    $stateValue = $value["recordings"];
    if ($currentState !== $stateValue) {
      $infoCmd->event($stateValue);
    }
    // $infoCmd->save();

    // commande action
    $cmd = self::createCmd($eqlogicId, "recordings off", "other", "", "action_stop_recordings", "JEEMATE_CAMERA_NVR_SET_OFF", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "recordings on", "other", "", "action_start_recordings", "JEEMATE_CAMERA_NVR_SET_ON", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "recordings toggle", "other", "", "action_toggle_recordings", "JEEMATE_CAMERA_NVR_SET_TOGGLE", 0, $infoCmd, 0, "action");
    // $cmd->save();


    $infoCmd = self::createCmd($eqlogicId, "snapshots Etat", "binary", "", "info_snapshots", "JEEMATE_CAMERA_SNAPSHOT_STATE", 0);
    //On vérifie la valeur présente et mets à jour que dans le cas ou elle est différente
    $currentState = $infoCmd->execCmd();
    $stateValue = $value["snapshots"];
    if ($currentState !== $stateValue) {
      $infoCmd->event($stateValue);
    }
    // $infoCmd->save();


    // commande action
    $cmd = self::createCmd($eqlogicId, "snapshots off", "other", "", "action_stop_snapshots", "JEEMATE_CAMERA_SNAPSHOT_SET_OFF", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "snapshots on", "other", "", "action_start_snapshots", "JEEMATE_CAMERA_SNAPSHOT_SET_ON", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "snapshots toggle", "other", "", "action_toggle_snapshots", "JEEMATE_CAMERA_SNAPSHOT_SET_TOGGLE", 0, $infoCmd, 0, "action");
    // $cmd->save();

    $infoCmd = self::createCmd($eqlogicId, "détection en cours", "binary", "", "info_detectNow", "JEEMATE_CAMERA_DETECT_EVENT_STATE", 1);
    // $infoCmd->save();
    $valueDetectNow = $infoCmd->execCmd();
    if (!isset($valueDetectNow) || $valueDetectNow == null || $valueDetectNow == '') {
      $infoCmd->event(1);
      // $infoCmd->save();
    }
    $infoCmd = self::createCmd($eqlogicId, "motion Etat", "binary", "", "info_motion", "JEEMATE_CAMERA_SNAPSHOT_STATE", 0);
    //On vérifie la valeur présente et mets à jour que dans le cas ou elle est différente
    $currentState = $infoCmd->execCmd();
    $stateValue = $value["motion"];
    if ($currentState !== $stateValue) {
      $infoCmd->event($stateValue);
    }
    // $infoCmd->save();

    // commande action
    $cmd = self::createCmd($eqlogicId, "motion off", "other", "", "action_stop_motion", "JEEMATE_CAMERA_SNAPSHOT_SET_OFF", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "motion on", "other", "", "action_start_motion", "JEEMATE_CAMERA_SNAPSHOT_SET_ON", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "motion toggle", "other", "", "action_toggle_motion", "JEEMATE_CAMERA_SNAPSHOT_SET_TOGGLE", 0, $infoCmd, 0, "action");
    // $cmd->save();
  }

  public static function createHTTPcmd($eqlogicId, $name, $link)
  {
    log::add("frigate", 'debug', '║ création de la commande ' . $name . ' pour ' . $eqlogicId . ' liens : ' . $link);

    $infoCmd = self::createCmd($eqlogicId, "Etat HTTP command", "string", "", "info_http", "", 0, null, 0, "info");
    // $infoCmd->save();

    // commande action
    $cmd = self::createCmd($eqlogicId, $name, "other", "", "action_http", "", 0, $infoCmd, 0, "action");
    // $cmd->save();
    log::add("frigate", 'debug', '║ commande crée');
    $cmd->setConfiguration("request", $link);
    // $cmd->save();
    log::add("frigate", 'debug', '║ commande mise à jour');
    return true;
  }
  public static function createEqStatsCmd($eqlogicId)
  {
    $cmd = self::createCmd($eqlogicId, "redémarrer frigate", "other", "", "action_restart", "GENERIC_ACTION", 1, "", 0, "action");
    // $cmd->save();

    $cmd = self::createCmd($eqlogicId, "status serveur", "binary", "", "info_status", "", 0, null, 0);
    // $cmd->save();
    // seulement en MQTT
    if (class_exists('mqtt2')) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['launchable'] === 'ok') {
        $cmd = self::createCmd($eqlogicId, "Disponibilité", "string", "", "info_available", "", 0, null, 0, "info");
        // $cmd->save();
      }
    }
    return true;
  }
  public static function editHTTP($cmdId, $link)
  {
    $cmd = cmd::byid($cmdId);
    $cmd->setConfiguration("request", $link);
    // $cmd->save();
    log::add("frigate", 'debug', '║ commande mise à jour');
    return true;
  }

  public static function createPTZdebug($eqlogicId)
  {
    log::add("frigate", 'debug', '║ création des commandes PTZ en mode DEBUG pour ' . $eqlogicId);
    self::createPTZcmds($eqlogicId);
    self::createPresetPTZcmds($eqlogicId, 1);
    self::createAudioCmds($eqlogicId);
    log::add("frigate", 'debug', '║ penser à supprimer les commandes après le debug... ');
  }
  private static function createPTZcmds($eqlogicId)
  {
    log::add("frigate", 'debug', '║ création des commandes PTZ move et zoom pour ' . $eqlogicId);
    // commande action
    $cmd = self::createCmd($eqlogicId, "PTZ move left", "other", "", "action_ptz_left", "CAMERA_LEFT", 1, "", 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "PTZ move right", "other", "", "action_ptz_right", "CAMERA_RIGHT", 1, "", 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "PTZ move up", "other", "", "action_ptz_up", "CAMERA_UP", 1, "", 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "PTZ move down", "other", "", "action_ptz_down", "CAMERA_DOWN", 1, "", 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "PTZ move stop", "other", "", "action_ptz_stop", "CAMERA_STOP", 0, "", 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "PTZ zoom in", "other", "", "action_ptz_zoom_in", "CAMERA_ZOOM", 1, "", 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($eqlogicId, "PTZ zoom out", "other", "", "action_ptz_zoom_out", "CAMERA_DEZOOM", 1, "", 0, "action");
    // $cmd->save();

    return true;
  }

  private static function createPresetPTZcmds($eqlogicId, $debug = false)
  {
    log::add("frigate", 'debug', '║ Création des commandes Preset PTZ pour ' . $eqlogicId);
    $eqlogic = eqLogic::byId($eqlogicId);
    $camera = $eqlogic->getConfiguration("name");
    if (!$debug) {
      $presets = self::getPresets($camera);
    } else {
      $presets = [
        "features" => [
          "pt",
          "zoom",
          "pt-r",
          "zoom-r",
          "zoom-a"
        ],
        "name" => "entree",
        "presets" => [
          "preset1",
          "preset2",
          "preset3",
          "preset4",
          "preset5",
          "preset6",
          "preset7",
          "preset8",
          "preset9",
          "preset10",
          "preset11",
          "preset12",
          "preset13"
        ]
      ];
    }

    $presetList = $presets['presets'];

    if (!is_array($presetList) || count($presetList) == 0) {
      return;
    } else {
    }

    $presetMaxforEqloc = $eqlogic->getConfiguration("presetMax") ?? 0;
    $presetMaxforall = config::byKey("presetMax", "frigate") ?? 0;
    if ($presetMaxforEqloc > 0) {
      $max = $presetMaxforEqloc;
    } else {
      $max = $presetMaxforall;
    }
    if ($max == 0) {
      return;
    }
    if ($max > 10) {
      $max = 10;
    }

    // Création des commandes jusqu'au nombre max de presets configurés
    for ($i = 0; $i < $max && $i < count($presetList); $i++) {
      $presetName = $presetList[$i];
      log::add(__CLASS__, 'debug', "║ PRESET CREE . " . $presetName); // Utiliser le nom du preset correspondant
      // Vérifier que le nom du preset est une chaîne de caractères valide
      if (is_string($presetName) && !empty($presetName)) {
        $cmd = self::createCmd($eqlogicId, $presetName, "other", "", "action_preset_" . $i, "CAMERA_PRESET", 1, "", 0, "action");
        // $cmd->save();
      }
    }
  }


  public static function setCmdsCron()
  {
    $frigate = frigate::byLogicalId('eqFrigateEvents', 'frigate');
    if (!is_object($frigate)) {
      return; // frigate n'existe pas
    }
    // Création des commandes Crons pour l'equipement général
    // commande infos
    $infoCmd = self::createCmd($frigate->getId(), "Cron etat", "binary", "", "info_Cron", "LIGHT_STATE", 0);
    // $infoCmd->save();
    $value = $infoCmd->execCmd();
    if (!isset($value) || $value == null || $value == '') {
      $infoCmd->event(1);
      // $infoCmd->save();
    }
    // commandes actions
    $cmd = self::createCmd($frigate->getId(), "Cron off", "other", "", "action_stopCron", "LIGHT_OFF", 1, $infoCmd, 0, "action");
    // $cmd->save();
    $cmd = self::createCmd($frigate->getId(), "Cron on", "other", "", "action_startCron", "LIGHT_ON", 1, $infoCmd, 0, "action");
    // $cmd->save();
  }

  public static function majEventsCmds($event)
  {
    log::add(__CLASS__, 'debug', "╔════════════════════════ :fg-warning:MAJ EVENTS:/fg: ═══════════════════");
    $eqlogicIds = [];
    $cameraActionsExist = false;
    // Maj des commandes de l'équipement events général
    $frigate = frigate::byLogicalId('eqFrigateEvents', 'frigate');
    if (is_object($frigate)) {
      $eqlogicIds[] = $frigate->getId();
    }
  
    // Recherche et création equipement caméra    
    $cameraName = $event->getCamera();
    $eqCamera = eqLogic::byLogicalId("eqFrigateCamera_" . $cameraName, "frigate");
    if (is_object($eqCamera)) {
      $eqlogicIds[] = $eqCamera->getId();
      // Récupération de la configuration des actions de la caméra
      $cameraActions = $eqCamera->getConfiguration('actions');
      if (is_array($cameraActions) && isset($cameraActions[0])) {
        $cameraAction = $cameraActions[0];
      } else {
        $cameraAction = null;
      }
      // Vérifier si la liste d'actions est vide
      $cameraActionsExist = !empty($cameraAction);
      self::eventAdd($event, $eqCamera->getId());
    }

    // verifier si la date de l'event est le plus récent
    
    $eventDate = $event->getStartTime();
    // récupérer info de la commande timestamp
    $cmdtimestamp = cmd::byEqLogicIdCmdName($eqCamera->getId(), "timestamp");
    $timestamp = $cmdtimestamp->execCmd();
    $cmdtype = cmd::byEqLogicIdCmdName($eqCamera->getId(), "type");
    if (is_object($cmdtype)) {
      $type = $cmdtype->execCmd();
    } else {
      $type = "";
    }
    // Vérifier si le timestamp est supérieur ou égale à la date de l'événement et le type end
    if (($timestamp >= $eventDate) && ($type === "end")) {
      log::add(__CLASS__, 'debug', "║ ACTION: L'évènement est plus ancien que le dernier évènement enregistré.");
      return;
    }

  //  $eqCamera->getId();

    if ($cameraActionsExist) {
      log::add(__CLASS__, 'debug', "║ ACTION: Vérification des actions caméra.");

      // Vérifier si toutes les actions sont désactivées
      $allActionsDisabled = true;
      foreach ($cameraAction as $action) {
        // Vérifier si l'action est activée
        $enable = $action['options']['enable'] ?? false;

        if ($enable) {
          // Si au moins une action est activée, on met à jour $allActionsDisabled à false
          $allActionsDisabled = false;
          log::add(__CLASS__, 'debug', "║ ACTION: Une action caméra est activée.");
          break;
        }
      }

      // Si toutes les actions sont désactivées, on met à jour $cameraActionsExist à false
      if ($allActionsDisabled) {
        $cameraActionsExist = false;
        log::add(__CLASS__, 'debug', "║ ACTION: Toutes les actions caméra sont désactivées.");
      }
    } else {
      log::add(__CLASS__, 'debug', "║ ACTION: Aucune action configurée.");
    }

    // Vérification des actions caméra existantes
    // Si la liste d'actions n'est pas vide et qu'au moins une action est activée
    // verifier si l'équipement event est autorisé a executer des actions
    $autorizeAction = $frigate->getConfiguration('autorizeActions');
    if ($autorizeAction == 1) {
      log::add(__CLASS__, 'debug', "║ ACTION: Les actions sont autorisées pour l'équipement Events (ID: " . $frigate->getId() . ").");
    } else {
      log::add(__CLASS__, 'debug', "║ ACTION: Les actions sont désactivées pour l'équipement Events (ID: " . $frigate->getId() . ").");
    }
    if ($cameraActionsExist && $autorizeAction) {
      log::add(__CLASS__, 'debug', "║ ACTION: Exécution des actions pour la caméra (ID: " . $eqCamera->getId() . ").");
      self::executeActionNewEvent($eqCamera->getId(), $event);
      log::add(__CLASS__, 'debug', "║ ACTION: Exécution des actions pour l'équipement Events (ID: " . $frigate->getId() . ").");
      self::executeActionNewEvent($frigate->getId(), $event);
    } elseif ($cameraActionsExist) {
      // Si les actions caméra sont activées mais que l'équipement event n'est pas autorisé à exécuter des actions
      log::add(__CLASS__, 'debug', "║ ACTION: Exécution des actions pour la caméra (ID: " . $eqCamera->getId() . ").");
      self::executeActionNewEvent($eqCamera->getId(), $event);
    } else {
      // Sinon, on exécute les actions suivantes
      log::add(__CLASS__, 'debug', "║ ACTION: Aucune action caméra activée, exécution des actions pour l'équipement Events (ID: " . $frigate->getId() . ").");
      self::executeActionNewEvent($frigate->getId(), $event);
    }



    foreach ($eqlogicIds as $eqlogicId) {
      // Creation des commandes infos
      $cmd = self::createCmd($eqlogicId, "caméra", "string", "", "info_camera", "GENERIC_INFO", 0, null, 0);
      $cmd->event($event->getCamera());
      // $cmd->save();

      $cmd = self::createCmd($eqlogicId, "label", "string", "", "info_label", "JEEMATE_CAMERA_DETECT_TYPE_STATE", 0, null, 0);
      $cmd->event($event->getLabel());
      // $cmd->save();

      $cmd = self::createCmd($eqlogicId, "clip disponible", "binary", "", "info_clips", "");
      $cmd->event($event->getHasClip());
      // $cmd->save();

      $cmd = self::createCmd($eqlogicId, "snapshot disponible", "binary", "", "info_snapshot", "");
      $cmd->event($event->getHasSnapshot());
      // $cmd->save();

      $cmd = self::createCmd($eqlogicId, "top score", "numeric", "%", "info_topscore", "GENERIC_INFO");
      $cmd->event($event->getTopScore());
      // $cmd->save();

      $cmd = self::createCmd($eqlogicId, "score", "numeric", "%", "info_score", "");
      $cmd->event($event->getScore());
      // $cmd->save();

      $cmd = self::createCmd($eqlogicId, "zones", "string", "", "info_zones", "", 0, null, 0);
      $cmd->event($event->getZones());
      // $cmd->save();

      $cmd = self::createCmd($eqlogicId, "description", "string", "", "info_description", "", 0, null, 0);
      $data = $event->getData();
      $dataArray = is_array($data) ? $data : json_decode($data, true);
      $description = isset($dataArray['description']) ? $dataArray['description'] : "";
      $cmd->event($description);
      // $cmd->save();

      $cmd = self::createCmd($eqlogicId, "id", "string", "", "info_id", "", 0, null, 0);
      $cmd->event($event->getEventId());
      // $cmd->save();

      $cmd = self::createCmd($eqlogicId, "type", "string", "", "info_type", "", 0, null, 0);
      $cmd->event($event->getType());

      $cmd = self::createCmd($eqlogicId, "timestamp", "numeric", "", "info_timestamp", "GENERIC_INFO");
      $cmd2 = self::createCmd($eqlogicId, "durée", "numeric", "sc", "info_duree", "GENERIC_INFO");
      $cmd->event($event->getStartTime());
      // $cmd->save();
      if ($event->getEndTime() != NULL) {
        $value = round($event->getEndTime() - $event->getStartTime(), 0);
      } else {
        $value = 0;
      }
      $cmd2->event($value);
      // $cmd2->save();


      $cmd = self::createCmd($eqlogicId, "URL snapshot", "string", "", "info_url_snapshot", "", 0, null, 0);
      $cmd->event($event->getSnapshot());
      // $cmd->save();


      $cmd = self::createCmd($eqlogicId, "URL clip", "string", "", "info_url_clip", "", 0, null, 0);
      $cmd->event($event->getClip());
      // $cmd->save();


      $cmd = self::createCmd($eqlogicId, "URL thumbnail", "string", "", "info_url_thumbnail", "", 0, null, 0);
      $cmd->event($event->getThumbnail());
      // $cmd->save();
    }
  }

  public static function majStatsCmds($stats, $mqtt = false)
  {
    // Statistiques pour chaque eqLogic caméras
    // Mise à jour des statistiques des caméras
    foreach ($stats['cameras'] as $cameraName => $cameraStats) {
      // Recherche equipement caméra
      $eqCamera = eqLogic::byLogicalId("eqFrigateCamera_" . $cameraName, "frigate");
      if (is_object($eqCamera)) {
        $eqlogicCameraId = $eqCamera->getId();
        foreach ($cameraStats as $key => $value) {
          // Créer ou récupérer la commande
          $cmd = self::createCmd($eqlogicCameraId, $key, "numeric", "", "cameras_" . $key, "GENERIC_INFO");
          // Enregistrer la valeur de l'événement
          $cmd->event($value);
          // $cmd->save();
          // Mise à jour de l'activité de la caméra en fonction de pid
          if ($key === 'pid') {
            $cameraEnabled = $value != 0;
            $cmd = $eqCamera->getCmd(null, 'enable_camera');
            if (is_object($cmd)) {
              $cmd->event($cameraEnabled);
            } else {
              log::add(__CLASS__, 'debug', "L'équipement camera " . $cameraName . " n'a pas de commande enable_camera.");
            }
          }
        }
      } else {
        log::add(__CLASS__, 'debug', "L'équipement camera " . $cameraName . " n'existe pas.");
      }
    }

    // Statistiques pour eqLogic statistiques générales
    $frigate = frigate::byLogicalId('eqFrigateStats', 'frigate');
    $eqlogicId = $frigate->getId();

    // Mise à jour des statistiques des détecteurs
    foreach ($stats['detectors'] as $detectorName => $detectorStats) {
      foreach ($detectorStats as $key => $value) {
        // Créer un nom de commande en combinant le nom du détecteur et la clé
        $cmdName = $detectorName . '_' . $key;
        // Créer ou récupérer la commande
        $cmd = self::createCmd($eqlogicId, $cmdName, "numeric", "", "detectors_" . $key, "GENERIC_INFO");
        // Enregistrer la valeur de l'évènement
        $cmd->event($value);
        // $cmd->save();

        if ($detectorName === "pid") {
          $cmdCpu = self::createCmd($eqlogicId, $detectorName . '_cpu', "numeric", "", "detectors_cpu", "GENERIC_INFO");
          $cmdCpu->event($stats['cpu_usages'][$value]['cpu']);
          // $cmdCpu->save();
          $cmdMem = self::createCmd($eqlogicId, $detectorName . '_memory', "numeric", "", "detectors_memory", "GENERIC_INFO");
          $cmdMem->event($stats['cpu_usages'][$value]['mem']);
          // $cmdMem->save();
        }
      }
    }

    // Mise à jour des usages GPU
    foreach ($stats['gpu_usages'] as $gpuName => $gpuStats) {
      foreach ($gpuStats as $key => $value) {
        // Créer un nom de commande en combinant le nom du GPU et la clé
        $cmdName = $gpuName . '_' . $key;
        // Créer ou récupérer la commande
        $cmd = self::createCmd($eqlogicId, $cmdName, "numeric", "", "gpu_" . $key, "GENERIC_INFO");
        // Enregistrer la valeur de l'événement
        $cmd->event($value);
        // $cmd->save();
      }
    }

    // Mise à jour des usages CPU
    if (isset($stats['cpu_usages']['frigate.full_system'])) {
      foreach ($stats['cpu_usages']['frigate.full_system'] as $key => $value) {
        $cmdName = 'Full system_' . $key;
        $cmd = self::createCmd($eqlogicId, $cmdName, "numeric", "", "cpu_" . $key, "GENERIC_INFO");
        $cmd->event($value);
        // $cmd->save();
      }
    } else {
      log::add('frigate', 'debug', "La clé 'frigate.full_system' n'existe pas dans cpu_usages.");
    }

    // Mise a jour storage
    // Filtrer les clés qui se terminent par "recordings"
    $recordingsPaths = array_filter($stats['service']['storage'], function ($key) {
      return preg_match('/recordings$/', $key);
    }, ARRAY_FILTER_USE_KEY);
    foreach ($recordingsPaths as $key => $value) {
      // Liste des valeurs à enregistrer
      $metrics = ['total', 'used', 'free'];

      foreach ($metrics as $metric) {
        if (isset($value[$metric])) {
          $cmdName = 'Recordings_' . ucfirst($metric); // Ex: Recordings_media_frigate_recordings_Total
          // Créer ou récupérer la commande
          $cmd = self::createCmd($eqlogicId, $cmdName, "numeric", "", $cmdName, "GENERIC_INFO");
          // Enregistrer la valeur correspondante
          $cmd->event($value[$metric]);
          // $cmd->save();
        }
      }
    }

    // Créer ou récupérer la commande version Frigate
    $version = strstr($stats['service']['version'], '-', true);

    $cmd = self::createCmd($eqlogicId, "version", "string", "", "info_version", "", 0, null, 0);
    // Enregistrer la valeur de l'événement
    $cmd->event($version);
    // $cmd->save();
    if ($version != config::byKey('frigate_version', 'frigate')) {
      config::save('frigate_version', $version, 'frigate');
    }

    // Créer ou récupérer la valeur de uptime en secondes
    $uptime = $stats['service']['uptime'] ?? 0;
    $cmd = self::createCmd($eqlogicId, "uptime", "numeric", "", "info_uptime", "", 0, null, 0);
    // Enregistrer la valeur de l'événement
    $cmd->event($uptime);
    // $cmd->save();

    // Créer ou récupérer la valeur de uptime en format lisible
    $uptimeTimestamp = time() - $uptime;
    $uptimeDate = date("Y-m-d H:i:s", $uptimeTimestamp);
    $cmd = self::createCmd($eqlogicId, "uptimeDate", "string", "", "info_uptimeDate", "", 0, null, 0);
    // Enregistrer la valeur de l'événement
    $cmd->event($uptimeDate);
    // $cmd->save();
  }

  private static function executeActionNewEvent($eqLogicId, $event)
  {
    // Récupération des URLs externes et internes
    $urlJeedom = network::getNetworkAccess('external');
    if ($urlJeedom == "") {
      $urlJeedom = network::getNetworkAccess('internal');
    }
    $getPreview = str_replace("snapshot.jpg", "preview.gif", $event->getSnapshot());
    // Initialisation des variables d'événement
    $eventId = $event->getEventId();
    $hasClip = $event->getHasClip();
    $hasSnapshot = $event->getHasSnapshot();
    $topScore = $event->getTopScore();
    $clip = $urlJeedom . $event->getClip();
    $snapshot = $urlJeedom . $event->getSnapshot();
    $thumbnail = $urlJeedom . $event->getThumbnail();
    $preview = $urlJeedom . $getPreview;
    $clipPath = "/var/www/html" . $event->getClip();
    $snapshotPath = "/var/www/html" . $event->getSnapshot();
    $thumbnailPath = "/var/www/html" . $event->getThumbnail();
    $previewPath = "/var/www/html" . $getPreview;
    $camera = $event->getCamera();
    $cameraId = eqLogic::byLogicalId("eqFrigateCamera_" . $camera, "frigate")->getId();
    $label = $event->getLabel();
    $data = $event->getData();
    $dataArray = is_array($data) ? $data : json_decode($data, true);
    $description = isset($dataArray['description']) ? $dataArray['description'] : "";
    $zones = $event->getZones();
    $score = $event->getScore();
    $type = $event->getType();
    $start = date("d-m-Y H:i:s", $event->getStartTime());
    $end = $event->getEndTime() ? date("d-m-Y H:i:s", $event->getEndTime()) : $start;
    $duree = $event->getEndTime() ? round($event->getEndTime() - $event->getStartTime(), 0) : 0;
    $time = date("H:i");
    $jeemate = $eventId . ";;start=" . $start . ";;end=" . $end . ";;camera=" . $camera . ";;label=" . $label . ";;zones=" . $zones . ";;topScore=" . $topScore . ";;type=" . $type . ";;snapshot=" . $snapshot . ";;thumbnail=" . $thumbnail . ";;clip=" . $clip;
    $conditionIsActived = false;
    $eqLogic = eqLogic::byId($eqLogicId);

    // Vérification de la condition d'exécution
    $conditionIf = $eqLogic->getConfiguration('conditionIf');
    if ($conditionIf && jeedom::evaluateExpression($conditionIf)) {
      $conditionIsActived = true;
    }

    $actionsArray = $eqLogic->getConfiguration('actions');

    // Vérifie que $cameraActions est bien un tableau et qu'il contient un élément à l'indice 0
    if (is_array($actionsArray) && isset($actionsArray[0])) {
      $actions = $actionsArray[0];
    } else {
      // Gérer le cas où $cameraActions n'est pas un tableau ou est vide
      // Par exemple, loguer un message d'erreur ou initialiser $cameraAction avec une valeur par défaut
      $actions = null;
      // Ou afficher un message d'erreur
    }
    if (is_array($actions)) {
      log::add("frigate_Actions", 'info', "╔═════════════════════════════ :b:START " . $type . ":/b: ═══════════════════════════════════╗");
      log::add("frigate_Actions", 'info',  "║ Caméra : " . $eqLogic->getHumanName());
      log::add("frigate_Actions", 'info',  "║ HasSnapshot : " . $hasSnapshot);
      log::add("frigate_Actions", 'info',  "║ HasClip : " . $hasClip);
      log::add("frigate_Actions", 'info',  "║ Label : " . $label);
      foreach ($actions as $action) {
        log::add("frigate_Actions", 'info', "╠════════════════════════════════════");

        // Vérifier la condition d'éxècution
        $options = $action['options'];
        $actionForced = $action['options']['actionForced'] ?? false;

        if (!$conditionIsActived) {
          log::add("frigate_Actions", 'info', "║ Commande en cour d'éxècution.");
        } elseif ($conditionIsActived && $actionForced) {
          log::add("frigate_Actions", 'info', "║ Commande en cour d'éxècution car la condition principale est ignorée");
        } else {
          log::add("frigate_Actions", 'info', "║ Action non exécutées car la condition principale " . $conditionIf .  " est vrai.");
          continue;
        }

        // vérifier si la commande est activée
        $enable = $action['options']['enable'] ?? false;
        if (!$enable) {
          log::add("frigate_Actions", 'info', "║ Commande désactivée");
          continue;
        }

        // vérifier si la condition de l'action est remplie
        $actionConditionIsActived = true;
        $actionCondition = $action['actionCondition'];
        if ($actionCondition != "" && !jeedom::evaluateExpression($actionCondition)) {
          $actionConditionIsActived = false;
        }
        log::add("frigate_Actions", 'info', "║ Condition de l'action  : " . $actionCondition . ", etat : " . json_encode($actionConditionIsActived));

        if (!$actionConditionIsActived) {
          log::add("frigate_Actions", 'info', "║ Condition de l'action non remplie : " . $actionCondition . ", l'action sera ignorée.");
          continue;
        }

        log::add("frigate_Actions", 'info',  "║ Action : " . json_encode($action));
        $cmd = $action['cmd'];
        $cmdLabelName = $action['cmdLabelName'] ?: "all";
        $cmdTypeName = $action['cmdTypeName'] ?: "end";
        $cmdZoneName = $action['cmdZoneName'] ?: "all";
        $cmdZoneEndName = $action['cmdZoneEndName'] ?: "";

        // Convertir les chaînes en tableaux
        $cmdLabels = array_map(fn($s) => self::cleanString(trim($s)), explode(',', $cmdLabelName));
        $cmdZones = array_map(fn($s) => self::cleanString(trim($s)), explode(',', $cmdZoneName));
        $cmdZonesEnd = array_map(fn($s) => self::cleanString(trim($s)), explode(',', $cmdZoneEndName));
        $eventZones = array_map(fn($s) => self::cleanString(trim($s)), explode(',', $zones));
        $cmdTypes = array_map(fn($s) => self::cleanString(trim($s)), explode(',', $cmdTypeName));

        // Ajouter aux tableaux si nécessaire une valeur par défaut
        if (empty($cmdLabels)) $cmdLabels[] = "all";
        if (empty($cmdZones)) $cmdZones[] = "all";
        if (empty($cmdTypes)) $cmdTypes[] = "end";
        log::add("frigate_Actions", 'info', "║ Labels configurés : " . json_encode($cmdLabels) . ", labels de l'évènement : " . json_encode($label));

        log::add("frigate_Actions", 'info', "║ Zones configurées : " . json_encode($cmdZones) . ", zones de l'évènement : " . json_encode($eventZones));

        log::add("frigate_Actions", 'info', "║ Types configurés : " . json_encode($cmdTypes) . ", type de l'évènement : " . json_encode($type));

        // Vérifier les trois conditions
        $labelMatch = in_array($label, $cmdLabels) || in_array("all", $cmdLabels);
        $typeMatch = in_array($type, $cmdTypes);
        // Verifier si on utilise zone end, si non utilisé gestion classique sinon verifier ordre des zones
        if (empty($cmdZoneEndName)) {
          log::add("frigate_Actions", 'info', "║ Pas de zone de sortie configurée, vérification des zones d'entrée uniquement.");
          // Vérifier si au moins une des zones d'entrée est présente dans les zones de l'événement
          $zoneMatch = count(array_intersect($cmdZones, $eventZones)) > 0 || in_array("all", $cmdZones);
        } else {
          // Récupérer les zones configurées
          $enterZone = $cmdZones[0]; // Zone d'entrée configurée
          $quitZone = $cmdZonesEnd[0]; // Zone de sortie configurée

          // Trouver les positions des zones dans la séquence
          $enterZonePos = array_search($enterZone, $eventZones);
          $quitZonePos = array_search($quitZone, $eventZones);

          // Vérifier que les deux zones sont présentes et dans le bon ordre
          if ($enterZonePos !== false && $quitZonePos !== false) {
            $zoneMatch = $enterZonePos < $quitZonePos;
          } else {
            $zoneMatch = false;
          }
          log::add("frigate_Actions", 'info', "║ Zones de l'évènement : " . json_encode($eventZones));
          log::add("frigate_Actions", 'info', "║ Zone d'entrée' : " . json_encode($enterZone));
          log::add("frigate_Actions", 'info', "║ Zone de sortie : " . json_encode($quitZone));
          if ($zoneMatch) {
            log::add("frigate_Actions", 'info', "║ Correspondance trouvé, déclenchement de l'action.");
          } else {
            log::add("frigate_Actions", 'info', "║ Les zones ne correspondent pas !");
          }
        }
        // Si au moins une des conditions n'est pas remplie, ignorer l'action
        if (!($labelMatch && $typeMatch && $zoneMatch)) {
          log::add("frigate_Actions", 'info', "║ Au moins une des conditions (label : " . json_encode($labelMatch) . ", type : " . json_encode($typeMatch) . ", zone : " . json_encode($zoneMatch) . ") n'est pas remplie, l'action sera ignorée.");
          continue;
        }

        $options = str_replace(
          ['#time#', '#event_id#', '#camera#', '#cameraId#', '#score#', '#has_clip#', '#has_snapshot#', '#top_score#', '#zones#', '#snapshot#', '#snapshot_path#', '#clip#', '#clip_path#', '#thumbnail#', '#thumbnail_path#', '#label#', '#description#', '#start#', '#end#', '#duree#', '#type#', '#jeemate#', '#preview#', '#preview_path#'],
          [$time, $eventId, $camera, $cameraId, $score, $hasClip, $hasSnapshot, $topScore, $zones, $snapshot, $snapshotPath, $clip, $clipPath, $thumbnail, $thumbnailPath, $label, $description, $start, $end, $duree, $type, $jeemate, $preview, $previewPath],
          $options
        );

        // Vérifie si le temps de début de l'événement est inférieur ou égal à trois heures avant le temps actuel
        if ($event->getStartTime() <= time() - 10800) {
          log::add("frigate_Actions", 'info', "║ Événement trop ancien (plus de 3 heures), il sera ignoré.");
          continue;
        }

        // Exécuter l'action selon le contenu des options
        $optionsJson = json_encode($action['options']);
        if (strpos($optionsJson, '#clip#') !== false || strpos($optionsJson, '#clip_path#') !== false) {
          if ($hasClip == 1) {
            log::add("frigate_Actions", 'info', "║ ACTION CLIP : " . $optionsJson);
            scenarioExpression::createAndExec('action', $cmd, $options);
          } else {
            log::add("frigate_Actions", 'info', "║ Le clip n'est pas disponible, actions non exécutées.");
            log::add("frigate_Actions", 'info', "╠════════════════════════════════════");
          }
        } elseif (strpos($optionsJson, '#snapshot#') !== false || strpos($optionsJson, '#snapshot_path#') !== false) {
          if ($hasSnapshot == 1) {
            log::add("frigate_Actions", 'info', "║ ACTION SNAPSHOT : " . $optionsJson);
            scenarioExpression::createAndExec('action', $cmd, $options);
          } else {
            log::add("frigate_Actions", 'info', "║ Le snapshot n'est pas disponible, actions non exécutées.");
            log::add("frigate_Actions", 'info', "╠════════════════════════════════════");
          }
        } else {
          log::add("frigate_Actions", 'info', "║ ACTION OTHER: " . $optionsJson);
          scenarioExpression::createAndExec('action', $cmd, $options);
        }
      }
      log::add("frigate_Actions", 'info', "╚═════════════════════════════ :b:END   " . $type . ":/b: ═══════════════════════════════════╝");
    }
  }

  public static function saveURL($eventId = null, $type = null, $camera, $mode = 0, $file = "")
  {
    // mode de fonctionnement : 0 = defaut, 1 = thumbnail, 2 = latest, 3 = snapshot, 4 = clip
    $result = "";
    $urlJeedom = network::getNetworkAccess('external');
    if ($urlJeedom == "") {
      $urlJeedom = network::getNetworkAccess('internal');
    }
    $urlfrigate = self::getUrlFrigate();
    $eqLogic = eqLogic::byLogicalId("eqFrigateCamera_" . $camera, "frigate");
    $timestamp = $eqLogic->getConfiguration('timestamp');
    $extra = "";
    if ($type == "preview") {
      $format = "gif";
    } elseif ($type == "snapshot") {
      $format = "jpg";
      $extra = '?timestamp=' . $timestamp . '&bbox=1';
    } else {
      $format = "mp4";
    }

    $lien = "http://" . $urlfrigate . "/api/events/" . $eventId . "/" . $type . "." . $format . $extra;
    $path = "/data/" . $camera . "/" . $eventId . "_" . $type . "." . $format;
    if ($mode == 1) {
      $lien = "http://" . $urlfrigate . "/api/events/" . $eventId . "/thumbnail.jpg";
      $path = "/data/" . $camera . "/" . $eventId . "_thumbnail.jpg";
    } elseif ($mode == 2) {
      $lien = $file;
      $path = "/data/" . $camera . "/latest.jpg";
    } elseif ($mode == 3) {
      $lien = urldecode($file);
      $path = "/data/snapshots/" . $eventId . "_snapshot.jpg";
    } elseif ($mode == 4) {
      $path = "/data/" . $camera . "/" . $eventId . "_clip.mp4";
      $newpath = dirname(__FILE__, 3) . $path;
      // clip creator
      $output = [];
      $return_var = 0;
      $cmd = 'ffmpeg -rtsp_transport tcp -loglevel fatal -i "' . $file . '" -c:v copy -bsf:a aac_adtstoasc -y -t 10 -movflags faststart ' . $newpath;
      exec($cmd, $output, $return_var);
      $result = "/plugins/frigate" . $path;
      log::add(__CLASS__, 'debug', "║ Commande exécutée : " . $cmd);
      log::add(__CLASS__, 'debug', "║ Sortie : " . implode("\n", $output));
      log::add(__CLASS__, 'debug', "║ Code de retour : " . $return_var);
      return $result;
    }

    // Vérifier si le fichier existe déjà
    if (file_exists($path) && $mode != 2) {
      return $urlJeedom . str_replace("/var/www/html", "", $path);
    }

    // Obtenir le répertoire du chemin de destination
    $destinationDir = dirname(dirname(__FILE__, 3) . $path);

    // Vérifier si le répertoire existe, sinon le créer
    if (!is_dir($destinationDir)) {
      if (!mkdir($destinationDir, 0755, true)) {
        log::add(__CLASS__, 'debug', "║ Échec de la création du répertoire.");
        return $result;
      }
    }

    $headers = @get_headers($lien);

    if ($headers && strpos($headers[0], '200') !== false) {
      // Le fichier existe, on peut le télécharger
      $content = file_get_contents($lien);
    } else {
      $content = false;
    }

    if ($content !== false) {
      // Enregistrer l'image ou la vidéo dans le dossier spécifié
      $file = file_put_contents(dirname(__FILE__, 3) . $path, $content);
      if ($file !== false) {
        $result = "/plugins/frigate" . $path;
        log::add(__CLASS__, 'debug', "║ Le fichier a été enregistré : " . $lien);
      } else {
        log::add(__CLASS__, 'debug', "║ Échec de l'enregistrement du fichier : " . $lien);
        $result = "error";
      }
    } else {
      log::add(__CLASS__, 'debug', "║ Le fichier n'existe pas ou une erreur s'est produite.");
      $result = "error";
    }

    return $result;
  }

  private static function cleanString($string)
  {
    // Supprimer les accents
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    // Mettre en minuscule
    return mb_strtolower($string);
  }

  public static function createSnapshot($eqLogic)
  {
    log::add(__CLASS__, 'debug', "╔════════════════════════════════════════════════");
    log::add(__CLASS__, 'debug', "║ Créer snapshot");
    $camera = $eqLogic->getConfiguration('name');
    $file = $eqLogic->getConfiguration('img');
    $timestamp = microtime(true);
    $formattedTimestamp = sprintf('%.6f', $timestamp);
    $startTime = time();
    $endTime = $startTime;
    $uniqueId = self::createUniqueId($formattedTimestamp);
    // create snapshot
    $url = frigate::saveURL($uniqueId, null, $camera, 3, $file);
    $urlClip = "";
    // mise a jour des commandes
    log::add(__CLASS__, 'debug', "║ Mise à jour de la commande.");
    $eqLogic->getCmd(null, 'info_url_capture')->event($url);
    $eqLogic->getCmd(null, 'info_label')->event("capture");
    $eqLogic->getCmd(null, 'info_score')->event(0);
    $eqLogic->getCmd(null, 'info_topscore')->event(0);
    $eqLogic->getCmd(null, 'info_duree')->event(0);

    // Creation de l'evenement  dans la DB
    log::add(__CLASS__, 'debug', "║ Creéation d'un nouveau évènement Frigate pour l'event ID: " . $uniqueId);
    $frigate = new frigate_events();
    $frigate->setCamera($camera);
    $frigate->setLasted($url);
    $frigate->setHasClip(0);
    $frigate->setClip($urlClip);
    $frigate->setHasSnapshot(1);
    $frigate->setSnapshot($url);
    $frigate->setStartTime($startTime);
    $frigate->setEndTime($endTime);
    $frigate->setEventId($timestamp);
    $frigate->setLabel("capture");
    $frigate->setThumbnail($url);
    $frigate->setTopScore(0);
    $frigate->setScore(0);
    $frigate->setType("end");
    $frigate->setIsFavorite(0);
    $frigate->save();
    log::add(__CLASS__, 'debug', "╚════════════════════════════════════════════════");
  }

  public static function createUniqueId($timestamp)
  {
    // Generate a random string of 6 characters
    $randomStr = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6);
    // Combine the timestamp and random string
    $uniqueId = $timestamp . '-' . $randomStr;

    return $uniqueId;
  }

  public static function getConfig()
  {
    $urlfrigate = self::getUrlFrigate();
    $resultURL = $urlfrigate . "/api/config";
    $config = self::getcURL("Configuration", $resultURL);
    if ($config === false) {
      log::add(__CLASS__, 'debug', "║ Erreur lors de la récupération de la configuration de Frigate.");
      log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-warning:ERREURS:/fg: ═══════════════════");
      return false;
    } else if ($config == null) {
      log::add(__CLASS__, 'error', "║Erreur: Impossible de récupérer la configuration de Frigate.");
      log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-warning:ERREURS:/fg: ═══════════════════");
      return false;
    } else {
      log::add(__CLASS__, 'debug', "║ Configuration de Frigate récupérée avec succès.");  
      log::add(__CLASS__, 'debug', "║ Configuration : " . json_encode($config));
    }
    return $config;
  }

  public static function preConfig_topic($value)
  {
    if (self::getTopic() != $value) {
      self::removeMQTTTopicRegistration();
    }
    return $value;
  }

  public static function postConfig_topic($value)
  {
    if (class_exists('mqtt2')) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['state'] === 'ok') {
        self::deamon_start();
      }
    }
  }

  public static function removeMQTTTopicRegistration()
  {
    $topic = self::getTopic();
    if (class_exists('mqtt2')) {
      log::add(__CLASS__, 'info', "Arrêt de l'écoute du topic Frigate sur mqtt2:'{$topic}'");
      mqtt2::removePluginTopic($topic);
    }
  }

  public static function deamon_start()
  {
    log::add(__CLASS__, 'info', 'deamon_start()');
    self::deamon_stop();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
      throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }
    // Enregistrement topic frigate
    mqtt2::addPluginTopic(__CLASS__, config::byKey('topic', 'frigate'));
    $mqttInfos = mqtt2::getFormatedInfos();
    log::add(__CLASS__, 'info', '[' . __FUNCTION__ . '] ' . __('Informations reçues de MQTT Manager', __FILE__) . ' : ' . json_encode($mqttInfos));

    return true;
  }

  public static function deamon_stop()
  {
    if (class_exists('mqtt2')) {
      log::add(__CLASS__, 'info', __('Arrêt du démon Frigate', __FILE__));
      mqtt2::removePluginTopic(config::byKey('frigate', 'frigate'));
    }
  }

  public static function deamon_info()
  {
    $return = [
      'log' => __CLASS__,
      'launchable' => 'ok',
      'state' => self::isRunning() ? 'ok' : 'nok'
    ];

    if (!class_exists('mqtt2')) {
      $return['launchable'] = 'nok';
      $return['launchable_message'] = __('Le plugin MQTT Manager n\'est pas installé', __FILE__);
    } elseif (mqtt2::deamon_info()['state'] != 'ok') {
      $return['launchable'] = 'nok';
      $return['launchable_message'] = __('Le démon MQTT Manager n\'est pas démarré', __FILE__);
    } elseif (self::getTopic() == '') {
      $return['launchable'] = 'nok';
      $return['launchable_message'] = __('Topic mqtt pour Frigate non défini.', __FILE__);
    }

    return $return;
  }


  public static function isRunning()
  {
    return true;
  }

  public static function handleMqttMessage($_message)
  {
    if (!isset($_message[self::getTopic()])) {
      return;
    }
    $frigate = frigate::byLogicalId('eqFrigateStats', 'frigate');
    if (!is_object($frigate)) {
      return; // frigate n'existe pas
    } else {
      $eqlogicId = $frigate->getId();
      $cmd = self::createCmd($eqlogicId, "version", "string", "", "info_version", "", 0, null, 0);
      $version = $cmd->execCmd();
      if ($version != config::byKey('frigate_version', 'frigate')) {
        config::save('frigate_version', $version, 'frigate');
      }
    }

    foreach ($_message[self::getTopic()] as $key => $value) {
      log::add("frigate_MQTT", 'info', 'handle Mqtt Message pour : :b:' . $key . ':/b:');
      log::add("frigate_MQTT", 'info', 'handle Mqtt Message pour : :b:' . $key . ':/b: = ' . json_encode($value));

      switch ($key) {
        case 'events':
          if (version_compare($version, "0.14", "<")) {
            log::add("frigate_MQTT", 'info', ' => Traitement mqtt events <0.14');
            self::getEvents(true, [$value['after']], $value['type']);
            event::add('frigate::events', array('message' => 'mqtt_update', 'type' => 'event'));
          } else {
            log::add("frigate_MQTT", 'info', ' => Traitement mqtt events non exécuté, version >= 0.14, utilisation de reviews.');
          }
          break;

        case 'reviews':
          $eventId = $value['after']['data']['detections'][0];
          $eventType = $value['type'];
          log::add("frigate_MQTT", 'info', ' => Traitement mqtt manual event <=');

          self::getEvent($eventId, $eventType);
          event::add('frigate::events', array('message' => 'mqtt_update_manual', 'type' => 'event'));
          break;

        case 'stats':
          log::add("frigate_MQTT", 'info', ' => Traitement mqtt stats');
          self::majStatsCmds($value, true);
          break;

        case 'available':
          log::add("frigate_MQTT", 'info', ' => Traitement mqtt available');
          $cmd = self::createCmd($eqlogicId, "Disponibilité", "string", "", "info_available", "", 0, null, 0, "info");
          $cmd->event($value);
          // $cmd->save();
          break;

        default:
          $eqCamera = eqLogic::byLogicalId("eqFrigateCamera_" . $key, "frigate");
          if (!is_object($eqCamera)) {
            continue 2;
          }

          log::add("frigate_MQTT", 'info', ' => Traitement mqtt camera ' . $key);
          self::processCameraData($eqCamera, $key, $value);
          break;
      }
    }
  }

  private static function processCameraData($eqCamera, $key, $data)
  {
    // recupérer la liste des object a surveiller
    $eqEvent = eqLogic::byLogicalId("eqFrigateEvents", "frigate");
    $objects = $eqEvent->getConfiguration("objects");
    foreach ($data as $innerKey => $innerValue) {
      if (in_array($innerKey, ['birdeye', 'improve_constrast', 'motion_contour_area', 'motion_threshold', 'ptz_autotracker'])) {

        continue;
      }

      if (in_array($innerKey, $objects)) {
        log::add("frigate_Detect", 'info', "╔═════════════════════════════ :fg-success:START OBJET DETECT :/fg: ════════════════════════════════╗");
        log::add("frigate_Detect", 'info', '║ Equipement : :b:' . $eqCamera->getHumanName() . ":/b:");
        log::add("frigate_Detect", 'info', "║ Objet : " . $innerKey . ', Etat : ' . json_encode($innerValue));
        // mise à jour pour la caméra
        self::handleObject($eqCamera, $innerKey, $innerValue);
        // mise à jour pour l'équipement event
        log::add("frigate_Detect", 'info', '║ Equipement : :b:' . $eqEvent->getHumanName() . ":/b:");
        self::handleObject($eqEvent, $innerKey, $innerValue);
        log::add("frigate_Detect", 'info', "╚══════════════════════════════════════════════════════════════════════════════════╝");
        continue;
      }

      switch ($innerKey) {
        case 'motion':
          self::handleMotion($eqCamera, $key, $innerValue);
          break;

        case 'detect':
          self::updateCameraState($eqCamera, $innerKey, $innerValue['state'], "JEEMATE_CAMERA_DETECT_STATE");
          break;

        case 'recordings':
          self::updateCameraState($eqCamera, $innerKey, $innerValue['state'], "JEEMATE_CAMERA_NVR_STATE");
          break;

        case 'snapshots':
          self::updateCameraState($eqCamera, $innerKey, $innerValue['state'], "JEEMATE_CAMERA_SNAPSHOT_STATE");
          break;

        case 'audio':
          self::updateCameraState($eqCamera, $innerKey, $innerValue['state'], "JEEMATE_CAMERA_AUDIO_STATE");
          break;

        case 'all':
          log::add("frigate_Detect", 'info', "╔═════════════════════════════ :fg-danger:START ALL DETECT:/fg: ═══════════════════════════════════╗");
          log::add("frigate_Detect", 'info', '║ Equipement : :b:' . $eqCamera->getHumanName() . ":/b:");
          log::add("frigate_Detect", 'info', '║ Objet : ' . $innerKey . ', Etat : ' . json_encode($innerValue));
          // mise à jour pour la caméra
          self::handleAllObject($eqCamera, $innerKey, $innerValue);
          // mise à jour pour l'équipement event
          log::add("frigate_Detect", 'info', '║ Equipement : :b:' . $eqEvent->getHumanName() . ":/b:");
          self::handleAllObject($eqEvent, $innerKey, $innerValue);
          log::add("frigate_Detect", 'info', "╚══════════════════════════════════════════════════════════════════════════════════╝");
          break;
      }
    }
  }

  private static function handleMotion($eqCamera, $key, $innerValue)
  {
    if (isset($innerValue['state']) && $innerValue['state']) {
      $state = ($innerValue['state'] == 'ON') ? "1" : "0";
      log::add("frigate_MQTT", 'info', $key . ' => Valeur motion state : ' . $state);
      $infoCmd = self::createCmd($eqCamera->getId(), 'motion Etat', 'binary', '', 'info_motion', 'JEEMATE_CAMERA_DETECT_STATE', 0);
      $infoCmd->event($state);
      // $infoCmd->save();
      $eqCamera->refreshWidget();
    }

    if (isset($innerValue) && !is_array($innerValue)) {
      $state = ($innerValue == 'ON') ? "1" : "0";
      log::add("frigate_MQTT", 'info', $key . ' => Valeur motion : ' . $state);
      $infoCmd = self::createCmd($eqCamera->getId(), 'détection en cours', 'binary', '', 'info_detectNow', 'JEEMATE_CAMERA_SNAPSHOT_STATE', 1);
      $infoCmd->event($state);
      // $infoCmd->save();
      $eqCamera->refreshWidget();
    }
  }

  private static function handleObject($eqCamera, $key, $innerValue)
  {
    // Traiter le cas où $innerValue est un nombre ou un tableau avec "active"
    if (is_array($innerValue)) {
      $value = ($innerValue["active"] !== 0) ? 1 : 0;
    } else {
      $value = ($innerValue !== 0) ? 1 : 0;
    }
    $infoCmd = self::createCmd($eqCamera->getId(), "Détection " . $key, "binary", "", "info_detect_" . $key, "JEEMATE_CAMERA_DETECT_EVENT_STATE", 0);
    $infoCmd->event($value);
    // $infoCmd->save();
    log::add("frigate_Detect", 'info', '║ Objet : ' . $key . ', Valeur enregistrée : ' . json_encode($value));
  }
  private static function handleAllObject($eqCamera, $key, $innerValue)
  {
    // Traiter le cas où $innerValue est un nombre ou un tableau avec "active"
    if (is_array($innerValue)) {
      $value = ($innerValue["active"] !== 0) ? 1 : 0;
    } else {
      $value = ($innerValue !== 0) ? 1 : 0;
    }
    $infoCmd = self::createCmd($eqCamera->getId(), "Détection tout", "binary", "", "info_detect_all", "JEEMATE_CAMERA_DETECT_EVENT_STATE", 0);
    $infoCmd->event($value);
    // $infoCmd->save();
    log::add("frigate_Detect", 'info', '║ Objet : ' . $key . ', Valeur enregistrée : ' . json_encode($value));
    if ($value === 0) {
      $cmds = cmd::byEqLogicId($eqCamera->getId(), "info");
      foreach ($cmds as $cmd) {
        if ((substr($cmd->getLogicalId(), 0, 12) == 'info_detect_') && ($cmd->getLogicalId() !== "info_detect_all") && ($cmd->execCmd() == 1)) {
          $cmd->event($value);
          // $cmd->save();
          log::add("frigate_Detect", 'info', '║ cmd : ' . $cmd->getName() . ', Valeur forcée : ' . json_encode($value));
        }
      }
    }
  }
  private static function updateCameraState($eqCamera, $type, $state, $jeemateState)
  {

    if (isset($state)) {
      $infoCmd = self::createCmd($eqCamera->getId(), $type . " Etat", "binary", "", "info_" . $type, $jeemateState, 0);
      $currentState = $infoCmd->execCmd(); // Obtenir l'état actuel

      $stateValue = ($state == 'ON') ? "1" : "0";

      if ($currentState !== $stateValue) {
        $infoCmd->event($stateValue);
        //  $infoCmd->save();
        $eqCamera->refreshWidget();
        log::add("frigate_MQTT", 'info', 'L\'etat de la commande ' . $type . ' a été modifié, mise a jour du status.');
      }
    }
  }



  public static function setFavorite($eventId, $isFav)
  {
    $events = frigate_events::byEventId($eventId);
    foreach ($events as $event) {
      $event->setIsFavorite($isFav);
      $event->save();
    }
    $state = $event->getIsFavorite();
    return $state;
  }


  public static function deleteLatestFile()
  {
    $folder = dirname(__FILE__, 3) . "/data/";
    $fileName = "latest.jpg";
    // Parcourt récursivement tous les fichiers et dossiers
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)) as $file) {
      if ($file->isFile() && $file->getFilename() === $fileName) {
        // Supprime le fichier
        unlink($file->getPathname());
        log::add(__CLASS__, 'debug', "║ Fichier supprimé: " . $file->getPathname());
      }
    }
  }

  public static function backupExclude()
  {
    // retourne le répertoire de sauvegarde des snapshots et des vidéos des events à ne pas enregistrer dans le backup Jeedom
    if (config::byKey('excludeBackup', 'frigate', 0)) {
      return ['data'];
    }
  }


  public static function getFrigateConfiguration()
  {
    log::add(__CLASS__, 'info', "getFrigateConfiguration");

    $urlfrigate = self::getUrlFrigate();
    $resultURL = $urlfrigate . "/api/config/raw";

    $ch = curl_init($resultURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);

    $curlResponse = curl_exec($ch);

    if ($curlResponse === false) {
      $error = 'Erreur cURL : ' . curl_error($ch);
      curl_close($ch);
      log::add(__CLASS__, 'error', '║ getFrigateConfiguration :: ' . $error);
      $response = array(
        'status' => 'error',
        'message' => $error
      );

      return $response;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
      $error = 'Erreur : Impossible de récupérer la configuration. Code de statut : ' . $httpCode;
      curl_close($ch);
      log::add(__CLASS__, 'error', '║ getFrigateConfiguration :: ' . $error);
      $response = array(
        'status' => 'error',
        'message' => $error
      );

      return $response;
    }

    curl_close($ch);

    //log::add(__CLASS__, 'info', "getFrigateConfiguration:: save config file");
    //$file = file_put_contents(dirname(__FILE__, 3) . '/data/config.yaml', $curlResponse);

    $cResponse = json_decode($curlResponse);
    if ($cResponse != null) {
      // 0.15
      $curlResponse = $cResponse;
    }

    $response = array(
      'status' => 'success',
      'message' => $curlResponse
    );

    return $response;
  }

  public static function sendFrigateConfiguration($frigateConfiguration, $restart = false)
  {
    $urlfrigate = self::getUrlFrigate();
    $resultURL = $urlfrigate . "/api/config/save" . ($restart ? '?save_option=restart' : '?save_option=saveonly');

    $ch = curl_init($resultURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-yaml'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $frigateConfiguration);

    log::add(__CLASS__, 'debug', '║ sendFrigateConfiguration :: Data : ' . $frigateConfiguration);
    $curlResponse = curl_exec($ch);

    if ($curlResponse === false) {
      $error = 'Erreur cURL : ' . curl_error($ch);
      curl_close($ch);
      log::add(__CLASS__, 'error', '║ sendFrigateConfiguration :: ' . $error);
      $response = array(
        'status' => 'error',
        'message' => $error
      );

      return $response;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
      $error = 'Erreur : Impossible de sauvegarder la configuration. Code de statut : ' . $httpCode;
      curl_close($ch);
      log::add(__CLASS__, 'error', '║ sendFrigateConfiguration :: ' . $error);
      $response = array(
        'status' => 'error',
        'message' => $error
      );

      return $response;
    }

    curl_close($ch);
    $response = array(
      'status' => 'success',
      'message' => $curlResponse
    );
    return $response;
  }


  private static function jsonFromUrl($jsonUrl)
  {
    $headers = @get_headers($jsonUrl);

    $code = substr($headers[0], 9, 3);
    if ($code == '200') {
      // Le fichier existe, on peut le télécharger
      $jsonContent = file_get_contents($jsonUrl);
    } else {
      $jsonContent = false;
      log::add(__CLASS__, 'error', "║ jsonFromUrl : HTTP Error $code lors du téléchargement de $jsonUrl");
    }

    // Vérifier si le téléchargement a réussi
    if ($jsonContent === false) {
      log::add(__CLASS__, 'error', "║ jsonFromUrl : Failed to retrieve JSON from URL");
      return null;
    }

    // Décoder le JSON en tableau PHP
    $jsonArray = json_decode($jsonContent, true);

    // Vérifier si la conversion a réussi
    if ($jsonArray === null && json_last_error() !== JSON_ERROR_NONE) {
      log::add(__CLASS__, 'error', "║ jsonFromUrl : Failed to decode JSON content");
      return null;
    }

    return $jsonArray;
  }

  /* private static function yamlToJsonFromUrl($yamlUrl)
 {
    // Télécharger le contenu YAML depuis l'URL
    $yamlContent = file_get_contents($yamlUrl);
    // Vérifier si le téléchargement a réussi
    if ($yamlContent === false) {
      log::add(__CLASS__, 'error', "yamlToJsonFromUrl : Failed to retrieve YAML from URL");
      return json_encode(["error" => "Failed to retrieve YAML from URL: $yamlUrl"]);
    }
    // Parser le contenu YAML
    $yamlArray = yaml_parse($yamlContent);
    // Vérifier si le parsing est réussi
    if ($yamlArray === false) {
      log::add(__CLASS__, 'error', "yamlToJsonFromUrl : Invalid YAML content or file not found");
      return json_encode(["error" => "Invalid YAML content or file not found"]);
    }
    // Convertir le tableau PHP en JSON
    $jsonContent = json_encode($yamlArray, JSON_PRETTY_PRINT);
    return $jsonContent;
  }

  */
  private static function checkFrigateStatus()
  {
    $frigate = frigate::byLogicalId('eqFrigateStats', 'frigate');
    if (!$frigate) {
      return;
    }
    $eqlogicId = $frigate->getId();
    $urlFrigate = self::getUrlFrigate();
    $etat = 0;

    $ch = curl_init($urlFrigate);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);

    // Obtenir le code de statut HTTP
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
      $etat = 1; // Site accessible
    } else {
      $etat = 0; // Site inaccessible
    }
    $cmd = self::createCmd($eqlogicId, "status serveur", "binary", "", "info_status", "", 0, null, 0);
    // Enregistrer la valeur de l'événement
    $cmd->event($etat);
    // $cmd->save();

    return $etat;
  }
  private static function checkFrigateVersion()
  {
    $urlfrigate = self::getUrlFrigate();
    $resultURL = $urlfrigate . "/api/stats";
    $stats = self::getcURL("Stats", $resultURL);
    if ($stats == null) {
      log::add(__CLASS__, 'error', "║ Erreur: Impossible de récupérer les stats de Frigate.");
      log::add(__CLASS__, 'debug', "╚════════════════════════ :fg-warning:ERREURS:/fg: ═══════════════════");
      return;
    }
    $version = strstr($stats['service']['version'], '-', true);
    $latestVersion = $stats['service']['latest_version'];
    if (version_compare($version, $latestVersion, "<")) {
      config::save('frigate_maj', 1, 'frigate');
      message::add('frigate', __("Une nouvelle version de Frigate (" . $latestVersion . ") est disponible.", __FILE__), null, null);
    } else {
      config::save('frigate_maj', 0, 'frigate');
    }
  }
  public static function getPluginVersion()
  {
    $pluginVersion = '0.0.0';
    try {
      if (!file_exists(dirname(__FILE__) . '/../../plugin_info/info.json')) {
        log::add('frigate', 'warning', '[Plugin-Version] fichier info.json manquant');
      }
      $data = json_decode(file_get_contents(dirname(__FILE__) . '/../../plugin_info/info.json'), true);
      if (!is_array($data)) {
        log::add('frigate', 'warning', '[Plugin-Version] Impossible de décoder le fichier info.json');
      }
      try {
        $pluginVersion = $data['pluginVersion'];
      } catch (\Exception $e) {
        log::add('frigate', 'warning', '[Plugin-Version] Impossible de récupérer la version du plugin');
      }
    } catch (\Exception $e) {
      log::add('frigate', 'debug', '[Plugin-Version] Get ERROR :: ' . $e->getMessage());
    }
    log::add('frigate', 'info', '[Plugin-Version] PluginVersion :: ' . $pluginVersion);
    return $pluginVersion;
  }
}
class frigateCmd extends cmd
{
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  private function parseEventParameters($_options)
  {
    // Valeurs par défaut
    // TODO : récupérer les valeurs par défaut pour chaque caméra (et plus au niveau de la config plugin)
    $defaults = [
      'label' => config::byKey('defaultLabel', 'frigate'),
      'video' => (int)config::byKey('defaultVideo', 'frigate'),
      'duration' => (int)config::byKey('defaultDuration', 'frigate'),
      'score' => (int)config::byKey('defaultScore', 'frigate')
    ];

    // Vérification de l'existence de la clé 'title'
    if (isset($_options['title']) && $_options['title'] != '') {
      $defaults['label'] = $_options['title'];
    }

    // Vérification de l'existence de la clé 'message'
    if (isset($_options['message'])) {
      $params = explode('|', $_options['message']);
      foreach ($params as $param) {
        $keyValue = explode('=', $param);

        // Vérification de l'existence d'un couple clé=valeur
        if (
          count($keyValue) === 2
        ) {
          $key = trim($keyValue[0]);
          $value = trim($keyValue[1]);

          if ($key === 'video' && is_numeric($value)) {
            $defaults['video'] = (int)$value;
          }

          if (
            $key === 'duration' && is_numeric($value) && $value > 0
          ) {
            $defaults['duration'] = (int)$value;
          }

          if ($key === 'score' && is_numeric($value) && $value >= 0 && $value <= 100) {
            $defaults['score'] = (int)$value;
          }
        }
      }
    }

    return $defaults;
  }


  // Exécution d'une commande
  public function execute($_options = array())
  {
    $frigate = $this->getEqLogic();
    $camera = $frigate->getConfiguration('name');
    $file = $frigate->getConfiguration('img');
    $logicalId = $this->getLogicalId();
    $cmdName = $this->getName();
    $link = $this->getConfiguration('request') ?? "";
    $user = $frigate->getConfiguration('userName');
    $password = $frigate->getConfiguration('password');
    // la pause doit etre entre 0.1s et 1.0s, on multiplie donc le resultat par 10000 pour faire le usleep
    $pause = config::byKey("pausePTZ", "frigate", 10);
    if ($pause < 0 || $pause > 10) {
      $pause = 10;
    }
    $pause = 10000 * $pause;

    switch ($logicalId) {
      case 'action_startCron':
        $this->updateCronStatus($frigate, 1, "Cron activé");
        break;
      case 'action_stopCron':
        $this->updateCronStatus($frigate, 0, "Cron désactivé");
        break;
      case 'action_restart':
        frigate::restartFrigate();
        break;
      case 'action_start_audio':
      case 'action_stop_audio':
        $this->publishCameraMessage($camera, 'audio/set', $logicalId === 'action_start_audio' ? 'ON' : 'OFF');
        break;
      case 'action_toggle_audio':
        $this->toggleCameraSetting($frigate, $camera, 'info_audio', 'audio/set');
        break;
      case 'action_start_detect':
      case 'action_stop_detect':
        $this->publishCameraMessage($camera, 'detect/set', $logicalId === 'action_start_detect' ? 'ON' : 'OFF');
        break;
      case 'action_toggle_detect':
        $this->toggleCameraSetting($frigate, $camera, 'info_detect', 'detect/set');
        break;
      case 'action_start_ptz_autotracker':
      case 'action_stop_ptz_autotracker':
        $this->publishCameraMessage($camera, 'ptz_autotracker/set', $logicalId === 'action_start_ptz_autotracker' ? 'ON' : 'OFF');
        break;
      case 'action_toggle_ptz_autotracker':
        $this->toggleCameraSetting($frigate, $camera, 'info_ptz_autotracker', 'ptz_autotracker/set');
        break;
      case 'action_start_recordings':
      case 'action_stop_recordings':
        $this->publishCameraMessage($camera, 'recordings/set', $logicalId === 'action_start_recordings' ? 'ON' : 'OFF');
        break;
      case 'action_toggle_recordings':
        $this->toggleCameraSetting($frigate, $camera, 'info_recordings', 'recordings/set');
        break;
      case 'action_enable_camera':
      case 'action_disable_camera':
        //config/set?cameras.frigate1.enabled=true
        $enable = $logicalId === 'action_enable_camera' ? 1 : 0;
        $response = frigate::enableCamera($camera, $enable);
        if ($response['success']) {
          $infoCamera = $logicalId === 'action_enable_camera' ? 1 : 0;
          $frigate->getCmd(null, 'enable_camera')->event($infoCamera);
        }
        break;
      case 'action_toggle_camera':
        $enable = 1 - $frigate->getCmd(null, 'enable_camera')->execCmd();
        $response = frigate::enableCamera($camera, $enable);
        if ($response['success']) {
          $frigate->getCmd(null, 'enable_camera')->event($enable);
        }
        break;
      case 'action_start_snapshots':
      case 'action_stop_snapshots':
        $this->publishCameraMessage($camera, 'snapshots/set', $logicalId === 'action_start_snapshots' ? 'ON' : 'OFF');
        break;
      case 'action_toggle_snapshots':
        $this->toggleCameraSetting($frigate, $camera, 'info_snapshots', 'snapshots/set');
        break;
      case 'action_start_motion':
      case 'action_stop_motion':
        $this->publishCameraMessage($camera, 'motion/set', $logicalId === 'action_start_motion' ? 'ON' : 'OFF');
        break;
      case 'action_toggle_motion':
        $this->toggleCameraSetting($frigate, $camera, 'info_motion', 'motion/set');
        break;
      case 'action_ptz_left':
        $this->publishCameraMessage($camera, 'ptz', 'MOVE_LEFT');
        usleep($pause);
        $this->publishCameraMessage($camera, 'ptz', 'STOP');
        break;
      case 'action_ptz_right':
        $this->publishCameraMessage($camera, 'ptz', 'MOVE_RIGHT');
        usleep($pause);
        $this->publishCameraMessage($camera, 'ptz', 'STOP');
        break;
      case 'action_ptz_up':
        $this->publishCameraMessage($camera, 'ptz', 'MOVE_UP');
        usleep($pause);
        $this->publishCameraMessage($camera, 'ptz', 'STOP');
        break;
      case 'action_ptz_down':
        $this->publishCameraMessage($camera, 'ptz', 'MOVE_DOWN');
        usleep($pause);
        $this->publishCameraMessage($camera, 'ptz', 'STOP');
        break;
      case 'action_ptz_stop':
        $this->publishCameraMessage($camera, 'ptz', 'STOP');
        break;
      case 'action_ptz_zoom_in':
        $this->publishCameraMessage($camera, 'ptz', 'ZOOM_IN');
        usleep($pause);
        $this->publishCameraMessage($camera, 'ptz', 'STOP');
        break;
      case 'action_ptz_zoom_out':
        $this->publishCameraMessage($camera, 'ptz', 'ZOOM_OUT');
        usleep($pause);
        $this->publishCameraMessage($camera, 'ptz', 'STOP');
        break;
      case 'action_preset_1':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_preset_2':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_preset_3':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_preset_4':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_preset_5':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_preset_6':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_preset_7':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_preset_8':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_preset_9':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_preset_0':
        $this->publishCameraMessage($camera, 'ptz', 'preset_' . $cmdName);
        break;
      case 'action_make_api_event':
        //score=12|video=1|duration=20
        $eventParams = self::parseEventParameters($_options);
        $result = frigate::createEvent($camera, $eventParams['label'], $eventParams['video'], $eventParams['duration'], $eventParams['score']);
        $deamon_info = frigate::deamon_info();
        if ($deamon_info['launchable'] === 'nok') {
          log::add('frigate', 'debug', "║ action_make_api_event result = " . json_encode($result));
          frigate::getEvent($result['event_id']);
        }
        break;
      case 'action_create_snapshot':
        frigate::createSnapshot($frigate);
        break;
      case 'action_http':
        // Gérer les variables user et password
        log::add('frigate', 'info', "║ 01 action_http $logicalId $link");
        $user = $frigate->getConfiguration("userName") ?? "";
        $password = $frigate->getConfiguration("password") ?? "";
        $link = str_replace("#user#", $user, $link);
        $link = str_replace("#password#", $password, $link);
        // Gérer les actions HTTP statiques
        log::add('frigate', 'info', "║ 02 action_http $logicalId $link");
        $response = self::getCurlcmd($link, $user, $password);
        if ($response !== false) {
          $frigate->getCmd(null, 'info_http')->event($response);
        } else {
          log::add('frigate', 'error', "Erreur lors de l'appel HTTP: $link");
        }
        break;
      default:
        // Gérer les actions HTTP dynamiques
        if (strpos($logicalId, 'action_http_') === 0) {
          $response = self::getCurlcmd($link, $user, $password);
          if ($response !== false) {
            $frigate->getCmd(null, 'info_http')->event($response);
          } else {
            log::add('frigate', 'error', "Erreur lors de l'appel HTTP: $link");
          }
        }
    }
  }
  private function getCurlcmd($link, $username, $password)
  {

    $ch = curl_init();
    $verbose = fopen('php://temp', 'w+'); // Flux temporaire pour verbose

    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, $verbose); // Rediriger verbose vers ce flux temporaire
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST); // Utiliser l'authentification Digest
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password"); // Nom d'utilisateur et mot de passe

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
      log::add('frigate', 'error', "Erreur cURL: " . curl_error($ch));
    } else {
      log::add('frigate', 'debug', "║ Resultat de la commande HTTP : " . $response);
    }

    // Récupérer les informations verbose
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    log::add('frigate', 'debug', "║ cURL verbose log: " . $verboseLog);

    fclose($verbose);
    curl_close($ch);
    return $response;
  }

  private function updateCronStatus($frigate, $status, $message)
  {
    $frigate->getCmd(null, 'info_Cron')->event($status);
    log::add(__CLASS__, 'debug', $message);
  }

  private function publishCameraMessage($camera, $topic, $message)
  {
    frigate::publish_camera_message($camera, $topic, $message);
  }

  private function toggleCameraSetting($frigate, $camera, $infoCmd, $setCmd)
  {
    $currentStatus = $frigate->getCmd(null, $infoCmd)->execCmd();
    $newStatus = $currentStatus == 1 ? 'OFF' : 'ON';
    frigate::publish_camera_message($camera, $setCmd, $newStatus);
  }

  /*     * **********************Getteur Setteur*************************** */
}
