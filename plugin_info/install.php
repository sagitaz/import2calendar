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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';


function import2calendar_install()
{
    $pluginVersion = import2calendar::getPluginVersion();
    config::save('pluginVersion', $pluginVersion, 'import2calendar');
    $cron = cron::byClassAndFunction('import2calendar', 'update');

    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('import2calendar');
        $cron->setFunction('update');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('* * * * *');
        $cron->setTimeout(30);
        $cron->save();
    }
}

function import2calendar_update()
{
    $pluginVersion = import2calendar::getPluginVersion();
    config::save('pluginVersion', $pluginVersion, 'import2calendar');
    $cron = cron::byClassAndFunction('import2calendar', 'update');
    if (!is_object($cron)) {
        $cron = new cron();
    }
    $cron->setClass('import2calendar');
    $cron->setFunction('update');
    $cron->setEnable(1);
    $cron->setDeamon(0);
    $cron->setSchedule('* * * * *');
    $cron->setTimeout(30);
    $cron->save();
    $cron->stop();
    import2calendar_migrateLogicalId();
}

/**
 * Repose les identifiants techniques que les versions antérieures à 1.4.9 faisaient
 * passer par __(), et que la traduction italienne rendait en 'import2calendario' et
 * 'ica'. La recherche par logicalId comparant en égalité stricte, ces installations
 * ne retrouvaient jamais l'agenda déjà créé et en créaient un nouveau à chaque parse.
 *
 * Requête directe et non $eqLogic->save() : calendar::postSave() supprime et recrée
 * des commandes, puis appelle rescheduleEvent() et refreshWidget(), ce qui n'a rien
 * à faire dans un simple renommage d'identifiant.
 *
 * Idempotente : sans ligne correspondante, elle ne coûte que deux SELECT.
 *
 * @return void
 */
function import2calendar_migrateLogicalId()
{
    $migrations = [
        ['eqType_name' => 'calendar', 'ancien' => 'import2calendario', 'nouveau' => 'import2calendar'],
        ['eqType_name' => 'import2calendar', 'ancien' => 'ica', 'nouveau' => 'ical'],
    ];

    foreach ($migrations as $migration) {
        $selection = [
            'eqType_name' => $migration['eqType_name'],
            'logicalId' => $migration['ancien'],
        ];
        $sql = 'SELECT COUNT(*) AS nombre
        FROM eqLogic
        WHERE eqType_name=:eqType_name
        AND logicalId=:logicalId';
        $resultat = DB::Prepare($sql, $selection, DB::FETCH_TYPE_ROW);
        $nombre = is_array($resultat) && isset($resultat['nombre']) ? (int) $resultat['nombre'] : 0;
        if ($nombre === 0) {
            continue;
        }

        $remplacement = [
            'eqType_name' => $migration['eqType_name'],
            'ancien' => $migration['ancien'],
            'nouveau' => $migration['nouveau'],
        ];
        $sql = 'UPDATE eqLogic
        SET logicalId=:nouveau
        WHERE eqType_name=:eqType_name
        AND logicalId=:ancien';
        DB::Prepare($sql, $remplacement, DB::FETCH_TYPE_ROW);

        log::add('import2calendar', 'info', '[Migration 1.4.9] ' . $nombre . ' équipement(s) ' . $migration['eqType_name'] . ' : logicalId ' . $migration['ancien'] . ' => ' . $migration['nouveau']);
        message::add('import2calendar', __("Identifiants techniques corrigés sur", __FILE__) . ' ' . $nombre . ' ' . __("équipement(s). Les versions précédentes pouvaient créer un agenda en double à chaque actualisation : vérifiez la liste de vos agendas dans le plugin Agenda et supprimez les doublons éventuels.", __FILE__), null, null);
    }
}


function import2calendar_remove()
{
    $cron = cron::byClassAndFunction('import2calendar', 'update');
    if (is_object($cron)) {
        $cron->remove();
    }
}
