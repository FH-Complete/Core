const BASE_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const APPROVE_ANRECHNUNG_DETAIL_URI = "lehre/anrechnung/ApproveAnrechnungDetail";

const ANRECHNUNGSTATUS_PROGRESSED_BY_STGL = 'inProgressDP';
const ANRECHNUNGSTATUS_PROGRESSED_BY_KF = 'inProgressKF';
const ANRECHNUNGSTATUS_APPROVED = 'approved';
const ANRECHNUNGSTATUS_REJECTED = 'rejected';

// TABULATOR FUNCTIONS
// ---------------------------------------------------------------------------------------------------------------------
// Returns relative height (depending on screen size)
function func_height(table){
    return $(window).height() * 0.50;
}

// Adds column details
function func_tableBuilt(table) {
    table.addColumn(
        {
            title: "Details",
            align: "center",
            width: 100,
            formatter: "link",
            formatterParams:{
                label:"Details",
                url:function(cell){
                    return  BASE_URL + "/" + APPROVE_ANRECHNUNG_DETAIL_URI + "?anrechnung_id=" + cell.getData().anrechnung_id
                },
                target:"_blank"
            }
        }, false, "status"  // place column after status
    );
}

// Formats row selectable/unselectable
function func_selectableCheck(row){
    let status_kurzbz = row.getData().status_kurzbz;

    return (status_kurzbz != ANRECHNUNGSTATUS_APPROVED && status_kurzbz != ANRECHNUNGSTATUS_REJECTED);
}

// Performes after row was updated
function func_rowUpdated(row){
    // TODO: check better solution...
    // status_kurzbz is not updated until page reload...therefor use the updated status_bezeichnung
    let status_bezeichnung = row.getData().status_bezeichnung;

    // Deselect and disable new selection of updated rows, but not when
    if (status_bezeichnung == 'genehmigt' ||
        status_bezeichnung == 'approved' ||
        status_bezeichnung == 'abgelehnt' ||
        status_bezeichnung == 'rejected')
    {
        row.deselect();
        row.getElement().style["pointerEvents"] = "none";
    }
}

// Formats null values to '-'
var format_nullToMinus = function(cell, formatterParams){
    return (cell.getValue() == null) ? '-' : cell.getValue();
}


$(function(){
    // Pruefen ob Promise unterstuetzt wird
    // Tabulator funktioniert nicht mit IE
    var canPromise = !! window.Promise;
    if(!canPromise)
    {
        alert("Diese Seite kann mit ihrem Browser nicht angezeigt werden. Bitte verwenden Sie Firefox, Chrome oder Edge um die Seite anzuzeigen");
        window.location.href='about:blank';
        return;
    }

    // Redraw table on resize to fit tabulators height to windows height
    window.addEventListener('resize', function(){
        $('#tableWidgetTabulator').tabulator('setHeight', $(window).height() * 0.50);
        $('#tableWidgetTabulator').tabulator('redraw', true);
    });

    // Approve Anrechnungen
    $("#approve-anrechnungen").click(function(){
        // Get selected rows data
        let selected_data = $('#tableWidgetTabulator').tabulator('getSelectedData')
            .map(function(data){
                // reduce to necessary fields
                return {
                    'anrechnung_id' : data.anrechnung_id,
                }
            });

        // Alert and exit if no anrechnung is selected
        if (selected_data.length == 0)
        {
            FHC_DialogLib.alertInfo('Bitte wählen Sie erst zumindest einen Antrag auf Anrechnung');
            return;
        }


        // Prepare data object for ajax call
        let data = {
            'data': selected_data
        };

        FHC_AjaxClient.ajaxCallPost(
            FHC_JS_DATA_STORAGE_OBJECT.called_path + "/approve",
            data,
            {
                successCallback: function (data, textStatus, jqXHR)
                {
                    if (data.error && data.retval != null)
                    {
                        // Print error message
                        FHC_DialogLib.alertWarning(data.retval);
                    }

                    if (!data.error && data.retval != null)
                    {
                        // Update status 'genehmigt'
                        $('#tableWidgetTabulator').tabulator('updateData', data.retval);

                        // Print success message
                        FHC_DialogLib.alertSuccess(data.retval.length + " Anrechnungsanträge wurden genehmigt.");
                    }
                },
                errorCallback: function (jqXHR, textStatus, errorThrown)
                {
                    FHC_DialogLib.alertError("Systemfehler<br>Bitte kontaktieren Sie Ihren Administrator.");
                }
            }
        );
    });

});