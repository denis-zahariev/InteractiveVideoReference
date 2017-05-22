<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/COPage/classes/class.ilPageComponentPluginGUI.php';

/**
 * Class ilInteractiveVideoReferencePlugin
 * @ilCtrl_isCalledBy ilInteractiveVideoReferencePluginGUI: ilPCPluggedGUI
 * @ilCtrl_Calls ilInteractiveVideoReferencePluginGUI: ilPropertyFormGUI
 */
class ilInteractiveVideoReferencePluginGUI extends \ilPageComponentPluginGUI
{
	/**
	 * @inheritdoc
	 */
	public function executeCommand()
	{
		/**
		 * @var $ilCtrl ilCtrl
		 */
		global $ilCtrl;

		$next_class = $ilCtrl->getNextClass();

		switch($next_class)
		{
			case 'ilpropertyformgui':
				$ilCtrl->forwardCommand($this->getConfigurationForm());
				break;

			default:
				$cmd = $ilCtrl->getCmd();
				if(in_array($cmd, array('create', 'save', 'edit', 'update', 'cancel')))
				{
					$this->$cmd();
				}
				break;
		}
	}

	/**
	 * @param bool $a_create
	 * @return ilPropertyFormGUI
	 */
	protected function getConfigurationForm($a_create = false)
	{
		/**
		 * @var $lng  ilLanguage
		 * @var $ilCtrl ilCtrl
		 */
		global $lng, $ilCtrl;

		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

		$form = new ilPropertyFormGUI();
		$form->setTitle($lng->txt('settings'));
		$form->setFormAction($ilCtrl->getFormAction($this));

		$pl = $this->getPlugin();

		$pl->includeClass('form/class.ilInteractiveVideoReferenceSelectionExplorerGUI.php');
		$ilCtrl->setParameterByClass('ilformpropertydispatchgui', 'postvar', 'xvid_ref_id');
		$explorer_gui = new ilInteractiveVideoReferenceSelectionExplorerGUI(
			array('ilpropertyformgui', 'ilformpropertydispatchgui', 'ilInteractiveVideoReferenceRepositorySelectorInputGUI'),
			'handleExplorerCommand'
		);

		$pl->includeClass('form/class.ilInteractiveVideoReferenceRepositorySelectorInputGUI.php');
		$sap_root_ref_id = new ilInteractiveVideoReferenceRepositorySelectorInputGUI(
			$pl->txt('xvid_ref_id'),
			'xvid_ref_id', $explorer_gui, false
		);
		$sap_root_ref_id->setRequired(true);
		$sap_root_ref_id->setInfo($pl->txt('xvid_ref_id_info'));
		$form->addItem($sap_root_ref_id);

		$show_button = new ilCheckboxInputGUI(
			$pl->txt('show_button'),
			'show_button'
		);
		$show_button->setValue(1);
		$show_button->setInfo($pl->txt('show_button_info'));
		$form->addItem($show_button);

		if($a_create)
		{
			$this->addCreationButton($form);
			$form->addCommandButton('cancel', $lng->txt('cancel'));
		}
		else
		{
			$form->addCommandButton('update', $lng->txt('save'));
			$form->addCommandButton('cancel', $lng->txt('cancel'));
		}

		return $form;
	}

	/**
	 * @inheritdoc
	 */
	public function insert()
	{
		global $tpl;

		$form = $this->getConfigurationForm(true);
		$tpl->setContent($form->getHTML());
	}

	/**
	 * @inheritdoc
	 */
	public function create()
	{
		global $tpl, $lng;

		$form = $this->getConfigurationForm(true);
		if($form->checkInput())
		{
			$properties = array(
				'xvid_ref_id' => $form->getInput('xvid_ref_id'),
				'show_button' => (int)$form->getInput('show_button')
			);

			if($this->createElement($properties))
			{
				ilUtil::sendSuccess($lng->txt('msg_obj_modified'), true);
				$this->returnToParent();
			}
		}

		$form->setValuesByPost();
		$tpl->setContent($form->getHtml());
	}

	/**
	 * @inheritdoc
	 */
	public function edit()
	{
		global $tpl;

		$properties = $this->getProperties();

		$form = $this->getConfigurationForm();
		$form->setValuesByArray(array(
			'xvid_ref_id' => $properties['xvid_ref_id'],
			'show_button' => (bool)$properties['show_button']
		));
		$tpl->setContent($form->getHTML());
	}

	/**
	 * 
	 */
	public function update()
	{
		global $tpl, $lng;

		$form = $this->getConfigurationForm();
		if($form->checkInput())
		{
			$properties = array(
				'xvid_ref_id' => $form->getInput('xvid_ref_id'),
				'show_button' => (int)$form->getInput('show_button')
			);

			if($this->updateElement($properties))
			{
				ilUtil::sendSuccess($lng->txt('msg_obj_modified'), true);
				$this->returnToParent();
			}
		}

		$form->setValuesByPost();
		$tpl->setContent($form->getHtml());
	}

	/**
	 * 
	 */
	public function cancel()
	{
		$this->returnToParent();
	}

	/**
	 * @return string
	 */
	private function getEmptyResponseString()
	{
		// Workaround for issue in ilPCPlugged
		return  '<!-- nothing -->';
	}

	/**
	 * @inheritdoc
	 */
	public function getElementHTML($a_mode, array $a_properties, $plugin_version)
	{
		/**
		 * @var $ilAccess ilAccessHandler
		 * @var $tree     ilTree
		 * @var $tpl ilTemplate
		 */
		global $ilAccess, $tree;

		if(!isset($a_properties['xvid_ref_id']) || !is_numeric($a_properties['xvid_ref_id']) || $a_properties['xvid_ref_id'] <= 0)
		{
			return $this->getEmptyResponseString();
		}

		$ref_id = $a_properties['xvid_ref_id'];
		if(!ilObject::_exists($ref_id, true))
		{
			return $this->getEmptyResponseString();
		}

		if($tree->isDeleted($ref_id))
		{
			return $this->getEmptyResponseString();
		}

		if(!$ilAccess->checkAccess('visible', '', $ref_id))
		{
			return $this->getEmptyResponseString();
		}

		$pl  = $this->getPlugin();
		$tpl = $pl->getTemplate('tpl.content.html');

		/**
		 * @var $xvid ilObjInteractiveVideo
		 */
		$xvid = ilObjectFactory::getInstanceByRefId($ref_id);

		$tpl->setVariable('TITLE', $xvid->getTitle());
		$tpl->setVariable('DESCRIPTION', $xvid->getDescription());
		$tpl->setVariable('ICON', ilObject::_getIcon($xvid->getId()));

		if($ilAccess->checkAccess('read', '', $ref_id))
		{
			$params = array();

			if(in_array($a_mode, array('presentation', 'preview')))
			{
				$params['xvid_referrer'] = urlencode($_SERVER['REQUEST_URI']);
			}
			$params['xvid_referrer_ref_id'] = (int)$_GET['ref_id'];

			require_once 'Services/Link/classes/class.ilLink.php';

			if($a_properties['show_button'])
			{
				require_once 'Services/UIComponent/Button/classes/class.ilLinkButton.php';
				$btn = ilLinkButton::getInstance();
				$btn->setCaption($pl->txt('goto_xvid'), false);
				$btn->setUrl(ilLink::_getLink($ref_id, 'xvid', $params));
				$tpl->setVariable('LINK_BUTTON', $btn->render());
			}

			$tpl->setVariable('LINKED_TITLE', $xvid->getTitle());
			$tpl->setVariable('URL', ilLink::_getLink($ref_id, 'xvid', $params));
		}
		else
		{
			$tpl->setVariable('UNLINKED_TITLE', $xvid->getTitle());
		}
		$GLOBALS['tpl']->addCss('./Customizing/global/plugins/Services/COPage/PageComponent/InteractiveVideoReference/templates/xvid_ref.css');

		return $tpl->get();
	}

}