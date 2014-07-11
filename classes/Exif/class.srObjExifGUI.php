<?php
require_once('class.srObjExif.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.srObjExifFormGUI.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');

/**
 * GUI-Class srObjExifGUI
 *
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @version           $Id:
 *
 */
class srObjExifGUI {

	/**
	 * @var ilTabsGUI
	 */
	protected $tabs_gui;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;


	/**
	 * @param $parent_gui
	 */
	public function __construct($parent_gui) {
		global $tpl, $ilCtrl, $ilToolbar, $lng;

		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent = $parent_gui;
		$this->toolbar = $ilToolbar;
		$this->pl = new ilPhotoGalleryPlugin();
		$this->tabs_gui = $this->parent->tabs_gui;
		$this->lng = $lng;

		//$this->tabs_gui->setBackTarget($this->pl->txt('back_to_diashow'), $this->ctrl->getLinkTargetByClass("srobjslidergui", "index"));

		$this->picture = new srObjPicture($_GET['picture_id']);
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();
		$this->ctrl->saveParameter($this, 'picture_id');

		switch ($cmd) {
			case '':
				$this->show();
				break;
			default:
				$this->$cmd();
				break;
		}

		return true;
	}


	/**
	 * @return bool
	 * @description set $this->lng with your LanguageObject or return false to use global Language
	 */
	protected function initLanguage() {
		global $lng;
		$this->pl = $lng;
		$this->lng = $lng;

		return true;
	}


	public function show() {
		$form = new srObjExifFormGUI($this, new srObjExif($_GET['picture_id']));
		$form->fillForm();
		$this->tpl->setContent($form->getHTML());
	}
}

?>