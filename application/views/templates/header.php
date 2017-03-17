<?php
if (! defined('BASEPATH'))
	exit('No direct script access allowed');
isset($title) ? $title = 'VileSci - '.$title : $title = 'VileSci';
!isset($jquery) ? $jquery = false : $jquery = $jquery;
!isset($tablesort) ? $tablesort = false : $tablesort = $tablesort;
!isset($sortList) ? $sortList = '0,0' : $sortList = $sortList;
!isset($widgets) ? $widgets = 'zebra' : $widgets = $widgets;
!isset($headers) ? $headers = '' : $headers = $headers;
!isset($tinymce) ? $tinymce = false : $tinymce = $tinymce;
!isset($jsoneditor) ? $jsoneditor = false : $jsoneditor = $jsoneditor;
!isset($jsonforms) ? $jsonforms = false : $jsonforms = $jsonforms;
!isset($textile) ? $textile = false : $textile = $textile;

if ($tablesort)
	$jquery = true;
?>
<!DOCTYPE HTML>
<html>
<head>
	<title><?php echo $title; ?></title>
	<meta charset="UTF-8">
	<link rel="shortcut icon" type="image/x-icon" href="<?php echo base_url('skin/images/Vilesci.ico'); ?>" />
	<link rel="stylesheet"    type="text/css"     href="<?php echo base_url('skin/vilesci.css'); ?>" />
<?php if($tablesort) : ?>
	<link rel="stylesheet"    type="text/css"     href="<?php echo base_url('skin/tablesort.css'); ?>" />
<?php endif ?>
<?php if($jquery) : ?>
	<script type="text/javascript" src="<?php echo base_url('include/js/jquery1.9.min.js'); ?>"></script>
<?php endif ?>
<?php if($tablesort && !empty($tableid)) : ?>
	<script language="Javascript" type="text/javascript">
		$(document).ready(function()
		{
			$("#<?php echo $tableid; ?>").tablesorter(
			{
				sortList: [[<?php echo $sortList; ?>]],
				widgets: ["<?php echo $widgets; ?>"],
				headers: {<?php echo $headers; ?>}
			});
		});
	</script>
<?php endif ?>
<?php if($tinymce) : ?>
	<script type="text/javascript" src="<?php echo base_url('vendor/tinymce/tinymce/tinymce.min.js');?>"></script>
<?php endif ?>
<?php if($textile) : ?>
	<script type="text/javascript" src="<?php echo base_url('include/js/textile.min.js');?>"></script>
<?php endif ?>
<?php if($jsoneditor) : ?>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url('vendor/jsoneditor/dist/jsoneditor.css');?>" />
	<script type="text/javascript" src="<?php echo base_url('vendor/jsoneditor/dist/jsoneditor.js');?>"></script>
<?php endif ?>
<?php if($jsonforms) : ?>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url('vendor/json-forms/dist/css/brutusin-json-forms.min.css'); ?>" />
	<script type="text/javascript" src="<?php echo base_url('vendor/json-forms/dist/js/brutusin-json-forms.min.js'); ?>"></script>
<?php endif ?>
</head>