<?php
require_once('class.srObjAlbum.php');
require_once('class.srObjAlbumTableGUI.php');
require_once('class.srObjAlbumFormGUI.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once('./Services/FileSystem/classes/class.ilFileSystemGUI.php');

/**
 * GUI-Class srObjAlbumGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version           $Id:
 *
 */
class srObjAlbumGUI {

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
	//protected $toolbar;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilObjPhotoGallery
	 */
	public $obj_photo_gallery;
	/**
	 * @var srObjAlbum
	 */
	public $obj_album;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;
	/**
	 * @var ilObjPhotoGalleryGUI
	 */
	protected $parent_gui;
	/**
	 * @var ilLocatorGUI
	 */
	public $locator;


	/**
	 * @param $parent_gui
	 */
	public function __construct($parent_gui) {
		global $tpl, $ilCtrl, $ilToolbar, $ilAccess, $ilTabs, $ilLocator;
		$this->tpl = $tpl;
		$this->access = $ilAccess;
		$this->ctrl = $ilCtrl;
		$this->parent_gui = $parent_gui;
		$this->locator = $ilLocator;
		//$this->toolbar = $ilToolbar;
		$this->tabs_gui = $ilTabs;
		$this->tabs_gui->clearTargets();

		$this->obj_album = srObjAlbum::find($_GET['album_id']);
		$this->pl = new ilPhotoGalleryPlugin();

		$this->ctrl->setParameterByClass('srObjPictureGUI', 'album_id', $_GET['album_id']);
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		//$this->setLocator();
		$cmd = $this->ctrl->getCmd();

		switch ($cmd) {
			case 'redirectToGalleryListAlbums':
				$this->ctrl->setParameterByClass('ilObjPhotoGalleryGUI', 'picutre_id', NULL);
				$this->ctrl->setParameterByClass('ilObjPhotoGalleryGUI', 'album_id', NULL);
				$this->ctrl->redirectByClass('ilObjPhotoGalleryGUI', 'listAlbums');
				break;
			case 'redirectToGalleryManageAlbums':
				$this->ctrl->setParameterByClass('srObjAlbumGUI', 'picutre_id', NULL);
				$this->ctrl->setParameterByClass('srObjAlbumGUI', 'album_id', NULL);
				$this->ctrl->redirectByClass('ilObjPhotoGalleryGUI', 'manageAlbums');
				break;
			case 'add':
			case 'create':
			case 'edit':
			case 'update':
			case 'delete':
			case 'confirmDelete':
			case 'download':
				$this->$cmd();
				break;
			case 'downloadAlbum':
				$this->download();
				break;
			case '':
			case 'listPictures':
				self::setLocator($this->obj_album->getId());
				$this->setTabs();
				$this->setSubTabs();
				$this->tabs_gui->setSubTabActive('list_pictures');
				$this->listPictures();
				break;
			case 'managePictures':
				self::setLocator($this->obj_album->getId());
				$this->setTabs();
				$this->setSubTabs();
				$this->tabs_gui->setSubTabActive('manage_pictures');
				$this->$cmd();
				break;
		}

		return true;
	}


	protected function setSubTabs() {
		$this->ctrl->setParameterByClass('srObjAlbumGUI', 'album_id', $this->obj_album->getId());
		$this->tabs_gui->addSubTab('list_pictures', $this->pl->txt('view'), $this->ctrl->getLinkTarget($this, 'listPictures'));

		// show tab "manage" on level album
		if (ilObjPhotoGalleryAccess::checkManageTabAccess($this->parent_gui->object->ref_id)) {
			$this->tabs_gui->addSubTab('manage_pictures', $this->pl->txt('manage'), $this->ctrl->getLinkTarget($this, 'managePictures'));
		}
	}


	protected function setTabs() {
		$this->tabs_gui->setBackTarget($this->pl->txt('back_to_gallery'), $this->ctrl->getLinkTarget($this->parent_gui));
	}


	/**
	 * @param int $album_id
	 */
	public static function setLocator($album_id) {
		global $ilCtrl, $ilLocator, $tpl;
		/**
		 * @var $srObjAlbum srObjAlbum
		 */
		$srObjAlbum = srObjAlbum::find($album_id);
		$ilCtrl->setParameterByClass("srObjAlbumGUI", 'album_id', $album_id);
		$ilLocator->addItem($srObjAlbum->getTitle(), $ilCtrl->getLinkTargetByClass("srObjAlbumGUI", "listPictures"));
		$tpl->setLocator();
	}


	public function listPictures() {
		$this->tpl->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/libs/foundation-5.0.2/js/modernizr.js');
		$this->tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/default/clearing.css');
		$tpl = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/default/Picture/tpl.clearing.html', false, true);
		/**
		 * @var $srObjPicture srObjPicture
		 */
		$tpl->setVariable('ALBUM_TITLE', $this->obj_album->getTitle());
		if ($this->access->checkAccess('read', '', $this->parent_gui->object->getRefId())) {
			foreach ($this->obj_album->getPictureObjects() as $srObjPicture) {
				$tpl->setCurrentBlock('picture');
				$tpl->setVariable('TITLE', $srObjPicture->getTitle());

				$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_id', $srObjPicture->getId());
				$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_type', srObjPicture::TITLE_MOSAIC);
				$src_preview = $this->ctrl->getLinkTargetByClass("srObjPictureGUI", "sendFile");
				$tpl->setVariable('SRC_PREVIEW', $src_preview);

				$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_id', $srObjPicture->getId());
				$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_type', srObjPicture::TITLE_PRESENTATION);
				$src_prensentation = $this->ctrl->getLinkTargetByClass("srObjPictureGUI", "sendFile");
				$tpl->setVariable('SRC_PRESENTATION', $src_prensentation);

				$tpl->parseCurrentBlock();
			}
			if ($this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
				$tpl->setCurrentBlock('add_new');
				$tpl->setVariable('SRC_ADDNEW', './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/images/addnew.jpg');
				$tpl->setVariable('LINK', $this->ctrl->getLinkTargetByClass('srObjPictureGUI', 'add'));
				$tpl->parseCurrentBlock();
			}
		} else {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		}
		$this->tpl->setContent($tpl->get());
	}


	public function managePictures() {
		if (!ilObjPhotoGalleryAccess::checkManageTabAccess($this->parent_gui->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			$tableGui = new srObjAlbumTableGUI($this, 'managePictures');
			$this->tpl->setContent($tableGui->getHTML());
		}
	}


	public function add() {
		if (! $this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this->parent_gui, '');
		} else {
			$form = new srObjAlbumFormGUI($this, new srObjAlbum());
			$this->tpl->setContent($form->getHTML());
		}
	}


	public function create() {
		if (! $this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this->parent_gui, '');
		} else {
			$form = new srObjAlbumFormGUI($this, new srObjAlbum());
			$form->setValuesByPost();
			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('success'), true);
				$this->ctrl->redirect($this->parent_gui, 'listAlbums');
			} else {
				$this->tpl->setContent($form->getHTML());
			}
		}
	}


	public function edit() {
		if (! $this->access->checkAccess('write', '', $_GET['ref_id'])) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this->parent_gui, '');
		} else {
			$form = new srObjAlbumFormGUI($this, srObjAlbum::find($_GET['album_id']));
			$form->fillForm();
			$this->tpl->setContent($form->getHTML());
		}
	}


	public function update() {
		if (! $this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this->parent_gui, '');
		} else {
			$form = new srObjAlbumFormGUI($this, srObjAlbum::find($_GET['album_id']));
			$form->setValuesByPost();
			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('success_edit'), true);
				$this->ctrl->redirect($this->parent_gui, 'manageAlbums');
			} else {
				$this->tpl->setContent($form->getHTML());
			}
		}
	}


	public function confirmDelete() {
		if (! $this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this->parent_gui, '');
		} else {
			if (! sizeof($_POST['album_ids']) AND ! $_GET['album_id']) {
				ilUtil::sendFailure($this->pl->txt('no_checkbox'), true);
				$this->ctrl->redirect($this, '');
			}
			if (sizeof($_POST['album_ids'])) {
				$arr_album_ids = $_POST['album_ids'];
			} else {
				$arr_album_ids[] = $_GET['album_id'];
			}
			$c_gui = new ilConfirmationGUI();
			// set confirm/cancel commands
			$c_gui->setFormAction($this->ctrl->getFormAction($this, 'delete'));
			$c_gui->setHeaderText($this->pl->txt('delete_album'));
			$c_gui->setCancel($this->pl->txt('cancel'), 'redirectToGalleryManageAlbums');
			$c_gui->setConfirm($this->pl->txt('delete'), 'delete');
			// add items to delete
			//			include_once('./Services/News/classes/class.ilNewsItem.php');
			foreach ($arr_album_ids as $album_id) {
				/**
				 * @var $album srObjAlbum
				 */
				$album = srObjAlbum::find($album_id);
				$url = $album->getPreviewWebSrc();
				$c_gui->addItem('album_ids[]', $album_id, $album->getTitle(), $url);
			}
			$this->tpl->setContent($c_gui->getHTML());
		}
	}


	public function delete() {
		if (! $this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this->parent_gui, '');
		} else {
			if (count($_POST['album_ids']) > 0) {
				// delete all selected news items
				foreach ($_POST['album_ids'] as $alb_id) {
					$album = srObjAlbum::find($alb_id);
					$album->delete();
				}
				ilUtil::sendSuccess($this->pl->txt('msg_removed_album'), true);
			} else {
				ilUtil::sendFailure($this->pl->txt('no_checkbox'), true);
			}
			$this->ctrl->redirect($this->parent_gui, 'manageAlbums');
		}
	}


	public function download() {
		$arr_album_id = array();
		if (! $this->access->checkAccess('read', '', $this->parent_gui->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			//TODO album_ids ist der falsche Begriff
			if (! sizeof($_POST['album_ids']) AND ! $_GET['album_id']) {
				ilUtil::sendFailure($this->pl->txt('no_checkbox'), true);
				$this->ctrl->redirect($this, '');
			}
			if (sizeof($_POST['album_ids'])) {
				$arr_album_id = $_POST['album_ids'];
			} else {
				$arr_album_id[] = $_GET['album_id'];
			}
		}
		// take album id
		foreach ($arr_album_id as $album_id) {
			/**
			 * @var $album srObjAlbum
			 */
			$album = srObjAlbum::find($album_id);
			foreach ($album->getPictureObjects() as $pic) {
				$arr_picture_ids[] = $pic->getId();
			}
		}
		// download array
		ilObjPhotoGalleryGUI::executeDownload($arr_picture_ids);
	}
}

?>