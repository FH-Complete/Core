<?php

class Recipient_model extends DB_Model
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'public.tbl_msg_recipient';
		$this->pk = array('person_id', 'message_id');
		$this->hasSequence = false;
	}
	
	/**
	 * Get data for a received message
	 */
	public function getMessage($message_id, $person_id)
	{
		// Checks if the operation is permitted by the API caller
		if (($chkRights = $this->isEntitled('public.tbl_msg_recipient', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_msg_message', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_person', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_kontakt', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		
		$query = 'SELECT mr.message_id,
						 mr.person_id,
						 mm.subject,
						 mm.body,
						 ks.kontakt,
						 p.nachname,
						 p.vorname,
						 b.uid
					FROM public.tbl_msg_recipient mr INNER JOIN public.tbl_msg_message mm USING (message_id)
						INNER JOIN public.tbl_person p ON (mm.person_id = p.person_id)
						LEFT JOIN public.tbl_benutzer b ON (mr.person_id = b.person_id)
						LEFT JOIN (
							SELECT person_id, kontakt FROM public.tbl_kontakt WHERE kontakttyp = \'email\'
						) ks ON (ks.person_id = mr.person_id)
				   WHERE mr.message_id = ? AND mr.person_id = ?';
		
		$parametersArray = array($message_id, $person_id);
		
		// Get data of the messages to sent
		$result = $this->db->query($query, $parametersArray);
		if (is_object($result))
			return success($result->result());
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}
	
	/**
	 * Get a received message identified by token
	 */
	public function getMessageByToken($token)
	{
		// Checks if the operation is permitted by the API caller
		if (($chkRights = $this->isEntitled('public.tbl_msg_recipient', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_msg_message', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_msg_status', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		
		$sql = 'SELECT r.message_id,
						m.person_id as sender_id,
						r.person_id as receiver_id,
						m.subject,
						m.body,
						m.insertamum,
						m.relationmessage_id,
						m.oe_kurzbz,
						s.status,
						s.statusinfo,
						s.insertamum as statusamum
				  FROM public.tbl_msg_recipient r JOIN public.tbl_msg_message m USING (message_id)
						JOIN (
							SELECT * FROM public.tbl_msg_status WHERE status < ? ORDER BY insertamum DESC, status DESC
						) s ON (r.message_id = s.message_id AND r.person_id = s.person_id)
				 WHERE r.token = ?
				 LIMIT 1';
		
		$result = $this->db->query($sql, array(MSG_STATUS_DELETED, $token));
		if (is_object($result))
			return success($result->result());
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}
	
	/**
	 * Get all received messages for a person identified by person_id
	 */
	public function getMessagesByPerson($person_id, $all)
	{
		// Checks if the operation is permitted by the API caller
		if (($chkRights = $this->isEntitled('public.tbl_msg_recipient', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_msg_message', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_person', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_msg_status', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		
		$sql = 'SELECT DISTINCT ON (r.message_id) r.message_id,
						m.person_id,
						m.subject,
						m.body,
						m.priority,
						m.insertamum,
						m.relationmessage_id,
						m.oe_kurzbz,
						p.anrede,
						p.titelpost,
						p.titelpre,
						p.nachname,
						p.vorname,
						p.vornamen,
						s.status,
						s.statusinfo,
						s.insertamum AS statusamum
				  FROM public.tbl_msg_recipient r JOIN public.tbl_msg_message m USING (message_id)
						JOIN public.tbl_person p ON (p.person_id = m.person_id)
						JOIN (
							SELECT message_id, person_id, status, statusinfo, insertamum
							  FROM public.tbl_msg_status
							 %s
						  ORDER BY insertamum DESC
						) s ON (m.message_id = s.message_id AND r.person_id = s.person_id)
				 WHERE r.person_id = ?
			  ORDER BY r.message_id DESC, s.status DESC';
		
		$parametersArray = array($person_id);
		
		if ($all == 'true')
		{
			$sql = sprintf($sql, '');
		}
		else
		{
			array_push($parametersArray, $person_id, $person_id);
			$sql = sprintf($sql, 'WHERE person_id = ? AND message_id NOT IN (SELECT message_id FROM public.tbl_msg_status WHERE status >= 3 AND person_id = ?)');
		}
		
		$result = $this->db->query($sql, $parametersArray);
		if (is_object($result))
			return success($result->result());
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}
	
	/**
	 * Get all received messages for a person identified by uid
	 */
	public function getMessagesByUID($uid, $all)
	{
		// Checks if the operation is permitted by the API caller
		// @ToDo: Define the special right for reading own messages 'basis/message:own'
		// if same user
		if ($uid === getAuthUID())
		{
			if (($chkRights = $this->isEntitled('public.tbl_msg_message', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
				return $chkRights;
		}
		// if different user, for reading messages from other users
		else
		{
			if (($chkRights = $this->isEntitled('public.tbl_msg_message', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
				return $chkRights;
		}

		// get Data
		$sql = 'SELECT b.uid,
						m.person_id,
						m.message_id,
						m.subject,
						m.body,
						m.priority,
						m.relationmessage_id,
						m.oe_kurzbz,
						m.insertamum,
						p.anrede,
						p.titelpost,
						p.titelpre,
						p.nachname,
						p.vorname,
						p.vornamen,
						s.status,
						s.statusinfo,
						s.insertamum AS statusamum
				  FROM public.tbl_msg_recipient r JOIN public.tbl_msg_message m USING (message_id)
						JOIN public.tbl_person p ON (r.person_id = p.person_id)
						JOIN public.tbl_benutzer b ON (r.person_id = b.person_id)
						JOIN (
							SELECT * FROM public.tbl_msg_status ORDER BY insertamum DESC LIMIT 1
						) s ON (r.message_id = s.message_id AND r.person_id = s.person_id)
				 WHERE b.uid = ?';
		
		if (! $all)
			$sql .= ' AND (status < 3 OR status IS NULL)';
		
		$result = $this->db->query($sql, array($uid));
		if (is_object($result))
			return success($result->result());
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}
	
	/**
	 * getMessages
	 * 
	 * Gets all the messages to be sent
	 * 
	 * @param kontaktType specifies the type of the kontakt to get
	 * @param sent specifies the status of the messages to get (NULL never sent, otherwise the shipping date)
	 * @param limit specifies the number of messages to get
	 * @param message_id specifies a single message
	 */
	public function getMessages($kontaktType, $sent, $limit = null, $message_id = null)
	{
		// Checks if the operation is permitted by the API caller
		if (($chkRights = $this->isEntitled('public.tbl_msg_recipient', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_msg_message', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		if (($chkRights = $this->isEntitled('public.tbl_kontakt', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $chkRights;
		
		$query = 'SELECT mm.message_id,
						 ks.kontakt as sender,
						 kr.kontakt as receiver,
						 mr.person_id as receiver_id,
						 mr.token,
						 mm.subject,
						 mm.body,
						 mr.sentinfo
					FROM public.tbl_msg_recipient mr INNER JOIN public.tbl_msg_message mm USING (message_id)
						LEFT JOIN (
							SELECT person_id, kontakt FROM public.tbl_kontakt WHERE kontakttyp = ?
						) ks ON (ks.person_id = mm.person_id)
						LEFT JOIN (
							SELECT person_id, kontakt FROM public.tbl_kontakt WHERE kontakttyp = ?
						) kr ON (kr.person_id = mr.person_id)';
		
		$parametersArray = array($kontaktType, $kontaktType);
		
		if (is_null($sent) || $sent == '')
		{
			$query .= ' WHERE mr.sent IS NULL';
		}
		else
		{
			array_push($parametersArray, $sent);
			$query .= ' WHERE mr.sent = ?';
		}
		
		if (!is_null($message_id))
		{
			array_push($parametersArray, $message_id);
			$query .= ' AND mm.message_id = ?';
		}
		
		$query .= ' ORDER BY mr.insertamum ASC';
		
		if (!is_null($limit))
		{
			$query .= ' LIMIT ?';
			array_push($parametersArray, $limit);
		}
		
		// Get data of the messages to sent
		$result = $this->db->query($query, $parametersArray);
		if (is_object($result))
			return success($result->result());
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}
}