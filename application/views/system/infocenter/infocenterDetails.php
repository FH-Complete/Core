<?php
$this->load->view('templates/FHC-Header', array('title' => 'InfocenterDetails', 'jquery3' => true, 'bootstrap' => true, 'fontawesome' => true, 'bootstrapdatepicker' => true, 'datatables' => true, 'sbadmintemplate' => true));
?>
<body>
<div id="wrapper">
	<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
		<?php

		echo $this->widgetlib->widget(
			'FHC_navheader'
		);

		echo $this->widgetlib->widget(
			'FHC_navigation',
			$navigationMenuArray
		);
		?>
	</nav>
	<div id="page-wrapper">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header">Infocenter
						Details: <?php echo $stammdaten->vorname.' '.$stammdaten->nachname ?></h3>
				</div>
			</div>
			<section>
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-heading text-center"><h4>Stammdaten</h4></div>
							<div class="panel-body">
								<div class="row">
									<div class="col-lg-6">
										<table class="table">
											<tr>
												<td><strong>Vorname</strong></td>
												<td><?php echo $stammdaten->vorname ?></td>
											</tr>
											<tr>
												<td><strong>Nachname</strong></td>
												<td>
													<?php echo $stammdaten->nachname ?></td>
											</tr>
											<tr>
												<td><strong>Geburtsdatum</strong></td>
												<td>
													<?php echo date_format(date_create($stammdaten->gebdatum), 'd.m.Y') ?></td>
											</tr>
											<tr>
												<td><strong>Sozialversicherungsnr</strong></td>
												<td>
													<?php echo $stammdaten->svnr ?></td>
											</tr>
											<tr>
												<td><strong>Staatsb&uuml;rgerschaft</strong></td>
												<td>
													<?php echo $stammdaten->staatsbuergerschaft ?></td>
											</tr>
											<tr>
												<td><strong>Geschlecht</strong></td>
												<td>
													<?php echo $stammdaten->geschlecht ?></td>
											</tr>
											<tr>
												<td><strong>Geburtsnation</strong></td>
												<td>
													<?php echo $stammdaten->geburtsnation ?></td>
											</tr>
											<tr>
												<td><strong>Geburtsort</strong></td>
												<td><?php echo $stammdaten->gebort ?></td>
											</tr>
										</table>
									</div>
									<div class="col-lg-6">
										<table class="table table-bordered">
											<thead>
											<tr>
												<th colspan="4" class="text-center">Kontakte</th>
											</tr>
											<tr>
												<th class="text-center">Typ</th>
												<th class="text-center">Kontakt</th>
												<th class="text-center">Zustellung</th>
												<th class="text-center">Anmerkung</th>
											</tr>
											</thead>
											<tbody>
											<?php foreach ($stammdaten->kontakte as $kontakt): ?>

												<tr>
													<td><?php echo ucfirst($kontakt->kontakttyp); ?></td>
													<td>
														<?php if ($kontakt->kontakttyp === 'email'): ?>
														<a href="mailto:<?php echo $kontakt->kontakt; ?>" target="_top">
															<?php
															endif;
															echo $kontakt->kontakt;
															if ($kontakt->kontakttyp === 'email'):
															?>
														</a>
													<?php endif; ?>
													</td>
													<td class="text-center"><?php echo $kontakt->zustellung === true ? '<span class="glyphicon glyphicon-ok"></span>' : ''; ?></td>
													<td><?php echo $kontakt->anmerkung; ?></td>
												</tr>
											<?php endforeach; ?>
											<?php foreach ($stammdaten->adressen as $adresse): ?>
												<tr>
													<td>
														Adresse
													</td>
													<td>
														<?php echo isset($adresse) ? $adresse->strasse.', '.$adresse->plz.' '.$adresse->ort : '' ?>
													</td>
													<td class="text-center">
														<?php echo $adresse->zustelladresse === true ? '<span class="glyphicon glyphicon-ok"></span>' : '' ?>
													</td>
													<td>
														<?php echo ($adresse->heimatadresse === true ? 'Heimatadresse' : '').($adresse->heimatadresse === true && $adresse->rechnungsadresse === true ? ', ' : '').($adresse->rechnungsadresse === true ? 'Rechnungsadresse' : ''); ?>
													</td>
												<tr/>
											<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
			<section>
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<a name="DokPruef"></a><!-- anchor for jumping to the section -->
							<div class="panel-heading text-center"><h4>Dokumentenpr&uuml;fung</h4></div>
							<div class="panel-body">
								<table id="doctable" class="table table-striped table-bordered table-condensed">
									<thead>
									<th>Name</th>
									<th>Typ</th>
									<th>Uploaddatum</th>
									<th>Ausstellungsnation</th>
									<th>Formal gepr&uuml;ft</th>
									<!--							<th>nachzureichen</th>
																<th>nachgereicht am</th>-->
									</thead>
									<tbody>
									<?php
									foreach ($dokumente as $dokument):
										$geprueft = isset($dokument->formal_geprueft_amum) ? "checked" : "";
										?>
										<tr>
											<td>
												<a href="../outputAkteContent/<?php echo $dokument->akte_id ?>"><?php echo empty($dokument->titel) ? $dokument->bezeichnung : $dokument->titel ?></a>
											</td>
											<td><?php echo $dokument->dokument_bezeichnung ?></td>
											<td><?php echo date_format(date_create($dokument->erstelltam), 'd.m.Y') ?></td>
											<td><?php echo $dokument->langtext ?></td>
											<td>
												<input type="checkbox" class="form-check-input"
													   id="prchkbx<?php echo $dokument->akte_id ?>" <?php echo $geprueft ?> />
												<?php echo isset($dokument->formal_geprueft_amum) ? date_format(date_create($dokument->formal_geprueft_amum), 'd.m.Y') : ''; ?>
											</td>
											<!--									<td class="text-center">
										<?php /*echo $dokument->nachgereicht === true ? 'X' : ''; */
											?>
									</td>
									<td>
										<?php /*echo isset($dokument->nachgereicht_am) ? date_format(date_create($dokument->nachgereicht_am), 'd.m.Y') : ''; */
											?>
									</td>-->
										</tr>
									<?php endforeach ?>
									</tbody>
								</table>
								<?php if (count($dokumente_nachgereicht) > 0): ?>
									<br/>
									<p>Nachzureichende Dokumente:</p>
									<table id="nachgdoctable" class="table table-striped table-bordered">
										<thead>
										<th>Typ</th>
										<th>Nachzureichen am</th>
										<th>Ausstellungsnation</th>
										<th>Anmerkung</th>
										</thead>
										<tbody>
										<?php
										foreach ($dokumente_nachgereicht as $dokument):
											?>
											<tr>
												<td><?php echo $dokument->dokument_bezeichnung ?></td>
												<td>
													<?php echo isset($dokument->nachgereicht_am) ? date_format(date_create($dokument->nachgereicht_am), 'd.m.Y') : ''; ?>
												</td>
												<td>
													<?php echo $dokument->langtext ?>
												</td>
												</td>
												<td>
													<?php echo $dokument->anmerkung; ?>
												</td>
											</tr>
										<?php endforeach ?>
										</tbody>
									</table>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</section>
			<section>
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-heading text-center">
								<a name="ZgvPruef"></a>
								<h4>ZGV-Pr&uuml;fung</h4>
							</div>
							<div class="panel-body">
								<div class="panel-group">
									<?php
									foreach ($zgvpruefungen as $zgvpruefung):
										$infoonly = $zgvpruefung->infoonly;
										$firstcolumns = array(3, 2, 2, 2, 3);
										//set bootstrap columns
										if($infoonly)
											$columns = array(3, 2, 2, 5);
										else
											$columns = array(4, 3, 2, 3);
									?>
										<div class="panel panel-default">
											<div class="panel-heading">
												<h4 class="panel-title">
													<a data-toggle="collapse"
													   href="#collapse<?php echo $zgvpruefung->prestudent_id ?>"><?php echo $zgvpruefung->studiengang.' - '.$zgvpruefung->studiengangbezeichnung ?></a>
												</h4>
											</div>
											<div id="collapse<?php echo $zgvpruefung->prestudent_id ?>"
												 class="panel-collapse collapse<?php echo $infoonly ? '' : ' in' ?>">
												<div class="panel-body">
													<form method="post"
														  action="../saveZgvPruefung/<?php echo $zgvpruefung->prestudent_id ?>">
														<div class="row">
															<div class="col-lg-<?php echo $firstcolumns[0] ?>">
																<div class="form-group">
																	<label>Freigegeben an Studiengang: </label>
																	<?php echo isset($zgvpruefung->prestudentstatus->bestaetigtam) ? "ja" : "nein" ?>
																</div>
															</div>
															<div class="col-lg-<?php echo $firstcolumns[1] ?>">
																<div class="form-group">
																	<label>Letzter Status: </label>
																	<?php
																	if(isset($zgvpruefung->prestudentstatus->status_kurzbz))
																	{
																		echo $zgvpruefung->prestudentstatus->status_kurzbz.(isset($zgvpruefung->prestudentstatus->bezeichnung_statusgrund[0]) && $zgvpruefung->prestudentstatus->status_kurzbz === 'Abgewiesener' ? ' ('.$zgvpruefung->prestudentstatus->bezeichnung_statusgrund[0].')' : '');
																	}
																	?>
																</div>
															</div>
															<div class="col-lg-<?php echo $firstcolumns[2] ?>">
																<div class="form-group">
																	<label>Studiensemester: </label>
																	<?php echo isset($zgvpruefung->prestudentstatus->studiensemester_kurzbz) ? $zgvpruefung->prestudentstatus->studiensemester_kurzbz : '' ?>
																</div>
															</div>
															<div class="col-lg-<?php echo $firstcolumns[3] ?>">
																<div class="form-group">
																	<label><span style="display: inline-block">Ausbildungs</span><span style="display: inline-block">semester: </span></label>
																	<?php echo isset($zgvpruefung->prestudentstatus->ausbildungssemester) ? $zgvpruefung->prestudentstatus->ausbildungssemester : '' ?>
																</div>
															</div>
															<div class="col-lg-<?php echo $firstcolumns[4] ?>">
																<div class="form-group">
																	<label>Orgform: </label>
																	<span style="display: inline-block">
																	<?php
																	$separator = (isset($zgvpruefung->prestudentstatus->orgform)) ? ', ' : '';
																	echo (isset($zgvpruefung->prestudentstatus->orgform) ? $zgvpruefung->prestudentstatus->orgform : '')
																	.(isset($zgvpruefung->prestudentstatus->sprachedetails->bezeichnung) ? $separator.$zgvpruefung->prestudentstatus->sprachedetails->bezeichnung[0] : '')
																	.(isset($zgvpruefung->prestudentstatus->alternative) ? ' ('.$zgvpruefung->prestudentstatus->alternative.')' : '') ?>
																	</span>
																</div>
															</div>
														</div>
														<div class="row">
															<div class="col-lg-<?php echo $columns[0] ?>">
																<div class="form-group">
																	<label>ZGV: </label>
																	<?php if ($infoonly)
																		echo $zgvpruefung->zgv_bez;
																	else
																		echo $this->widgetlib->widget(
																			'Zgv_widget',
																			array(DropdownWidget::SELECTED_ELEMENT => $zgvpruefung->zgv_code),
																			array('name' => 'zgv', 'id' => 'zgv')
																		); ?>
																</div>
															</div>
															<div class="col-lg-<?php echo $columns[1] ?>">
																<div class="form-group">
																	<label>ZGV Ort: </label>
																	<?php if ($infoonly):
																		echo html_escape($zgvpruefung->zgvort);
																	else:
																		?>
																		<input type="text" class="form-control"
																			   value="<?php echo $zgvpruefung->zgvort ?>"
																			   name="zgvort">
																	<?php endif; ?>
																</div>
															</div>
															<div class="col-lg-<?php echo $columns[2] ?>">
																<div class="form-group">
																	<label>ZGV Datum: </label>
																	<?php if ($infoonly):
																		echo date_format(date_create($zgvpruefung->zgvdatum), 'd.m.Y');
																	else:
																		?>
																		<input type="text"
																			   class="dateinput form-control"
																			   value="<?php echo empty($zgvpruefung->zgvdatum) ? "" : date_format(date_create($zgvpruefung->zgvdatum), 'd.m.Y') ?>"
																			   name="zgvdatum">
																	<?php endif; ?>
																</div>
															</div>
															<div class="col-lg-<?php echo $columns[3] ?>">
																<div class="form-group">
																	<label>ZGV Nation: </label>
																	<?php if ($infoonly)
																		echo $zgvpruefung->zgvnation_bez;
																	else
																		echo $this->widgetlib->widget(
																			'Nation_widget',
																			array(DropdownWidget::SELECTED_ELEMENT => $zgvpruefung->zgvnation_code),
																			array('name' => 'zgvnation', 'id' => 'zgvnation')
																		); ?>
																</div>
															</div>
														</div>
														<!-- show only master zgv if master studiengang - start -->
														<?php if ($zgvpruefung->studiengangtyp === 'm') : ?>
															<div class="row">
																<div class="col-lg-<?php echo $columns[0] ?>">
																	<div class="form-group"><label>ZGV Master: </label>
																		<?php
																		if ($infoonly)
																			echo $zgvpruefung->zgvmas_bez;
																		else
																			echo $this->widgetlib->widget(
																				'Zgvmaster_widget',
																				array(DropdownWidget::SELECTED_ELEMENT => $zgvpruefung->zgvmas_code),
																				array('name' => 'zgvmas', 'id' => 'zgvmas')
																			); ?>
																	</div>
																</div>
																<div class="col-lg-<?php echo $columns[1] ?>">
																	<div class="form-group">
																		<label>ZGV Master Ort: </label>
																		<?php if ($infoonly):
																			echo $zgvpruefung->zgvmaort;
																		else:
																			?>
																			<input type="text" class="form-control"
																				   value="<?php echo $zgvpruefung->zgvmaort ?>"
																				   name="zgvmaort">
																		<?php endif; ?>
																	</div>
																</div>
																<div class="col-lg-<?php echo $columns[2] ?>">
																	<div class="form-group">
																		<label>ZGV Master Datum: </label>
																		<?php if ($infoonly):
																			echo date_format(date_create($zgvpruefung->zgvmadatum), 'd.m.Y');
																		else:
																			?>
																			<input type="text"
																				   class="dateinput form-control"
																				   value="<?php echo empty($zgvpruefung->zgvmadatum) ? "" : date_format(date_create($zgvpruefung->zgvmadatum), 'd.m.Y') ?>"
																				   name="zgvmadatum">
																		<?php endif; ?>
																	</div>
																</div>
																<div class="col-lg-<?php echo $columns[3] ?>">
																	<div class="form-group"><label>ZGV Master
																			Nation: </label>
																		<?php
																		if ($infoonly)
																			echo $zgvpruefung->zgvmanation_bez;
																		else
																			echo $this->widgetlib->widget(
																				'Nation_widget',
																				array(DropdownWidget::SELECTED_ELEMENT => $zgvpruefung->zgvmanation_code),
																				array('name' => 'zgvmanation', 'id' => 'zgvmanation')
																			); ?>
																	</div>
																</div>
															</div>
															<!-- show only master zgv if master studiengang - end -->
														<?php endif; ?>
														<?php if (!$infoonly): ?>
															<div class="row">
																<div class="col-lg-12">
																	<button type="submit" class="btn btn-default">
																		Speichern
																	</button>
																</div>
															</div>
														<?php endif; ?>
													</form>

													<?php
													//Prestudenten cannot be abgewiesen or freigegeben if already done
													if (!$infoonly) :
														?>
														<hr>
														<div class="row">
															<div class="col-lg-12">
																<form method="post"
																	  action="../saveAbsage/<?php echo $zgvpruefung->prestudent_id ?>">
																	<div class="form-group">
																		<label class="d-inline float-left">Absagegrund:</label>
																		<select name="statusgrund"
																				class="d-inline float-right">
																			<?php foreach ($statusgruende as $statusgrund): ?>
																				<option value="<?php echo $statusgrund->statusgrund_id ?>"><?php echo $statusgrund->bezeichnung_mehrsprachig[0] ?></option>
																			<?php endforeach ?>
																		</select>
																	</div>
																	<button type="button" class="btn btn-default"
																			data-toggle="modal"
																			data-target="#absageModal">
																		Absage
																	</button>
																	<div class="modal fade" id="absageModal"
																		 tabindex="-1"
																		 role="dialog"
																		 aria-labelledby="absageModalLabel"
																		 aria-hidden="true">
																		<div class="modal-dialog">
																			<div class="modal-content">
																				<div class="modal-header">
																					<button type="button" class="close"
																							data-dismiss="modal"
																							aria-hidden="true">&times;
																					</button>
																					<h4 class="modal-title"
																						id="absageModalLabel">Absage
																						best&auml;tigen</h4>
																				</div>
																				<div class="modal-body">
																					Bei Absage von InteressentInnen
																					erhalten
																					diese den Status "Abgewiesener"
																					und<br/>deren
																					Zgvdaten können
																					im Infocenter nicht mehr bearbeitet
																					oder
																					freigegeben werden.
																					<br/>Alle nicht gespeicherten
																					Zgvdaten
																					gehen
																					verloren. Fortfahren?
																				</div>
																				<div class="modal-footer">
																					<button type="button"
																							class="btn btn-default"
																							data-dismiss="modal">
																						Abbrechen
																					</button>
																					<button type="submit"
																							class="btn btn-primary">
																						InteressentIn abweisen
																					</button>
																				</div>
																			</div>
																			<!-- /.modal-content -->
																		</div>
																		<!-- /.modal-dialog -->
																	</div>
																</form>
															</div>
														</div>
														<hr>
														<div class="row">
															<div class="col-lg-12">
																<button type="button" class="btn btn-default"
																		data-toggle="modal"
																		data-target="#freigabeModal">
																	Freigabe an Studiengang
																</button>
															</div>
														</div>
														<div class="modal fade" id="freigabeModal" tabindex="-1"
															 role="dialog"
															 aria-labelledby="freigabeModalLabel" aria-hidden="true">
															<div class="modal-dialog">
																<div class="modal-content">
																	<div class="modal-header">
																		<button type="button" class="close"
																				data-dismiss="modal"
																				aria-hidden="true">&times;
																		</button>
																		<h4 class="modal-title" id="freigabeModalLabel">
																			Freigabe
																			best&auml;tigen</h4>
																	</div>
																	<div class="modal-body">
																		Bei Freigabe von InteressentInnen wird deren
																		Interessentenstatus bestätigt und<br/>deren
																		Zgvdaten
																		können im
																		Infocenter nicht mehr bearbeitet oder
																		freigegeben
																		werden.
																		<br/>Alle nicht gespeicherten Zgvdaten gehen
																		verloren.
																		Fortfahren?
																	</div>
																	<div class="modal-footer">
																		<button type="button" class="btn btn-default"
																				data-dismiss="modal">Abbrechen
																		</button>
																		<a href="../saveFreigabe/<?php echo $zgvpruefung->prestudent_id ?>">
																			<button type="button"
																					class="btn btn-primary">
																				InteressentIn freigeben
																			</button>
																		</a>
																	</div>
																</div>
																<!-- /.modal-content -->
															</div>
															<!-- /.modal-dialog -->
														</div>
													<?php endif; //end if infoonly
													?>
												</div>
											</div>
										</div>
									<?php endforeach; // end foreach zgvpruefungen?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
			<section>
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-default">
							<div class="panel-heading text-center">
								<a name="NotizAkt"></a>
								<h4 class="text-center">Notizen &amp; Aktivit&auml;ten</h4>
							</div>
							<div class="panel-body">
								<div class="row">
									<div class="col-lg-6">
										<form method="post" action="../saveNotiz/<?php echo $stammdaten->person_id ?>">
											<div class="form-group">
												<div class="text-center">
													<label>Notiz hinzuf&uuml;gen</label>
												</div>
												<div class="form-group">
													<label>Titel: </label><input type="text" class="form-control"
																				 name="notiztitel"/>
												</div>
												<div class="form-group">
													<label>Text: </label><textarea name="notiz" class="form-control"
																				   rows="10"
																				   cols="32"></textarea>
												</div>
												<button type="submit" class="btn btn-default">Speichern</button>
											</div>
										</form>
										<table id="notiztable" class="table table-bordered table-hover">
											<thead>
											<th>Datum</th>
											<th>Notiz</th>
											<th>User</th>
											</thead>
											<tbody>

											<?php foreach ($notizen as $notiz): ?>
												<tr data-toggle="tooltip"
													title="<?php echo isset($notiz->text) ? $notiz->text : '' ?>">
													<td><?php echo date_format(date_create($notiz->insertamum), 'd.m.Y H:i:s') ?></td>
													<td><?php echo html_escape($notiz->titel) ?></td>
													<td><?php echo $notiz->verfasser_uid ?></td>
												</tr>
											<?php endforeach ?>
											</tbody>
										</table>
									</div>
									<div class="col-lg-6">
										<table id="logtable" class="table table-bordered table-hover">
											<thead>
											<th>Datum</th>
											<th>Aktivit&auml;t</th>
											<th>User</th>
											</thead>
											<tbody>
											<?php foreach ($logs as $log): ?>
												<tr data-toggle="tooltip"
													title="<?php echo isset($log->logdata->message) ? $log->logdata->message : '' ?>">
													<td><?php echo date_format(date_create($log->zeitpunkt), 'd.m.Y H:i:s') ?></td>
													<td><?php echo isset($log->logdata->name) ? $log->logdata->name : '' ?></td>
													<td><?php echo $log->insertvon ?></td>
												</tr>
											<?php endforeach ?>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
	</div>
</div>
<script>

	$(document).ready(function ()
		{
			//language for datatables
			/*			var german =
			 {
			 "sEmptyTable":      "Keine Daten in der Tabelle vorhanden",
			 "sInfo":            "_START_ bis _END_ von _TOTAL_ Einträgen",
			 "sInfoEmpty":       "0 bis 0 von 0 Einträgen",
			 "sInfoFiltered":    "(gefiltert von _MAX_ Einträgen)",
			 "sInfoPostFix":     "",
			 "sInfoThousands":   ".",
			 "sLengthMenu":      "_MENU_ Einträge anzeigen",
			 "sLoadingRecords":  "Wird geladen...",
			 "sProcessing":      "Bitte warten...",
			 "sSearch":          "Suchen",
			 "sZeroRecords":     "Keine Einträge vorhanden.",
			 "oPaginate": {
			 "sFirst":       "Erste",
			 "sPrevious":    "Zurück",
			 "sNext":        "Nächste",
			 "sLast":        "Letzte"
			 },
			 "oAria": {
			 "sSortAscending":  ": aktivieren, um Spalte aufsteigend zu sortieren",
			 "sSortDescending": ": aktivieren, um Spalte absteigend zu sortieren"
			 },
			 select: {
			 rows: {
			 _: '%d Zeilen ausgewählt',
			 0: 'Zum Auswählen auf eine Zeile klicken',
			 1: '1 Zeile ausgewählt'
			 }
			 }
			 };*/
			var german = "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/German.json";
			//hack for disabling table pagination if only one page
			var drawCallback = function (settings)
			{
				var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
				pagination.toggle(this.api().page.info().pages > 1);
			};

			//format for sorting dates in tables
			$.fn.dataTable.moment("DD.MM.YYYY");
			$.fn.dataTable.moment("DD.MM.YYYY HH:mm:ss");

			//initialise datatables and datepicker
			$("#doctable").DataTable({
				"language": {"url": german},
				"responsive": true,
				"paging": false,
				"searching": false,
				"info": false,
				"order": [[2, "desc"], [1, "asc"]]
			});
			$("#nachgdoctable").DataTable({
				"language": {"url": german},
				"responsive": true,
				"paging": false,
				"searching": false,
				"info": false,
				"order": [[2, "asc"], [1, "desc"]]
			});
			$("#logtable").DataTable({
				"language": {"url": german},
				"responsive": false,
				"lengthChange": false,
				"info": false,
				"pageLength": 25,
				"order": [[0, "desc"]],
				"drawCallback": drawCallback
			});
			$("#notiztable").DataTable({
				"language": {"url": german},
				"responsive": false,
				"lengthChange": false,
				"info": false,
				"pageLength": 13,
				"order": [[0, "desc"], [2, "asc"]],
				"drawCallback": drawCallback
			});
			$(".dateinput").datepicker({
				"language": "de",
				"format": "dd.mm.yyyy"
			});
			//javascript hack - not nice!
			$("select").addClass('form-control');

			//add click events to "formal geprüft" checkboxes
			<?php foreach($dokumente as $dokument): ?>

			if ($("#prchkbx<?php echo $dokument->akte_id; ?>"))
			{
				$("#prchkbx<?php echo $dokument->akte_id; ?>").click(function ()
				{
					window.location = "../saveFormalGeprueft?akte_id=<?php echo $dokument->akte_id; ?>&formal_geprueft=" + this.checked + "&person_id=<?php echo $stammdaten->person_id ?>";
				});
			}
			<?php endforeach ?>
		}
	);
</script>
</body>
<?php $this->load->view('templates/FHC-Footer'); ?>
