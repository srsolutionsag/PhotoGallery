<?php

/**
 * GUI-Class srObjAlbumGUI
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 */
class srObjAlbumGUI
{
    public const CMD_LIST_PICTURES = 'listPictures';
    public const CMD_MANAGE_PICTURES = 'managePictures';
    public const CMD_REDIRECT_TO_GALLERY_LIST_ALBUMS = 'redirectToGalleryListAlbums';
    public const CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS = 'redirectToGalleryManageAlbums';
    public const TAB_LIST_PICTURES = 'list_pictures';
    public const TAB_MANAGE_PICTURES = 'manage_pictures';

    protected ilTabsGUI $tabs_gui;
    protected ilPropertyFormGUI $form;
    protected ilCtrl $ctrl;
    protected ilGlobalTemplateInterface $tpl;
    public ilObjPhotoGallery $obj_photo_gallery;
    public ?ActiveRecord $obj_album;
    protected ilAccessHandler $access;
    protected ilObjPhotoGalleryGUI $parent_gui;
    public ilLocatorGUI $locator;
    public ILIAS\DI\UIServices $ui;
    public ilPhotoGalleryPlugin $pl;
    public \ILIAS\HTTP\Services $http;
    public \ILIAS\Refinery\Factory $refinery;

    public function __construct(ilObjPhotoGalleryGUI $parent_gui)
    {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->access = $DIC->access();
        $this->ctrl = $DIC->ctrl();
        $this->parent_gui = $parent_gui;
        $this->locator = $DIC["ilLocator"];
        $this->ui = $DIC->ui();
        $this->tabs_gui = $DIC->tabs();
        $this->tabs_gui->clearTargets();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();

        $album_id = $this->http->wrapper()->query()->has('album_id')
            ? $this->http->wrapper()->query()->retrieve('album_id', $this->refinery->kindlyTo()->int())
            : null;

        $this->obj_album = srObjAlbum::find($album_id);
        $this->pl = ilPhotoGalleryPlugin::getInstance();

        $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'album_id', $album_id);
    }

    public function executeCommand(): bool
    {
        $cmd = $this->ctrl->getCmd();

        switch ($cmd) {
            case self::CMD_REDIRECT_TO_GALLERY_LIST_ALBUMS:
                $this->ctrl->setParameterByClass(ilObjPhotoGalleryGUI::class, 'picture_id', null);
                $this->ctrl->setParameterByClass(ilObjPhotoGalleryGUI::class, 'album_id', null);
                $this->ctrl->redirectByClass(ilObjPhotoGalleryGUI::class, ilObjPhotoGalleryGUI::CMD_LIST_ALBUMS);
                break;
            case self::CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS:
                $this->ctrl->setParameterByClass(self::class, 'picture_id', null);
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
        $this->tabs_gui->addSubTab(
            self::TAB_LIST_PICTURES,
            $this->pl->txt('view'),
            $this->ctrl->getLinkTarget($this, self::CMD_LIST_PICTURES)
        );

        // show tab "manage" on level album
        if (ilObjPhotoGalleryAccess::checkManageTabAccess($this->parent_gui->getObject()->getRefId())) {
            $this->tabs_gui->addSubTab(
                self::TAB_MANAGE_PICTURES,
                $this->pl->txt('manage'),
                $this->ctrl->getLinkTarget($this, self::CMD_MANAGE_PICTURES)
            );
        }
    }

    protected function setTabs()
    {
        $this->tabs_gui->setBackTarget(
            $this->pl->txt('back_to_gallery'),
            $this->ctrl->getLinkTarget($this->parent_gui)
        );
    }

    public static function setLocator(int $album_id): void
    {
        global $DIC;
        $ilCtrl = $DIC->ctrl();
        /**
         * @var $srObjAlbum srObjAlbum
         */
        $srObjAlbum = srObjAlbum::find($album_id);
        $ilCtrl->setParameterByClass(self::class, 'album_id', $album_id);
        $DIC["ilLocator"]->addItem(
            $srObjAlbum->getTitle(),
            $ilCtrl->getLinkTargetByClass(self::class, self::CMD_LIST_PICTURES)
        );
        $DIC->ui()->mainTemplate()->setLocator();
    }

    public function listPictures(): void
    {
        $this->tpl->addJavaScript($this->pl->getDirectory() . '/templates/libs/foundation-5.0.2/js/modernizr.js');
        $this->tpl->addCss($this->pl->getDirectory() . '/templates/default/clearing.css');
        $tpl = $this->pl->getTemplate('default/Picture/tpl.clearing.html', false);
        $tpl->setVariable('ALBUM_TITLE', $this->obj_album->getTitle());
        if ($this->access->checkAccess('read', '', $this->parent_gui->getObject()->getRefId())) {
            /**
             * @var $srObjPicture srObjPicture
             */
            foreach ($this->obj_album->getPictureObjects() as $srObjPicture) {
                $tpl->setCurrentBlock('picture');
                $tpl->setVariable('TITLE', $srObjPicture->getTitle());

                $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $srObjPicture->getId());
                $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_type', srObjPicture::TITLE_MOSAIC);
                $src_preview = $this->ctrl->getLinkTargetByClass(
                    srObjPictureGUI::class,
                    srObjPictureGUI::CMD_SEND_FILE
                );
                $tpl->setVariable('SRC_PREVIEW', $src_preview);

                $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $srObjPicture->getId());
                $this->ctrl->setParameterByClass(
                    srObjPictureGUI::class,
                    'picture_type',
                    srObjPicture::TITLE_PRESENTATION
                );
                $src_prensentation = $this->ctrl->getLinkTargetByClass(
                    srObjPictureGUI::class,
                    srObjPictureGUI::CMD_SEND_FILE
                );
                $tpl->setVariable('SRC_PRESENTATION', $src_prensentation);

                $tpl->parseCurrentBlock();
            }
            if ($this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
                $tpl->setCurrentBlock('add_new');
                $tpl->setVariable('SRC_ADDNEW', $this->pl->getDirectory() . '/templates/images/addnew.jpg');
                $tpl->setVariable(
                    'LINK',
                    $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, atTableGUI::CMD_ADD)
                );
                $tpl->parseCurrentBlock();
            }
        } else {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        }
        $this->tpl->setContent($tpl->get());
    }

    public function managePictures(): void
    {
        if (!ilObjPhotoGalleryAccess::checkManageTabAccess($this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        } else {
            $tableGui = new srObjAlbumTableGUI($this, srObjAlbumGUI::CMD_MANAGE_PICTURES);
            $this->tpl->setContent($tableGui->getHTML());
        }
    }

    public function add(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $form = new srObjAlbumFormGUI($this, new srObjAlbum());
            $this->tpl->setContent($form->getHTML());
        }
    }

    public function create(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $form = new srObjAlbumFormGUI($this, new srObjAlbum());
            $form->setValuesByPost();
            if ($form->saveObject()) {
                $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('success'), true);
                $this->ctrl->redirect($this->parent_gui, ilObjPhotoGalleryGUI::CMD_LIST_ALBUMS);
            } else {
                $this->tpl->setContent($form->getHTML());
            }
        }
    }

    public function edit(): void
    {
        $to_int = $this->refinery->kindlyTo()->int();
        $ref_id = $this->http->wrapper()->query()->retrieve('ref_id', $to_int);
        if (!$this->access->checkAccess('write', '', $ref_id)) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $album_id = $this->http->wrapper()->query()->retrieve('album_id', $to_int);
            /**
             * @var $album srObjAlbum
             */
            $album = srObjAlbum::find($album_id);
            $form = new srObjAlbumFormGUI($this, $album);
            $form->fillForm();
            $this->tpl->setContent($form->getHTML());
        }
    }

    public function update(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $album_id = $this->http->wrapper()->query()->retrieve('album_id', $this->refinery->kindlyTo()->int());
            /**
             * @var $album srObjAlbum
             */
            $album = srObjAlbum::find($album_id);
            $form = new srObjAlbumFormGUI($this, $album);
            $form->setValuesByPost();
            if ($form->saveObject()) {
                $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('success_edit'), true);
                $this->ctrl->redirect($this->parent_gui, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
            } else {
                $this->tpl->setContent($form->getHTML());
            }
        }
    }

    public function confirmDelete(): void
    {
        $arr_album_ids = [];
        if (!$this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $to_int = $this->refinery->kindlyTo()->int();
            $album_id = null;
            if ($this->http->wrapper()->query()->has('album_id')) {
                $album_id = $this->http->wrapper()->query()->retrieve('album_id', $to_int);
            }
            $album_ids = [];
            if ($this->http->wrapper()->post()->has('album_ids')) {
                $album_ids = $this->http->wrapper()->post()->retrieve(
                    'album_ids',
                    $this->refinery->kindlyTo()->listOf($to_int)
                );
            }
            if ($album_id === null && !count($album_ids)) {
                $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_checkbox'), true);
                $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS);
            }
            if (count($album_ids)) {
                $arr_album_ids = $album_ids;
            } else {
                $arr_album_ids[] = $album_id;
            }
            $c_gui = new ilConfirmationGUI();
            // set confirm/cancel commands
            $c_gui->setFormAction($this->ctrl->getFormAction($this, atTableGUI::CMD_DELETE));
            $c_gui->setHeaderText($this->pl->txt('delete_album'));
            $c_gui->setCancel($this->pl->txt('cancel'), self::CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS);
            $c_gui->setConfirm($this->pl->txt('delete'), atTableGUI::CMD_DELETE);
            // add items to delete
            foreach ($arr_album_ids as $album_id) {
                /**
                 * @var $album srObjAlbum
                 */
                $album = srObjAlbum::find($album_id);
                $folder_icon = ilObject::_getIcon($album->getId(), "small", "fold");
                $c_gui->addItem('album_ids[]', $album_id, $album->getTitle(), $folder_icon);
            }
            $this->tpl->setContent($c_gui->getHTML());
        }
    }

    public function delete(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $to_int = $this->refinery->kindlyTo()->int();
            $album_ids = $this->http->wrapper()->post()->retrieve(
                'album_ids',
                $this->refinery->kindlyTo()->listOf($to_int)
            );
            if ((is_countable($album_ids) ? count($album_ids) : 0) > 0) {
                // delete all selected news items
                foreach ($album_ids as $alb_id) {
                    $album = srObjAlbum::find($alb_id);
                    $album->delete();
                }
                $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('msg_removed_album'), true);
            } else {
                $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_checkbox'), true);
            }
            $this->ctrl->redirect($this->parent_gui, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
        }
    }

    public function download(): void
    {
        $arr_album_id = [];
        if (!$this->access->checkAccess('read', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS);
        } else {
            $to_int = $this->refinery->kindlyTo()->int();
            $album_id = -1;
            if ($this->http->wrapper()->query()->has('album_id')) {
                $album_id = $this->http->wrapper()->query()->retrieve('album_id', $to_int);
            }
            $album_ids = [];
            if ($this->http->wrapper()->post()->has('album_ids')) {
                $album_ids = $this->http->wrapper()->post()->retrieve(
                    'album_ids',
                    $this->refinery->kindlyTo()->listOf($to_int)
                );
            }
            if (empty($album_ids) && $album_id === -1) {
                $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_checkbox'), true);
                $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS);
            }
            if (count($album_ids)) {
                $arr_album_id = $album_ids;
            } else {
                $arr_album_id[] = $album_id;
            }
        }
        // take album id
        $arr_picture_ids = [];
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
