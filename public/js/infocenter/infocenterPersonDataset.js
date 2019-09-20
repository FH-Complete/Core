/**
 * Javascript file for infocenter overview page
 */

/**
* Global function used by FilterWidget JS to refresh the side menu
* NOTE: it is called from the FilterWidget JS therefore must be a global function
* 		To be called only if the page has a customized menu (currently only index)
*/
if (FHC_JS_DATA_STORAGE_OBJECT.called_method == 'index')
{
	function refreshSideMenuHook()
	{
		FHC_NavigationWidget.refreshSideMenuHook('system/infocenter/InfoCenter/setNavigationMenuArrayJson');
	}
}

/**
 *
 */
var InfocenterPersonDataset = {

	/**
	 * adds person table additional actions html (above and beneath it)
	 */
	appendTableActionsHtml: function()
	{
		var currurl = window.location.href;
		var url = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router + "/system/Messages/write";

		var formHtml = '<form id="sendMsgsForm" method="post" action="'+ url +'" target="_blank"></form>';
		$("#datasetActionsTop").before(formHtml);

		var selectAllHtml =
			'<a href="javascript:void(0)" class="selectAll">' +
			'<i class="fa fa-check"></i>&nbsp;Alle</a>&nbsp;&nbsp;' +
			'<a href="javascript:void(0)" class="unselectAll">' +
			'<i class="fa fa-times"></i>&nbsp;Keinen</a>&nbsp;&nbsp;&nbsp;&nbsp;';

		var actionHtml = 'Mit Ausgew&auml;hlten:&nbsp;&nbsp;' +
			'<a href="javascript:void(0)" class="sendMsgsLink">' +
			'<i class="fa fa-envelope"></i>&nbsp;Nachricht senden</a>';

		var legendHtml = '<i class="fa fa-circle text-danger"></i> Gesperrt&nbsp;&nbsp;&nbsp;&nbsp;' +
			'<i class="fa fa-circle text-info"></i> Geparkt';

		var personcount = 0;

		FHC_AjaxClient.ajaxCallGet(
			'widgets/Filters/rowNumber',
			{
				filterUniqueId: FHC_FilterWidget.getFilterUniqueIdPrefix()
			},
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.hasData(data))
					{
						personcount = FHC_AjaxClient.getData(data);

						if (personcount > 0)
						{
							var persontext = personcount === 1 ? "Person" : "Personen";
							var countHtml = personcount + " " + persontext;

							// Count Records after Filtering
							$("#filterTableDataset").bind("filterEnd", function() {
								var cnt = $("#filterTableDataset tr:visible").length - 2;
								$(".filterTableDatasetCntFiltered").html(cnt + ' / ');
							});

							$("#datasetActionsTop, #datasetActionsBottom").append(
								"<div class='row'>"+
								"<div class='col-xs-6'>" + selectAllHtml + "&nbsp;&nbsp;" + actionHtml + "</div>"+
								"<div class='col-xs-4'>" + legendHtml + "</div>"+
								"<div class='col-xs-2 text-right'>" +
								"<span class='filterTableDatasetCntFiltered'></span>" +
								countHtml +	"</div>"+
								"<div class='clearfix'></div>"+
								"</div>"
							);
							$("#datasetActionsBottom").append("<br><br>");

							InfocenterPersonDataset.setTableActions();
						}
					}
				},
				errorCallback: function(jqXHR, textStatus, errorThrown) {
					alert(textStatus);
				}
			}
		);
	},

	/**
	 * sets functionality for the actions above and beneath the person table
	 */
	setTableActions: function()
	{
		$(".sendMsgsLink").click(function() {
			var idsel = $("#filterTableDataset input:checked[name=PersonId\\[\\]]");
			if(idsel.length > 0)
			{
				var form = $("#sendMsgsForm");
				form.find("input[type=hidden]").remove();
				for (var i = 0; i < idsel.length; i++)
				{
					var id = $(idsel[i]).val();
					form.append("<input type='hidden' name='person_id[]' value='" + id + "'>");
				}
				form.submit();
			}
		});

		$(".selectAll").click(function()
			{
				//select only trs if not filtered by tablesorter
				var trs = $("#filterTableDataset tbody tr").not(".filtered");
				trs.find("input[name=PersonId\\[\\]]").prop("checked", true);
			}
		);

		$(".unselectAll").click(function()
			{
				var trs = $("#filterTableDataset tbody tr").not(".filtered");
				trs.find("input[name=PersonId\\[\\]]").prop("checked", false);
			}
		);
	}

};

/**
 * When JQuery is up
 */
$(document).ready(function() {

	InfocenterPersonDataset.appendTableActionsHtml();

});
