<?php

class Studiengang_widget extends DropdownWidget
{
    public function display($widgetData)
	{
		// Studiengaenge
		$this->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$this->StudiengangModel->addOrder('kurzbzlang');
		
		$this->addSelectToModel($this->StudiengangModel, 'studiengang_kz', '\'(\' || kurzbzlang || \') \' || tbl_studiengang.bezeichnung');

		// If 'studiengang' (array of specific studiengaenge) is given, retrieve these studiengaenge only
		if (isset($widgetData['studiengang']) && !empty($widgetData['studiengang']))
        {
            $condition = '
                studiengang_kz IN ('. implode(',', $widgetData['studiengang']) . ') AND
                aktiv = true
            ';
        }
		// Default: retrieve all studiengaenge
		else
        {
            $condition = array('aktiv' => true);
        }

        $this->setElementsArray(
            $this->StudiengangModel->loadWhere($condition),
            true,
            $this->p->t('ui', 'bitteEintragWaehlen'),
            'No studiengaenge found'
        );

		$this->loadDropDownView();
    }
}