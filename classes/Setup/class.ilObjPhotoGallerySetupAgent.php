<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\Refinery\Factory;

/**
 * @author Lukas Zehnder <lukas@sr.solutions>
 */
class ilObjPhotoGallerySetupAgent extends \ilPluginDefaultAgent
{
    /**
     * @readonly
     */
    private \ILIAS\Data\Factory $data_factory;
    /**
     * @readonly
     */
    private \ilLanguage $lng;
    /**
     * @readonly
     */
    private Factory $refinery;

    public function __construct(
        Factory $refinery,
        \ILIAS\Data\Factory $data_factory,
        \ilLanguage $lng
    ) {
        $this->data_factory = $data_factory;
        $this->lng = $lng;
        $this->refinery = $refinery;
        parent::__construct('PhotoGallery');
    }

    public function getMigrations(): array
    {
        return [new ilObjPhotoGalleryMigration()];
    }
}
