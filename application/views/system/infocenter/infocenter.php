<?php
	$this->load->view(
		'templates/FHC-Header',
		array(
			'title' => 'Info Center',
			'jquery3' => true,
			'bootstrap' => true,
			'fontawesome' => true,
			'sbadmintemplate' => true,
			'tablesorter' => false,
			'customCSSs' => 'vendor/FHC-vendor/jquery-tablesorter/css/theme.default.css',
			'customJSs' => array(
				'vendor/FHC-vendor/jquery-tablesorter/js/jquery.tablesorter.js',
				'vendor/FHC-vendor/jquery-tablesorter/js/jquery.tablesorter.widgets.js'
			)
		)
	);
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
						<h3 class="page-header">Infocenter &Uuml;bersicht</h3>
					</div>
				</div>
				<div>
					<?php
						$this->load->view('system/infocenter/infocenterData.php');
					?>
				</div>
			</div>
		</div>
	</div>
	<script>
		//javascript hacks for bootstrap
		$("select").addClass("form-control");
		$("input[type=text]").addClass("form-control");
		$("input[type=button]").addClass("btn btn-default");
		$("#tableDataset").addClass('table table-bordered table-responsive table-condensed');
	</script>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>
