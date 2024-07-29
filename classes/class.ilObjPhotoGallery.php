<?php
/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

/**
 * Application class for ilObjPhotoGallery repository object.
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * $Id$
 */
class ilObjPhotoGallery extends ilObjectPlugin
{
    /**
     * @var bool
     */
    protected $object;

    public function __construct(int $a_ref_id = 0)
    {
        //        global $DIC;
        //        /**
        //         * @var $ilDB ilDB
        //         */
        parent::__construct($a_ref_id);
        //        $this->db = $DIC->database();
    }

    protected function initType(): void
    {
        $this->setType(ilPhotoGalleryPlugin::PLUGIN_ID);
    }

    public function hasDirectory(): bool
    {
        return is_dir($this->getDirectory());
    }

    public function createDirectory(): void
    {
        ilFileUtils::createDirectory($this->getDirectory());
    }

    public function getDirectory(): string
    {
        global $ilias;

        return $_SERVER['DOCUMENT_ROOT'] . '/' . ILIAS_WEB_DIR . '/' . $ilias->client_id . '/' . $this->getType(
        ) . '/' . $this->getId();
    }

    /**
     * @return srObjAlbum[]
     */
    public function getAlbumObjects(): array
    {
        return srObjAlbum::where(['object_id' => $this->getId()])->orderBy('create_date')->orderBy('title')->get();
    }

    /**
     * @param                     $a_target_id
     * @param                     $a_copy_id
     */
    public function doClone($a_target_id, $a_copy_id, ilObjPhotoGallery $new_obj): void
    {
    }
}
