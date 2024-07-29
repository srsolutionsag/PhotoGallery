<?php
/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

/**
 * ListGUI implementation for PhotoGallery object plugin. This one
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponfing ...Access class.
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 * @author        Fabian Schmid <fs@studer-raimann.ch>
 * @author        Martin Studer <ms@studer-raimann.ch>
 */
class ilObjPhotoGalleryListGUI extends ilObjectPluginListGUI
{
    public function initType(): void
    {
        $this->setType(ilPhotoGalleryPlugin::PLUGIN_ID);
    }

    public function getGuiClass(): string
    {
        return ilObjPhotoGalleryGUI::class;
    }

    public function initCommands(): array
    {
        return [
            [
                'permission' => 'read',
                'cmd' => ilObjPhotoGalleryGUI::CMD_SHOW_CONTENT,
                'default' => true
            ],
            [
                'permission' => 'write',
                'cmd' => ilObjPhotoGalleryGUI::CMD_EDIT_PROPERTIES,
                'txt' => $this->txt('edit'),
                'default' => false
            ],
        ];
    }

    public function getProperties(): array
    {
        $props = [];
        //        $this->plugin->includeClass('class.ilObjPhotoGalleryAccess.php');
        if (!ilObjPhotoGalleryAccess::checkOnline($this->obj_id)) {
            $props[] = ['alert' => true, 'property' => $this->txt('status'), 'value' => $this->txt('offline')];
        }

        return $props;
    }
}
