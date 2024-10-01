<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

use ILIAS\DI\UIServices;
use ILIAS\HTTP\Services;
use ILIAS\Refinery\Factory;
use srag\Plugins\PhotoGallery\Preview\PreviewGenerator;

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
    protected PreviewGenerator $previews;
    protected ilToolbarGUI $toolbar;
    protected ilDBInterface $db;

    protected ilTabsGUI $tabs_gui;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    protected ilObjPhotoGallery $obj_photo_gallery;
    protected ?ActiveRecord $obj_album;
    protected ilAccessHandler $access;
    protected ilObjPhotoGalleryGUI $parent_gui;
    protected ilLocatorGUI $locator;
    protected UIServices $ui;
    protected ilPhotoGalleryPlugin $pl;
    protected Services $http;
    protected Factory $refinery;
    protected \ILIAS\ResourceStorage\Services $irss;

    public function __construct(ilObjPhotoGalleryGUI $parent_gui)
    {
        global $DIC, $xphoDIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->access = $DIC->access();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->parent_gui = $parent_gui;
        $this->locator = $DIC["ilLocator"];
        $this->ui = $DIC->ui();
        $this->tabs_gui = $DIC->tabs();
        $this->tabs_gui->clearTargets();
        $this->toolbar = $DIC->toolbar();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
        $this->irss = $DIC->resourceStorage();
        $this->db = $DIC->database();

        $album_id = $this->http->wrapper()->query()->has('album_id')
            ? $this->http->wrapper()->query()->retrieve('album_id', $this->refinery->kindlyTo()->int())
            : null;

        $this->obj_album = srObjAlbum::find($album_id);
        $this->pl = ilPhotoGalleryPlugin::getInstance();

        $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'album_id', $album_id);

        $this->previews = $xphoDIC[PreviewGenerator::class];
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
                $album_id = $this->http->wrapper()->query()->retrieve('album_id', $this->refinery->kindlyTo()->int());
                if(!$this->isMigrationOfAlbumCompleted($album_id)) {
                    $this->showMigrationErrorAndRedirect(ilObjPhotoGalleryGUI::class, ilObjPhotoGalleryGUI::CMD_LIST_ALBUMS);
                }
                self::setLocator($album_id);
                $this->setTabs();
                $this->setSubTabs();
                $this->tabs_gui->activateSubTab(self::TAB_LIST_PICTURES);
                $this->listPictures();
                break;
            case self::CMD_MANAGE_PICTURES:
                $album_id = $this->http->wrapper()->query()->retrieve('album_id', $this->refinery->kindlyTo()->int());
                if(!$this->isMigrationOfAlbumCompleted($album_id)) {
                    $this->showMigrationErrorAndRedirect(ilObjPhotoGalleryGUI::class, ilObjPhotoGalleryGUI::CMD_LIST_ALBUMS);
                }
                self::setLocator($album_id);
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
        if (!$this->access->checkAccess('read', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        }

        // picture cards
        $cards = [];
        /**
         * @var $srObjPicture srObjPicture
         */
        foreach ($this->obj_album->getPictureObjects() as $srObjPicture) {
            // image for the card
            $picture_rid = $srObjPicture->getPictureRID();
            $picture_identifier = $this->irss->manage()->find($picture_rid);
            if ($picture_identifier !== null) {
                $src_preview = $this->previews->getURL($picture_identifier, 512);
            }
            $image = $this->ui->factory()->image()->responsive(
                $src_preview,
                $srObjPicture->getTitle()
            );
            //            $this->ctrl->setParameterByClass(srObjPicture::class, 'picture_id', $srObjPicture->getId());
            $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $srObjPicture->getId());
            $this->ctrl->setParameterByClass(
                srObjPictureGUI::class,
                'picture_type',
                srObjPicture::TITLE_PRESENTATION
            );
            $src_presentation = $this->ctrl->getLinkTargetByClass(
                srObjPictureGUI::class,
                srObjPictureGUI::CMD_SHOW_PICTURE
            );
            $card = $this->ui->factory()->card()->standard(
                "",
                $image->withAction($src_presentation)
            );
            $cards[] = $card;
        }
        if ($this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
            $add_new_picture_image = $this->ui->factory()->image()->responsive(
                $this->pl->getDirectory() . '/templates/images/addnew.svg',
                $this->pl->txt('upload_pictures')
            );
            $add_new_picture_action = $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, atTableGUI::CMD_ADD);
            $add_new_picture_card = $this->ui->factory()->card()->standard(
                "",
                $add_new_picture_image->withAction($add_new_picture_action)
            );
            $cards[] = $add_new_picture_card;
            // create add picture button and add it to toolbar
            $add_picture_button = $this->ui->factory()->button()->primary(
                $this->pl->txt('upload_pictures'),
                $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, atTableGUI::CMD_ADD)
            );
            $this->toolbar->addComponent($add_picture_button);
        }

        $deck = $this->ui->factory()->deck($cards);
        $this->tpl->setContent($this->ui->renderer()->render($deck));
    }

    public function managePictures(): void
    {
        if (!ilObjPhotoGalleryAccess::checkManageTabAccess($this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        } else {
            $table_gui = new srObjAlbumTableGUI();
            $this->tpl->setContent($table_gui->getTableForRepresentation());
        }
    }

    public function add(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $form_gui = new srObjAlbumFormGUI($this, new srObjAlbum());
            $this->tpl->setContent($this->ui->renderer()->render([$form_gui->getForm()]));
        }
    }

    public function create(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            $form_gui = new srObjAlbumFormGUI($this, new srObjAlbum());
            $form = $form_gui->getForm();
            $form = $form->withRequest($this->http->request());
            $data = $form->getData();
            if ($form_gui->saveData($data)) {
                $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('success'), true);
                $this->ctrl->redirect($this->parent_gui, ilObjPhotoGalleryGUI::CMD_LIST_ALBUMS);
            } else {
                $this->tpl->setContent($this->ui->renderer()->render([$form]));
            }
        }
    }

    public function edit(): void
    {
        $album_ids = $this->retrieveAlbumIDs();
        $album_id = $album_ids[0];
        if (!$this->isMigrationOfAlbumCompleted($album_id)) {
            $this->showMigrationErrorAndRedirect(ilObjPhotoGalleryGUI::class, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
        }
        $this->ctrl->setParameterByClass(srObjAlbumFormGUI::class, 'album_id', $album_id);
        $this->ctrl->setParameter($this, 'album_id', $album_id);
        /**
         * @var $album srObjAlbum
         */
        $album = srObjAlbum::find($album_id);
        $form_gui = new srObjAlbumFormGUI($this, $album);
        $this->tpl->setContent($this->ui->renderer()->render([$form_gui->getForm()]));
    }

    public function update(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        } else {
            if (!$this->http->wrapper()->query()->has('album_id')) {
                $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_album_ids'), true);
                $this->ctrl->redirect($this->parent_gui, '');
            }
            $to_int = $this->refinery->kindlyTo()->int();
            $album_id = $this->http->wrapper()->query()->retrieve('album_id', $to_int);
            /**
             * @var $album srObjAlbum
             */
            $album = srObjAlbum::find($album_id);
            $form_gui = new srObjAlbumFormGUI($this, $album);
            $form = $form_gui->getForm();
            $form = $form->withRequest($this->http->request());
            $data = $form->getData();
            if ($form_gui->saveData($data)) {
                $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('success_edit'), true);
                $this->ctrl->redirect($this->parent_gui, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
            } else {
                $this->tpl->setContent($this->ui->renderer()->render([$form]));
            }
        }
    }

    public function confirmDelete(): void
    {
        $album_ids = $this->retrieveAlbumIDs();
        $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_ids', implode(',', $album_ids));
        $this->ctrl->clearParameterByClass(srObjAlbumGUI::class, 'album_ids');
        $items = [];
        $has_unmigrated_album = false;
        foreach ($album_ids as $album_id) {
            if (!$this->isMigrationOfAlbumCompleted($album_id)) {
                $has_unmigrated_album = true;
                break;
            }
            /**
             * @var $album srObjAlbum
             */
            $album = srObjAlbum::find($album_id);
            if ($album === null) {
                continue;
            }
            $items[] = $this->ui->factory()->modal()->interruptiveItem(
                $album_id,
                $album->getTitle()
            );
        }

        if (!$has_unmigrated_album) {
            $modal = $this->ui->factory()->modal()->interruptive(
                $this->lng->txt('delete'),
                $this->pl->txt('delete_album'),
                $this->ctrl->getLinkTarget($this, 'delete')
            )->withAffectedItems($items);
        } else {
            $error_msg = (count($album_ids) > 1) ? $this->pl->txt('migration_of_albums_not_completed') : $this->pl->txt('migration_of_album_not_completed');
            $modal = $this->ui->factory()->modal()->roundtrip(
                $this->lng->txt('error'),
                [$this->ui->factory()->messageBox()->failure($error_msg)],
            );
        }

        echo($this->ui->renderer()->renderAsync([$modal]));
        exit();
    }

    public function delete(): void
    {
        if (!$this->access->checkAccess('delete', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirectByClass(ilObjPhotoGalleryGUI::class, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
        }

        $album_ids = array_map('intval', $this->http->request()->getParsedBody()['interruptive_items']);
        if ((is_countable($album_ids) ? count($album_ids) : 0) > 0) {
            // delete all selected items
            foreach ($album_ids as $album_id) {
                /**
                 * @var $album srObjAlbum
                 */
                $album = srObjAlbum::find($album_id);
                if ($album === null) {
                    continue;
                }
                // delete album and its pictures in irss
                $album_collection_rid = $album->getAlbumCollectionRID();
                $collection_identifier = $this->irss->collection()->id($album_collection_rid);
                $this->irss->collection()->remove(
                    $collection_identifier,
                    new ilObjPhotoGalleryStakeholder($album->getUserId()),
                    true
                );
                $pictures = $album->getPictureObjects();
                foreach ($pictures as $picture) {
                    // delete pictures of album in db
                    $picture->delete();
                }
                // delete album in db
                $album->delete();
            }
            $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('msg_removed_album'), true);
        } else {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_checkbox'), true);
        }
        $this->ctrl->redirect($this->parent_gui, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
    }

    public function download(): void
    {
        if (!$this->access->checkAccess('rep_robj_xpho_download_images', '', $this->parent_gui->getObject()->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirectByClass(ilObjPhotoGalleryGUI::class, ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS);
        }

        $album_ids = $this->retrieveAlbumIDs();
        $picture_ids = [];
        foreach ($album_ids as $album_id) {
            if (!$this->isMigrationOfAlbumCompleted($album_id)) {
                $this->showMigrationErrorAndRedirect(
                    ilObjPhotoGalleryGUI::class,
                    ilObjPhotoGalleryGUI::CMD_MANAGE_ALBUMS,
                    (count($album_ids) > 1)
                );
            }
            /**
             * @var $album srObjAlbum
             */
            $album = srObjAlbum::find($album_id);
            foreach ($album->getPictureObjects() as $pic) {
                $picture_ids[] = $pic->getId();
            }
        }
        // download array
        ilObjPhotoGalleryGUI::executeDownload($picture_ids);
    }

    protected function retrieveAlbumIDs(): array
    {
        /**
         * @var $gallery ilObjPhotoGallery
         */
        $gallery = $this->parent_gui->getObject();
        if (!$this->access->checkAccess('read', '', $gallery->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        }
        if (!$this->http->wrapper()->query()->has('gallery_album_ids')) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_album_ids'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        }
        $to_str_array = $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->string());
        $album_ids = $this->http->wrapper()->query()->retrieve('gallery_album_ids', $to_str_array);
        if (empty($album_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_album_ids'), true);
            $this->ctrl->redirect($this->parent_gui, '');
        }
        // handle ALL_OBJECTS special case
        if ($album_ids[0] === 'ALL_OBJECTS') {
            $album_ids = [];

            $albums = $gallery->getAlbumObjects();
            foreach ($albums as $album) {
                $album_ids[] = $album->getId();
            }
        } else {
            $album_ids = array_map('intval', $album_ids);
        }
        return $album_ids;
    }

    protected function isMigrationOfAlbumCompleted(int $album_id): bool
    {
        $han_no_collection = true;
        $query_album = $this->db->queryF(
            "SELECT album.album_collection_rid FROM sr_obj_pg_album AS album WHERE album.id = %s;",
            ['integer'],
            [$album_id]
        );
        $result_album = $this->db->fetchAssoc($query_album);
        if($result_album['album_collection_rid'] !== null && $result_album['album_collection_rid'] !== '') {
            $han_no_collection = false;
        }

        $query_pictures = $this->db->queryF(
            "SELECT COUNT(DISTINCT(picture.id)) AS amount FROM sr_obj_pg_pic AS picture"
            ." JOIN sr_obj_pg_album AS album ON picture.album_id = album.id"
            ." WHERE album.id = %s AND (picture.picture_rid IS NULL OR picture.picture_rid = '');",
            ['integer'],
            [$album_id]
        );
        $result_pictures = $this->db->fetchAssoc($query_pictures);
        $has_unmigrated_pictures = $result_pictures['amount'] > 0;

        return !($han_no_collection || $has_unmigrated_pictures);
    }

    protected function showMigrationErrorAndRedirect($redirect_class, $redirect_cmd, $affects_group_selection = false): void
    {
        if($affects_group_selection) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('migration_of_albums_not_completed'), true);
        } else {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('migration_of_album_not_completed'), true);
        }
        $this->ctrl->redirectByClass($redirect_class, $redirect_cmd);
    }
}
