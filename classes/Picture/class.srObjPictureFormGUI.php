<?php
require_once('./Services/FileUpload/classes/class.ilFileUploadGUI.php');
require_once('./Services/Form/classes/class.ilDragDropFileInputGUI.php');
require_once('./Services/Accordion/classes/class.ilAccordionGUI.php');

/**
 * Class srObjPictureFormGUI
 *
 * @author              Zeynep Karahan <zk@studer-raimann.ch>
 * @author              Martin Studer <ms@studer-raimann.ch>
 *
 * @version             1.0.0
 */
class srObjPictureFormGUI extends ilPropertyFormGUI {

	/**
	 * @var  srObjPicture
	 */
	protected $picture;
	/**
	 * @var srObjPictureGUI
	 */
	protected $parent_gui;
	/**
	 * @var ilLog
	 */
	protected $log;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var ilPhotoGalleryPlugin
	 */
	protected $pl;
	/**
	 * @var srObjAlbum
	 */
	protected $album;

	/**
	 * @param              $parent_gui
	 * @param srObjPicture $picture
	 */
	public function __construct($parent_gui, srObjPicture $picture) {
		parent::__construct();
		global $DIC;
		$this->ctrl = $DIC->ctrl();
		$this->user = $DIC->user();
		$this->picture = $picture;
		$this->parent_gui = $parent_gui;
		$this->pl = ilPhotoGalleryPlugin::getInstance();
		$this->ctrl->saveParameter($parent_gui, 'picture_id');
		$this->album = new srObjAlbum($_GET['album_id']);
		$this->initForm();
		$this->log = $DIC["ilLog"];
	}


	private function initForm() {
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		if ($this->picture->getId() == 0) {
			$this->setTitle($this->pl->txt('upload_pic'));
		} else {
			$this->setTitle($this->pl->txt('edit_pic'));
		}
		$cmd = ($this->ctrl->getCmd() == 'post') ? $_GET['fallbackCmd'] : $this->ctrl->getCmd();
		switch ($cmd) {
			//			case atTableGUI::CMD_UPDATE:
			case atTableGUI::CMD_EDIT:
			case atTableGUI::CMD_UPDATE:
				$title = new ilTextInputGUI($this->pl->txt('pic_title'), 'title');
				$title->setRequired(true);
				$this->addItem($title);
				$desc = new ilTextAreaInputGUI($this->pl->txt('description'), 'description');
				$this->addItem($desc);
				$date_input = new ilDateTimeInputGUI($this->pl->txt('date'), 'create_date');
				$date_input->setDate(new ilDate($this->picture->getCreateDate(), IL_CAL_DATE));
				$this->addItem($date_input);
				$vorschau = new ilCheckboxInputGUI($this->pl->txt('select_preview'), 'preview');
				$vorschau->setValue(1);
				$this->addItem($vorschau);
				$this->addCommandButton(atTableGUI::CMD_UPDATE, $this->pl->txt('save'));
				$this->addCommandButton(srObjPictureGUI::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES, $this->pl->txt('cancel'));
				$this->setFormAction($this->ctrl->getFormActionByClass(srObjPictureGUI::class, atTableGUI::CMD_UPDATE));
				break;
			case atTableGUI::CMD_ADD:
			case atTableGUI::CMD_CREATE:
				$this->setMultipart(true);
				// TODO image type is missed
				$file_input = new ilDragDropFileInputGUI($this->pl->txt('pic'), 'upload_files');
				$file_input->setRequired(true);
				$file_input->setSuffixes(array(
					'jpg',
					'jpeg',
					'png',
					'gif'
				));
				$file_input->setCommandButtonNames(atTableGUI::CMD_CREATE, srObjPictureGUI::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
				$this->addItem($file_input);
				$this->addCommandButton(atTableGUI::CMD_CREATE, $this->pl->txt('add_pic'));
				$this->addCommandButton(srObjPictureGUI::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES, $this->pl->txt('cancel'));
				$this->setFormAction($this->ctrl->getFormActionByClass(srObjPictureGUI::class, atTableGUI::CMD_CREATE));
				break;
		}
	}


	public function fillForm() {
		$array = array(
			'title' => $this->picture->getTitle(),
			'description' => $this->picture->getDescription(),
			'preview' => $this->album->getPreviewId() == $this->picture->getId(),
			'suffix' => $this->picture->getSuffix(),
		);
		$this->setValuesByArray($array, true);
	}


	/**
	 * @description returns whether checkinput was successful or not.
	 *
	 * @return bool
	 */
	public function fillObject() {
		if (!$this->checkInput()) {
			return false;
		}
		$this->picture->setTitle($this->getInput('title'));
		$this->picture->setDescription($this->getInput('description'));
		if (!$this->picture->getId()) {
			$this->picture->setAlbumId($_GET['album_id']);
		}
		$this->picture->setUserId($this->user->getId());
		$date_array = $this->getInput('create_date');
		if (is_array($date_array)) {
			$date = $date_array['date'];
		} else {
			$date = date('Y-m-d', strtotime($date_array));
		}
		$this->picture->setCreateDate($date); // TODO bei MultipleFileUpload Exif-Daten verwenden
		if ($this->getInput('preview') == 1) {
			$this->album->setPreviewId($_GET['picture_id']);
		}

		return true;
	}


	public function saveObject() {
		if (!$this->fillObject()) {
			return false;
		}
		if ($this->picture->getId()) {
			if ($_FILES['upload_files']['tmp_name']) {
				$this->picture->uploadPicture($_FILES['upload_files']['tmp_name']);
				$ext = strtolower(end(explode('.', $_FILES['upload_files']['name'])));
				$this->picture->setSuffix($ext);
			}
			$this->picture->update();
			$this->album->update();
		} else {
			$ext = strtolower(end(explode('.', $_FILES['upload_files']['name'])));
			$this->picture->setSuffix($ext);
			if (function_exists('exif_read_data')) {
				$exif = @exif_read_data($_FILES['upload_files']['tmp_name'], 0, true);
			}
			if (isset($exif["EXIF"]["DateTimeOriginal"])) {
				//TODO Refactoring
				$exifPieces = explode(" ", $exif["EXIF"]["DateTimeOriginal"]);
				$this->picture->setCreateDate(str_replace(":", "-", $exifPieces[0]));
			} else {
				$this->picture->setCreateDate(date('Y-m-d'));
			}
			$this->picture->create();
			$this->picture->uploadPicture($_FILES['upload_files']['tmp_name']);
			// create answer object
			$response = new stdClass();
			$response->fileName = $_FILES['upload_files']['name'];
			$response->fileSize = intval($_FILES['upload_files']['size']);
			$response->fileType = $_FILES['upload_files']['type'];
			$response->fileUnzipped = '';
			$response->error = NULL;

			return $response;
		}

		return true;
	}
}