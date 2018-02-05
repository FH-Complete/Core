<div class="navbar-default sidebar" role="navigation">
	<div class="sidebar-nav navbar-collapse">
		<ul class="nav" id="side-menu">
			<li id="collapseicon" class="text-right" style="cursor: pointer; color: #337ab7"><i
						class="fa fa-angle-double-left fa-fw"></i>
			</li>
			<?php NavigationMenuWidget::printNavigationMenu(); ?>
		</ul>
	</div>
	<i id="collapseinicon" class="fa fa-angle-double-right fa-fw"></i>
</div>
<style>
	#collapseinicon{
		display: none;
		cursor: pointer;
		color: #337ab7;
		border-bottom: 1px solid #e7e7e7;
		border-right: 1px solid #e7e7e7;
		border-left: 1px solid #e7e7e7;
		position: absolute;
		width: 45px;
		height: 20px;
		background-color: #F8F8F8;
	}
</style>
<script>
	//hiding/showing navigation menu - works only with sb admin 2 template!!
	$("#collapseicon").click(
		function ()
		{
			$("#page-wrapper").css('margin-left', '0px');
			$("#side-menu").hide();
			$("#collapseinicon").show();
		}
	);

	$("#collapseinicon").click(
		function ()
		{
			$("#page-wrapper").css('margin-left', '250px');
			$("#side-menu").show();
			$("#collapseinicon").hide();
		}
	);

</script>