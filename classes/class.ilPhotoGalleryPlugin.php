<?php

require_once('./Services/Repository/classes/class.ilRepositoryObjectPlugin.php');

/**
 * PhotoGallery repository object plugin
 *
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version $Id$
 *
 */
class ilPhotoGalleryPlugin extends ilRepositoryObjectPlugin {

	const PLUGIN_NAME = 'PhotoGallery';
	/**
	 * @var ilPhotoGalleryPlugin
	 */
	protected static $instance;


	/**
	 * @return ilPhotoGalleryPlugin
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @var ilDB
	 */
	protected $db;


	public function __construct() {
		parent::__construct();

		global $DIC;

		$this->db = $DIC->database();
	}


	/**
	 * @return string
	 */
	public function getPluginName() {
		return self::PLUGIN_NAME;
	}


	protected function uninstallCustom() {
		require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Exif/class.srObjExif.php';
		require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Album/class.srObjAlbum.php';
		require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Picture/class.srObjPhotoData.php';
		require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Picture/class.srObjPicture.php';

		$this->db->dropTable(srObjExif::TABLE_NAME, false);
		$this->db->dropTable(srObjAlbum::TABLE_NAME, false);
		$this->db->dropTable(srObjPhotoData::TABLE_NAME, false);
		$this->db->dropTable(srObjPicture::TABLE_NAME, false);

		// TODO Delete photos folder

		return true;
	}
}

?>
