<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * GUI-Class srObjAlbumGUI
 *
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version           $Id:
 *
 */
class srObjAlbumFormGUI extends ilPropertyFormGUI {

	/**
	 * @var  srObjAlbum
	 */
	protected $album;
	/**
	 * @var srObjAlbumGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;


	/**
	 * @param            $parent_gui
	 * @param srObjAlbum $album
	 */
	public function __construct($parent_gui, srObjAlbum $album) {
		global $ilCtrl;
		$this->album = $album;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = new ilPhotoGalleryPlugin();
		$this->ctrl->saveParameter($parent_gui, 'album_id');
		$this->initForm();
	}


	private function initForm() {
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		if ($this->album->getId() == 0) {
			$this->setTitle($this->pl->txt('create_album'));
		} else {
			$this->setTitle($this->pl->txt('edit_album'));
		}
		$title = new ilTextInputGUI($this->pl->txt('albumtitle'), 'title');
		$title->setRequired(true);
		$this->addItem($title);
		$desc = new ilTextAreaInputGUI($this->pl->txt('description'), 'description');
		$this->addItem($desc);
		switch ($this->ctrl->getCmd()) {
			//			case 'update':
			case 'edit':
				$date_input = new ilDateTimeInputGUI($this->pl->txt('date'), 'create_date');
				$date_input->setDate(new ilDate($this->album->getCreateDate(), IL_CAL_DATE));
				$this->addItem($date_input);
				break;
			case 'add':
				$date_input = new ilDateTimeInputGUI($this->pl->txt('date'), 'create_date');
				$date_input->setDate(new ilDate(date('Y-m-d'), IL_CAL_DATE));
				$this->addItem($date_input);
				break;
		}
		if ($this->album->getId() == 0) {
			$this->addCommandButton('create', $this->pl->txt('create_album'));
			$this->addCommandButton('redirectToGalleryListAlbums', $this->pl->txt('cancel'));
		} else {
			$this->addCommandButton('update', $this->pl->txt('save'));
			$this->addCommandButton('redirectToGalleryManageAlbums', $this->pl->txt('cancel'));
		}
	}


	public function fillForm() {
		$array = array(
			'title' => $this->album->getTitle(),
			'description' => $this->album->getDescription(),
		);
		$this->setValuesByArray($array);
	}


	/**
	 * returns whether checkinput was successful or not.
	 *
	 * @return bool
	 */
	public function fillObject() {
		global $ilUser;
		if (! $this->checkInput()) {
			return false;
		}
		$this->album->setTitle($this->getInput('title'));
		$this->album->setDescription($this->getInput('description'));
		$date_array = $this->getInput('create_date');
		$date = $date_array['date']['y'] . '-' . $date_array['date']['m'] . '-' . $date_array['date']['d'];
		$this->album->setCreateDate($date);
		$this->album->setObjectId(ilObject::_lookupObjectId($_GET['ref_id']));
		$this->album->setUserId($ilUser->getId());

		return true;
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}
		if ($this->album->getId()) {
			$this->album->update();
		} else {
			$this->album->create();
		}

		return true;
	}
}