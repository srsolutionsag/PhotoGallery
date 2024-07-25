<?php

use ILIAS\HTTP\Services;
use ILIAS\Refinery\Factory AS Refinery;

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

    protected ilAccessHandler $access;
    protected ilTabsGUI $tabs_gui;
    protected ilPropertyFormGUI $form;
    protected ilToolbarGUI $toolbar;
    protected ilCtrl $ctrl;
    private Services $http;
    private ilLanguage $lng;
    private Refinery $refinery;
    protected ilGlobalTemplateInterface $tpl;
    public \ActiveRecord|null $obj_picture;
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
        $this->http = $DIC->http();
        $this->lng = $DIC->language();
        $this->parent = $parent_gui;
        $this->refinery = $DIC->refinery();
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
        $picture_ids = $this->retrievePictureIDs();
        $picture_id = $picture_ids[0];
        $this->ctrl->setParameterByClass(srObjPictureFormGUI::class, 'picture_id', $picture_id);
        $this->ctrl->setParameter($this, 'picture_id', $picture_id);
        /**
         * @var $picture srObjPicture
         */
        $picture = srObjPicture::find($picture_id);
        $form_gui = new srObjPictureFormGUI($this, $picture);
        $form_gui->fillForm();
        $this->tpl->setContent($form_gui->getHTML());
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
        $picture_ids = $this->retrievePictureIDs();
        $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_ids', implode(',', $picture_ids));
        $delete_action = $this->ctrl->getLinkTarget($this, 'delete');
        $this->ctrl->clearParameterByClass(srObjPictureGUI::class, 'picture_ids');
        $items = [];
        foreach ($picture_ids as $picture_id) {
            /**
             * @var $picture srObjPicture
             */
            $picture = srObjPicture::find($picture_id);
            if ($picture === null) {
                continue;
            }
            $picture_title = $picture->getTitle();
            $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $picture_id);
            $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_type', srObjPicture::TITLE_PREVIEW);
            $src_preview = $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, srObjPictureGUI::CMD_SEND_FILE);
            $image = $this->ui->factory()->image()->standard($src_preview, $picture_title);
            $items[] = $this->ui->factory()->modal()->interruptiveItem()->standard(
                $picture_id,
                $picture_title,
                $image
            );
        }
        $interruptive_modal = $this->ui->factory()->modal()->interruptive(
            $this->lng->txt('delete'),
            $this->pl->txt('delete_picture'),
            $delete_action
        )->withAffectedItems($items);
        echo($this->ui->renderer()->renderAsync([$interruptive_modal]));
        exit();
    }

    public function delete(): void
    {
        $picture_ids = array_map('intval', $this->http->request()->getParsedBody()['interruptive_items']);
        if ((is_countable($picture_ids) ? count($picture_ids) : 0) > 0) {
            // delete all selected items
            foreach ($picture_ids as $picture_id) {
                /**
                 * @var $picture srObjPicture
                 */
                $picture = srObjPicture::find($picture_id);
                if($picture === null) {
                    continue;
                }
                //if the current picture serves as the album's preview image remove the preview before deletion
                $album_id = $picture->getAlbumId();
                $album = srObjAlbum::find($album_id);
                if ($album !== null && ((int) $picture->getId() === $album->getPreviewId())) {
                    $album->setPreviewId(0);
                    $album->update();
                }

                $picture->delete();
            }
            $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('msg_removed_album'), true);
        } else {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_checkbox'), true);
        }
        $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
    }

    public function download(): void
    {
        ilObjPhotoGalleryGUI::executeDownload($this->retrievePictureIDs());
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


    protected function retrievePictureIDs(): array
    {
        $album_id = $this->retrieveAlbumID();
        if (!$this->access->checkAccess('read', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $album_id);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }
        if (!$this->http->wrapper()->query()->has('album_picture_ids')) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture_ids'), true);
            $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $album_id);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }
        $to_int = $this->refinery->kindlyTo()->string();
        $to_str_array = $this->refinery->kindlyTo()->listOf($to_int);
        $picture_ids = $this->http->wrapper()->query()->retrieve('album_picture_ids', $to_str_array);
        if (empty($picture_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture_ids'), true);
            $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $album_id);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }
        $album = srObjAlbum::find($album_id);
        if ($album === null) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_album'), true);
            $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $album_id);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }

        // handle ALL_OBJECTS special case
        if($picture_ids[0] === 'ALL_OBJECTS') {
            $picture_ids = [];
            $pictures = $album->getPictureObjects();
            foreach ($pictures as $picture) {
                $picture_ids[] = $picture->getId();
            }
        } else {
            $picture_ids = array_map('intval', $picture_ids);
        }
        return $picture_ids;
    }

    protected function retrieveAlbumID(): int
    {
        if (!$this->http->wrapper()->query()->has('album_id')) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_album_id'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES);
        }
        $to_int = $this->refinery->kindlyTo()->string();
        $album_id = $this->http->wrapper()->query()->retrieve('album_id', $to_int);
        if (empty($album_id)) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_album_id'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES);
        }

        return $album_id;
    }
}
