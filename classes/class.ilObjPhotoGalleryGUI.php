<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Repository/classes/class.ilObjectPluginGUI.php');
require_once('class.ilPhotoGalleryPlugin.php');
require_once('./Services/Calendar/classes/class.ilDate.php');
require_once('./Services/AccessControl/classes/class.ilPermissionGUI.php');
require_once('./Services/InfoScreen/classes/class.ilInfoScreenGUI.php');
require_once('./Modules/File/classes/class.ilFileException.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Album/class.srObjAlbum.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Album/class.srObjAlbumGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Picture/class.srObjPictureGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Picture/class.srObjPicture.php');
require_once('class.ilObjPhotoGallery.php');
require_once('class.ilObjPhotoGalleryTableGUI.php');

/**
 * User Interface class for example repository object.
 *
 * @author            Alex Killing <alex.killing@gmx.de>
 * @author            Fabian Schmid <Fabian.schmid@ilub.unibe.ch>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 *
 * $Id$
 *
 * Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 *   screens) and ilInfoScreenGUI (handles the info screen).
 *
 * @ilCtrl_isCalledBy ilObjPhotoGalleryGUI: ilRepositoryGUI, ilObjPluginDispatchGUI, ilAdministrationGUI
 * @ilCtrl_Calls      ilObjPhotoGalleryGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 * @ilCtrl_Calls      ilObjPhotoGalleryGUI: srObjAlbumGUI, srObjPictureGUI, srObjExifGUI
 *
 */
class ilObjPhotoGalleryGUI extends ilObjectPluginGUI {

	/**
	 * @var ilObjPhotoGallery
	 */
	public $object;
	/**
	 * @var ilPhotoGalleryPlugin
	 */
	protected $pl;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var ilNavigationHistory
	 */
	protected $history;
	/**
	 * @var ilTabsGUI
	 */
	public $tabs_gui;
	/**
	 * @var ilTemplate
	 */
	public $tpl;
	/**
	 * @var ilAccessHandler
	 */
	public $access;


	protected function afterConstructor() {
		global $tpl, $ilCtrl, $ilAccess, $ilNavigationHistory, $ilTabs;

		$this->tpl = $tpl;
		$this->history = $ilNavigationHistory;
		$this->access = $ilAccess;
		$this->ctrl = $ilCtrl;
		$this->tabs_gui = $ilTabs;
		$this->pl = new ilPhotoGalleryPlugin();
		if ($_GET['rl'] == 'true') {
			$this->pl->updateLanguages();
		}
	}


	/**
	 * @return string
	 */
	final function getType() {
		return 'xpho';
	}


	public function executeCommand() {
		if ($this->access->checkAccess('read', '', $_GET['ref_id'])) {
			$this->history->addItem($_GET['ref_id'], $this->ctrl->getLinkTarget($this, $this->getStandardCmd()), $this->getType(), '');
		}
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);
		$this->tpl->getStandardTemplate();
		$this->setTitleAndDescription();
		$this->setLocator();
		$this->tpl->setTitleIcon($this->pl->getImagePath("icon_" . $this->getType() . "_b.png"),
			$this->pl->txt("icon") . " " . $this->pl->txt("obj_" . $this->getType()));

		switch ($next_class) {
			case 'ilpermissiongui':
				$this->setTabs();
				$this->tabs_gui->setTabActive('permissions');
				$perm_gui = new ilPermissionGUI($this);
				$this->ctrl->forwardCommand($perm_gui);
				break;
			case 'ilinfoscreengui':
				$this->setTabs();
				$this->tabs_gui->setTabActive('info');
				$info_gui = new ilInfoScreenGUI($this);
				$this->ctrl->forwardCommand($info_gui);
				break;
			case 'srobjalbumgui':
				$this->setTabs();
				$this->tabs_gui->setTabActive('content');
				$album_gui = new srObjAlbumGUI($this);
				$this->ctrl->forwardCommand($album_gui);
				break;
			case 'srobjpicturegui':
				$picture_gui = new srObjPictureGUI($this);
				$this->ctrl->forwardCommand($picture_gui);
				break;
			case 'srobjphotogallerygui':
			case '':
				switch ($cmd) {
					case 'create':
						$this->create();
						break;
					case 'save':
						$this->save();
						break;
					case 'edit':
						$this->setTabs();
						$this->edit();
						break;
					case 'update':
						;
						parent::update();
						break;
					case 'manageAlbums':
						$this->setTabs();
						$this->tabs_gui->setTabActive('content');
						$this->setSubTabsContent();
						$this->tabs_gui->setSubTabActive('manage_albums');
						$this->manageAlbums();
						break;
					case 'showContent':
					case 'listAlbums':
					case '':
						$this->setTabs();
						$this->tabs_gui->setTabActive('content');
						$this->setSubTabsContent();
						$this->tabs_gui->setSubTabActive('list_albums');
						$this->listAlbums();
						break;
				}
				break;
		}
		$this->tpl->show();
	}


	public function edit() {
		global $ilCtrl, $ilTabs;
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->pl->txt("edit"));
		// title
		$ti = new ilTextInputGUI($this->pl->txt("gallery_title"), "title");
		$ti->setMaxLength(128);
		$ti->setSize(40);
		$ti->setRequired(true);
		$form->addItem($ti);
		// description
		$ta = new ilTextAreaInputGUI($this->pl->txt("description"), "desc");
		$ta->setCols(40);
		$ta->setRows(2);
		$form->addItem($ta);
		$ta->setValue($this->object->getDescription());
		$ti->setValue($this->object->getTitle());
		$form->addCommandButton("update", $this->pl->txt("save"));
		$form->setFormAction($this->ctrl->getFormAction($this, "update"));
		$form->addCommandButton("showContent", $this->pl->txt('cancel'));
		$form->setFormAction($this->ctrl->getFormAction($this, "showContent"));
		$ilTabs->activateTab('settings');
		$this->tpl->setContent($form->getHTML());
	}


	public function saveObject() {
		if (! $this->access_handler->checkAccess("write", "", $this->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent, "");
		} else {
			$this->object->update();
		}

		return true;
	}


	/**
	 * @return string
	 */
	function getAfterCreationCmd() {
		return 'listAlbums';
	}


	/**
	 * @return string
	 */
	function getStandardCmd() {
		return 'listAlbums';
	}


	protected function setTabs() {
		$next_class = $this->ctrl->getNextClass($this);
		$this->tabs_gui->addTab("content", $this->pl->txt("content"), $this->ctrl->getLinkTarget($this, 'showContent'));
		$this->tabs_gui->addTab("info", $this->pl->txt("info"), $this->ctrl->getLinkTargetByClass('ilinfoscreengui', 'showSummary'));
		if ($this->access_handler->checkAccess("write", "", $this->object->getRefId())) {
			$this->tabs_gui->addTab("settings", $this->pl->txt("settings"), $this->ctrl->getLinkTarget($this, 'edit'));
		}
		if ($this->access->checkAccess('edit_permission', '', $this->object->getRefId())) {
			$this->tabs_gui->addTab("permissions", $this->pl->txt("permissions"), $this->ctrl->getLinkTargetByClass('ilpermissiongui', 'perm'));
		}

		return true;
	}


	protected function setSubTabsContent() {
		$this->tabs_gui->addSubTab('list_albums', $this->pl->txt('view'), $this->ctrl->getLinkTarget($this, 'listAlbums'));
		$this->tabs_gui->addSubTab('manage_albums', $this->pl->txt('manage'), $this->ctrl->getLinkTarget($this, 'manageAlbums'));
	}


	public function listAlbums() {
		$this->tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/default/clearing.css');
		$tpl = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/default/Album/tpl.clearing.html', true, true);
		$obj_id = ilObject::_lookupObjectId($this->object->getRefId());

		/**
		 * @var $srObjAlbum srObjAlbum
		 */
		if ($this->access->checkAccess('read', '', $this->object->getRefId())) {
			foreach ($this->object->getAlbumObjects() as $srObjAlbum) {
				$this->ctrl->setParameterByClass('srObjAlbumGUI', 'album_id', $srObjAlbum->getId());
				$tpl->setCurrentBlock('picture');
				$tpl->setVariable('TITLE', $srObjAlbum->getTitle());
				$tpl->setVariable('DATE', date('d.m.Y', strtotime($srObjAlbum->getCreateDate())));
				$tpl->setVariable('COUNT', $srObjAlbum->getPictureCount() . " " . $this->pl->txt('pics'));
				$tpl->setVariable('LINK', $this->ctrl->getLinkTargetByClass('srObjAlbumGUI'));

				if ($srObjAlbum->getPreviewId() > 0) {
					$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_id', $srObjAlbum->getPreviewId());
					$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_type', srObjPicture::TITLE_MOSAIC);
					$src_mosaic = $this->ctrl->getLinkTargetByClass("srObjPictureGUI", "sendFile");
				} else {
					//TODO Refactor
					$src_mosaic = './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/images/nopreview.jpg';
				}

				$tpl->setVariable('SRC_PREVIEW', $src_mosaic);
				$tpl->parseCurrentBlock();
			}
			if ($this->access->checkAccess('write', '', $this->object->getRefId())) {
				$tpl->setCurrentBlock('addnew');
				$tpl->setVariable('SRC_ADDNEW', './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/images/addnew.jpg');
				$tpl->setVariable('LINK_ADDNEW', $this->ctrl->getLinkTargetByClass('srObjAlbumGUI', 'add'));
				$tpl->parseCurrentBlock();
			}
		} else {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		}
		$this->tpl->setContent($tpl->get());
	}


	public function manageAlbums() {
		if (! $this->access->checkAccess('read', '', $this->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			$tableGui = new ilObjPhotoGalleryTableGUI($this, 'manageAlbums');
			$this->tpl->setContent($tableGui->getHTML());
		}
	}


	//download pictures/album
	static function executeDownload($arr_picture_ids) {
		global $ilCtrl, $ilAccess;
		$pl = new ilPhotoGalleryPlugin();
		//TODO bringen wir hier das GET weg?
		if (! $ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
			ilUtil::sendFailure($pl->txt("permission_denied"), true);
			$ilCtrl->redirectByClass('ilObjPhotoGalleryGUI', "");
		}
		if (! sizeof($arr_picture_ids)) {
			ilUtil::sendFailure($pl->txt("no_checkbox"), true);
			$ilCtrl->redirectByClass('ilObjPhotoGalleryGUI', "");
		}
		$zip = PATH_TO_ZIP;
		$tmpdir = ilUtil::ilTempnam();
		ilUtil::makeDir($tmpdir);
		$zipbasedir = $tmpdir . DIRECTORY_SEPARATOR . 'pictures';
		ilUtil::makeDir($zipbasedir);
		$tmpzipfile = $tmpdir . DIRECTORY_SEPARATOR . 'pictures.zip';
		foreach ($arr_picture_ids as $picture_id) {
			$picture = srObjPicture::find($picture_id);
			$title = $picture->getTitle();
			$oldPictureFilename = $picture->getPicturePath() . "/original." . $picture->getSuffix();
			$newPictureFilename =
				$zipbasedir . DIRECTORY_SEPARATOR . ilUtil::getASCIIFilename($title . '_' . $picture->getId() . "." . $picture->getSuffix());
			// copy to temporal directory
			if (! copy($oldPictureFilename, $newPictureFilename)) {
				throw new ilFileException("Could not copy " . $oldPictureFilename . " to " . $newPictureFilename);
			}
			touch($newPictureFilename, filectime($oldPictureFilename));
		}
		try {
			ilUtil::zip($zipbasedir, $tmpzipfile);
			rename($tmpzipfile, $zipfile = ilUtil::ilTempnam());
			ilUtil::delDir($tmpdir);
			ilUtil::deliverFile($zipfile, "pictures.zip", '', false, true);
		} catch (ilFileException $e) {
			ilUtil::sendInfo($e->getMessage(), true);
		}
	}
}

?>