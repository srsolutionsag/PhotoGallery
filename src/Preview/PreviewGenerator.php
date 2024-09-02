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

use ILIAS\ResourceStorage\Identification\ResourceIdentification;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
interface PreviewGenerator
{
    /**
     * @return string an URL to the Preview Image in the given size
     */
    public function getURL(ResourceIdentification $rid, int $max_size): string;

    /**
     * @return string Identification for the preview: In ILIAS 8 this is a ResourceIdentification we must
     * store in the database, in ILIAS 9 this is a Flavour and can be ignored.
     */
    public function generate(ResourceIdentification $rid, int $max_size): string;
}
