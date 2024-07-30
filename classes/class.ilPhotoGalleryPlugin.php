<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

use ILIAS\DI\UIServices;

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
    private static UIServices $ui;

    protected static \ilDBInterface $database;
    protected static \ilComponentRepositoryWrite $component_repo;
    /**
     * @var ilPhotoGalleryPlugin
     */
    protected static $instance;

    public function __construct(
        ilDBInterface $db,
        ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        global $DIC;
        self::$ui = $DIC->ui();
        self::$database = $db;
        self::$component_repo = $component_repository;
        parent::__construct($db, $component_repository, $id);
    }

    /**
     * @return ilPhotoGalleryPlugin
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self(self::$database, self::$component_repo, self::PLUGIN_ID);
        }

        return self::$instance;
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

        parent::afterUpdate();
        if (PHP_SAPI === 'cli') {
            return;
        }
        if ($this->getNumberOfUnmigratedAlbums() <= 0) {
            return;
        }
        self::$ui->mainTemplate()->setOnScreenMessage("info", $this->txt('after_update_migration_info'), true);
    }

    private function getNumberOfUnmigratedAlbums(): int
    {
        $query = self::$database->query(
            "SELECT COUNT(DISTINCT(a.id)) AS amount FROM sr_obj_pg_album AS a"
            ." WHERE a.album_collection_rid IS NULL OR a.album_collection_rid = '';"
        );
        $result = self::$database->fetchObject($query);

        return (int) $result->amount;
    }
}
