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
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @author            Gabriel Comte <gc@studer-raimann.ch>
 *
 * $Id$
 *
 * @ilCtrl_isCalledBy ilObjPhotoGalleryGUI: ilRepositoryGUI, ilObjPluginDispatchGUI, ilAdministrationGUI
 * @ilCtrl_Calls      ilObjPhotoGalleryGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 * @ilCtrl_Calls      ilObjPhotoGalleryGUI: srObjAlbumGUI, srObjPictureGUI, srObjExifGUI
 *
 */
class ilObjPhotoGalleryGUI extends ilObjectPluginGUI {

	const XPHO = 'xpho';
	const CMD_MANAGE_ALBUMS = 'manageAlbums';
	const CMD_SHOW_CONTENT = 'showContent';
	const CMD_SHOW_SUMMARY = 'showSummary';
	const CMDEDIT = 'edit';
	const CMD_LIST_ALBUMS = 'listAlbums';
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
		$this->pl = ilPhotoGalleryPlugin::getInstance();

		// add a link pointing to this object in footer [The "Permanent Link" in the footer]
		if ($this->object !== null) {
			$this->tpl->setPermanentLink($this->pl->getId(), $this->object->getRefId());
		}
	}


	/**
	 * @return string
	 */
	final function getType() {
		return self::XPHO;
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

		$this->tpl->setTitleIcon($this->pl->getImagePath('icon_' . $this->getType() . '.svg'), $this->pl->txt('icon') . ' ' . $this->pl->txt('obj_'
				. $this->getType()));
		switch ($next_class) {
			case 'ilpermissiongui':
				$this->setTabs();
				$this->tabs_gui->setTabActive('permissions');
				$perm_gui = new ilPermissionGUI($this);
				$this->ctrl->forwardCommand($perm_gui);
				$this->tpl->show();
				break;
			case 'ilinfoscreengui':
				$this->setTabs();
				$this->tabs_gui->setTabActive('info');
				$info_gui = new ilInfoScreenGUI($this);
				$this->ctrl->forwardCommand($info_gui);
				$this->tpl->show();
				break;
			case 'srobjalbumgui':
				$this->setTabs();
				$this->tabs_gui->setTabActive('content');
				$album_gui = new srObjAlbumGUI($this);
				$this->ctrl->forwardCommand($album_gui);
				$this->tpl->show();
				break;
			case 'srobjpicturegui':
				$picture_gui = new srObjPictureGUI($this);
				$this->ctrl->forwardCommand($picture_gui);
				$this->tpl->show();
				break;
			case 'ilcommonactiondispatchergui';
				include_once("Services/Object/classes/class.ilCommonActionDispatcherGUI.php");
				$gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
				$this->ctrl->forwardCommand($gui);
				break;
			case 'srobjphotogallerygui':
			case '':
				switch ($cmd) {
					case 'create':
						$this->tpl->setTitle($this->pl->txt('obj_title_create_new'));
						$this->create();
						break;
					case 'save':
						$this->save();
						$this->tpl->show();
						break;
					case self::CMDEDIT:
					case 'editProperties':
						$this->setTabs();
						$this->edit();
						$this->tpl->show();
						break;
					case 'update':
						parent::update();
						$this->tpl->show();
						break;
					case self::CMD_MANAGE_ALBUMS:
						$this->setTabs();
						$this->tabs_gui->setTabActive('content');
						$this->setSubTabsContent();
						$this->tabs_gui->setSubTabActive('manage_albums');
						$this->manageAlbums();
						$this->tpl->show();
						break;
					case 'infoScreen':
						$this->setTabs();

						$this->ctrl->setCmd("showSummary");
						$this->ctrl->setCmdClass("ilinfoscreengui");
						$this->infoScreen();

						$this->tabs_gui->setTabActive('info');

						$this->tpl->show();
						break;
					case self::CMD_SHOW_CONTENT:
					case self::CMD_LIST_ALBUMS:
					case '':
						$this->setTabs();
						$this->tabs_gui->setTabActive('content');
						$this->setSubTabsContent();
						$this->tabs_gui->setSubTabActive('list_albums');
						$this->listAlbums();
						$this->tpl->show();
						break;
				}
				break;
		}
	}


	public function edit() {
		$this->tabs_gui->activateTab('settings');
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->pl->txt('edit'));
		// title
		$ti = new ilTextInputGUI($this->pl->txt('gallery_title'), 'title');
		$ti->setMaxLength(128);
		$ti->setSize(40);
		$ti->setRequired(true);
		$form->addItem($ti);
		// description
		$ta = new ilTextAreaInputGUI($this->pl->txt('description'), 'desc');
		$ta->setRows(2);
		$form->addItem($ta);
		$ta->setValue($this->object->getDescription());
		$ti->setValue($this->object->getTitle());
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->addCommandButton('update', $this->pl->txt('save'));
		$form->addCommandButton(self::CMD_SHOW_CONTENT, $this->pl->txt('cancel'));
		$this->tpl->setContent($form->getHTML());
	}


	public function saveObject() {
		if (!$this->access_handler->checkAccess('write', '', $this->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this->parent, '');
		} else {
			$this->object->update();
		}
		$this->ctrl->redirect($this->parent, '');

		return true;
	}


	/**
	 * @return string
	 */
	function getAfterCreationCmd() {
		return self::CMD_LIST_ALBUMS;
	}


	/**
	 * @return string
	 */
	function getStandardCmd() {
		return self::CMD_LIST_ALBUMS;
	}


	protected function setTabs() {
		$this->tabs_gui->addTab('content', $this->pl->txt('content'), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_CONTENT));
		$this->tabs_gui->addTab('info', $this->pl->txt('info'), $this->ctrl->getLinkTargetByClass('ilinfoscreengui', self::CMD_SHOW_SUMMARY));
		if ($this->access_handler->checkAccess('write', '', $this->object->getRefId())) {
			$this->tabs_gui->addTab('settings', $this->pl->txt('settings'), $this->ctrl->getLinkTarget($this, self::CMDEDIT));
		}
		if ($this->access->checkAccess('edit_permission', '', $this->object->getRefId())) {
			$this->tabs_gui->addTab('permissions', $this->pl->txt('permissions'), $this->ctrl->getLinkTargetByClass('ilpermissiongui', 'perm'));
		}
		return true;
	}


	protected function setSubTabsContent() {
		$this->tabs_gui->addSubTab('list_albums', $this->pl->txt('view'), $this->ctrl->getLinkTarget($this, self::CMD_LIST_ALBUMS));

		// show tab "manage" on level overview
		if (ilObjPhotoGalleryAccess::checkManageTabAccess($this->object->ref_id)) {
			$this->tabs_gui->addSubTab('manage_albums', $this->pl->txt('manage'), $this->ctrl->getLinkTarget($this, self::CMD_MANAGE_ALBUMS));
		}
	}


	public function listAlbums() {
		$this->tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/default/clearing.css');
		$tpl = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/default/Album/tpl.clearing.html', true, true);

		/**
		 * @var $srObjAlbum srObjAlbum
		 */
		if ($this->access->checkAccess('read', '', $this->object->getRefId())) {
			foreach ($this->object->getAlbumObjects() as $srObjAlbum) {
				$this->ctrl->setParameterByClass('srObjAlbumGUI', 'album_id', $srObjAlbum->getId());
				$tpl->setCurrentBlock('picture');
				$tpl->setVariable('TITLE', $srObjAlbum->getTitle());
				$tpl->setVariable('DATE', date('d.m.Y', strtotime($srObjAlbum->getCreateDate())));
				$tpl->setVariable('COUNT', $srObjAlbum->getPictureCount() . ' ' . $this->pl->txt('pics'));
				$tpl->setVariable('LINK', $this->ctrl->getLinkTargetByClass('srObjAlbumGUI'));

				if ($srObjAlbum->getPreviewId() > 0) {
					$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_id', $srObjAlbum->getPreviewId());
					$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_type', srObjPicture::TITLE_MOSAIC);
					$src_mosaic = $this->ctrl->getLinkTargetByClass('srObjPictureGUI', 'sendFile');
				} else {
					//TODO Refactor
					$src_mosaic = './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/images/nopreview.jpg';
				}

				$tpl->setVariable('SRC_PREVIEW', $src_mosaic);
				$tpl->parseCurrentBlock();
			}
			if ($this->access->checkAccess('write', '', $this->object->getRefId())) {
				$tpl->setCurrentBlock('add_new');
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
		if (!ilObjPhotoGalleryAccess::checkManageTabAccess($this->object->getRefId())) {
			ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
			$this->ctrl->redirect($this, '');
		} else {
			$tableGui = new ilObjPhotoGalleryTableGUI($this, self::CMD_MANAGE_ALBUMS . '');
			$this->tpl->setContent($tableGui->getHTML());
		}
	}


	/**
	 * @param $arr_picture_ids
	 *
	 * @throws ilFileException
	 */
	public static function executeDownload($arr_picture_ids) {
		global $ilCtrl, $ilAccess;
		$pl = ilPhotoGalleryPlugin::getInstance();
		//TODO bringen wir hier das GET weg?
		if (!$ilAccess->checkAccess('read', '', $_GET['ref_id'])) {
			ilUtil::sendFailure($pl->txt('permission_denied'), true);
			$ilCtrl->redirectByClass('ilObjPhotoGalleryGUI', '');
		}
		if (!sizeof($arr_picture_ids)) {
			ilUtil::sendFailure($pl->txt('no_checkbox'), true);
			$ilCtrl->redirectByClass('ilObjPhotoGalleryGUI', '');
		} else {
			if (sizeof($arr_picture_ids) == 1) {
				// only one picture ==> do not make a .zip !
				$picture_id = $arr_picture_ids[0];

				$picture = srObjPicture::find($picture_id);
				$title = $picture->getTitle();
				$oldPictureFilename = $picture->getPicturePath() . '/original.' . $picture->getSuffix();

				try {
					ilUtil::deliverFile($oldPictureFilename, $title, '', false, true);
				} catch (ilFileException $e) {
					ilUtil::sendInfo($e->getMessage(), true);
				}
			} else {
				$zip = PATH_TO_ZIP;
				$tmpdir = ilUtil::ilTempnam();
				ilUtil::makeDir($tmpdir);
				$zipbasedir = $tmpdir . DIRECTORY_SEPARATOR . 'pictures';
				ilUtil::makeDir($zipbasedir);
				$tmpzipfile = $tmpdir . DIRECTORY_SEPARATOR . 'pictures.zip';
				foreach ($arr_picture_ids as $picture_id) {
					$picture = srObjPicture::find($picture_id);
					$title = $picture->getTitle();
					$oldPictureFilename = $picture->getPicturePath() . '/original.' . $picture->getSuffix();
					$newPictureFilename = $zipbasedir . DIRECTORY_SEPARATOR . ilUtil::getASCIIFilename($title . '_' . $picture->getId() . '.'
							. $picture->getSuffix());
					// copy to temporal directory
					if (!copy($oldPictureFilename, $newPictureFilename)) {
						throw new ilFileException('Could not copy ' . $oldPictureFilename . ' to ' . $newPictureFilename);
					}
					touch($newPictureFilename, filectime($oldPictureFilename));
				}
				try {
					ilUtil::zip($zipbasedir, $tmpzipfile);
					rename($tmpzipfile, $zipfile = ilUtil::ilTempnam());
					ilUtil::delDir($tmpdir);
					ilUtil::deliverFile($zipfile, 'pictures.zip', '', false, true);
				} catch (ilFileException $e) {
					ilUtil::sendInfo($e->getMessage(), true);
				}
			}
		}
	}


	/**
	 * @param ilObject $gallery
	 */
	function afterSave(ilObject $gallery) {
		global $ilAppEventHandler;
		/** @var $ilAppEventHandler ilAppEventHandler */
		$ilAppEventHandler->raise('Services/Object', 'afterSave', array(
			'object' => $gallery,
			'obj_id' => $gallery->getId(),
			'obj_type' => $gallery->getType()
		));

		parent::afterSave($gallery);
	}
}