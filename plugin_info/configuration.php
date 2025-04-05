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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Version Plugin}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Version du Plugin (A indiquer sur Community)}}"></i></sup>
      </label>
      <div class="col-lg-2">
        <input class="configKey form-control" data-l1key="pluginVersion" readonly />
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Nombre de jours passé de retention}}
        <sup><i class="fas fa-question-circle tooltips" title="min : 3, max : 31"></i></sup>
      </label>
      <div class="col-lg-2">
        <input type="number" class="configKey form-control" data-l1key="numberOfDays" />
      </div>
    </div>
  </fieldset>
</form>