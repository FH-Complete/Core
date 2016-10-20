<?php

class Person_model extends DB_Model
{
	/**
	 * 
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'public.tbl_person';
		$this->pk = 'person_id';
	}
	
	public function getPersonKontaktByZugangscode($zugangscode, $email)
	{
		$this->addJoin('public.tbl_kontakt', 'person_id');
		
		return $this->loadWhere(array('zugangscode' => $zugangscode, 'kontakt' => $email));
	}

	/**
	 * 
	 */
	public function checkBewerbung($email, $studiensemester_kurzbz = null)
	{
		if (($isEntitled = $this->isEntitled('public.tbl_person', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $isEntitled;
		if (($isEntitled = $this->isEntitled('public.tbl_kontakt', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $isEntitled;
		if (($isEntitled = $this->isEntitled('public.tbl_benutzer', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $isEntitled;
		if (($isEntitled = $this->isEntitled('public.tbl_prestudent', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $isEntitled;
		if (($isEntitled = $this->isEntitled('public.tbl_prestudentstatus', PermissionLib::SELECT_RIGHT, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			return $isEntitled;
		
		$checkBewerbungQuery = '';
		$parametersArray = array($email, $email, $email);
		
		if (is_null($studiensemester_kurzbz))
		{
			$checkBewerbungQuery = 'SELECT DISTINCT p.person_id, p.zugangscode, p.insertamum
 									  FROM public.tbl_person p JOIN public.tbl_kontakt k ON p.person_id = k.person_id
							     LEFT JOIN public.tbl_benutzer b ON p.person_id = b.person_id
								     WHERE k.kontakttyp = \'email\'
									   AND (kontakt = ? OR alias || \'@' . DOMAIN . '\' = ? OR uid || \'@' . DOMAIN . '\' = ?)
								  ORDER BY p.insertamum DESC
								     LIMIT 1';
		}
		else
		{
			$checkBewerbungQuery = 'SELECT DISTINCT p.person_id, p.zugangscode, p.insertamum
									  FROM public.tbl_person p JOIN public.tbl_kontakt k ON p.person_id = k.person_id
								 LEFT JOIN public.tbl_benutzer b ON p.person_id = b.person_id
									  JOIN public.tbl_prestudent ps ON p.person_id = ps.person_id
									  JOIN public.tbl_prestudentstatus pst ON pst.prestudent_id = ps.prestudent_id
									 WHERE k.kontakttyp = \'email\'
									   AND (kontakt = ? OR alias || \'@' . DOMAIN . '\' = ? OR uid || \'@' . DOMAIN . '\' = ?)
									   AND studiensemester_kurzbz = ?
								  ORDER BY p.insertamum DESC
									 LIMIT 1';
			
			array_push($parametersArray, $studiensemester_kurzbz);
		}
		
		return $this->execQuery($checkBewerbungQuery, $parametersArray);
	}
	
	public function updatePerson($person)
	{
		if (isset($person['svnr']) && $person['svnr'] != '')
		{
			$this->PersonModel->addOrder('svnr', 'DESC');
			$result =  $this->PersonModel->loadWhere(array(
				'person_id != ' => $person['person_id'],
				'SUBSTRING(svnr FROM 1 FOR 10) = ' => $person['svnr'])
			);
			if (hasData($result))
			{
				if (count($result->retval) == 1 && $result->retval[0]->svnr == $person['svnr'])
				{
					$person['svnr'] = $person['svnr'] . 'v1';
				}
				else
				{
					$person['svnr'] = $person['svnr'] . 'v' . ($result->retval[0]->svnr{11} + 1);
				}
			}
		}
		
		return $this->PersonModel->update($person['person_id'], $person);
	}
}