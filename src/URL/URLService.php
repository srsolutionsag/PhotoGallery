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

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class URLService
{
    private URLBuilder $builder;

    public function __construct()
    {
        if (version_compare(ILIAS_VERSION_NUMERIC, '9.0', '>=')) {
            $this->builder = new URLBuilderTokenDelivery();
        } else {
        }
        $this->builder = new URLBuilderPluginDelivery();
    }

    public function get(): URLBuilder
    {
        return $this->builder;
    }
}
