<?php

class Notiz_model extends DB_Model
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'public.tbl_notiz';
		$this->pk = 'notiz_id';
	}
	
	// ------------------------------------------------------------------------------------------------------
	/**
	 * Get a specialization for a prestudent
	 */
	public function getSpecialization($prestudent_id, $titel)
	{
		// Join with the table public.tbl_notizzuordnung using notiz_id
		$this->addJoin('public.tbl_notizzuordnung', 'notiz_id');
		
		return $this->NotizModel->loadWhere(array('prestudent_id' => $prestudent_id, 'titel' => $titel));
	}
	
	/**
	 * Remove a specialization
	 */
	public function rmSpecialization($notiz_id)
	{
		// Loads model Notizzuordnung_model
		$this->load->model('person/Notizzuordnung_model', 'NotizzuordnungModel');
		
		// Start DB transaction
		$this->db->trans_start(false);
		
		$result = $this->delete(array('notiz_id' => $notiz_id));
		if (is_object($result) && $result->error == EXIT_SUCCESS)
		{
			$result = $this->NotizzuordnungModel->delete(array('notiz_id' => $notiz_id));
		}
		
		// Transaction complete!
		$this->db->trans_complete();
		
		// Check if everything went ok during the transaction
		if ($this->db->trans_status() === false || (is_object($result) && $result->error != EXIT_SUCCESS))
		{
			$this->db->trans_rollback();
			$result = error($result->msg, EXIT_ERROR);
		}
		else
		{
			$this->db->trans_commit();
			$result = success('Specialization successfully removed');
		}
		
		return $result;
	}

	/**
	 * Add a specialization for a prestudent
	 */
	public function addSpecialization($prestudent_id, $titel, $text)
	{
		// Loads model Notizzuordnung_model
		$this->load->model('person/Notizzuordnung_model', 'NotizzuordnungModel');
		
		// Start DB transaction
		$this->db->trans_start(false);
		
		$result = $this->insert(array('titel' => $titel, 'text' => $text, 'erledigt' => true));
		$notiz_id = $result->retval;
		if (is_object($result) && $result->error == EXIT_SUCCESS)
		{
			$result = $this->NotizzuordnungModel->insert(array('notiz_id' => $notiz_id, 'prestudent_id' => $prestudent_id));
		}
		
		// Transaction complete!
		$this->db->trans_complete();
		
		// Check if everything went ok during the transaction
		if ($this->db->trans_status() === false || (is_object($result) && $result->error != EXIT_SUCCESS))
		{
			$this->db->trans_rollback();
			$result = error($result->msg, EXIT_ERROR);
		}
		else
		{
			$this->db->trans_commit();
			$result = success($notiz_id);
		}
		
		return $result;
	}
	// ------------------------------------------------------------------------------------------------------
}