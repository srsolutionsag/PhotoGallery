<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\HTTP\Services as HttpServices;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\ResourceStorage\Collection\CollectionBuilder;
use ILIAS\ResourceStorage\Resource\Repository\CollectionDBRepository;
use ILIAS\ResourceStorage\Events\Subject;
use ILIAS\ResourceStorage\Collection\ResourceCollection;

/**
 * @author            Lukas Zehnder <lukas@sr.solutions>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 */
class srObjAlbumFormGUI
{
    private CollectionBuilder $collection_builder;
    private ilCtrlInterface $ctrl;
    private ilDBInterface $db;
    private ilLanguage $lng;
    private Factory $ui_factory;
    private Renderer $ui_renderer;
    private ilObjUser $user;
    private HttpServices $http;
    private Refinery $refinery;
    protected srObjAlbum $album;
    protected srObjAlbumGUI $parent_gui;
    protected ilPhotoGalleryPlugin $pl;

    public function __construct(srObjAlbumGUI $parent_gui, srObjAlbum $album)
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->db = $DIC->database();
        $this->http = $DIC->http();
        $this->lng = $DIC->language();
        $this->user = $DIC->user();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->refinery = $DIC->refinery();
        $this->album = $album;
        $this->parent_gui = $parent_gui;
        $this->pl = ilPhotoGalleryPlugin::getInstance();
        $this->ctrl->saveParameter($parent_gui, 'album_id');
        $this->collection_builder = new CollectionBuilder(
            new CollectionDBRepository($this->db),
            new Subject()
        );
    }

    public function getForm(): StandardForm
    {
        // create input fields
        $form_action = $this->ctrl->getFormAction($this->parent_gui, atTableGUI::CMD_CREATE);
        $form_submit_label = $this->pl->txt('create_album');
        $form_title = $this->pl->txt('create_album');
        $hidden_id_input = $this->ui_factory->input()->field()->hidden()->withValue(0);
        $title_input = $this->ui_factory->input()->field()->text(
            $this->pl->txt('albumtitle')
        )->withRequired(true);
        $description_input = $this->ui_factory->input()->field()->text(
            $this->pl->txt('description')
        );
        $date_input = $this->ui_factory->input()->field()->dateTime(
            $this->pl->txt('date')
        )->withValue(new DateTimeImmutable("now"));
        $sort_type_input = $this->ui_factory->input()->field()->radio(
            $this->pl->txt('sort_type'),
            $this->pl->txt('album_sort_type_info')
        )->withRequired(true);
        foreach (srObjAlbum::$sort_types as $type) {
            $sort_type_input = $sort_type_input->withOption($type, $this->pl->txt("sort_type_$type"));
        }
        $sort_type_input = $sort_type_input->withValue(srObjAlbum::$sort_types[0]);
        $sort_direction_input = $this->ui_factory->input()->field()->radio(
            $this->pl->txt('sort_direction'),
            $this->pl->txt('album_sort_direction_info')
        )->withOption(
            'asc',
            $this->pl->txt('sort_direction_asc')
        )->withOption(
            'desc',
            $this->pl->txt('sort_direction_desc')
        )->withValue(
            'asc'
        )->withRequired(true);

        // if editing existing album change the action, submit label and title of the form and fill the input fields
        if ($this->album->getId() !== 0) {
            $form_action = $this->ctrl->getFormAction($this->parent_gui, atTableGUI::CMD_UPDATE);
            $form_submit_label = $this->pl->txt('save');
            $form_title = $this->pl->txt('edit_album');
            $hidden_id_input = $hidden_id_input->withValue($this->album->getId());
            $title_input = $title_input->withValue($this->album->getTitle());
            $description_input = $description_input->withValue($this->album->getDescription());
            $date_input = $date_input->withValue(new DateTimeImmutable($this->album->getCreateDate()));
            $sort_type_input = $sort_type_input->withValue($this->album->getSortType());
            $sort_direction_input = $sort_direction_input->withValue($this->album->getSortDirection());
        }

        // create sections and assign fields
        $main_section = $this->ui_factory->input()->field()->section(
            [
                "album_id" => $hidden_id_input,
                "title" => $title_input,
                "description" => $description_input,
                "create_date" => $date_input
            ],
            $form_title
        );
        $settings_section = $this->ui_factory->input()->field()->section(
            [
                "sort_type" => $sort_type_input,
                "sort_direction" => $sort_direction_input
            ],
            $this->lng->txt('settings')
        );

        // create form and assign sections
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [
                "main_section" => $main_section,
                "settings_section" => $settings_section
            ]
        )->withSubmitLabel($form_submit_label);
    }

    public function saveData($data): bool
    {
        if (empty($data) || !$this->http->wrapper()->query()->has('ref_id')) {
            return false;
        }

        try {
            $gallery_ref_id = $this->http->wrapper()->query()->retrieve('ref_id', $this->refinery->kindlyTo()->int());
            $album = new srObjAlbum();
            if ((int) $data['main_section']['album_id'] !== 0) {
                $album = srObjAlbum::find($data['main_section']['album_id']);
            }

            $album->setTitle($data['main_section']['title']);
            $album->setDescription($data['main_section']['description']);
            /**
             * @var DateTimeImmutable $create_date
             */
            $create_date = $data['main_section']['create_date'];
            $album->setCreateDate($create_date->format('Y-m-d'));
            $album->setSortType($data['settings_section']['sort_type']);
            $album->setSortDirection($data['settings_section']['sort_direction']);
            $album->setObjectId(ilObject::_lookupObjectId($gallery_ref_id));
            $album->setUserId($this->user->getId());

            if ((int) $data['main_section']['album_id'] !== 0) {
                $album->update();
            } else {
                $album_collection = $this->collection_builder->new(ResourceCollection::NO_SPECIFIC_OWNER);
                $this->collection_builder->store($album_collection);
                $album_collection_rid = $album_collection->getIdentification()->serialize();
                $album->setAlbumCollectionRID($album_collection_rid);
                $album->create();
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
