<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

require_once('./Services/Repository/classes/class.ilObjectPlugin.php');

/**
 * Application class for ilObjPhotoGallery repository object.
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 *
 * $Id$
 */
class ilObjPhotoGallery extends ilObjectPlugin {

	/**
	 * @var bool
	 */
	protected $object;


	/**
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		parent::__construct($a_ref_id);
		$this->db = $ilDB;
	}


	final function initType() {
		$this->setType('xpho');
	}


	public function doCreate() {
	}


	public function doRead() {
	}


	public function doUpdate() {
	}


	public function doDelete() {
	}


	/**
	 * @return bool
	 */
	public function hasDirectory() {
		return is_dir($this->getDirectory());
	}


	public function createDirectory() {
		ilUtil::createDirectory($this->getDirectory());
	}


	/**
	 * @return string
	 */
	public function getDirectory() {
		global $ilias;

		return $_SERVER['DOCUMENT_ROOT'] . '/' . ILIAS_WEB_DIR . '/' . $ilias->client_id . '/' . $this->getType() . '/' . $this->getId();
	}


	/**
	 * @return srObjAlbum[]
	 */
	public function getAlbumObjects() {
		return srObjAlbum::where(array( 'object_id' => $this->getId() ))->orderBy('create_date')->get();
	}


	/**
	 * @param                     $a_target_id
	 * @param                     $a_copy_id
	 * @param ilObjPhotoGallery   $new_obj
	 */
	public function doClone($a_target_id, $a_copy_id, ilObjPhotoGallery $new_obj) {
	}
}

?>
