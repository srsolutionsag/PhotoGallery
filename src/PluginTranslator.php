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

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class PluginTranslator implements Translator
{
    private \ilPluginLanguage $language_handler;

    public function __construct(\ilPluginLanguage $language_handler)
    {
        $this->language_handler = $language_handler;
    }

    public function txt(string $key): string
    {
        return $this->language_handler->txt($key);
    }

    public function moduleTxt(string $key, string $module): string
    {
        return $this->language_handler->txt($module . "_" . $key);
    }

}
