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
class URLBuilderPluginDelivery implements URLBuilder
{

    private Services $irss;
    private \ilCtrlInterface $ctrl;

    public function __construct()
    {
        global $DIC;
        $this->irss = $DIC->resourceStorage();
        $this->ctrl = $DIC->ctrl();
    }

    public function getForRid(ResourceIdentification $rid): ?string
    {
        $this->ctrl->setParameterByClass(
            \ilPhotoGalleryDeliveryGUI::class, \ilPhotoGalleryDeliveryGUI::RID, $rid->serialize()
        );

        return $this->ctrl->getLinkTargetByClass([
            \ilUIPluginRouterGUI::class,
            \ilPhotoGalleryDeliveryGUI::class
        ]);
    }
}
