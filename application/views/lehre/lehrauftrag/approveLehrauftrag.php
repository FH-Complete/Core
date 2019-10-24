<?php
$this->load->view(
    'templates/FHC-Header',
    array(
        'title' => 'Lehrauftrag',
        'jquery' => true,
        'jqueryui' => true,
        'jquerycheckboxes' => true,
        'bootstrap' => true,
        'fontawesome' => true,
        'sbadmintemplate' => true,
        'tabulator' => true,
        'momentjs' => true,
        'ajaxlib' => true,
        'dialoglib' => true,
        'filterwidget' => true,
        'navigationwidget' => true,
        'phrases' => array(
            'global' => array('lehrauftraegeErteilen'),
        ),
        'customJSs' => array('public/js/bootstrapper.js')
    )
);

?>

<body>
    <?php echo $this->widgetlib->widget('NavigationWidget'); ?>

    <div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="page-header">
                    <?php echo ucfirst($this->p->t('global', 'lehrauftraegeErteilen')); ?>
                </h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <form id="formLehrauftrag" class="form-inline" action="" method="get">
                    <div class="form-group">
                        <?php
                        echo $this->widgetlib->widget(
                            'Studiensemester_widget',
                            array(
                                DropdownWidget::SELECTED_ELEMENT => $studiensemester_selected
                            ),
                            array(
                                'name' => 'studiensemester',
                                'id' => 'studiensemester'
                            )
                        );
                        ?>
                    </div>
                    <div class="form-group">
                        <?php
                        echo $this->widgetlib->widget(
                            'Organisationseinheit_widget',
                            array(
                                DropdownWidget::SELECTED_ELEMENT => $organisationseinheit_selected,
                                'organisationseinheit' => $organisationseinheit
                            ),
                            array(
                                'name' => 'organisationseinheit',
                                'id' => 'organisationseinheit'
                            )
                        );
                        ?>
                    </div>
                    <div class="form-group">
                        <?php
                        echo $this->widgetlib->widget(
                            'Ausbildungssemester_widget',
                            array(
                                DropdownWidget::SELECTED_ELEMENT => $ausbildungssemester_selected,
                                'number_semester' => 6
                            ),
                            array(
                                'name' => 'ausbildungssemester',
                                'id' => 'ausbildungssemester'
                            )
                        );
                        ?>
                    </div>
                    <button type="submit" name="submit" value="anzeigen" class="btn btn-default form-group">Anzeigen</button>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <?php $this->load->view('lehre/lehrauftrag/approveLehrauftragData.php'); ?>
            </div>
        </div>

        <br>
        <div class="row">
            <div class="col-xs-12">
                <button id="approve-lehrauftraege" class="btn btn-primary pull-right">Lehrauftrag erteilen</button>
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group" role="group">
                        <button id="show-all" class="btn btn-default btn-lehrauftrag" type="button" data-toggle="tooltip" data-placement="left" title="Alle anzeigen"><i class='fa fa-users'></i></button>
                        <button id="show-new" class="btn btn-default btn-lehrauftrag" type="button" data-toggle="tooltip" data-placement="left" title="Nur neue anzeigen"><i class='fa fa-user-plus'></i></button>
                        <button id="show-changed" class="btn btn-default btn-lehrauftrag" type="button" data-toggle="tooltip" data-placement="left" title="Nur geänderte anzeigen"></button><!-- png img set in javascript -->
                        <button id="show-ordered" class="btn btn-default btn-lehrauftrag active focus" type="button" data-toggle="tooltip" data-placement="left" title="Nur bestellte anzeigen"></button><!-- png img set in javascript -->
                        <button id="show-approved" class="btn btn-default btn-lehrauftrag" type="button" data-toggle="tooltip" data-placement="left" title="Nur erteilte anzeigen"></button><!-- png img set in javascript -->
                        <button id="show-accepted" class="btn btn-default btn-lehrauftrag" type="button" data-toggle="tooltip" data-placement="left" title="Nur angenommene anzeigen"><i class='fa fa-handshake-o'></i></button>
                    </div>
                    <div class="btn-group" role="group" style="margin-left: 15px;">
                        <button id="show-dummies" class="btn btn-default btn-lehrauftrag" type="button" data-toggle="tooltip" data-placement="left" title="Nur verplante ohne Lektor anzeigen (Dummies)"><i class='fa fa-user-secret'></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>
</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>


<script type="text/javascript">

    const COLOR_LIGHTGREY = "#f5f5f5";

    /**
     * PNG icons used in status- and filter buttons
     * Setting png icons is a workaround to use font-awsome 5.9.0 icons until system can be updated to newer font awsome version.
     * */
    const ICON_LEHRAUFTRAG_ORDERED = '<img src="../../../public/images/icons/fa-user-tag.png" style="height: 30px; width: 30px; margin: -6px;">';
    const ICON_LEHRAUFTRAG_APPROVED = '<img src="../../../public/images/icons/fa-user-check.png" style="height: 30px; width: 30px; margin: -6px;">';
    const ICON_LEHRAUFTRAG_CHANGED = '<img src="../../../public/images/icons/fa-user-edit.png" style="height: 30px; width: 30px; margin: -6px;">';

    // -----------------------------------------------------------------------------------------------------------------
    // Mutators - setter methods to manipulate table data when entering the tabulator
    // -----------------------------------------------------------------------------------------------------------------

    // Converts string date postgre style to string DD.MM.YYYY.
    // This will allow correct filtering.
    var mut_formatStringDate = function(value, data, type, params, component) {
        if (value != null)
        {
            var d = new Date(value);
            return ("0" + (d.getDate())).slice(-2)  + "." + ("0" + (d.getMonth() + 1)).slice(-2) + "." + d.getFullYear();
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Header filter
    // -----------------------------------------------------------------------------------------------------------------

    // Filters values using comparison operator or just by string comparison
    function hf_filterStringnumberWithOperator(headerValue, rowValue, rowData){

        // If string starts with <, <=, >, >=, !=, ==, compare values with that operator
        var operator = '';
        if (headerValue.match(/([<=>!]{1,2})/g)) {
            var operator_arr = headerValue.match(/([<=>!]{1,2})/g);
            operator = operator_arr[0];

            headerValue = headerValue
                .replace(operator, '')
                .trim()
            ;

            // return if value comparison is true
            return eval(rowValue + operator + headerValue);
        }

        // If just a stringnumber, return if exact match found
        return parseFloat(rowValue) == headerValue;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Custom filters
    // -----------------------------------------------------------------------------------------------------------------

    // Filters bestellte initially
    function func_initialFilter(){
        return [
            {field: 'personalnummer', type: '>=', value: 0},    // NOT dummy lector
            {field: 'bestellt', type: '!=', value: null},       // AND bestellt
            {field: 'erteilt', type: '=', value: null},         // AND NOT erteilt
            {field: 'akzeptiert', type: '=', value: null}       // AND NOT akzeptiert
        ]
    }

    // Filters geaenderte
    function filter_showChanged(data){

        // Filters geaenderte from status bestellt on
        return  data.personalnummer >= 0 &&          // NOT dummy lector AND
                data.bestellt != null &&            // bestellt AND
                data.betrag != data.vertrag_betrag; // geaendert
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Tabulator table format functions
    // -----------------------------------------------------------------------------------------------------------------
    // Formats the group header
    function func_groupHeader(data){
        return data[0].lv_bezeichnung;  // change name to lehrveranstaltung
    };

    // Formats the rows
    function func_rowFormatter(row){
        var is_dummy = (row.getData().personalnummer <= 0 && row.getData().personalnummer != null);

        var bestellt = row.getData().bestellt;
        var erteilt = row.getData().erteilt;
        var akzeptiert = row.getData().akzeptiert;

        var betrag = row.getData().betrag;
        var vertrag_betrag = row.getData().vertrag_betrag;

        /*
        Formats the color of the rows depending on their status
        - blue: dummy lectors
        - orange: geaendert
        - default (white) : bestellte
        - green: akzeptiert
        - grey: all other (marks unselectable)
         */
        row.getCells().forEach(function(cell){
            if(is_dummy)
            {
                cell.getElement().classList.add('bg-info');                      // dummy lectors
            }
            else if (bestellt != null && (betrag != vertrag_betrag)  && !row._row.element.classList.contains('tabulator-calcs')) // exclude calculation rows
            {
                cell.getElement().classList.add('bg-warning');                  // geaenderte
            }
            else if(bestellt != null && erteilt == null)
            {
                return;                                                         // bestellt
            }
            else if(bestellt != null && erteilt != null && akzeptiert != null)
            {
                cell.getElement().classList.add('bg-success')                   // akzeptiert
            }
            else
            {
                row.getElement().style["background-color"] = COLOR_LIGHTGREY;   // default
            }
        });
    }

    // Formats row selectable/unselectable
    function func_selectableCheck(row){
        var is_dummy = (row.getData().personalnummer <= 0 && row.getData().personalnummer != null);

        var betrag = row.getData().betrag;
        var vertrag_betrag = row.getData().vertrag_betrag;

        // only allow to select bestellte Lehraufträge
        return  !is_dummy &&                        // NOT dummy lector
                row.getData().bestellt != null &&   // AND NOT neue
                row.getData().erteilt == null &&    // AND bestellt
                betrag == vertrag_betrag;           // AND nicht geändert
    }

    // Adds column status
    function func_tableBuilt(table) {
        // Add status column to table
        table.addColumn(
            {
                title: "<i class='fa fa-user-o'></i>",
                field: "status",
                width:40,
                align:"center",
                downloadTitle: 'Status',
                formatter: status_formatter,
                tooltip: status_tooltip
            }, true
        );
    }

    // Sets status values into column status
    function func_renderStarted(table){
        // set literally status to each row - this enables sorting by status despite using icons
        table.getRows().forEach(function(row){
            var bestellt = row.getData().bestellt;
            var erteilt = row.getData().erteilt;
            var akzeptiert = row.getData().akzeptiert;

            var betrag = parseFloat(row.getData().betrag);
            var vertrag_betrag = parseFloat(row.getData().vertrag_betrag);

            if (bestellt != null && (betrag != vertrag_betrag))
            {
                row.getData().status = 'Geändert';      // geaendert
            }
            else if (bestellt == null && erteilt == null && akzeptiert == null)
            {
                row.getData().status = 'Neu';           // neu
            }
            else if (bestellt != null && erteilt == null && akzeptiert == null)
            {
                row.getData().status = 'Bestellt';      // bestellt
            }
            else if (bestellt != null && erteilt != null && akzeptiert == null)
            {
                row.getData().status = 'Erteilt';       // erteilt
            }
            else if (bestellt != null && erteilt != null && akzeptiert != null)
            {
                row.getData().status = 'Akzeptiert';    // akzeptiert
            }
            else
            {
                row.getData().status = null;            // default
            }
        });
    }

    // Performes after row was updated
    function func_rowUpdated(row){

        // Refresh status icon and row color
        row.reformat(); // retriggers cell formatters and rowFormatter callback

        // Deselect and disable new selection of updated rows
        row.deselect();
        row.getElement().style["pointerEvents"] = "none";
    }


    // Tabulator footer element
    // -----------------------------------------------------------------------------------------------------------------

    // Adds a footer with buttons select all / deselect all / download
    function func_footerElement(){

        var footer_html = '<div class="row">';
        footer_html += '<div class="col-lg-12" style="padding: 5px;">';

        footer_html += '<div class="btn-toolbar pull-right" role="toolbar">';
        footer_html += '<div class="btn-group" role="group">';
        footer_html += '<button id="download-csv" class="btn btn-default" type="button" data-toggle="tooltip" data-placement="left" title="Download CSV" onclick="footer_downloadCSV()"><small>CSV&nbsp;&nbsp;</small><i class="fa fa-arrow-down"></i></button>';
        footer_html += '</div>';
        footer_html += '</div>';

        footer_html += '<div class="btn-toolbar" role="toolbar">';
        footer_html += '<div class="btn-group" role="group">';
        footer_html += '<button id="select-all" class="btn btn-default pull-left" type="button" onclick="footer_selectAll()">Alle auswählen</button>';
        footer_html += '<button id="deselect-all" class="btn btn-default pull-left" type="button" onclick="footer_deselectAll()">Alle abwählen</button>';
        footer_html += '<span id="number-selected" style="margin-left: 20px; line-height: 2; font-weight: normal"></span>';
        footer_html += '</div>';
        footer_html += '</div>';

        footer_html += '</div>';
        footer_html += '</div>';

        return footer_html;
    }

    // Performs download CSV
    function footer_downloadCSV(){
        $('#filterTabulator').tabulator("download", "csv", "data.csv", {bom:true}); // BOM for correct UTF-8 char output
    }

    /*
     * Performs select all
     * Select all (filtered) rows that are bestellt
     */
    function footer_selectAll(){
        $('#filterTabulator').tabulator('getRows', true)
            .filter(row => row.getData().personalnummer >= 0 && // NOT dummies
                    row.getData().bestellt != null &&           // AND bestellt
                    row.getData().erteilt == null)              // AND NOT erteilt
            .forEach((row => row.select()));
    }

    /*
     * Performs deselect all
     * Deselect all (filtered) rows
     */
    function footer_deselectAll(){
        $('#filterTabulator').tabulator('deselectRow');
    }

    // Displays number of selected rows on row selection change
    function func_rowSelectionChanged(data, rows){
        $('#number-selected').html("Für Erteilung ausgewählt: <strong>" + rows.length + "</strong>");
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Tabulator columns format functions
    // -----------------------------------------------------------------------------------------------------------------
    // Generates status icons
    status_formatter = function(cell, formatterParams, onRendered){
        var is_dummy = (cell.getRow().getData().personalnummer <= 0 && cell.getRow().getData().personalnummer != null);

        var bestellt = cell.getRow().getData().bestellt;
        var erteilt = cell.getRow().getData().erteilt;
        var akzeptiert = cell.getRow().getData().akzeptiert;

        var betrag = cell.getRow().getData().betrag;
        var vertrag_betrag = cell.getRow().getData().vertrag_betrag;

        // commented icons would be so nice to have with fontawsome 5.11...
        if (is_dummy)
        {
            return "<i class='fa fa-user-secret'></i>";    // dummy lector
        }
        else if (bestellt != null && (betrag != vertrag_betrag))
        {
            return ICON_LEHRAUFTRAG_CHANGED;               // geaendert
            // return "<i class='fas fa-user-edit'></i>";
        }
        else if (bestellt == null && erteilt == null && akzeptiert == null)
        {
            return "<i class='fa fa-user-plus'></i>";       // neu
        }
        else if (bestellt != null && erteilt == null && akzeptiert == null)
        {
            return ICON_LEHRAUFTRAG_ORDERED;                // bestellt
            // return "<i class='fa fa-user-tag'></i>";
        }
        else if (bestellt != null && erteilt != null && akzeptiert == null)
        {
            return ICON_LEHRAUFTRAG_APPROVED;               // erteilt
            // return "<i class='fas fa-user-check'></i>";
        }
        else if (bestellt != null && erteilt != null && akzeptiert != null)
        {
            return "<i class='fa fa-handshake-o'></i>";     // akzeptiert
            // return "<i class='fas fa-user-graduate'></i>";
        }
        else
        {
            return "<i class='fa fa-user'></i>";            // default
        }
    };

    // Generates status tooltip
    status_tooltip = function(cell){
        var is_dummy = (cell.getRow().getData().personalnummer <= 0 && cell.getRow().getData().personalnummer != null);

        var bestellt = cell.getRow().getData().bestellt;
        var erteilt = cell.getRow().getData().erteilt;
        var akzeptiert = cell.getRow().getData().akzeptiert;

        var betrag = cell.getRow().getData().betrag;
        var vertrag_betrag = cell.getRow().getData().vertrag_betrag;

        var text = 'Lehrauftragstunden/-stundensatz geändert.';
            text += "\n";

        if (is_dummy)                                                               // dummy (no lector)
        {
            return 'Neuer Lehrauftrag. Ohne Lektor verplant.'
        }
        else if (bestellt != null && erteilt == null && betrag != vertrag_betrag)   // geaendert (when never erteilt before)
        {
            return text += 'Wartet auf Bestellung, danach Erteilen möglich.';
        }
        else if (bestellt != null && erteilt != null && betrag != vertrag_betrag)   // geaendert (when has been erteilt once)
        {
            return text += 'Wartet auf neuerliche Bestellung, danach erneut Erteilen möglich.';
        }
        else if (bestellt == null)                                                  // neu
        {
            return 'Neuer Lehrauftrag. Wartet auf Bestellung.';
        }
        else if (bestellt != null && erteilt == null && akzeptiert == null)         // bestellt
        {
            return 'Letzter Status: Bestellt. Wartet auf Erteilung.';
        }
        else if (bestellt != null && erteilt != null && akzeptiert == null)         // erteilt
        {
            return 'Letzter Status: Erteilt. Wartet auf Annahme durch Lektor.';
        }
        else if (bestellt != null && erteilt != null && akzeptiert != null)         // akzeptiert
        {
            return 'Letzter Status: Angenommen. Vertrag wurde beidseitig abgeschlossen.';
        }
    }

    // Generates bestellt tooltip
    bestellt_tooltip = function(cell){
        if (cell.getRow().getData().bestellt_von != null)
        {
            return 'Bestellt von: ' + cell.getRow().getData().bestellt_von;
        }
    }

    // Generates erteilt tooltip
    erteilt_tooltip = function(cell){
        if (cell.getRow().getData().erteilt_von != null) {
            return 'Erteilt von: ' + cell.getRow().getData().erteilt_von;
        }
    }

    // Generates akzeptiert tooltip
    akzeptiert_tooltip = function(cell){
        if (cell.getRow().getData().akzeptiert_von != null) {
            return 'Angenommen von: ' + cell.getRow().getData().akzeptiert_von;
        }
    }
$(function() {

    // Show all rows
    $("#show-all").click(function(){
        $('#filterTabulator').tabulator('clearFilter');
    });

    // Show only rows with new lehrauftraege (not dummy lectors)
    $("#show-new").click(function(){
        $('#filterTabulator').tabulator('setFilter',
            [
                {field: 'personalnummer', type: '>=', value: 0},
                {field: 'bestellt', type: '=', value: null},
                {field: 'erteilt', type: '=', value: null},
                {field: 'akzeptiert', type: '=', value: null}
            ]
        );
    });

    // Show only rows with ordered lehrauftraege
    $("#show-ordered").click(function(){
        $('#filterTabulator').tabulator('setFilter',
            [
                {field: 'personalnummer', type: '>=', value: 0},
                {field: 'bestellt', type: '!=', value: null},
                {field: 'erteilt', type: '=', value: null},
                {field: 'akzeptiert', type: '=', value: null}
            ]
        );
    });

    // Show only rows with erteilte lehrauftraege
    $("#show-approved").click(function(){
        $('#filterTabulator').tabulator('setFilter',
            [
                {field: 'bestellt', type: '!=', value: null},
                {field: 'erteilt', type: '!=', value: null},
                {field: 'akzeptiert', type: '=', value: null}
            ]
        );
    });

    // Show only rows with accepted lehrauftraege
    $("#show-accepted").click(function(){
        $('#filterTabulator').tabulator('setFilter',
            [
                {field: 'bestellt', type: '!=', value: null},
                {field: 'erteilt', type: '!=', value: null},
                {field: 'akzeptiert', type: '!=', value: null}
            ]
        );
    });

    // Show only rows with dummy lectors
    $("#show-dummies").click(function(){
        $('#filterTabulator').tabulator('setFilter',
            [
                {field: 'personalnummer', type: '!=', value: null},
                {field: 'personalnummer', type: '<=', value: 0},
            ]
        );
    });

    // Show only rows with dummy lectors
    $("#show-changed").click(function(){
        // needs custom filter to compare fields betrag and vertrag_betrag
        $('#filterTabulator').tabulator('setFilter', filter_showChanged);
    });

    // Set png-icons into filter-buttons
    $(".btn-lehrauftrag").each(function(){
        switch(this.id) {
            case 'show-ordered':
                this.innerHTML = ICON_LEHRAUFTRAG_ORDERED;
                break;
            case 'show-approved':
                this.innerHTML = ICON_LEHRAUFTRAG_APPROVED;
                break;
            case 'show-changed':
                this.innerHTML = ICON_LEHRAUFTRAG_CHANGED;
                break;
        }
    });

    // De/activate and un/focus on clicked button, En-/Disable 'Lehrauftrag erteilen'
    $(".btn-lehrauftrag").click(function() {

        // De/activate and un/focus on clicked button
        $(".btn-lehrauftrag").removeClass('focus').removeClass('active');
        $(this).addClass('focus').addClass('active');

        // Enable button 'Lehrauftrag bestellen' by default
        $('#approve-lehrauftraege').attr('disabled', false).attr('title', '');

        // Disable button Lehrauftrag bestellen for dummies
        if (this.id == 'show-dummies')
        {
            $('#approve-lehrauftraege').attr('disabled', true).attr('title', 'Lehraufträge ohne Lektorzuteilung können nicht bestellt werden.');
        }
    });

    // Approve Lehrauftraege
    $("#approve-lehrauftraege").click(function(){

        var selected_data = $('#filterTabulator').tabulator('getSelectedData')
            .filter(function(val){
                // filter pseudo lines of groupBy (e.g. the bottom calculations lines)
                return val.row_index != null || typeof(val.row_index) !== 'undefined';
            })
            .map(function(data){
                // reduce to necessary fields
                return {
                    'row_index': data.row_index,
                    'mitarbeiter_uid' : data.mitarbeiter_uid,
                    'vertrag_id' : data.vertrag_id,
                }
            });

        // Alert and exit if no lehraufgang is selected
        if (selected_data.length == 0)
        {
            FHC_DialogLib.alertInfo('Bitte wählen Sie erst zumindest einen Lehrauftrag');
            return;
        }

        /*
         * Prepare data object for ajax call
         * NOTE: Stringify to send only ONE post param (json string) instead of many single post params.
         * This avoids issues with POST param limitation.
         */
        var data = {
            'selected_data': JSON.stringify(selected_data)
        };

        FHC_AjaxClient.ajaxCallPost(
            FHC_JS_DATA_STORAGE_OBJECT.called_path + "/approveLehrauftrag",
            data,
            {
                successCallback: function (data, textStatus, jqXHR)
                {
                    if (!data.error && data.retval != null)
                    {
                        // Update status 'Erteilt'
                        $('#filterTabulator').tabulator('updateData', data.retval);
                    }

                    FHC_DialogLib.alertSuccess(data.retval.length + " Lehraufträge wurden erteilt.");
                },
                errorCallback: function (jqXHR, textStatus, errorThrown)
                {
                    FHC_DialogLib.alertError("Systemfehler<br>Bitte kontaktieren Sie Ihren Administrator.");
                }
            }
        );

     });
});
</script>
