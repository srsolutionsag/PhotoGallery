<?php

/**
 * GUI-Class srObjExifGUI
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 */
class srObjExifGUI
{
    public $parent;
    /**
     * @var \ilPhotoGalleryPlugin
     */
    public $pl;
    public $lng;
    /**
     * @var \srObjPicture
     */
    public $picture;
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
     * @param $parent_gui
     */
    public function __construct($parent_gui)
    {
        global $DIC;

        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->parent = $parent_gui;
        $this->toolbar = $DIC->toolbar();
        $this->pl = ilPhotoGalleryPlugin::getInstance();
        $this->tabs_gui = $this->parent->tabs_gui;
        $this->lng = $DIC->language();

        //$this->tabs_gui->setBackTarget($this->pl->txt('back_to_diashow'), $this->ctrl->getLinkTargetByClass(srObjSliderGUI::class, srObjSliderGUI::CMD_INDEX));

        $this->picture = new srObjPicture($_GET['picture_id']);
    }

    public function executeCommand(): bool
    {
        $cmd = $this->ctrl->getCmd();
        $this->ctrl->saveParameter($this, 'picture_id');

        switch ($cmd) {
            case '':
                $this->show();
                break;
            default:
                $this->$cmd();
                break;
        }

        return true;
    }

    /**
     * @description set $this->lng with your LanguageObject or return false to use global Language
     */
    protected function initLanguage(): bool
    {
        global $DIC;
        $this->lng = $DIC->language();

        return true;
    }

    public function show(): void
    {
        $form = new srObjExifFormGUI($this, new srObjExif($_GET['picture_id']));
        $form->fillForm();
        $this->tpl->setContent($form->getHTML());
    }
}
