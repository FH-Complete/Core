<?php

/**
 * Messages GUI logic
 * This model extends CI_Model because here is just implemented logic
 * It does not represent a resource (ex. like models that extend DB_Model)
 */
class Messages_model extends CI_Model
{
	const REPLY_SUBJECT_PREFIX = 'Re: ';
	const REPLY_BODY_FORMAT = '<br><br><blockquote><i>On %s %s %s wrote:</i></blockquote><blockquote style="border-left:2px solid; padding-left: 8px">%s</blockquote>';

	const NO_AUTH_UID = 'online'; // hard coded uid if no authentication is performed

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Loads the message library
		$this->load->library('MessageLib'); // MessageModel loaded here!
		// Loads the person log library
		$this->load->library('PersonLogLib');
		// Loads the widget library
		$this->load->library('WidgetLib');

		// Loads model MessageToken_model
		$this->load->model('system/MessageToken_model', 'MessageTokenModel');

		// Loads model Benutzerrolle_model
		$this->load->model('system/Benutzerrolle_model', 'BenutzerrolleModel');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Prepares data for the view system/messages/htmlRead using a token that identifies a single message
	 */
	public function prepareHtmlRead($token)
	{
		if (isEmptyString($token)) show_error('The given token is not valid');

		// Retrieves message using the given token
		$messageResult = $this->MessageTokenModel->getMessageByToken($token);
		if (isError($messageResult)) show_error(getData($messageResult));
		if (!hasData($messageResult)) show_error('No message found with the given token');

		$message = getData($messageResult)[0]; // Found message data

		// Set message as read
		$srmsbtResult = $this->MessageTokenModel->setReadMessageStatusByToken($token);
		if (isError($srmsbtResult)) show_error(getData($srmsbtResult));

		// Retrieves message sender information
		$senderResult = $this->MessageTokenModel->getSenderData($message->sender_id);
		if (isError($senderResult)) show_error(getData($senderResult));
		if (!hasData($senderResult)) show_error('No sender information found');

		$sender = getData($senderResult)[0]; // Found sender data

		// Check if the receiver is an employee
		$isEmployee = false; // not by default
		$isEmployeeResult = $this->MessageTokenModel->isEmployee($message->receiver_id);
		if (isError($isEmployeeResult)) show_error(getData($isEmployeeResult));
		if (hasData($isEmployeeResult)) $isEmployee = true;

		// If the sender is an employee and are present configurations to reply
		$hrefReply = '';
		if ($isEmployee && !isEmptyString($this->config->item(MessageLib::CFG_REDIRECT_VIEW_MESSAGE_URL)))
		{
			$hrefReply = $this->config->item(MessageLib::CFG_MESSAGE_SERVER).
				$this->config->item(MessageLib::CFG_REDIRECT_VIEW_MESSAGE_URL).
				$token;
		}

		return array (
			'sender' => $sender,
			'message' => $message,
			'hrefReply' => $hrefReply
		);
	}

	/**
	 * Prepares data for the view system/messages/htmlWriteReply using a token that identifies a single message
	 */
	public function prepareHtmlWriteReply($token)
	{
		if (isEmptyString($token)) show_error('The given token is not valid');

		// Retrieves message using the given token
		$messageResult = $this->MessageTokenModel->getMessageByToken($token);
		if (isError($messageResult)) show_error(getData($messageResult));
		if (!hasData($messageResult)) show_error('No message found with the given token');

		$message = getData($messageResult)[0]; // Found message data

		// Retrieves message sender information
		$senderResult = $this->MessageTokenModel->getSenderData($message->sender_id);
		if (isError($senderResult)) show_error(getData($senderResult));
		if (!hasData($senderResult)) show_error('No sender information found');

		$sender = getData($senderResult)[0]; // Found sender data

		$replySubject = self::REPLY_SUBJECT_PREFIX.$message->subject;
		$replyBody = $this->_getReplyBody($message->body, $sender->vorname, $sender->nachname, $message->sent);

		return array (
			'receiver' => $sender->vorname.' '.$sender->nachname, // yep! the sender of the sent message is the receiver of the reply message
			'subject' => $replySubject,
			'body' => $replyBody,
			'receiver_id' => $message->sender_id,
			'relationmessage_id' => $message->message_id,
			'token' => $token
		);
	}

	/**
	 * Prepares data for the view system/messages/htmlWriteTemplate using person ids as main parameter
	 * Wrap method to _prepareHtmlWriteTemplate
	 */
	public function prepareHtmlWriteTemplatePersons($persons, $message_id = null, $recipient_id = null)
	{
		// Retrieves persons information
		$msgVarsData = $this->MessageModel->getMsgVarsDataByPersonId($persons);

		return $this->_prepareHtmlWriteTemplate($msgVarsData, $message_id, $recipient_id);
	}

	/**
	 * Prepares data for the view system/messages/htmlWriteTemplate using prestudent ids as main parameter
	 * Wrap method to _prepareHtmlWriteTemplate
	 */
	public function prepareHtmlWriteTemplatePrestudents($prestudents, $message_id = null, $recipient_id = null)
	{
		// Retrieves prestudents information
		$msgVarsData = $this->MessageModel->getMsgVarsDataByPrestudentId($prestudents);

		return $this->_prepareHtmlWriteTemplate($msgVarsData, $message_id, $recipient_id);
	}

	/**
	 * Sends a new message or a reply to a message (if $relationmessage_id is given)
	 * using the template stored in the subject and body
	 */
	public function sendImplicitTemplate($persons, $subject, $body, $relationmessage_id = null)
	{
		// Retrieves the sender id
		$authUser = $this->_getAuthUser();
		if (isError($authUser)) show_error(getData($authUser));
		if (!hasData($authUser)) show_error('The current logged user person_id is not defined');

		$sender_id = getData($authUser)[0]->person_id;

		// Retrieves message vars data for the given user/s
		$msgVarsData = $this->MessageModel->getMsgVarsDataByPersonId($persons);
		if (isError($msgVarsData)) show_error(getData($msgVarsData));
		if (!hasData($msgVarsData)) show_error('No recipients were given');

		foreach (getData($msgVarsData) as $receiver)
		{
			$msgVarsDataArray = $this->_lowerReplaceSpaceArrayKeys((array)$receiver); // replaces array keys

			$parsedSubject = parseText($subject, $msgVarsDataArray);
			$parsedBody = parseText($body, $msgVarsDataArray);

			$message = $this->messagelib->sendMessageUser(
				$msgVarsDataArray['person_id'],	// receiverPersonId
				$parsedSubject,					// subject
				$parsedBody,					// body
				$sender_id,						// sender_id
				null,							// senderOU
				$relationmessage_id,			// relationmessage_id
				MSG_PRIORITY_NORMAL				// priority
			);

			if (isError($message)) return $message;
			if (!hasData($message)) return error('No messages were saved in database');

			// Write log entry
			$personLog = $this->_personLog($sender_id, $msgVarsDataArray['person_id'], getData($message)[0]);
			if (isError($personLog)) return $personLog;
		}

		return success('Messages sent successfully');
	}

	/**
	 * Sends a new message using the given template and information present in parameter prestudents
	 * Extra variables can be added using parameter $msgVars
	 */
	public function sendExplicitTemplate($prestudents, $oe_kurzbz, $vorlage_kurzbz, $msgVars)
	{
		// Retrieves the sender id
		$authUser = $this->_getAuthUser();
		if (isError($authUser)) show_error(getData($authUser));
		if (!hasData($authUser)) show_error('The current logged user person_id is not defined');

		$sender_id = getData($authUser)[0]->person_id;

		// Retrieves message vars data for the given user/s
		$msgVarsData = $this->MessageModel->getMsgVarsDataByPrestudentId($prestudents);
		if (isError($msgVarsData)) show_error(getData($msgVarsData));
		if (!hasData($msgVarsData)) show_error('No recipients were given');

		$this->load->model('crm/Prestudent_model', 'PrestudentModel');
		$prestudentsData = $this->PrestudentModel->getOrganisationunits($prestudents);

		// Adds the organisation unit to each prestudent
		if (isEmptyString($oe_kurzbz) && hasData($msgVarsData) && hasData($prestudentsData))
		{
			$this->CLMessagesModel->_addOeToPrestudents($msgVarsData, $prestudentsData);
		}

		foreach (getData($msgVarsData) as $receiver)
		{
			$msgVarsDataArray = $this->_lowerReplaceSpaceArrayKeys((array)$receiver); // replaces array keys

			// Additional message variables
			if (is_array($msgVars)) $msgVarsDataArray = array_merge($msgVarsDataArray, $msgVars);

			$message = $this->messagelib->sendMessageUserTemplate(
				$msgVarsDataArray['person_id'],			// receiversPersonId
				$vorlage_kurzbz,						// vorlage
				$msgVarsDataArray,						// parseData
				null,									// orgform
				$sender_id,								// sender_id
				$oe_kurzbz								// senderOU
			);

			if (isError($message)) return $message;

			// Write log entry
			$personLog = $this->_personLog($sender_id, $msgVarsDataArray['person_id'], getData($message)[0]);
			if (isError($personLog)) return $personLog;
		}

		return success('Messages sent successfully');
	}

	/**
	 * Send a reply to a single recipient for a message identified by a token (no templates are used)
	 */
	public function sendReply($receiver_id, $subject, $body, $relationmessage_id, $token)
	{
		// Retrieves message sender information
		$senderResult = $this->MessageTokenModel->getSenderData($receiver_id);
		if (isError($senderResult)) show_error(getData($senderResult));
		if (!hasData($senderResult)) show_error('No sender information found');

		$sender = getData($senderResult)[0]; // Found sender data

		$messageResult = $this->MessageTokenModel->getMessageByToken($token);
		if (isError($messageResult)) show_error(getData($messageResult));
		// Security check! It is possible to reply only to a received message!!
		if (!hasData($messageResult) || $relationmessage_id != getData($messageResult)[0]->message_id)
		{
			show_error('An error occurred while sending your message, please contact the site administrator');
		}

		$sender_id = getData($messageResult)[0]->receiver_id;

		$message = $this->messagelib->sendMessageUser(
			$receiver_id,			// receiverPersonId
			$subject,				// subject
			$body,					// body
			$sender_id,				// sender_id, the receiver of the previous message is the sender of the current one
			null,					// senderOU
			$relationmessage_id,	// relationmessage_id
			MSG_PRIORITY_NORMAL		// priority
		);

		if (isError($message)) return $message;
		if (!hasData($message)) return error('No messages were saved in database');

		// Write log entry
		$personLog = $this->_personLog($sender_id, $receiver_id, getData($message)[0]);
		if (isError($personLog)) return $personLog;

		return success('Messages sent successfully');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods called by controller system/messages/Messages

	/**
	 * Returns an object that represent a template store in database
	 * If no templates are found with the given parameter or the given parameter is an empty string,
	 * then an error is returned
	 */
	public function getVorlage($vorlage_kurzbz)
	{
		$getVorlage = error('The given vorlage_kurzbz is not valid');

		if (!isEmptyString($vorlage_kurzbz))
		{
			$this->load->model('system/Vorlagestudiengang_model', 'VorlagestudiengangModel');
			$this->VorlagestudiengangModel->addOrder('version','DESC');

			$getVorlage = $this->VorlagestudiengangModel->loadWhere(array('vorlage_kurzbz' => $vorlage_kurzbz));
		}

		return $getVorlage;
	}

	/**
	 * Parse the given given text using data from the given user
	 * Use the CI parser which performs simple text substitution for pseudo-variable
	 */
	public function parseMessageText($person_id, $text)
	{
		$parseMessageText = error('The given person_id is not a valid number');

		if (is_numeric($person_id))
		{
			$parseMessageText = $this->MessageModel->getMsgVarsDataByPersonId($person_id);
		}

		if (hasData($parseMessageText))
		{
			$parseMessageText = success(
				parseText(
					$text,
					$this->_lowerReplaceSpaceArrayKeys((array)getData($parseMessageText)[0])
				)
			);
		}

		return $parseMessageText;
	}

	/**
	 * Outputs message data for a message (identified my msg id and receiver id) in JSON format
	 */
	public function getMessageFromIds($message_id, $receiver_id)
	{
		$getMessageFromIds = error('The given message id or receiver id are not valid');

		if (is_numeric($message_id) && is_numeric($receiver_id))
		{
			$getMessageFromIds = $this->messagelib->getMessage($message_id, $receiver_id);
		}

		if (isError($getMessageFromIds) || !hasData($getMessageFromIds))
		{
			return array();
		}
		else
		{
			return array(getData($getMessageFromIds)[0]);
		}
	}

	//------------------------------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Returns the current authenticated person object
	 */
	private function _getAuthUser()
	{
		$this->load->model('person/Person_model', 'PersonModel');
		$authUser = $this->PersonModel->getByUid(getAuthUID());

		return $authUser;
	}

	/**
	 * Replaces data array keys to a lowercase string with underscores instead of spaces
	 */
	private function _lowerReplaceSpaceArrayKeys($data)
	{
		$tmpData = array();

		foreach ($data as $key => $val)
		{
			$tmpData[str_replace(' ', '_', strtolower($key))] = $val;
		}

		return $tmpData;
	}

	/**
	 * Add organisation unit to an array of prestudents (objects)
	 */
	private function _addOeToPrestudents(&$msgVarsData, $prestudentsData)
	{
		for ($i = 0; $i < count(getData($msgVarsData)); $i++)
		{
			for ($p = 0; $p < count(getData($prestudentsData)); $p++)
			{
				if (getData($prestudentsData)[$p]->prestudent_id == getData($msgVarsData)[$i]->prestudent_id)
				{
					$msgVarsData->retval[$i]->oe_kurzbz = getData($prestudentsData)[$p]->oe_kurzbz;
					break;
				}
			}
		}
	}

	/**
	 * Perform a person log after a message is sent
	 */
	private function _personLog($sender_id, $receiver_id, $message_id)
	{
		// In case the message is accessed via ViewMessage controller -> no authentication
		// If no authentication is performed then use a hard coded uid
		$loggedUserUID = function_exists('getAuthUID') ? getAuthUID() : self::NO_AUTH_UID;

		return $this->personloglib->log(
			$receiver_id,
			'Action',
			array(
				'name' => 'Message sent',
				'message' => 'Message sent from person '.$sender_id.' to '.$receiver_id.', message id: '.$message_id,
				'success' => 'true'
			),
			'kommunikation',
			'core',
			null,
			$loggedUserUID
		);
	}

	/**
	 *
	 */
	private function _getReplyBody($body, $receiverName, $receiverSurname, $sentDate)
	{
		return sprintf(
			self::REPLY_BODY_FORMAT,
			date_format(date_create($sentDate), 'd.m.Y H:i'), $receiverName, $receiverSurname, $body
		);
	}

	/**
	 * Prepares data for the view system/messages/htmlWriteTemplate using the given parameters
	 */
	private function _prepareHtmlWriteTemplate($info, $message_id, $recipient_id)
	{
		// Checks that info parameter is valid
		if (isError($info)) show_error(getData($info));
		if (!hasData($info)) show_error('No recipients were given');

		// If the message id and recipient id are given, then both they must be valid numbers
		if ((is_numeric($message_id) && !is_numeric($recipient_id))
			|| (!is_numeric($message_id) && is_numeric($recipient_id)))
		{
			show_error('If given, message id and recipient id both must be valid numbers');
		}

		// ---------------------------------------------------------------------------------------
		// Retrieves the recipients information and builds:
		// - recipientsArray: an array that contains objects with id (person_id) and description (Vorname + Nachname) of recipient
		// - recipientsList: a string that contains all the recipients descriptions (Vorname + Nachname) separated by ;
		// - persons: a string that contains HTML input hidden with alla the receivers id (person_id)
		$recipientsArray = array();
		$recipientsList = '';
		$persons = '';
		foreach (getData($info) as $receiver)
		{
			$recipient = new stdClass();
			$recipient->id = $receiver->person_id;
			$recipient->description = $receiver->Vorname.' '.$receiver->Nachname;

			$recipientsArray[] = $recipient;
			$recipientsList .= $receiver->Vorname.' '.$receiver->Nachname.'; ';
			$persons .= '<input type="hidden" name="persons[]" value="'.$receiver->person_id.'">'."\n";
		}

		// ---------------------------------------------------------------------------------------
		// Retrieves the message to reply to, if it is specified by parameters $message_id and $recipient_id
		$replySubject = ''; // message reply subject
		$replyBody = ''; // message reply body
		$relationmessage = ''; // input hidden that contains the message id to be replied to
		// If both are given and they are valid
		if (is_numeric($message_id) && is_numeric($recipient_id))
		{
			// Retrieves a received message from tbl_msg_recipient
			$messageResult = $this->messagelib->getMessage($message_id, $recipient_id);
			if (isError($messageResult)) show_error(getData($messageResult));
			if (!hasData($messageResult)) show_error('The selected message does not exist');

			$message = getData($messageResult)[0];

			$replySubject = self::REPLY_SUBJECT_PREFIX.$message->subject;
			$replyBody = $this->_getReplyBody($message->body, $receiver->Vorname, $receiver->Nachname, $message->sent);
			$relationmessage = '<input type="hidden" name="relationmessage_id" value="'.$message_id.'">';
		}

		// ---------------------------------------------------------------------------------------
		// Retrieves message vars from database view vw_msg_vars_person
		$variablesResult = $this->messagelib->getMessageVarsPerson();
		if (isError($variablesResult)) show_error(getData($variablesResult));

		// Then builds an array that contains objects with id (person_id) and description (Vorname + Nachname) of recipient
		$variables = array();
		foreach (getData($variablesResult) as $id => $description)
		{
			$tmpVar = new stdClass();
			$tmpVar->id = $id;
			$tmpVar->description = $description;

			$variables[] = $tmpVar;
		}

		// ---------------------------------------------------------------------------------------
		// Retrieves the sender id
		$authUser = $this->_getAuthUser();
		if (isError($authUser)) show_error(getData($authUser));
		if (!hasData($authUser)) show_error('The current logged user person_id is not defined');

		$sender_id = getData($authUser)[0]->person_id;

		// ---------------------------------------------------------------------------------------
 		// Organisation units and a boolean (true if the sender is administrator) are used to get the templates
		$organisationUnits = $this->messagelib->getOeKurzbz($sender_id);
		if (isError($organisationUnits)) show_error(getData($organisationUnits));
		$senderIsAdmin = $this->BenutzerrolleModel->isAdminByPersonId($sender_id);
		if (isError($senderIsAdmin)) show_error(getData($senderIsAdmin));

		// ---------------------------------------------------------------------------------------
		// Returns data as an array
		return array (
			'recipientsList' => $recipientsList,
			'subject' => $replySubject,
			'body' => $replyBody,
			'variables' => $variables,
			'organisationUnits' => getData($organisationUnits),
			'senderIsAdmin' => getData($senderIsAdmin),
			'recipientsArray' => $recipientsArray,
			'persons' => $persons,
			'relationmessage_id' => $relationmessage
		);
	}
}
