<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('import2calendar');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes import2calendars}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement import2calendar trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save" id="bt_saveActions"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#colortab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-brush"></i> {{Couleurs des évènements}}</a></li>
			<li role="presentation"><a href="#starttab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-flag"></i> {{Action(s) de début}}</a></li>
			<li role="presentation"><a href="#endtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-flag-checkered"></i> {{Action(s) de fin}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<div class="alert alert-info" role="alert">
					{{Attention, ne pas modifier l'agenda créé depuis le plugin agenda}}.<br>
					{{A chaque sauvegarde ou cron défini celui-ci sera modifié}}.<br>
				</div>
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux et spécifiques de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
								</div>
							</div>

							<legend><i class="fas fa-cogs"></i> {{Paramètres de l'import}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{ICAL}}
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ical" placeholder="{{ICAL}}">
								</div>
								<label class="col-sm-4 control-label">{{ICAL général}}
								</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="icalAuto">
										<option value="" selected>{{Aucun}}</option>
										<option value="https://fr.ftp.opendatasoft.com/openscol/fr-en-calendrier-scolaire/Zone-A-B-C-Corse.ics">{{Vacance scolaire FR zone A-B-C}}</option>
										<option value="https://fr.ftp.opendatasoft.com/openscol/fr-en-calendrier-scolaire/Zone-A.ics">{{Vacance scolaire FR zone A}}</option>
										<option value="https://fr.ftp.opendatasoft.com/openscol/fr-en-calendrier-scolaire/Zone-B.ics">{{Vacance scolaire FR zone B}}</option>
										<option value="https://fr.ftp.opendatasoft.com/openscol/fr-en-calendrier-scolaire/Zone-C.ics">{{Vacance scolaire FR zone C}}</option>
										<option value="https://etalab.github.io/jours-feries-france-data/ics/jours_feries_metropole.ics">{{Jours fériés Métropole}}</option>

									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Auto-actualisation}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Fréquence de rafraîchissement du calendrier}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<div class="input-group">
										<input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="autorefresh" placeholder="{{Cliquer sur ? pour afficher l'assistant cron}}">
										<span class="input-group-btn">
											<a class="btn btn-default cursor jeeHelper roundedRight" data-helper="cron" title="Assistant cron">
												<i class="fas fa-question-circle"></i>
											</a>
										</span>
									</div>
								</div>
							</div>


							<legend><i class="fas fa-paint-brush"></i> {{Paramètres d'affichage}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Icône}}
								</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr" data-l1key="configuration" data-l2key="icon"></span>
									<a class="btn btn-default btn-sm" id="bt_chooseIcon"><i class="fas fa-icons"></i></a>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Couleur de fond}}
								</label>
								<div class="col-sm-6">
									<input type="color" class="eqLogicAttr" data-l1key="configuration" data-l2key="color" value='2980b9'>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Couleur du texte}}
								</label>
								<div class="col-sm-6">
									<input type="color" class="eqLogicAttr" data-l1key="configuration" data-l2key="text_color" value='2980b9'>
								</div>
							</div>
						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<div class="col-lg-6">

						</div>
					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->
			<div role="tabpanel" class="tab-pane" id="colortab">
				<div class="alert alert-info" role="alert">
					{{Attention, ne pas modifier les couleurs depuis le plugin agenda}}.<br>
				</div>
				<div class="colorAttr form-group" id="actionTab">
					<br>
					<div class="alert alert-success bt_addColor" role="alert" style="cursor:pointer !important;">
						{{Ajouter une couleur personnalisé}}.
					</div>
					<form class="form-horizontal">
						<fieldset>
							<div class="form-control">
								<a class="col-sm-1">{{}}
								</a>
								<a class="col-sm-5">{{Nom de l'évènement}}
								</a>
								<a class="col-sm-1 text-center">{{Fond}}
								</a>
								<a class="col-sm-1 text-center">{{Texte}}
								</a>
								</div>
							<div id="div_color" class="col-xs-12" style="padding:10px;margin-bottom:15px;background-color:rgb(var(--bg-color));">
								</div>
						</fieldset>
					</form>
				</div>
			</div><!-- /.tabpanel  #colortab-->
			<div role="tabpanel" class="tab-pane" id="starttab">
				<div class="alert alert-info" role="alert">
					{{Attention, ne pas modifier les actions depuis le plugin agenda}}.<br>
				</div>
				<div class="startAttr form-group" id="actionTab">
					<br>
					<div class="alert alert-success bt_addActionStart" role="alert" style="cursor:pointer !important;">
						{{Ajouter une action de début}}.
					</div>
					<form class="form-horizontal">
						<fieldset>
							<div id="div_start" class="col-xs-12" style="padding:10px;margin-bottom:15px;">
							</div>
						</fieldset>
					</form>
				</div>
			</div><!-- /.tabpanel  #starttab-->
			<div role="tabpanel" class="tab-pane" id="endtab">
				<div class="alert alert-info" role="alert">
					{{Attention, ne pas modifier les actions depuis le plugin agenda}}.<br>
				</div>
				<div class="endAttr" id="actionTab">
					<br>
					<div class="alert alert-warning bt_addActionEnd" role="alert" style="cursor:pointer !important;">
						{{Ajouter une action de fin}}.
					</div>
					<form class="form-horizontal">
						<fieldset>
							<div id="div_end" class="col-xs-12" style="padding:10px;margin-bottom:15px;">
							</div>
						</fieldset>
					</form>
				</div>
			</div><!-- /.tabpanel  #endtab-->
		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<?php
include_file('desktop', 'import2calendar', 'js', 'import2calendar');
include_file('core', 'plugin.template', 'js');
?>