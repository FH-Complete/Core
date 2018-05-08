/**
 *
 */
function getUrlParameter(sParam)
{
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++)
	{
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam)
		{
            return sParameterName[1];
        }
    }
}

var fhc_controller_id = getUrlParameter("fhc_controller_id");

/**
 * javascript file for infocenterDetails page
 */
$(document).ready(
	function ()
	{
		//initialise table sorter
		addTablesorter("doctable", [[2, 1], [1, 0]], ["zebra"]);
		addTablesorter("nachgdoctable", [[2, 0], [1, 1]], ["zebra"]);
		addTablesorter("msgtable", [[0, 1], [2, 0]], ["zebra", "filter"], 2);
		tablesortAddPager("msgtable", "msgpager", 10);

		formatNotizTable();
		formatLogTable();

		//initialise datepicker
		$.datepicker.setDefaults($.datepicker.regional['de']);
		$(".dateinput").datepicker({
			"dateFormat": "dd.mm.yy"
		});

		//add submit event to message send link
		$("#sendmsglink").click(
			function ()
			{
				$("#sendmsgform").submit();
			}
		);

		//add click events to "formal geprüft" checkboxes
		$(".prchbox").click(function ()
		{
			var boxid = this.id;
			var personid = $("#hiddenpersonid").val();
			var akteid = boxid.substr(boxid.indexOf("_") + 1);
			var checked = this.checked;
			saveFormalGeprueft(personid, akteid, checked)
		});

		//zgv übernehmen
		$(".zgvUebernehmen").click(function ()
		{
			var btn = $(this);
			var personid = $("#hiddenpersonid").val();
			var prestudentid = this.id.substr(this.id.indexOf("_") + 1);
			$('#zgvUebernehmenNotice').remove();
			zgvUebernehmen(personid, prestudentid, btn)
		});

		//zgv speichern
		$(".zgvform").on('submit', function (e)
			{
				e.preventDefault();
				var data = $(this).serializeArray();
				saveZgv(data);
			}
		);

		//show popup with zgvinfo
		$(".zgvinfo").click(function ()
			{
				var prestudentid = this.id.substr(this.id.indexOf("_") + 1);
				openZgvInfoForPrestudent(prestudentid);
			}
		);

		//prevent opening modal when Statusgrund not chosen
		$(".absageModal").on('show.bs.modal', function (e)
			{
				var id = this.id.substr(this.id.indexOf("_") + 1);
				var statusgrvalue = $("#statusgrselect_" + id + " select[name=statusgrund]").val();
				if (statusgrvalue === "null")
				{
					$("#statusgrselect_" + id).addClass("has-error");
					return e.preventDefault();
				}
			}
		);

		//remove red mark when statusgrund is selected again
		$("select[name=statusgrund]").change(
			function ()
			{
				$(this).parent().removeClass("has-error");
			}
		);

		//save notiz
		$("#notizform").on("submit", function (e)
			{
				e.preventDefault();
				var personId = $("#hiddenpersonid").val();
				var notizId = $("#notizform :input[name='hiddenNotizId']").val();
				var data = $(this).serializeArray();

				if (notizId !== '')
				{
					updateNotiz(notizId, personId, data);
				}
				else
				{
					saveNotiz(personId, data);
				}
			}
		);

		//update notiz - autofill notizform
		$(document).on("click", "#notiztable tbody tr", function ()
			{
				var notizId = $(this).find("td:eq(3)").html();
				var notizTitle = $(this).find("td:eq(1)").text();
				var notizContent = this.title;

				$("#notizform label:first").text("Notiz ändern").css("color", "red");
				$("#notizform :input[type='reset']").css("display", "inline-block");

				$("#notizform :input[name='hiddenNotizId']").val(notizId);
				$("#notizform :input[name='notiztitel']").val(notizTitle);
				$("#notizform :input[name='notiz']").val(notizContent);
			}
		);

		//update notiz - abbrechen-button: reset styles
		$("#notizform :input[type='reset']").click(function ()
			{
				resetNotizFields();
			}
		);

	});

function openZgvInfoForPrestudent(prestudent_id)
{
	var screenwidth = screen.width;
	var popupwidth = 760;
	var marginleft = screenwidth - popupwidth;
	console.log(marginleft);
	window.open("../getZgvInfoForPrestudent/" + prestudent_id, "_blank","resizable=yes,scrollbars=yes,width="+popupwidth+",height="+screen.height+",left="+marginleft);
}

// -----------------------------------------------------------------------------------------------------------------
// ajax calls

function saveFormalGeprueft(personid, akteid, checked)
{
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "../saveFormalGeprueft/" + personid + '?fhc_controller_id=' + fhc_controller_id,
		data: {"akte_id": akteid, "formal_geprueft": checked},
		success: function (data, textStatus, jqXHR)
		{
			if (data === null)
			{
				$("#formalgeprueftam_" + akteid).text("");
			}
			else
			{
				fgdatum = $.datepicker.parseDate("yy-mm-dd", data);
				gerfgdatum = $.datepicker.formatDate("dd.mm.yy", fgdatum);
				$("#formalgeprueftam_" + akteid).text(gerfgdatum);
			}
			//refresh doctable tablesorter, formal geprueft changed!
			$("#doctable").trigger("update");
			refreshLog();
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			alert(textStatus + " - " + errorThrown + " - " + jqXHR.responseText);
		}
	});
}

function zgvUebernehmen(personid, prestudentid, btn)
{
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "../getLastPrestudentWithZgvJson/" + personid + '?fhc_controller_id=' + fhc_controller_id,
		success: function (data, textStatus, jqXHR)
		{
			if (data !== null)
			{
				var zgvcode = data.zgv_code !== null ? data.zgv_code : "null";
				var zgvort = data.zgvort !== null ? data.zgvort : "";
				var zgvdatum = data.zgvdatum;
				var gerzgvdatum = "";
				if (zgvdatum !== null)
				{
					zgvdatum = $.datepicker.parseDate("yy-mm-dd", data.zgvdatum);
					gerzgvdatum = $.datepicker.formatDate("dd.mm.yy", zgvdatum);
				}
				var zgvnation = data.zgvnation !== null ? data.zgvnation : "null";
				$("#zgv_" + prestudentid).val(zgvcode);
				$("#zgvort_" + prestudentid).val(zgvort);
				$("#zgvdatum_" + prestudentid).val(gerzgvdatum);
				$("#zgvnation_" + prestudentid).val(zgvnation);
			}
			else
			{
				btn.after("&nbsp;&nbsp;<span id='zgvUebernehmenNotice' class='text-warning'>keine ZGV vorhanden</span>");
			}
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			alert(textStatus + " - " + errorThrown + " - " + jqXHR.responseText);
		}
	});
}

function saveZgv(data)
{
	var prestudentid = data[0].value;
	$("#zgvSpeichernNotice").remove();
	$.ajax({
		type: "POST",
		dataType: "json",
		data: data,
		url: "../saveZgvPruefung/" + prestudentid + '?fhc_controller_id=' + fhc_controller_id,
		success: function (data, textStatus, jqXHR)
		{
			if (data === prestudentid)
			{
				refreshLog();
				$("#zgvSpeichern_" + prestudentid).before("<span id='zgvSpeichernNotice' class='text-success'>ZGV erfolgreich gespeichert!</span>&nbsp;&nbsp;");
			}
			else
			{
				$("#zgvSpeichern_" + prestudentid).before("<span id='zgvSpeichernNotice' class='text-danger'>Fehler beim Speichern der ZGV!</span>&nbsp;&nbsp;");
			}
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			alert(textStatus + " - " + errorThrown + " - " + jqXHR.responseText);
		}
	});
}

function saveNotiz(personid, data)
{
	$.ajax({
		type: "POST",
		dataType: "json",
		data: data,
		url: "../saveNotiz/" + personid + '?fhc_controller_id=' + fhc_controller_id,
		success: function (data, textStatus, jqXHR)
		{
			refreshNotizen();
			refreshLog();
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			alert(textStatus + " - " + errorThrown + " - " + jqXHR.responseText);
		}
	});
}

function updateNotiz(notizId, personId, data)
{
	$.ajax({
		type: "POST",
		dataType: "json",
		data: data,
		url: "../updateNotiz/" + notizId + "/" + personId + '?fhc_controller_id=' + fhc_controller_id,
		success: function (data, textStatus, jqXHR)
		{
			if (data)
			{
				refreshNotizen();
				refreshLog();
				resetNotizFields();
			}
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			alert(textStatus + " - " + errorThrown + " - " + jqXHR.responseText);
		}
	});
}

// -----------------------------------------------------------------------------------------------------------------
// methods executed after ajax (refreshers)

function refreshLog()
{
	var personid = $("#hiddenpersonid").val();
	$("#logs").load('../reloadLogs/' + personid,
		function ()
		{
			//readd tablesorter
			formatLogTable()
		}
	);
}

function formatLogTable()
{
	addTablesorter("logtable", [[0, 1]], ["filter"], 2);
	tablesortAddPager("logtable", "logpager", 23);
	$("#logtable").addClass("table-condensed");
}

function refreshNotizen()
{
	$("#notizform").find("input[type=text], textarea").val("");
	var personid = $("#hiddenpersonid").val();
	$("#notizen").load('../reloadNotizen/' + personid,
		function ()
		{
			//readd tablesorter
			formatNotizTable()
		}
	);
}

function formatNotizTable()
{
	addTablesorter("notiztable", [[0, 1]], ["filter"], 2);
	tablesortAddPager("notiztable", "notizpager", 10);
	$("#notiztable").addClass("table-condensed");
}

function resetNotizFields()
{
	$("#notizform :input[name='hiddenNotizId']").val("");
	$("#notizform label:first").text("Notiz hinzufügen").css("color", "black");
	$("#notizform :input[type='reset']").css("display", "none");
}
