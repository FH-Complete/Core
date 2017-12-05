<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class TestFilterWidget extends VileSci_Controller
{
	public function __construct()
    {
        parent::__construct();

        $this->load->library('WidgetLib');
    }

	/**
	 *
	 */
	public function sql()
	{
		echo $this->widgetlib->widget(
			'FilterWidget',
			array(
				'app' => 'core',
				'datasetName' => 'kontakts',
				'filterKurzbz' => 'This filter filters',
				'query' => '
					SELECT p.person_id AS "PersonId",
							p.nachname AS "Nachname",
							p.vorname AS "Vorname",
							k.kontakt AS "Email",
							p.aktiv AS "Aktiv"
					  FROM public.tbl_person p INNER JOIN public.tbl_kontakt k USING(person_id)
					 WHERE p.aktiv = TRUE
					   AND p.person_id = k.person_id
					   AND k.kontakttyp = \'email\'
					   AND p.person_id < 1000
				',
				'checkboxes' => array('PersonId'),
				'additionalColumns' => array('Delete', 'Edit'),
				'formatRaw' => function($fieldName, $fieldValue, $datasetRaw) {

					if ($fieldName == 'PersonId')
					{
						$datasetRaw->{$fieldName} = '<a href="view/'.$datasetRaw->PersonId.'">'.$fieldValue.'</a>';
					}
					if ($fieldName == 'Delete')
					{
						$datasetRaw->{$fieldName} = '<a href="delete/'.$datasetRaw->PersonId.'">Delete</a>';
					}
					if ($fieldName == 'Edit')
					{
						$datasetRaw->{$fieldName} = '<a href="edit/'.$datasetRaw->PersonId.'">Edit</a>';
					}

					return $datasetRaw;
				}
			)
		);
	}
}
