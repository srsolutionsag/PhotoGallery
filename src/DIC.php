<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

declare(strict_types=1);

namespace srag\Plugins\PhotoGallery;

use ILIAS\DI\Container;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class DIC extends Container
{
    public function ilias(): Container
    {
        return $this[Container::class];
    }

    public function translator(): Translator
    {
        return $this[Translator::class];
    }

    public function plugin(): \ilPhotoGalleryPlugin
    {
        return $this[\ilPhotoGalleryPlugin::class];
    }
}
