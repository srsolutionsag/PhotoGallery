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
use srag\Plugins\PhotoGallery\Preview\PreviewService;
use srag\Plugins\PhotoGallery\Picture\PictureRepository;
use srag\Plugins\PhotoGallery\Picture\PictureDBRepository;
use srag\Plugins\PhotoGallery\URL\URLService;
use srag\Plugins\PhotoGallery\Preview\PreviewGenerator;
use srag\Plugins\PhotoGallery\URL\URLBuilder;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Init
{
    private static ?DIC $container = null;

    public static function init(\ilPhotoGalleryPlugin $plugin, \ilPluginLanguage $language_handler): DIC
    {
        if (self::$container instanceof DIC) {
            return self::$container;
        }
        global $DIC;

        $container = new DIC();
        $container[Container::class] = static fn(): Container => $DIC;
        $container[Translator::class] = static fn(): Translator => new PluginTranslator($language_handler);
        $container[\ilPhotoGalleryPlugin::class] = static fn(): \ilPhotoGalleryPlugin => $plugin;
        $container[PictureRepository::class] = static fn(): PictureRepository => new PictureDBRepository(
            $DIC->database()
        );
        $container[URLService::class] = static fn(): URLService => new URLService();
        $container[URLBuilder::class] = static fn(): URLBuilder => $container[URLService::class]->get();

        $container[PreviewService::class] = static fn(): PreviewService => new PreviewService($container[URLBuilder::class]);
        $container[PreviewGenerator::class] = static fn(): PreviewGenerator => $container[PreviewService::class]->get();

        return self::$container = $container;
    }
}
