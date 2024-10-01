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
}


function import2calendar_remove()
{
    $cron = cron::byClassAndFunction('import2calendar', 'update');
    if (is_object($cron)) {
        $cron->remove();
    }
}
