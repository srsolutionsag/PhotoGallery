<?php
require_once('class.srObjPicture.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.srObjPictureFormGUI.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once('./Services/FileSystem/classes/class.ilFileSystemGUI.php');
require_once('./Modules/WorkspaceFolder/classes/class.ilObjWorkspaceFolderGUI.php');
require_once('./Services/JSON/classes/class.ilJsonUtil.php');
require_once('./Services/Utilities/classes/class.ilMimeTypeUtil.php');

/**
 * GUI-Class srObjPictureGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 *
 * @version           $Id:
 *
 */
class srObjPictureGUI {
	const CMD_REDIRECT_TO_ALBUM_LIST_PICTURES = 'redirectToAlbumListPictures';
	const CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES = 'redirectToAlbumManagePictures';
	const CMD_SEND_FILE = 'sendFile';
	/**
	 * @var ilAccessHandler
	 */
	protected $access;
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
	 * @var srObjPicture
	 */
	public $obj_picture;


	/**
	 * @param $parent_gui
	 */
	public function __construct($parent_gui) {
		global $DIC;
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->access = $DIC->access();
		$this->ctrl = $DIC->ctrl();
		$this->parent = $parent_gui;
		$this->toolbar = $DIC->toolbar();
		$this->tabs_gui = $DIC->tabs();
		$this->obj_picture = srObjPicture::find($_GET['picture_id']);
		$this->pl = ilPhotoGalleryPlugin::getInstance();

		$this->ctrl->setParameterByClass(self::class, 'album_id', $_GET['album_id']);
		srObjAlbumGUI::setLocator($_GET['album_id']);
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();
		//$this->ctrl->saveParameter($this, 'user_id');
		//$this->ctrl->saveParameter($this, 'picture_id');

		switch ($cmd) {
			case self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES:
				$this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'picutre_id', null);
				$this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $_GET['album_id']);
				$this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_LIST_PICTURES);
				break;
			case self::CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES:
				$this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'picutre_id', null);
				$this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $_GET['album_id']);
				$this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
				break;
			case self::CMD_SEND_FILE:
			case atTableGUI::CMD_ADD:
			case atTableGUI::CMD_CREATE:
			case atTableGUI::CMD_EDIT:
			case atTableGUI::CMD_UPDATE:
			case atTableGUI::CMD_DELETE:
			case atTableGUI::CMD_CONFIRM_DELETE:
			case atTableGUI::CMD_DOWNLOAD:
				$this->$cmd();
				break;
		}

		return true;
	}


	public function add() {
		if (!$this->access->checkAccess('write', '', $this->parent->ref_id)) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			$form = new srObjPictureFormGUI($this, new srObjPicture());
			$this->tpl->setContent($form->getHTML());
		}
	}


	/**
	 * @description for AJAX Drag&Drop Fileupload
	 */
	public function create() {
		$response = '';
		if (!$this->access->checkAccess('write', '', $this->parent->ref_id)) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this->parent);
		}
		$form = new srObjPictureFormGUI($this, new srObjPicture());
		$form->setValuesByPost();
		$response = $form->saveObject();
//			if ($response === false) {
//				ilUtil::sendFailure($this->pl->txt('wrong_filetype'), true);
//			}
		if ($response !== false) {
			header('Vary: Accept');
			header('Content-type: text/plain');
			echo json_encode($response);
			exit;
		}
		$this->tpl->setContent($form->getHTML());
	}


	public function edit() {
		if (!$this->access->checkAccess('write', '', $this->parent->ref_id)) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			$form = new srObjPictureFormGUI($this, $this->obj_picture);
			$form->fillForm();
			$this->tpl->setContent($form->getHTML());
		}
	}


	public function update() {
		if (!$this->access->checkAccess('write', '', $this->parent->ref_id)) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			$form = new srObjPictureFormGUI($this, $this->obj_picture);

			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('success_edit'), true);

				$this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'picture_id', null);
				$this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $this->obj_picture->getAlbumId());
				$this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
			} else {
				$form->setValuesByPost();
				$this->tpl->setContent($form->getHTML());
			}
		}
	}


	public function confirmDelete() {
		if (!$this->access->checkAccess('write', '', $this->parent->ref_id)) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			if ((!isset($_POST['picture_ids']) || !sizeof($_POST['picture_ids'])) AND !$_GET['picture_id']) {
				ilUtil::sendFailure($this->pl->txt('no_checkbox'), true);
				$this->ctrl->redirect($this, '');
			}
			if (isset($_POST['picture_ids']) && sizeof($_POST['picture_ids'])) {
				$arr_picture_ids = $_POST['picture_ids'];
			} else {
				$arr_picture_ids[] = $_GET['picture_id'];
			}
			$c_gui = new ilConfirmationGUI();
			$c_gui->setFormAction($this->ctrl->getFormAction($this, atTableGUI::CMD_DELETE));
			$c_gui->setHeaderText($this->pl->txt('delete_pic'));
			$c_gui->setCancel($this->pl->txt('cancel'), srObjPictureGUI::CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES);
			$c_gui->setConfirm($this->pl->txt('delete'), atTableGUI::CMD_DELETE);
			// add items to delete
			include_once('./Services/News/classes/class.ilNewsItem.php');
			foreach ($arr_picture_ids as $picture_id) {
				/**
				 * @var $srObjPicture srObjPicture
				 */
				$srObjPicture = srObjPicture::find($picture_id);
				$url = $srObjPicture->getPreviewWebSrc();
				$c_gui->addItem('picture_ids[]', $picture_id, $srObjPicture->getTitle(), $url);
			}
			$this->tpl->setContent($c_gui->getHTML());
		}
	}


	public function delete() {
		if (!$this->access->checkAccess('write', '', $this->parent->ref_id)) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			if (count($_POST['picture_ids']) > 0) {
				foreach ($_POST['picture_ids'] as $pic_id) {
					/**
					 * @var $srObjPicture srObjPicture
					 */
					$srObjPicture = srObjPicture::find($pic_id);
					//saveAlbumID before Deletion
					$album_id = $srObjPicture->getAlbumId();

					$srObjPicture->delete();
				}
				ilUtil::sendSuccess($this->pl->txt('msg_removed_pic'), true);
			} else {
				ilUtil::sendFailure($this->pl->txt('no_checkbox'), true);
			}
			$this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $album_id);
			$this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
		}
	}


	public function download() {
		if (!$this->access->checkAccess('read', '', $this->parent->ref_id)) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			if (!sizeof($_POST['picture_ids']) AND !$_REQUEST['picture_id']) {
				ilUtil::sendFailure($this->pl->txt('no_checkbox'), true);
				$this->ctrl->redirect($this, '');
			}
			if (sizeof($_POST['picture_ids'])) {
				$arr_picture_ids = $_POST['picture_ids'];
			} else {
				$arr_picture_ids[] = $_REQUEST['picture_id'];
			}
			ilObjPhotoGalleryGUI::executeDownload($arr_picture_ids);
		}
	}


	protected function sendFile() {
		if (!$this->access->checkAccess('read', '', $this->parent->ref_id)) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		}
		/**
		 * @var $srObjPicture srObjPicture
		 */
		$srObjPicture = srObjPicture::find($_GET['picture_id']);
		ilUtil::deliverFile($srObjPicture->getSrc($_GET['picture_type']), $srObjPicture->getTitle() . '.' . $srObjPicture->getSuffix());
	}
}

?>