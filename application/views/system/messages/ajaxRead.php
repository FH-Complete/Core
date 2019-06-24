<?php
	$this->load->view(
		'templates/FHC-Header',
		array(
			'title' => 'Read personal messages',
			'jquery' => true,
			'jqueryui' => true,
			'bootstrap' => true,
			'momentjs' => true,
			'tabulator' => true,
			'ajaxlib' => true,
			'dialoglib' => true,
			'customJSs' => array('public/js/messaging/messageClient.js')
		)
	);
?>
	<body>
		<div id="lstMessagesPanel"></div>
		<div id="readMessagePanel"></div>
	</body>

<?php $this->load->view("templates/FHC-Footer"); ?>
