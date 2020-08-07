<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * GUI-Class srObjAlbumGUI
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version           $Id:
 */
class srObjAlbumFormGUI extends ilPropertyFormGUI
{

    /**
     * @var  srObjAlbum
     */
    protected $album;
    /**
     * @var srObjAlbumGUI
     */
    protected $parent_gui;
    /**
     * @var ilPhotoGalleryPlugin
     */
    protected $pl;
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilLanguage
     */
    protected $lng;
    /**
     * @var ilObjUser
     */
    protected $user;

    /**
     * @param            $parent_gui
     * @param srObjAlbum $album
     */
    public function __construct($parent_gui, srObjAlbum $album)
    {
        parent::__construct();
        global $DIC;
        $this->ctrl       = $DIC->ctrl();
        $this->lng        = $DIC->language();
        $this->user       = $DIC->user();
        $this->album      = $album;
        $this->parent_gui = $parent_gui;
        $this->pl         = ilPhotoGalleryPlugin::getInstance();
        $this->ctrl->saveParameter($parent_gui, 'album_id');
        $this->initForm();
    }

    private function initForm()
    {
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        if ($this->album->getId() == 0) {
            $this->setTitle($this->pl->txt('create_album'));
        } else {
            $this->setTitle($this->pl->txt('edit_album'));
        }
        $title = new ilTextInputGUI($this->pl->txt('albumtitle'), 'title');
        $title->setRequired(true);
        $this->addItem($title);
        $desc = new ilTextAreaInputGUI($this->pl->txt('description'), 'description');
        $this->addItem($desc);
        switch ($this->ctrl->getCmd()) {
            //			case atTableGUI::CMD_UPDATE:
            case atTableGUI::CMD_EDIT:
                $date_input = new ilDateTimeInputGUI($this->pl->txt('date'), 'create_date');
                $date_input->setDate(new ilDate($this->album->getCreateDate(), IL_CAL_DATE));
                $this->addItem($date_input);
                break;
            case atTableGUI::CMD_ADD:
                $date_input = new ilDateTimeInputGUI($this->pl->txt('date'), 'create_date');
                $date_input->setDate(new ilDate(date('Y-m-d'), IL_CAL_DATE));
                $this->addItem($date_input);
                break;
        }

        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->lng->txt('settings'));
        $this->addItem($header);

        $item = new ilRadioGroupInputGUI($this->pl->txt('sort_type'), 'sort_type');
        $item->setRequired(true);
        $item->setInfo($this->pl->txt('album_sort_type_info'));
        foreach (srObjAlbum::$sort_types as $type) {
            $item->addOption(new ilRadioOption($this->pl->txt("sort_type_$type"), $type));
        }
        $this->addItem($item);

        $item = new ilRadioGroupInputGUI($this->pl->txt('sort_direction'), 'sort_direction');
        $item->setRequired(true);
        $item->setInfo($this->pl->txt('album_sort_direction_info'));
        $item->addOption(new ilRadioOption($this->pl->txt('sort_direction_asc'), 'asc'));
        $item->addOption(new ilRadioOption($this->pl->txt('sort_direction_desc'), 'desc'));
        $this->addItem($item);

        if ($this->album->getId() == 0) {
            $this->addCommandButton(atTableGUI::CMD_CREATE, $this->pl->txt('create_album'));
            $this->addCommandButton(srObjAlbumGUI::CMD_REDIRECT_TO_GALLERY_LIST_ALBUMS, $this->pl->txt('cancel'));
        } else {
            $this->addCommandButton(atTableGUI::CMD_UPDATE, $this->pl->txt('save'));
            $this->addCommandButton(srObjAlbumGUI::CMD_REDIRECT_TO_GALLERY_MANAGE_ALBUMS, $this->pl->txt('cancel'));
        }
    }

    public function fillForm()
    {
        $array = array(
            'title'          => $this->album->getTitle(),
            'description'    => $this->album->getDescription(),
            'sort_type'      => $this->album->getSortType(),
            'sort_direction' => $this->album->getSortDirection(),
        );
        $this->setValuesByArray($array, true);
    }

    /**
     * returns whether checkinput was successful or not.
     * @return bool
     */
    public function fillObject()
    {
        if (!$this->checkInput()) {
            return false;
        }
        $this->album->setTitle($this->getInput('title'));
        $this->album->setDescription($this->getInput('description'));
        $date_array = $this->getInput('create_date');
        if (is_array($date_array)) {
            $date = $date_array['date']['y'] . '-' . $date_array['date']['m'] . '-' . $date_array['date']['d'];
        } else {
            $date = date('Y-m-d', strtotime($date_array));
        }
        $this->album->setCreateDate($date);
        $this->album->setObjectId(ilObject::_lookupObjectId($_GET['ref_id']));
        $this->album->setUserId($this->user->getId());
        $this->album->setSortType($this->getInput('sort_type'));
        $this->album->setSortDirection($this->getInput('sort_direction'));
        return true;
    }

    /**
     * @return bool
     */
    public function saveObject()
    {
        if (!$this->fillObject()) {
            return false;
        }
        if ($this->album->getId()) {
            $this->album->update();
        } else {
            $this->album->create();
        }

        return true;
    }
}
