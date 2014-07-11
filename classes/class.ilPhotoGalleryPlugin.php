<?php

include_once('./Services/Repository/classes/class.ilRepositoryObjectPlugin.php');
//require_once('class.ilPhotoGalleryConfig.php');

/**
 * PhotoGallery repository object plugin
 *
 * @author  Alex Killing <alex.killing@gmx.de>
 * @author  Fabian Schmid <fabian.schmid@ilub.unibe.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 *
 * @version $Id$
 *
 */
class ilPhotoGalleryPlugin extends ilRepositoryObjectPlugin {

	/**
	 * @return string
	 */
	function getPluginName() {
		return 'PhotoGallery';
	}


	/**
	 * @return ilPhotoGalleryConfig
	 */
	public function getConfigObject() {
		$conf = new ilPhotoGalleryConfig($this->getConfigTableName());

		return $conf;
	}


	/**
	 * @return string
	 */
	public function getConfigTableName() {
		return $this->getSlotId() . substr(strtolower($this->getPluginName()), 0, 20 - strlen($this->getSlotId())) . '_c';
	}
}

?>
