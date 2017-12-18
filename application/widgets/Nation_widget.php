<?php

class Nation_widget extends DropdownWidget
{
	public function display($widgetData)
	{
		// Nation
		$this->load->model('codex/nation_model', 'NationModel');
		$this->NationModel->addOrder('nation_code');

		$this->addSelectToModel($this->NationModel, 'nation_code', 'kurztext');

		$this->setElementsArray(
			$this->NationModel->load(),
			true,
			'Select a nation...',
			'No nation found'
		);

		$this->loadDropDownView($widgetData);
	}
}