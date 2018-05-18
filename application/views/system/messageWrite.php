<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'MessageReply',
		'jquery' => true,
		'bootstrap' => true,
		'fontawesome' => true,
		'tinymce' => true,
		'sbadmintemplate' => true,
		'customCSSs' => array('public/css/sbadmin2/admintemplate_contentonly.css', 'public/css/messageWrite.css'),
		'customJSs' => array('public/js/bootstrapper.js')
	)
);
?>
<body>
<?php
$href = site_url().'/system/Messages/send/';
?>
<div id="wrapper">
	<div id="page-wrapper">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header">Send Message</h3>
				</div>
			</div>
			<form id="sendForm" method="post" action="<?php echo $href; ?>">
				<?php $this->load->view('system/messageForm.php'); ?>
				<br>
				<div class="row">
					<div class="col-lg-3 text-right">
						<?php
						echo $this->widgetlib->widget(
							'Vorlage_widget',
							array('oe_kurzbz' => $oe_kurzbz, 'isAdmin' => $isAdmin),
							array('name' => 'vorlage', 'id' => 'vorlageDnD')
						);
						?>
					</div>
				</div>
				<?php if (isset($receivers) && count($receivers) > 0): ?>
					<hr>
					<div class="row">
						<div class="col-lg-12">
							<label>Preview:</label>
						</div>
					</div>
					<div class="well">
						<div class="row">
							<div class="col-lg-5">
								<div class="form-grop form-inline">
									<label>Recipient:</label>
									<select id="recipients">
										<?php 
										if (count($receivers) > 1)
											echo '<option value="-1">Select...</option>';

										$idtype = $personOnly === true ? 'person_id' : 'prestudent_id';
										foreach ($receivers as $receiver)
										{
											?>
											<option value="<?php echo $receiver->{$idtype}; ?>"><?php echo $receiver->Vorname." ".$receiver->Nachname; ?></option>
											<?php
										}
										?>
									</select>
									&nbsp;
									<strong><a href="#" id="refresh">Refresh</a></strong>
								</div>
							</div>
							<div class="col-lg-2">

							</div>
						</div>
						<br>
						<textarea id="tinymcePreview"></textarea>
					</div>
					<?php
				endif;
				?>

				<?php
				for ($i = 0; $i < count($receivers); $i++)
				{
					$receiver = $receivers[$i];
					if ($personOnly === true)
					{
						$receiverid = $receiver->person_id;
						$fieldname = 'persons[]';
					}
					else
					{
						$receiverid = $receiver->prestudent_id;
						$fieldname = 'prestudents[]';
					}
					echo '<input type="hidden" name="'.$fieldname.'" value="'.$receiverid.'">'."\n";
				}
				?>

				<?php
				if (isset($message))
				{
					?>
					<input type="hidden" name="relationmessage_id" value="<?php echo $message->message_id; ?>">
					<?php
				}
				?>

			</form>
		</div>
	</div>
</div>
<script>
	const CONTROLLER_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router + "/"+FHC_JS_DATA_STORAGE_OBJECT.called_path;

	tinymce.init({
		selector: "#bodyTextArea",
		plugins: "autoresize",
		autoresize_min_height: 150,
		autoresize_max_height: 600,
		autoresize_bottom_margin: 10
	});

	tinymce.init({
		menubar: false,
		toolbar: false,
		statusbar: false,
		readonly: 1,
		selector: "#tinymcePreview",
		plugins: "autoresize",
		autoresize_min_height: 150,
		autoresize_bottom_margin: 10
	});

	$(document).ready(function ()
	{
		if ($("#variables"))
		{
			$("#variables").dblclick(function ()
			{
				if ($("#bodyTextArea"))
				{
					//if editor active add at cursor position, otherwise at end
					if (tinymce.activeEditor.id === "bodyTextArea")
						tinymce.activeEditor.execCommand('mceInsertContent', false, $(this).children(":selected").val());
					else
						tinyMCE.get("bodyTextArea").setContent(tinyMCE.get("bodyTextArea").getContent() + $(this).children(":selected").val());
				}
			});
		}

		if ($("#recipients"))
		{
			$("#recipients").change(tinymcePreviewSetContent);
		}

		if ($("#refresh"))
		{
			$("#refresh").click(tinymcePreviewSetContent);
		}

		if ($("#sendButton") && $("#sendForm"))
		{
			$("#sendButton").click(function ()
			{
				if ($("#subject") && $("#subject").val() != '' && tinyMCE.get("bodyTextArea").getContent() != '')
				{
					$("#sendForm").submit();
				}
				else
				{
					alert("Subject and text are required fields!");
				}
			});
		}

		if ($("#vorlageDnD"))
		{
			$("#vorlageDnD").change(function ()
			{
				if (this.value != '')
				{
					$.ajax({
						dataType: "json",
						url: CONTROLLER_URL+"/getVorlage",
						data: {"vorlage_kurzbz": this.value},
						success: function (data, textStatus, jqXHR)
						{
							tinyMCE.get("bodyTextArea").setContent(data.retval[0].text);
							$("#subject").val(data.retval[0].subject);
						},
						error: function (jqXHR, textStatus, errorThrown)
						{
							alert(textStatus + " - " + errorThrown);
						}
					});
				}
			});
		}

		$("#subject").focus();

	});

	function tinymcePreviewSetContent()
	{
		if ($("#tinymcePreview"))
		{
			if ($("#recipients").children(":selected").val() > -1)
			{
				parseMessageText($("#recipients").children(":selected").val(), tinyMCE.get("bodyTextArea").getContent());
			}
			else
			{
				tinyMCE.get("tinymcePreview").setContent("");
			}
		}
	}

	function parseMessageText(receiver_id, text)
	{
		<?php
		$idtype = $personOnly === true ? 'person_id' : 'prestudent_id';
		?>

		$.ajax({
			dataType: "json",
			url: CONTROLLER_URL+"/parseMessageText",
			data: {"<?php echo $idtype ?>": receiver_id, "text": text},
			success: function (data, textStatus, jqXHR)
			{
				tinyMCE.get("tinymcePreview").setContent(data);
			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				alert(textStatus + " - " + errorThrown + " - " + jqXHR.responseText);
			}
		});
	}
</script>

</body>

<?php $this->load->view("templates/FHC-Footer"); ?>
