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
	 * @return string
	 */
	public function getPluginName() {
		return self::PLUGIN_NAME;
	}


	protected function uninstallCustom() {
		// TODO: Implement uninstallCustom() method.
	}
}

?>
