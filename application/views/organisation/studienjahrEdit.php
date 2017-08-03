<?php
$jahr = $studienjahr[0];
$this->load->view('templates/header', array('title' => 'StudienjahrEdit', 'jquery' => true));
?>
<body>
<div class="row">
	<div class="row">
		<div class="span4">
			<h2>Studienjahr bearbeiten: <?php echo $jahr->studienjahr_kurzbz; ?></h2>
			<form method="post" action="<?php echo APP_ROOT."index.ci.php/organisation/studienjahr/saveStudienjahr" ?>">

				<table>
					<?php include('studienjahrForm.php'); ?>
					<input type="hidden" name="studienjahrkurzbz" value="<?php echo $jahr->studienjahr_kurzbz; ?>"/>
			</form>
		</div>
	</div>
</div>
</body>
</html>