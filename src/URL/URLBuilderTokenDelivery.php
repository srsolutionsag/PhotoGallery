<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

declare(strict_types=1);

namespace srag\Plugins\PhotoGallery\URL;

use ILIAS\ResourceStorage\Services;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class URLBuilderTokenDelivery implements URLBuilder
{
    private Services $irss;

    public function __construct()
    {
        global $DIC;
        $this->irss = $DIC->resourceStorage();
    }

    public function getForRid(ResourceIdentification $rid): ?string
    {
        return $this->irss->consume()->src($rid)->getSrc();
    }
}
