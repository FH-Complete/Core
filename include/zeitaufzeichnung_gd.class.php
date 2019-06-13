<?php
/**
 * FH-Complete
 *
 * @package		FHC-API
 * @author		FHC-Team
 * @copyright	Copyright (c) 2016, fhcomplete.org
 * @license		GPLv3
 * @link		http://fhcomplete.org
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------
/**
 * Klasse Zeitaufzeichnung Geteilte Dienste
 * @create 13-06-2019
 */
require_once(dirname(__FILE__).'/basis_db.class.php');
class zeitaufzeichnung_gd extends basis_db
{
    public $new;		                // boolean
    public $result = array();	        // object array

    // Table columns
    public $zeitaufzeichnungs_gd_id;	// integer
    public $uid;                        // varchar(32)
    public $studiensemester_kurzbz;		// varchar(16)
    public $selbstverwaltete_pause;		// boolean
    public $insertamum;				    // timestamp
    public $insertvon;				    // varchar(32)
    public $updateamum;				    // timestamp
    public $updatevon;				    // varchar(32)

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Saves decision about self-managing breaks during parted working times.
     * @return boolean  True, if saving succeeded.
     */
    public function save()
    {
        if (is_string($this->uid) &&
            is_string($this->studiensemester_kurzbz) &&
            is_bool($this->selbstverwaltete_pause))
        {
            $qry = '
                INSERT INTO campus.tbl_zeitaufzeichnung_gd (
                    uid,
                    studiensemester_kurzbz,
                    selbstverwaltete_pause,
                    insertvon
                )
                VALUES ('.
                    $this->db_add_param($this->uid). ', '.
                    $this->db_add_param($this->studiensemester_kurzbz). ', '.
                    $this->db_add_param($this->selbstverwaltete_pause). ', '.
                    $this->db_add_param($this->uid). '
                );
            ';

            if ($this->db_query($qry))
            {
                return true;
            }
            else
            {
                $this->errormsg = 'Fehler beim Speichern der selbstverwalteten Pause';
                return false;
            }
        }
        else
        {
            $this->errormsg = 'Falsche Parameterübergabe';
            return false;
        }
    }
}