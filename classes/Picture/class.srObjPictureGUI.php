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
use ILIAS\ResourceStorage\Flavour\Definition\CropToSquare;

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
    public const CMD_UPLOAD = 'upload';
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
    /**
     * @var \ActiveRecord|null
     */
    public $obj_picture;
    public UIServices $ui;
    private \ILIAS\ResourceStorage\Services $irss;

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
        $this->irss = $DIC->resourceStorage();

        $this->ctrl->setParameterByClass(self::class, 'album_id', $_GET['album_id']);
        srObjAlbumGUI::setLocator($_GET['album_id']);
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
                    case self::CMD_SEND_FILE:
                    case self::CMD_UPLOAD:
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
                $picture_flavour = new ilObjPhotoGalleryCropToSquare(48, 75);
                $flavour = $this->irss->flavours()->get($picture_identifier, $picture_flavour);
                $flavour_urls = $this->irss->consume()->flavourUrls($flavour)->getURLsAsArray();
                $src_preview = $flavour_urls[0];
            }
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
                // delete picture in IRSS
                $picture_identifier = $this->irss->manage()->find($picture_rid);
                $album_collection_rid = $album->getAlbumCollectionRID();
                $collection_identifier = $this->irss->collection()->id($album_collection_rid);
                if ($picture_identifier === null && $collection_identifier === null) {
                    continue;
                }
                $collection = $this->irss->collection()->get($collection_identifier, $album->getUserId());
                $collection->remove($picture_identifier);
                $this->irss->manage()->remove(
                    $picture_identifier,
                    new ilObjPhotoGalleryStakeholder($picture->getUserId())
                );
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
        ilObjPhotoGalleryGUI::executeDownload($this->retrievePictureIDs());
    }

    protected function sendFile(): void
    {
        if (!$this->access->checkAccess('read', '', $this->parent->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        }
        if (!$this->http->wrapper()->query()->has('picture_id')) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture_id'), true);
            $this->ctrl->redirect($this, '');
        }

        $tpl = $this->pl->getTemplate('default/tpl.picture_slideshow.html', false);

        // get data for image elements
        $picture_id = $this->http->wrapper()->query()->retrieve('picture_id', $this->refinery->kindlyTo()->int());
        /**
         * @var $srObjPicture srObjPicture
         */
        $srObjPicture = srObjPicture::find($picture_id);
        $picture_rid = $srObjPicture->getPictureRID();
        $picture_identifier = $this->irss->manage()->find($picture_rid);
        if ($picture_identifier === null) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('no_picture'), true);
            $this->ctrl->redirect($this, '');
        }
        /**
         * @var $album srObjAlbum
         */
        $album = srObjAlbum::find($srObjPicture->getAlbumId());
        $gallery_obj_id = $album->getObjectId();
        $gallery = ilObjectFactory::getInstanceByObjId($gallery_obj_id);

        // create image elements for slideshow
        $nr_elements_before_target = 0;
        $pictures = $album->getPictureArrays();
        $pictures = $this->sortPictures($pictures, $album->getSortType(), $album->getSortDirection());
        $key_of_target_picture = array_search($srObjPicture->asArray(), $pictures);
        foreach ($pictures as $picture_key => $picture) {
            $pic_id = $this->irss->manage()->find($picture['picture_rid']);
            $picture_src = $this->irss->consume()->src($pic_id)->getSrc();
            $description = $picture['description'];
            $optional_description_info = ($description !== "") ? ($this->pl->txt('description') . ': ' . $description . ' | ') : "";
            $picture_infos = $this->pl->txt('gallery') . ': ' . $gallery->getTitle() . ' | '
                . $this->pl->txt('album') . ': ' . $album->getTitle() . ' | '
                . $this->pl->txt('picture') . ': ' . $picture['title'] . ' | '
                . $optional_description_info
                . $this->lng->txt('create_date') . ': ' . $picture['create_date'];
            $img_element = '<div class="xpho_slideshow_slide_container">'
                . '<img class="xpho_slideshow_slide_image" src="' . $picture_src . '"/>'
                . '<div class="xpho_slideshow_slide_label_wrapper"><div class="xpho_slideshow_slide_label">' . $picture_infos . '</div></div>'
                . '</div>';
            $img_elements[] = $img_element;
            if($picture_key < $key_of_target_picture) {
                $nr_elements_before_target++;
            }
        }
        // order image elements
        $elements_before = array_slice($img_elements, 0, $nr_elements_before_target);
        $target_element = $img_elements[$nr_elements_before_target];
        $elements_after = array_slice($img_elements, $nr_elements_before_target + 1);
        $img_elements = array_merge([$target_element], $elements_after, $elements_before);
        // add image elements to template
        $tpl->setVariable('IMAGE_ELEMENTS', implode("      ", $img_elements));
        // add back button to template
        $back_target = $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_LIST_PICTURES);
        $tpl->setVariable('BACK_BUTTON_TARGET', $back_target);
        $tpl->setVariable('BACK_BUTTON_TEXT', $this->pl->txt('back_to_album'));

        $this->tpl->addCss($this->pl->getStyleSheetLocation('default/picture_slideshow.css'));
        $this->tpl->setContent($tpl->get());
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

    private function sortPictures(array $pictures, string $sort_type, string $sort_direction)
    {
        if ($sort_type === srObjAlbum::SORT_TYPE_TITLE) {
            usort($pictures, function ($a, $b) {
                return strcmp($a["title"], $b["title"]);
            });
        } elseif ($sort_type === srObjAlbum::SORT_TYPE_CREATE_DATE) {
            usort($pictures, function ($a, $b) {
                return strtotime($a["date"]) - strtotime($b["date"]);
            });
        }

        if($sort_direction === srObjAlbum::SORT_TYPE_DIRECTION_DESC) {
            $pictures = array_reverse($pictures);
        }

        return $pictures;
    }
}
