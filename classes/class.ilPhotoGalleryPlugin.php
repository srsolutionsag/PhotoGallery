<?php

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
        $this->db->dropTable(srObjExif::TABLE_NAME, false);
        $this->db->dropTable(srObjAlbum::TABLE_NAME, false);
        $this->db->dropTable(srObjPhotoData::TABLE_NAME, false);
        $this->db->dropTable(srObjPicture::TABLE_NAME, false);
    }
}
