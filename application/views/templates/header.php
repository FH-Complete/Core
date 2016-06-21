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

if ($tablesort)
	$jquery = true;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title><?php echo $title; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="shortcut icon" href="<?php echo base_url('skin/images/Vilesci.ico'); ?>" type="image/x-icon">
	<link rel="stylesheet" href="<?php echo base_url('skin/vilesci.css'); ?>" type="text/css" />

<?php if($tablesort) : ?>
	<link href="<?php echo base_url('skin/tablesort.css'); ?>" rel="stylesheet" type="text/css"/>
<?php endif ?>

<?php if($jquery) : ?>
	<script src="<?php echo base_url('include/js/jquery1.9.min.js'); ?>" type="text/javascript"></script>
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

<?php if($jsoneditor) : ?>
	<link href="<?php echo base_url('vendor/jsoneditor/dist/jsoneditor.css');?>" rel="stylesheet" type="text/css">
  <script src="<?php echo base_url('vendor/jsoneditor/dist/jsoneditor.js');?>"></script>
<?php endif ?>

<?php if($jsonforms) : ?>
	<link rel="stylesheet" href="<?php echo base_url('vendor/json-forms/dist/css/brutusin-json-forms.min.css'); ?>"/>
	<script src="<?php echo base_url('vendor/json-forms/dist/js/brutusin-json-forms.min.js'); ?>"></script>
<?php endif ?>

</head>

