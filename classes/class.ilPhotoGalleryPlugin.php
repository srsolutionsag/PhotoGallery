<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

use srag\Plugins\PhotoGallery\DIC;
use srag\Plugins\PhotoGallery\Init;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * PhotoGallery repository object plugin
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>

 */
class ilPhotoGalleryPlugin extends ilRepositoryObjectPlugin
{
    public const PLUGIN_ID = 'xpho';
    public const PLUGIN_NAME = 'PhotoGallery';

    protected static $instance;
    private DIC $container;

    public function __construct(
        ilDBInterface $db,
        ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        global $xphoDIC;
        parent::__construct($db, $component_repository, $id);
        $this->container = $xphoDIC = Init::init($this, $this->getLanguageHandler());
    }

    /**
     * @return ilPhotoGalleryPlugin
     * @deprecated use \srag\Plugins\PhotoGallery\DIC instead to access plugin
     */
    public static function getInstance(): ilPhotoGalleryPlugin
    {
        global $DIC;
        return self::$instance = $DIC['component.factory']->getPlugin(self::PLUGIN_ID);
    }

    public function txt(string $a_var): string
    {
        return $this->container->translator()->txt($a_var);
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    protected function uninstallCustom(): void
    {
        $this->db->dropTable('sr_obj_pg_exif_data', false);
        $this->db->dropTable(srObjAlbum::TABLE_NAME, false);
        $this->db->dropTable('rep_robj_xpho_data', false);
        $this->db->dropTable(srObjPicture::TABLE_NAME, false);
    }

    protected function afterUpdate(): void
    {
        global $DIC;

        parent::afterUpdate();
        if (PHP_SAPI === 'cli') {
            return;
        }
        if ($this->getNumberOfUnmigratedAlbums() <= 0) {
            return;
        }
        $DIC->ui()->mainTemplate()->setOnScreenMessage("info", $this->txt('after_update_migration_info'), true);
    }

    private function getNumberOfUnmigratedAlbums(): int
    {
        $query = $this->db->query(
            "SELECT COUNT(DISTINCT(a.id)) AS amount FROM sr_obj_pg_album AS a"
            ." WHERE a.album_collection_rid IS NULL OR a.album_collection_rid = '';"
        );
        $result = $this->db->fetchObject($query);

        return (int) $result->amount;
    }
}
