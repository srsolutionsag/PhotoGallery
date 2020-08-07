<?php

/**
 * GUI-Class srObjAlbumGUI
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 */
class srObjAlbumGUI
{

    const CMD_LIST_PICTURES = 'listPictures';
    const CMD_MANAGE_PICTURES = 'managePictures';
    const CMD_REDIRECT_TO_GALLERY_LIST_ALBUMS = 'redirectToGalleryListAlbums';
    const CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS = 'redirectToGalleryManageAlbums';
    const TAB_LIST_PICTURES = 'list_pictures';
    const TAB_MANAGE_PICTURES = 'manage_pictures';
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
    public function __construct($parent_gui)
    {
        global $DIC;
        $this->tpl        = $DIC->ui()->mainTemplate();
        $this->access     = $DIC->access();
        $this->ctrl       = $DIC->ctrl();
        $this->parent_gui = $parent_gui;
        $this->ilLocator  = $DIC["ilLocator"];
        //$this->toolbar = $DIC->toolbar();
        $this->tabs_gui = $DIC->tabs();
        $this->tabs_gui->clearTargets();

        $this->obj_album = srObjAlbum::find($_GET['album_id']);
        $this->pl        = ilPhotoGalleryPlugin::getInstance();

        $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'album_id', $_GET['album_id']);
    }

    /**
     * @return bool
     */
    public function executeCommand()
    {
        //$this->setLocator();
        $cmd = $this->ctrl->getCmd();

        switch ($cmd) {
            case self::CMD_REDIRECT_TO_GALLERY_LIST_ALBUMS:
                $this->ctrl->setParameterByClass(ilObjPhotoGalleryGUI::class, 'picutre_id', null);
                $this->ctrl->setParameterByClass(ilObjPhotoGalleryGUI::class, 'album_id', null);
                $this->ctrl->redirectByClass(ilObjPhotoGalleryGUI::class, ilObjPhotoGalleryGUI::CMD_LIST_ALBUMS);
                break;
            case self::CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS:
                $this->ctrl->setParameterByClass(self::class, 'picutre_id', null);
                $this->ctrl->setParameterByClass(self::class, 'album_id', null);
                $this->ctrl->redirectByClass(ilObjPhotoGalleryGUI::class, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
                break;
            case atTableGUI::CMD_ADD:
            case atTableGUI::CMD_CREATE:
            case atTableGUI::CMD_EDIT:
            case atTableGUI::CMD_UPDATE:
            case atTableGUI::CMD_DELETE:
            case atTableGUI::CMD_CONFIRM_DELETE:
            case atTableGUI::CMD_DOWNLOAD:
                $this->$cmd();
                break;
            case atTableGUI::CMD_DOWNLOAD_ALBUM:
                $this->download();
                break;
            case '':
            case self::CMD_LIST_PICTURES:
                self::setLocator($this->obj_album->getId());
                $this->setTabs();
                $this->setSubTabs();
                $this->tabs_gui->activateSubTab(self::TAB_LIST_PICTURES);
                $this->listPictures();
                break;
            case self::CMD_MANAGE_PICTURES:
                self::setLocator($this->obj_album->getId());
                $this->setTabs();
                $this->setSubTabs();
                $this->tabs_gui->activateSubTab(self::TAB_MANAGE_PICTURES);
                $this->$cmd();
                break;
        }

        return true;
    }

    protected function setSubTabs()
    {
        $this->ctrl->setParameterByClass(self::class, 'album_id', $this->obj_album->getId());
        $this->tabs_gui->addSubTab(self::TAB_LIST_PICTURES, $this->pl->txt('view'), $this->ctrl->getLinkTarget($this, self::CMD_LIST_PICTURES));

        // show tab "manage" on level album
        if (ilObjPhotoGalleryAccess::checkManageTabAccess($this->parent_gui->object->ref_id)) {
            $this->tabs_gui->addSubTab(self::TAB_MANAGE_PICTURES, $this->pl->txt('manage'), $this->ctrl->getLinkTarget($this, self::CMD_MANAGE_PICTURES));
        }
    }

    protected function setTabs()
    {
        $this->tabs_gui->setBackTarget($this->pl->txt('back_to_gallery'), $this->ctrl->getLinkTarget($this->parent_gui));
    }

    /**
     * @param int $album_id
     */
    public static function setLocator($album_id)
    {
        global $DIC;
        $ilCtrl = $DIC->ctrl();
        /**
         * @var $srObjAlbum srObjAlbum
         */
        $srObjAlbum = srObjAlbum::find($album_id);
        $ilCtrl->setParameterByClass(self::class, 'album_id', $album_id);
        $DIC["ilLocator"]->addItem($srObjAlbum->getTitle(), $ilCtrl->getLinkTargetByClass(self::class, self::CMD_LIST_PICTURES));
        $DIC->ui()->mainTemplate()->setLocator();
    }

    public function listPictures()
    {
        $this->tpl->addJavaScript($this->pl->getDirectory() . '/templates/libs/foundation-5.0.2/js/modernizr.js');
        $this->tpl->addCss($this->pl->getDirectory() . '/templates/default/clearing.css');
        $tpl = $this->pl->getTemplate('default/Picture/tpl.clearing.html', false);
        /**
         * @var $srObjPicture srObjPicture
         */
        $tpl->setVariable('ALBUM_TITLE', $this->obj_album->getTitle());
        if ($this->access->checkAccess('read', '', $this->parent_gui->object->getRefId())) {
            foreach ($this->obj_album->getPictureObjects() as $srObjPicture) {
                $tpl->setCurrentBlock('picture');
                $tpl->setVariable('TITLE', $srObjPicture->getTitle());

                $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $srObjPicture->getId());
                $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_type', srObjPicture::TITLE_MOSAIC);
                $src_preview = $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, srObjPictureGUI::CMD_SEND_FILE);
                $tpl->setVariable('SRC_PREVIEW', $src_preview);

                $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $srObjPicture->getId());
                $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_type', srObjPicture::TITLE_PRESENTATION);
                $src_prensentation = $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, srObjPictureGUI::CMD_SEND_FILE);
                $tpl->setVariable('SRC_PRESENTATION', $src_prensentation);

                $tpl->parseCurrentBlock();
            }
            if ($this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
                $tpl->setCurrentBlock('add_new');
                $tpl->setVariable('SRC_ADDNEW', $this->pl->getDirectory() . '/templates/images/addnew.jpg');
                $tpl->setVariable('LINK', $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, atTableGUI::CMD_ADD));
                $tpl->parseCurrentBlock();
            }
        } else {
            ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        }
        $this->tpl->setContent($tpl->get());
    }

    public function managePictures()
    {
        if (!ilObjPhotoGalleryAccess::checkManageTabAccess($this->parent_gui->object->getRefId())) {
            ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        } else {
            $tableGui = new srObjAlbumTableGUI($this, srObjAlbumGUI::CMD_MANAGE_PICTURES);
            $this->tpl->setContent($tableGui->getHTML());
        }
    }

    public function add()
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
            ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $form = new srObjAlbumFormGUI($this, new srObjAlbum());
            $this->tpl->setContent($form->getHTML());
        }
    }

    public function create()
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
            ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $form = new srObjAlbumFormGUI($this, new srObjAlbum());
            $form->setValuesByPost();
            if ($form->saveObject()) {
                ilUtil::sendSuccess($this->pl->txt('success'), true);
                $this->ctrl->redirect($this->parent_gui, ilObjPhotoGalleryGUI::CMD_LIST_ALBUMS);
            } else {
                $this->tpl->setContent($form->getHTML());
            }
        }
    }

    public function edit()
    {
        if (!$this->access->checkAccess('write', '', $_GET['ref_id'])) {
            ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $form = new srObjAlbumFormGUI($this, srObjAlbum::find($_GET['album_id']));
            $form->fillForm();
            $this->tpl->setContent($form->getHTML());
        }
    }

    public function update()
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
            ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $form = new srObjAlbumFormGUI($this, srObjAlbum::find($_GET['album_id']));
            $form->setValuesByPost();
            if ($form->saveObject()) {
                ilUtil::sendSuccess($this->pl->txt('success_edit'), true);
                $this->ctrl->redirect($this->parent_gui, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
            } else {
                $this->tpl->setContent($form->getHTML());
            }
        }
    }

    public function confirmDelete()
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
            ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            if (!sizeof($_POST['album_ids']) and !$_GET['album_id']) {
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
            $c_gui->setFormAction($this->ctrl->getFormAction($this, atTableGUI::CMD_DELETE));
            $c_gui->setHeaderText($this->pl->txt('delete_album'));
            $c_gui->setCancel($this->pl->txt('cancel'), self::CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS);
            $c_gui->setConfirm($this->pl->txt('delete'), atTableGUI::CMD_DELETE);
            // add items to delete
            //			include_once('./Services/News/classes/class.ilNewsItem.php');
            foreach ($arr_album_ids as $album_id) {
                /**
                 * @var $album srObjAlbum
                 */
                $album = srObjAlbum::find($album_id);
                $url   = $album->getPreviewWebSrc();
                $c_gui->addItem('album_ids[]', $album_id, $album->getTitle(), $url);
            }
            $this->tpl->setContent($c_gui->getHTML());
        }
    }

    public function delete()
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->object->getRefId())) {
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
            $this->ctrl->redirect($this->parent_gui, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
        }
    }

    public function download()
    {
        $arr_album_id = array();
        if (!$this->access->checkAccess('read', '', $this->parent_gui->object->getRefId())) {
            ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        } else {
            //TODO album_ids ist der falsche Begriff
            if (!sizeof($_POST['album_ids']) and !$_GET['album_id']) {
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


