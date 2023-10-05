<?php

/**
 * GUI-Class srObjPictureGUI
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 */
class srObjPictureGUI
{
    public $parent;
    /**
     * @var \ilPhotoGalleryPlugin
     */
    public $pl;
    public const CMD_REDIRECT_TO_ALBUM_LIST_PICTURES = 'redirectToAlbumListPictures';
    public const CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES = 'redirectToAlbumManagePictures';
    public const CMD_SEND_FILE = 'sendFile';
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
     * @var \ActiveRecord|null
     */
    public $obj_picture;
    public ILIAS\DI\UIServices $ui;

    /**
     * @param $parent_gui
     */
    public function __construct($parent_gui)
    {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->access = $DIC->access();
        $this->ctrl = $DIC->ctrl();
        $this->parent = $parent_gui;
        $this->toolbar = $DIC->toolbar();
        $this->tabs_gui = $DIC->tabs();
        $this->obj_picture = srObjPicture::find($_GET['picture_id']);
        $this->pl = ilPhotoGalleryPlugin::getInstance();
        $this->ui = $DIC->ui();

        $this->ctrl->setParameterByClass(self::class, 'album_id', $_GET['album_id']);
        srObjAlbumGUI::setLocator($_GET['album_id']);
    }

    public function executeCommand(): bool
    {
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

    public function add(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        } else {
            $form = new srObjPictureFormGUI($this, new srObjPicture());
            $this->tpl->setContent($form->getHTML());
        }
    }

    /**
     * @description for AJAX Drag&Drop Fileupload
     */
    public function create(): void
    {
        $response = '';
        if (!$this->access->checkAccess('write', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent);
        }
        $form = new srObjPictureFormGUI($this, new srObjPicture());
        $form->setValuesByPost();
        $response = $form->saveObject();

        $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
    }

    public function edit(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        } else {
            $form = new srObjPictureFormGUI($this, $this->obj_picture);
            $form->fillForm();
            $this->tpl->setContent($form->getHTML());
        }
    }

    public function update(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        } else {
            $form = new srObjPictureFormGUI($this, $this->obj_picture);

            if ($form->saveObject()) {
                $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('success_edit'), true);

                $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'picture_id', null);
                $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $this->obj_picture->getAlbumId());
                $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
            } else {
                $form->setValuesByPost();
                $this->tpl->setContent($form->getHTML());
            }
        }
    }

    public function confirmDelete(): void
    {
        $arr_picture_ids = [];
        if (!$this->access->checkAccess('write', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES);
        } else {
            if ((!isset($_POST['picture_ids']) || !(is_countable($_POST['picture_ids']) ? count(
                $_POST['picture_ids']
            ) : 0)) && !$_GET['picture_id']) {
                $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_checkbox'), true);
                $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES);
            }
            if (isset($_POST['picture_ids']) && (is_countable($_POST['picture_ids']) ? count(
                $_POST['picture_ids']
            ) : 0)) {
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
            foreach ($arr_picture_ids as $picture_id) {
                /**
                 * @var $srObjPicture srObjPicture
                 */
                $srObjPicture = srObjPicture::find($picture_id);
                $file_icon = ilObject::_getIcon($srObjPicture->getId(), "small", "file");
                $c_gui->addItem('picture_ids[]', $picture_id, $srObjPicture->getTitle(), $file_icon);
            }
            $this->tpl->setContent($c_gui->getHTML());
        }
    }

    public function delete(): void
    {
        $album_id = null;
        if (!$this->access->checkAccess('write', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES);
        } else {
            if ((is_countable($_POST['picture_ids']) ? count($_POST['picture_ids']) : 0) > 0) {
                foreach ($_POST['picture_ids'] as $pic_id) {
                    /**
                     * @var $srObjPicture srObjPicture
                     */
                    $srObjPicture = srObjPicture::find($pic_id);
                    //saveAlbumID before Deletion
                    $album_id = $srObjPicture->getAlbumId();
                    //if the current picture serves as the album's preview image remove the preview before deletion
                    $srObjAlbum = srObjAlbum::find($album_id);
                    if ($srObjPicture->getId() == $srObjAlbum->getPreviewId()) {
                        $srObjAlbum->setPreviewId(0);
                        $srObjAlbum->update();
                    }

                    $srObjPicture->delete();
                }
                $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('msg_removed_pic'), true);
            } else {
                $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_checkbox'), true);
            }
            $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $album_id);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }
    }

    public function download(): void
    {
        $arr_picture_ids = [];
        if (!$this->access->checkAccess('read', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        } else {
            if (!empty($_REQUEST['picture_id'])) {
                $arr_picture_ids[] = $_REQUEST['picture_id'];
            } elseif (!empty($_REQUEST['picture_ids'])) {
                $arr_picture_ids = $_POST['picture_ids'];
            } else {
                $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_checkbox'), true);
                $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'picutre_id', null);
                $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $_GET['album_id']);
                $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
            }
            ilObjPhotoGalleryGUI::executeDownload($arr_picture_ids);
        }
    }

    protected function sendFile(): void
    {
        if (!$this->access->checkAccess('read', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        }
        /**
         * @var $srObjPicture srObjPicture
         */
        $srObjPicture = srObjPicture::find($_GET['picture_id']);
        $path_to_file = $srObjPicture->getSrc($_GET['picture_type']);
        ilFileDelivery::deliverFileInline($path_to_file, $srObjPicture->getTitle() . '.' . $srObjPicture->getSuffix());
    }
}
