<?php

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\HTTP\Services as HttpServices;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\FileUpload\MimeType;

/**
 * GUI-Class srObjPictureFormGUI
 * @author            Lukas Zehnder <lukas@sr.solutions>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 */
class srObjPictureFormGUI
{
    private ilCtrlInterface $ctrl;
    private ilLanguage $lng;
    private Factory $ui_factory;
    private Renderer $ui_renderer;
    private UploadHandler $upload_handler;
    private ilObjUser $user;
    private HttpServices $http;
    private Refinery $refinery;
    protected srObjPicture $picture;
    protected srObjAlbum $album;
    protected srObjPictureGUI $parent_gui;
    protected ilPhotoGalleryPlugin $pl;

    public function __construct(srObjPictureGUI $parent_gui, srObjPicture $picture)
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->lng = $DIC->language();
        $this->user = $DIC->user();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->upload_handler = new ilObjPhotoGalleryUploadHandlerGUI();
        $this->refinery = $DIC->refinery();
        $this->picture = $picture;
        $album_id = $this->http->wrapper()->query()->has('album_id') ? $this->http->wrapper()->query()->retrieve('album_id', $this->refinery->kindlyTo()->int()) : 0;
        $this->album = new srObjAlbum($album_id);
        $this->parent_gui = $parent_gui;
        $this->pl = ilPhotoGalleryPlugin::getInstance();
        $this->ctrl->saveParameter($parent_gui, 'album_id');
        $this->ctrl->saveParameter($parent_gui, 'picture_id');
    }


    public function getForm(): StandardForm
    {
        $cmd = $this->ctrl->getCmd();
        switch ($cmd) {
            case atTableGUI::CMD_EDIT:
            case atTableGUI::CMD_UPDATE:
                return $this->getUpdateForm();
            case atTableGUI::CMD_ADD:
            case atTableGUI::CMD_CREATE:
                return $this->getCreateForm();
            default:
                throw new Exception("Unknown command $cmd");
        }
    }


    private function getCreateForm(): StandardForm
    {
        $form_action = $this->ctrl->getFormActionByClass(srObjPictureGUI::class, atTableGUI::CMD_CREATE);
        $form_submit_label = $this->pl->txt('upload_pic');
        $form_title = $this->pl->txt('upload_pic');

        // create input fields
        $hidden_input = $this->ui_factory->input()->field()->hidden()->withValue(0);
        $upload_input = $this->ui_factory->input()->field()->file(
            $this->upload_handler,
            $this->pl->txt('upload_files')
        )->withAcceptedMimeTypes(
            [MimeType::IMAGE__JPEG, MimeType::IMAGE__PNG, MimeType::IMAGE__GIF]
        )->withRequired(true);

        // create section and assign fields
        $section = $this->ui_factory->input()->field()->section(
            [
                "picture_id" => $hidden_input,
                "picture_file" => $upload_input
            ],
            $form_title
        );

        // create form and assign section
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [$section]
        )->withSubmitLabel($form_submit_label);
    }


    private function getUpdateForm(): StandardForm
    {
        $form_action = $this->ctrl->getFormAction($this->parent_gui, atTableGUI::CMD_UPDATE);
        $form_submit_label = $this->pl->txt('edit_pic');
        $form_title = $this->pl->txt('edit_pic');

        // create input fields
        $hidden_id_input = $this->ui_factory->input()->field()->hidden();
        $title_input = $this->ui_factory->input()->field()->text(
            $this->pl->txt('pic_title')
        )->withRequired(true);
        $description_input = $this->ui_factory->input()->field()->text(
            $this->pl->txt('description')
        );
        $date_input = $this->ui_factory->input()->field()->dateTime(
            $this->pl->txt('date')
        );
        $is_preview_input = $this->ui_factory->input()->field()->checkbox(
            $this->pl->txt('select_preview')
        );

        // fill the input fields
        $hidden_id_input = $hidden_id_input->withValue($this->picture->getId());
        $title_input = $title_input->withValue($this->picture->getTitle());
        $description_input = $description_input->withValue($this->picture->getDescription());
        $date_input = $date_input->withValue(new DateTimeImmutable($this->picture->getCreateDate()));
        $is_preview_input = $is_preview_input->withValue(
            $this->album->getPreviewId() === $this->picture->getId()
        );

        // create section and assign fields
        $section = $this->ui_factory->input()->field()->section(
            [
                "picture_id" => $hidden_id_input,
                "title" => $title_input,
                "description" => $description_input,
                "create_date" => $date_input,
                "preview" => $is_preview_input
            ],
            $form_title
        );

        // create form and assign section
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [$section]
        )->withSubmitLabel($form_submit_label);
    }


    public function saveData($data): bool
    {
        if (empty($data)) {
            return false;
        }
        if ((int)$data[0]['picture_id'] === 0) {
            return $this->storeUploadedPicture($data);
        }
        return $this->updatePictureData($data);
    }


    private function storeUploadedPicture($data): bool
    {
        $file_rid = $data[0]['picture_file'] ?? []; //TODO: figure out how to upload several files and handle the upload.
        //TODO: Create an irss collection upon creatin an album. access this collection here and store the rid within it.

        return false;
    }


    private function updatePictureData($data): bool
    {
        $picture_id = $data[0]['picture_id'];
        /**
         * @var srObjPicture $picture
         */
        $picture = srObjPicture::find($picture_id);
        if ($picture === null) {
            return false;
        }
        $picture->setTitle($data[0]['title']);
        $picture->setDescription($data[0]['description']);
        /**
         * @var DateTimeImmutable $create_date
         */
        $create_date = $data[0]['create_date'];
        $picture->setCreateDate($create_date->format('Y-m-d'));
        $picture->update();

        $is_preview = $data[0]['preview'];
        if ($is_preview) {
            $this->album->setPreviewId($picture_id);
        }
        if (!$is_preview && ((int)$picture->getId() === $this->album->getPreviewId())) {
            $this->album->setPreviewId(0);
        }
        $this->album->update();

        return true;
    }
}
