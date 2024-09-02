<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

declare(strict_types=1);

namespace srag\Plugins\PhotoGallery\Preview;

use ILIAS\ResourceStorage\Services;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class PreviewGeneratorFlavour implements PreviewGenerator
{
    private Services $irss;

    public function __construct()
    {
        global $DIC;
        $this->irss = $DIC->resourceStorage();
    }

    protected function getDefinition(int $max_size): \ilObjPhotoGalleryCropToSquare
    {
        return new \ilObjPhotoGalleryCropToSquare($max_size, 50);
    }

    public function getURL(ResourceIdentification $rid, int $max_size): string
    {
        $definition = $this->getDefinition($max_size);
        $flavour = $this->irss->flavours()->get($rid, $definition);

        return $this->irss->consume()->flavourUrls($flavour)->getURLsAsArray()[0] ?? '';
    }

    public function generate(ResourceIdentification $rid, int $max_size): string
    {
        $definition = $this->getDefinition($max_size);
        $flavour = $this->irss->flavours()->get($rid, $definition);

        return $flavour->getPersistingName();
    }

}
