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

use srag\Plugins\PhotoGallery\URL\URLBuilder;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class PreviewService
{
    private PreviewGenerator $generator;

    public function __construct(URLBuilder $url_builder)
    {
        if (version_compare(ILIAS_VERSION_NUMERIC, '9.0', '>=')) {
            $this->generator = new PreviewGeneratorFlavour();
        } else {
        }
        $this->generator = new PreviewGeneratorIRSS($url_builder);
    }

    public function get(): PreviewGenerator
    {
        return $this->generator;
    }
}
