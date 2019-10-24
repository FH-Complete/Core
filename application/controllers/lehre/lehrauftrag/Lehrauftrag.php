<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * The controller Lehrauftrag displays all Lehrauftraege within a study semester.
 * Heads of degree programs can order Lehrauftraege, which subsequently will generate the corresponding contracts
 * automatically.
 * Department leaders can approve the ordered Lehrauftraege.
 */
class Lehrauftrag extends Auth_Controller
{
    const APP = 'lehrauftrag';
    const LEHRAUFTRAG_URI = 'lehre/lehrauftrag/Lehrauftrag';    // URL prefix for this controller
    const BERECHTIGUNG_LEHRAUFTRAG_BESTELLEN = 'lehre/lehrauftrag_bestellen';

    private $_uid;  // uid of the logged user

    /**
     * Constructor
     */
    public function __construct()
    {
        // Set required permissions
        parent::__construct(
            array(
                'index' => 'lehre/lehrauftrag_bestellen:r',
                'orderLehrauftrag' => 'lehre/lehrauftrag_bestellen:rw',
            )
        );

        // Load models
        $this->load->model('system/Benutzerrolle_model', 'BenutzerrolleModel');
        $this->load->model('organisation/Organisationseinheit_model', 'OrganisationseinheitModel');
        $this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
        $this->load->model('organisation/Studiengang_model', 'StudiengangModel');
        $this->load->model('accounting/Vertrag_model', 'VertragModel');

        // Load libraries
        $this->load->library('WidgetLib');
        $this->load->library('PermissionLib');

        // Load helpers
        $this->load->helper('array');
        $this->load->helper('url');
        $this->load->helper('hlp_sancho_helper');

        // Load language phrases
        $this->loadPhrases(
            array(
                'global',
                'ui'
            )
        );

        $this->_setAuthUID(); // sets property uid

        $this->setControllerId(); // sets the controller id
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Public methods
    /**
     * Main page of Lehrauftrag
     */
    public function index()
    {
        // Set studiengang selected for studiengang dropdown
        $studiengang_kz = $this->input->get('studiengang'); // if provided by selected studiengang
        $studiengang_kz = ($studiengang_kz == 'null' ? null : $studiengang_kz);

        // Retrieve studiengaenge the user is entitled for to populate studiengang dropdown
        if (!$studiengang_kz_arr = $this->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_LEHRAUFTRAG_BESTELLEN)) {
            show_error('Fehler bei Berechtigungsprüfung');
        }

        // Set studiensemester selected for studiengang dropdown
        $studiensemester_kurzbz = $this->input->get('studiensemester'); // if provided by selected studiensemester
        if (is_null($studiensemester_kurzbz)) // else set next studiensemester as default value
        {
            $studiensemester = $this->StudiensemesterModel->getNext();
            if (hasData($studiensemester))
            {
                $studiensemester_kurzbz = $studiensemester->retval[0]->studiensemester_kurzbz;
            }
            elseif (isError($studiensemester))
            {
                show_error($studiensemester->error);
            }
        }

        // Set ausbildungssemester selected for ausbildungssemester dropdown
        $ausbildungssemester = $this->input->get('ausbildungssemester'); // if provided by selected ausbildungssemester
        $ausbildungssemester = ($ausbildungssemester == 'null' ? null : $ausbildungssemester);

        $view_data = array(
            'studiengang_selected' => $studiengang_kz,
            'studiengang' => $studiengang_kz_arr,
            'studiensemester_selected' => $studiensemester_kurzbz,
            'ausbildungssemester_selected' => $ausbildungssemester,
        );

        $this->load->view('lehre/lehrauftrag/orderLehrauftrag.php', $view_data);
    }

    public function orderLehrauftrag()
    {
        $new_lehrvertrag_data_arr = array();    // information of new lehrvertraege to be used in mail
        $lehrauftrag_arr = json_decode($this->input->post('selected_data'));

        // Loop through lehraufträge
        if(is_array($lehrauftrag_arr))
        {
            foreach($lehrauftrag_arr as $lehrauftrag)
            {
                $lehreinheit_id = (isset($lehrauftrag->lehreinheit_id)) ? $lehrauftrag->lehreinheit_id : null;
                $lehrveranstaltung_id = (isset($lehrauftrag->lehrveranstaltung_id)) ? $lehrauftrag->lehrveranstaltung_id : null;
                $person_id = (isset($lehrauftrag->person_id)) ? $lehrauftrag->person_id : null;
                $mitarbeiter_uid = (isset($lehrauftrag->mitarbeiter_uid)) ? $lehrauftrag->mitarbeiter_uid : null;
                $vertrag_id = (isset($lehrauftrag->vertrag_id)) ? $lehrauftrag->vertrag_id : null;
                $projektarbeit_id = (isset($lehrauftrag->projektarbeit_id)) ? $lehrauftrag->projektarbeit_id : null;
                $stunden = (isset($lehrauftrag->stunden)) ? $lehrauftrag->stunden : null;
                $betrag = (isset($lehrauftrag->betrag)) ? $lehrauftrag->betrag : null;
                $vertrag_betrag = (isset($lehrauftrag->vertrag_betrag)) ? $lehrauftrag->vertrag_betrag : null;
                $studiensemester_kurzbz = (isset($lehrauftrag->studiensemester_kurzbz)) ? $lehrauftrag->studiensemester_kurzbz : null;

                // update contract if contract exists and the betrag was changed
                $hasChanged = (floatval($betrag) != floatval($vertrag_betrag)) ? true : false;
                if (!is_null($vertrag_id) && $hasChanged)
                {
                    $vertrag_obj = new StdClass();
                    $vertrag_obj->vertrag_id = $vertrag_id;
                    $vertrag_obj->vertragsstunden = $stunden;
                    $vertrag_obj->betrag = $betrag;

                    $result = $this->VertragModel->updateVertrag(
                        $vertrag_obj,
                        $mitarbeiter_uid
                    );

                    if (isSuccess($result))
                    {
                        $json []= array(
                            'row_index' => $lehrauftrag->row_index,
                            'bestellt' => date('Y-m-d'),
                            'vertrag_betrag' => $betrag,
                            'erteilt' => null
                        );
                    }
                }
                // else save new contract
                else
                {
                    $result = $this->VertragModel->save(
                        $person_id,
                        $mitarbeiter_uid,
                        $lehrveranstaltung_id,
                        $lehreinheit_id,
                        $projektarbeit_id,
                        $stunden,
                        $betrag,
                        $studiensemester_kurzbz
                    );

                    if (isSuccess($result))
                    {
                        $json []= array(
                            'row_index' => $lehrauftrag->row_index,
                            'bestellt' => date('Y-m-d'),
                            'vertrag_betrag' => $betrag
                        );
                    }

                    $new_lehrvertrag_data_arr[] = array(
                        'studiensemester_kurzbz' => $lehrauftrag->studiensemester_kurzbz,
                        'studiengang_kz' => $lehrauftrag->studiengang_kz,
                        'lv_oe_kurzbz' => $lehrauftrag->lv_oe_kurzbz
                    );
                }
            }
        }

        if (isset($json) && !isEmptyArray($json))
        {
            $this->outputJsonSuccess($json);
        }

        // Send email to Mitarbeiter
        // if(!$this->_sendMail($new_lehrvertrag_data_arr)) // TODO: slows down Bestell-process -> better chronjob?
        {
           // return error information // TODO: implement after decision regarding communication process
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Private methods

    /**
     * Retrieve the UID of the logged user and checks if it is valid
     */
    private function _setAuthUID()
    {
        $this->_uid = getAuthUID();

        if (!$this->_uid) show_error('User authentification failed');
    }

    private function _sendMail($lehrvertrag_data_arr)
    {
        // Cluster data of new lehrvertraege as needed to send mail
        $lehrvertrag_data_arr = $this->_cluster_newVertragData($lehrvertrag_data_arr);

        foreach ($lehrvertrag_data_arr as $lehrvertrag_data)
        {
            // Get mail recipients
            $result = $this->BenutzerrolleModel->getBenutzerByBerechtigung('lehre/lehrauftrag_erteilen', $lehrvertrag_data['lv_oe_kurzbz']);

            // If given lv organisational unit has no authorized user, check if is a Kompetenzfeld.
            // If so, look up for authorized user on Department level.
            if (!hasData($result)) {
                $result = $this->OrganisationseinheitModel->getParent($lehrvertrag_data['lv_oe_kurzbz']);

                if (hasData($result)) {
                    if ($result->retval[0]->organisationseinheittyp_kurzbz === 'Department') {
                        $result = $this->BenutzerrolleModel->getBenutzerByBerechtigung('lehre/lehrauftrag_erteilen', $result->retval[0]->oe_kurzbz);
                    }
                }
            }

            // Set mail recipients (department assistance/leader)
            $to = '';
            $to_arr = array();
            foreach ($result->retval as $berechtigung) {
                $to_arr []= $berechtigung->uid . '@' . DOMAIN; // TODO: als array, dann splitten mit ;? oder als array lassen?
            }
            $to = implode(', ', $to_arr);

            // Set link to lehrauftrag-site with preselected studiengang and studiensemester of new lehrauftraege
            $url = site_url(self::LEHRAUFTRAG_URI).'?studiensemester='. $lehrvertrag_data['studiensemester_kurzbz']. '&studiengang='. $lehrvertrag_data['studiengang_kz'];

            // Prepare mail content
            $content_data_arr = array(
                'anzahl' => $lehrvertrag_data['amount_new_lehrvertraege'],
                'studiengang' => $lehrvertrag_data['studiengang_kz'],
                'studiensemester' => $lehrvertrag_data['studiensemester_kurzbz'],
                'link' => anchor($url, 'Lehrverträge Übersicht')
            );

            // Send mail
            sendSanchoMail(
                'LehrauftragBestellMail',
                $content_data_arr,
                $to,
                'Bestellung neuer Lehraufträge',
                'sancho_header_min_bw.jpg',
                'sancho_footer_min_bw.jpg'
            );
        }
    }

    /**
     * Clusters data as needed for _sendMail.
     * Makes array of new lehrvertraege unique (by studiensemester, studiengang and lv_oe_kurzbz)
     * Adds the amount of lehrvertraege of each unique array element.
     * @param $new_lehrvertrag_data_arr
     * @return array
     */
    private function _cluster_newVertragData($new_lehrvertrag_data_arr)
    {
        $unique_new_lehrvertrag_data_arr = array_unique($new_lehrvertrag_data_arr, SORT_REGULAR);
        foreach ($unique_new_lehrvertrag_data_arr as &$new_lehrvertrag)
        {
            $cnt = 1;
            foreach ($new_lehrvertrag_data_arr as $item)
            {
                if ($new_lehrvertrag['studiensemester_kurzbz'] === $item['studiensemester_kurzbz'] &&
                    $new_lehrvertrag['studiengang_kz'] === $item['studiengang_kz'] &&
                    $new_lehrvertrag['lv_oe_kurzbz'] === $item['lv_oe_kurzbz'])
                {
                    $new_lehrvertrag['amount_new_lehrvertraege'] = $cnt++;
                }
            }
        }

        return $unique_new_lehrvertrag_data_arr;
    }

    private function validateGetPost($lehrauftrag){
        $lehreinheit_id = (isset($lehrauftrag['lehreinheit_id']) && is_numeric($lehrauftrag['lehreinheit_id'])) ? $lehrauftrag['lehreinheit_id'] : null;
        $lehrveranstaltung_id = (isset($lehrauftrag['lehrveranstaltung_id']) && is_numeric($lehrauftrag['lehrveranstaltung_id'])) ? $lehrauftrag['lehrveranstaltung_id'] : null;
        $person_id = (isset($lehrauftrag['person_id']) && is_numeric($lehrauftrag['person_id'])) ? $lehrauftrag['person_id'] : null;
        $mitarbeiter_uid = (isset($lehrauftrag['mitarbeiter_uid']) && is_string($lehrauftrag['mitarbeiter_uid'])) ? $lehrauftrag['mitarbeiter_uid'] : null;
        $vertrag_id = (isset($lehrauftrag['vertrag_id']) && is_numeric($lehrauftrag['vertrag_id'])) ? $lehrauftrag['vertrag_id'] : null;
        $projektarbeit_id = (isset($lehrauftrag['projektarbeit_id']) && is_numeric($lehrauftrag['projektarbeit_id'])) ? $lehrauftrag['projektarbeit_id'] : null;
        $stunden = (isset($lehrauftrag['stunden']) && is_numeric($lehrauftrag['stunden'])) ? $lehrauftrag['stunden'] : null;
        $betrag = (isset($lehrauftrag['betrag']) && is_numeric($lehrauftrag['betrag'])) ? $lehrauftrag['betrag'] : null;
        $studiensemester_kurzbz = (isset($lehrauftrag['studiensemester_kurzbz']) && is_string($lehrauftrag['studiensemester_kurzbz'])) ? $lehrauftrag['betrag'] : null;

        return array(
            $lehreinheit_id,
            $lehrveranstaltung_id,
            $person_id,
            $mitarbeiter_uid,
            $vertrag_id,
            $projektarbeit_id,
            $stunden,
            $betrag,
            $studiensemester_kurzbz
        );

// LIST IS TO BE SET ABOVE!!!:
//        list(
//            $lehreinheit_id,
//            $lehrveranstaltung_id,
//            $person_id,
//            $mitarbeiter_uid,
//            $vertrag_id,
//            $projektarbeit_id,
//            $stunden,
//            $betrag,
//            $studiensemester_kurzbz
//            ) = $this->_validateGetPost($lehrauftrag);
    }
}
