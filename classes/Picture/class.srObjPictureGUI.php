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
use ILIAS\Refinery\Factory as Refinery;
use srag\Plugins\PhotoGallery\Preview\PreviewGenerator;
use srag\Plugins\PhotoGallery\URL\URLBuilder;

/**
 * GUI-Class srObjPictureGUI
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 */
class srObjPictureGUI
{
    protected URLBuilder $url_builder;
    protected PreviewGenerator $previews;
    protected ilObjPhotoGalleryGUI $parent;
    protected ilPhotoGalleryPlugin $pl;
    public const CMD_REDIRECT_TO_ALBUM_LIST_PICTURES = 'redirectToAlbumListPictures';
    public const CMD_REDIRECT_TO_ALBUM_MANAGE_PICTURES = 'redirectToAlbumManagePictures';
    public const CMD_UPLOAD = 'upload';
    public const CMD_SET_AS_PREVIEW = 'setAsPreview';

    public const CMD_SHOW_PICTURE = 'showPicture';
    public const CMD_SHOW_PREVIOUS_PICTURE = 'showPreviousPicture';
    public const CMD_SHOW_NEXT_PICTURE = 'showNextPicture';

    protected ilAccessHandler $access;
    protected ilTabsGUI $tabs_gui;
    protected ilPropertyFormGUI $form;
    protected ilToolbarGUI $toolbar;
    protected ilCtrl $ctrl;
    protected Services $http;
    protected ilLanguage $lng;
    protected Refinery $refinery;
    protected ilGlobalTemplateInterface $tpl;
    protected ?srObjPicture $obj_picture = null;
    protected UIServices $ui;
    protected \ILIAS\ResourceStorage\Services $irss;

    /**
     * @param $parent_gui
     */
    public function __construct(ilObjPhotoGalleryGUI $parent_gui)
    {
        global $DIC, $xphoDIC;
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
        $this->irss = $DIC->resourceStorage();

        $this->ctrl->setParameterByClass(self::class, 'album_id', $_GET['album_id']);
        srObjAlbumGUI::setLocator($_GET['album_id']);
        $this->previews = $xphoDIC[PreviewGenerator::class];
        $this->url_builder = $xphoDIC[URLBuilder::class];
    }

    public function executeCommand(): bool
    {
        $next_class = $this->ctrl->getNextClass();
        $cmd = $this->ctrl->getCmd();

        switch ($next_class) {
            case strtolower(ilObjPhotoGalleryUploadHandlerGUI::class):
                $this->ctrl->forwardCommand(new ilObjPhotoGalleryUploadHandlerGUI());
                break;
            default:
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
                    case self::CMD_UPLOAD:
                    case self::CMD_SET_AS_PREVIEW:
                    case self::CMD_SHOW_PICTURE:
                    case self::CMD_SHOW_PREVIOUS_PICTURE:
                    case self::CMD_SHOW_NEXT_PICTURE:
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
            $form_gui = new srObjPictureFormGUI($this, new srObjPicture());
            $this->tpl->setContent($this->ui->renderer()->render([$form_gui->getForm()]));
        }
    }

    /**
     * @description for AJAX Drag&Drop Fileupload
     */
    public function create(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent, '');
        }

        $form_gui = new srObjPictureFormGUI($this, new srObjPicture());
        $form = $form_gui->getForm();
        $form = $form->withRequest($this->http->request());
        $data = $form->getData();
        if ($form_gui->saveData($data)) {
            $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('success'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
        } else {
            $this->tpl->setContent($this->ui->renderer()->render([$form]));
        }
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
        $picture_rid = $picture->getPictureRID();
        $picture_identifier = $this->irss->manage()->find($picture_rid);
        if($picture_identifier === null) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->lng->txt('file_not_found'), true);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }
        $form_gui = new srObjPictureFormGUI($this, $picture);
        $this->tpl->setContent($this->ui->renderer()->render([$form_gui->getForm()]));
    }

    public function update(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        }
        if (!$this->http->wrapper()->query()->has('picture_id')) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture_id'), true);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }
        $to_int = $this->refinery->kindlyTo()->int();
        $picture_id = $this->http->wrapper()->query()->retrieve('picture_id', $to_int);
        /**
         * @var $picture srObjPicture
         */
        $picture = srObjPicture::find($picture_id);
        $form_gui = new srObjPictureFormGUI($this, $picture);
        $form = $form_gui->getForm();
        $form = $form->withRequest($this->http->request());
        $data = $form->getData();
        if ($form_gui->saveData($data)) {
            $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('success_edit'), true);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        } else {
            $this->tpl->setContent($this->ui->renderer()->render([$form]));
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
            /**
             * @var srObjPicture $picture
             */
            $picture_rid = $picture->getPictureRID();
            $picture_identifier = $this->irss->manage()->find($picture_rid);
            if ($picture_identifier !== null) {
                $src_preview = $this->previews->getURL($picture_identifier, 96);
                $image = $this->ui->factory()->image()->standard($src_preview, $picture_title);
            } else {
                $image = $this->ui->factory()->image()->standard("", $this->lng->txt('file_not_found'));
            }
            $items[] = $this->ui->factory()->modal()->interruptiveItem(
                $picture_id,
                $picture_title,
                $image
            );
        }
        $interruptive_modal = $this->ui->factory()->modal()->interruptive(
            $this->lng->txt('delete'),
            $this->pl->txt('delete_pic'),
            $delete_action
        )->withAffectedItems($items);
        echo($this->ui->renderer()->renderAsync([$interruptive_modal]));
        exit();
    }

    public function delete(): void
    {
        if (!$this->access->checkAccess('delete', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }

        $picture_ids = array_map('intval', $this->http->request()->getParsedBody()['interruptive_items']);
        if ((is_countable($picture_ids) ? count($picture_ids) : 0) > 0) {
            // delete all selected items
            foreach ($picture_ids as $picture_id) {
                /**
                 * @var $picture srObjPicture
                 */
                $picture = srObjPicture::find($picture_id);
                if ($picture === null) {
                    continue;
                }
                $picture_rid = $picture->getPictureRID();
                //if the current picture serves as the album's preview image remove the preview before deletion
                $album_id = $picture->getAlbumId();
                /**
                 * @var $album srObjAlbum
                 */
                $album = srObjAlbum::find($album_id);
                if ($album !== null && ((int) $picture->getId() === $album->getPreviewId(
                        ) || $picture_rid === $album->getPreviewPictureRID())) {
                    $album->setPreviewId(0);
                    $album->setPreviewPictureRID('');
                    $album->update();
                }
                $picture_identifier = $this->irss->manage()->find($picture_rid);
                if ($picture_identifier !== null) {
                    // delete picture in IRSS
                    $album_collection_rid = $album->getAlbumCollectionRID();
                    $collection_identifier = $this->irss->collection()->id($album_collection_rid);
                    $collection = $this->irss->collection()->get($collection_identifier, $album->getUserId());
                    $collection->remove($picture_identifier);
                    $this->irss->manage()->remove(
                        $picture_identifier,
                        new ilObjPhotoGalleryStakeholder($picture->getUserId())
                    );
                }
                // delete picture in DB
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
        if (!$this->access->checkAccess('rep_robj_xpho_download_images', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }
        ilObjPhotoGalleryGUI::executeDownload($this->retrievePictureIDs());
        $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
    }

    public function setAsPreview(): void
    {
        $picture_ids = $this->retrievePictureIDs();
        $picture_id = $picture_ids[0];

        /**
         * @var $picture srObjPicture
         */
        $picture = srObjPicture::find($picture_id);
        if($picture === null) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture'), true);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }

        $album_id = $picture->getAlbumId();
        /**
         * @var $album srObjAlbum
         */
        $album = srObjAlbum::find($album_id);
        if($album === null) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_album'), true);
            $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
        }

        $picture_rid = $picture->getPictureRID();
        $picture_identifier = $this->irss->manage()->find($picture_rid);
        if ($picture_identifier !== null) {
            $album->setPreviewId($picture_id);
            $album->setPreviewPictureRID($picture_rid);
            $album->update();

            $this->ui->mainTemplate()->setOnScreenMessage("success",
                sprintf($this->pl->txt('success_picture_set_as_preview'),$picture->getTitle()),
                true
            );
        } else {
            $this->ui->mainTemplate()->setOnScreenMessage("failure",
                $this->lng->txt('file_not_found'),
                true
            );
        }
        $this->ctrl->redirectByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES);
    }


    protected function showPicture()
    {
        $picture_id = $this->retrievePictureID();

        // get objects which are needed for data / image retrieval
        /**
         * @var $picture srObjPicture
         */
        $picture = srObjPicture::find($picture_id);
        if($picture === null) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
        }
        $picture_rid = $picture->getPictureRID();
        $picture_identifier = $this->irss->manage()->find($picture_rid);
        if ($picture_identifier !== null) {
            /**
             * @var $album srObjAlbum
             */
            $album = srObjAlbum::find($picture->getAlbumId());
            if($album === null) {
                $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_album'), true);
                $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
            }
            $gallery_obj_id = $album->getObjectId();
            $gallery = ilObjectFactory::getInstanceByObjId($gallery_obj_id);
            if ($gallery === null) {
                $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_gallery'), true);
                $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
            }

            // assemble data for image label
            $description = $picture->getDescription();
            $optional_description_info = ($description !== "") ? ($this->pl->txt(
                    'description'
                ) . ': ' . $description . ' | ') : "";
            $img_text = $this->pl->txt('gallery') . ': ' . $gallery->getTitle() . ' | '
                . $this->pl->txt('album') . ': ' . $album->getTitle() . ' | '
                . $this->pl->txt('picture') . ': ' . $picture->getTitle() . ' | '
                . $optional_description_info
                . $this->lng->txt('date') . ': ' . $picture->getCreateDate();

            // get image URL
            $img_src = $this->url_builder->getForRid($picture_identifier);

            $tpl = $this->pl->getTemplate('default/tpl.picture_slideshow.html', false);
            // add image src and text to template
            $tpl->setVariable('IMG_SRC', $img_src);
            $tpl->setVariable('IMG_TEXT', $img_text);
            // add back button to template
            $back_target = $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_LIST_PICTURES);
            $tpl->setVariable('BACK_BUTTON_TARGET', $back_target);
            $tpl->setVariable('BACK_BUTTON_TEXT', $this->pl->txt('back_to_album'));
            // add previous and next button targets to template
            $this->ctrl->saveParameterByClass(srObjPictureGUI::class, 'picture_id');
            $target_prev = $this->ctrl->getLinkTarget($this, self::CMD_SHOW_PREVIOUS_PICTURE);
            $target_next = $this->ctrl->getLinkTarget($this, self::CMD_SHOW_NEXT_PICTURE);
            $tpl->setVariable('PREV_BUTTON_TARGET', $target_prev);
            $tpl->setVariable('NEXT_BUTTON_TARGET', $target_next);

            $this->tpl->addCss($this->pl->getStyleSheetLocation('default/picture_slideshow.css'));
            $this->tpl->setContent($tpl->get());
        } else {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->lng->txt('file_not_found'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
        }
    }

    protected function showPreviousPicture()
    {
        $picture_id = $this->retrievePictureID();
        $previous_picture_id = $this->getAdjacentPictureId($picture_id, 'previous');
        $previous_picture_rid = srObjPicture::find($previous_picture_id)->getPictureRID();
        $previous_picture_identifier = $this->irss->manage()->find($previous_picture_rid);
        if ($previous_picture_identifier === null) {
            $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $previous_picture_id);
            $this->ctrl->redirect($this, self::CMD_SHOW_PREVIOUS_PICTURE);
        }
        $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $previous_picture_id);
        $this->ctrl->redirect($this, self::CMD_SHOW_PICTURE);
    }

    protected function showNextPicture()
    {
        $picture_id = $this->retrievePictureID();
        $next_picture_id = $this->getAdjacentPictureId($picture_id, 'next');
        $next_picture_rid = srObjPicture::find($next_picture_id)->getPictureRID();
        $next_picture_identifier = $this->irss->manage()->find($next_picture_rid);
        if ($next_picture_identifier === null) {
            $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $next_picture_id);
            $this->ctrl->redirect($this, self::CMD_SHOW_NEXT_PICTURE);
        }
        $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $next_picture_id);
        $this->ctrl->redirect($this, self::CMD_SHOW_PICTURE);
    }

    protected function getAdjacentPictureId(int $picture_id, string $direction): int
    {
        /**
         * @var $picture srObjPicture
         */
        $picture = srObjPicture::find($picture_id);
        if($picture === null) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture'), true);
            $this->ctrl->redirect($this, '');
        }
        /**
         * @var $album srObjAlbum
         */
        $album = srObjAlbum::find($picture->getAlbumId());
        if($album === null) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_album'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
        }

        $pictures_array = $album->getPictureArrays();
        $pictures_array = $this->sortPictures($pictures_array, $album->getSortType(), $album->getSortDirection());

        $key_of_target_picture = array_search($picture_id, array_column($pictures_array, 'id'));

        if($direction === 'previous') {
            $key_of_adjacent_picture = $key_of_target_picture - 1;
            if ($key_of_adjacent_picture < 0) {
                $key_of_adjacent_picture = count($pictures_array) - 1;
            }
        } else {
            $key_of_adjacent_picture = $key_of_target_picture + 1;
            if ($key_of_adjacent_picture >= count($pictures_array)) {
                $key_of_adjacent_picture = 0;
            }
        }
        return $pictures_array[$key_of_adjacent_picture]['id'];
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
        if ($picture_ids[0] === 'ALL_OBJECTS') {
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


    protected function retrievePictureID(): int
    {
        if (!$this->http->wrapper()->query()->has('picture_id')) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture_id'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
        }
        $to_int = $this->refinery->kindlyTo()->string();
        $picture_id = $this->http->wrapper()->query()->retrieve('picture_id', $to_int);
        if (empty($picture_id)) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture_id'), true);
            $this->ctrl->redirect($this, self::CMD_REDIRECT_TO_ALBUM_LIST_PICTURES);
        }

        return $picture_id;
    }

    private function sortPictures(array $pictures, string $sort_type, string $sort_direction): array
    {
        if ($sort_type === srObjAlbum::SORT_TYPE_TITLE) {
            usort($pictures, static fn($a, $b): int => strcmp($a["title"] ?? '', $b["title"] ?? ''));
        } elseif ($sort_type === srObjAlbum::SORT_TYPE_CREATE_DATE) {
            usort($pictures, static fn($a, $b): int => strtotime($a["date"] ?? '') - strtotime($b["date"] ?? ''));
        }

        if ($sort_direction === srObjAlbum::SORT_TYPE_DIRECTION_DESC) {
            return array_reverse($pictures);
        }

        return $pictures;
    }
}
