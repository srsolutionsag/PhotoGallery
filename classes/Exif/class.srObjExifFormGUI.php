<?php
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');

/**
 * Created by JetBrains PhpStorm.
 * User: zeynep
 *
 * @author                  Zeynep Karahan <zk@studer-raimann.ch>
 *                          Date: 24.10.13
 *                          Time: 11:12
 *                          To change this template use File | Settings | File Templates.
 */
class srObjExifFormGUI extends ilPropertyFormGUI {

	/**
	 * @var  srObjExif
	 */
	protected $exif;
	/**
	 * @var  srObjExifGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var srObjPicture
	 */
	protected $picture;


	public function __construct(srObjExifGUI $parent_gui, srObjExif $exif) {
		global $ilCtrl, $tpl, $ilLocator;

		$this->tpl = $tpl;
		$this->exif = $exif;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->ilLocator = $ilLocator;

		$this->ctrl->saveParameter($parent_gui, 'picture_id');
		$this->pl = new ilPhotoGalleryPlugin();
		$this->initForm();
	}


	/**
	 * @param string $mode
	 */
	public function initForm($mode = 'show') {
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));

		$title = new ilNonEditableValueGUI($this->pl->txt('pic_title'), 'title');
		$this->addItem($title);

		$desc = new ilNonEditableValueGUI($this->pl->txt('description'), 'description');
		$this->addItem($desc);

		//TODO LATER Original.jpg ersetzen.
		$arr_exif = @exif_read_data($this->parent_gui->picture->getPicturePath() . "/presentation.jpg");
		foreach ($arr_exif as $key => $value) {
			$exif_field = new ilNonEditableValueGUI($key, $key);
			$exif_field->setValue($value);
			$this->addItem($exif_field);
		}
		$this->fillForm();

		if (is_object($this->exif)) {
			$this->ilLocator->addItem($this->pl->txt("exif_data"), $this->ctrl->getLinkTargetByClass("srobjexifgui", ""), "", $_REQUEST['picture_id']);
			$this->ctrl->saveParameter($this, $_REQUEST["album_id"]);
			$this->tpl->setLocator();
		}
	}


	public function fillForm() {
		$array = array(
			'title' => $this->parent_gui->picture->getTitle(),
			'description' => $this->parent_gui->picture->getDescription(),

		);
		$this->setValuesByArray($array);
	}


	public function saveObject() {

		if ($this) {
			return false;
		}
		if ($this->picture->getId()) {
			$this->exif->update();
			$this->picture->uploadPicture($_FILES['image_type']['tmp_name']);
			$this->picture->update();
		} else {
			$this->exif->create();
			$this->picture->uploadPicture($_FILES['image_type']['tmp_name']);
			$this->picture->update();
		}

		return true;
	}
}