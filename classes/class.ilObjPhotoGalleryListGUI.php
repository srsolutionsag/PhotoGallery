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

include_once('./Services/Repository/classes/class.ilObjectPluginListGUI.php');

/**
 * ListGUI implementation for PhotoGallery object plugin. This one
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponfing ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 *
 * @author        Fabian Schmid <fs@studer-raimann.ch>
 * @author        Martin Studer <ms@studer-raimann.ch>
 *
 */
class ilObjPhotoGalleryListGUI extends ilObjectPluginListGUI {

	function initType() {
		$this->setType('xpho');
	}


	function getGuiClass() {
		return 'ilObjPhotoGalleryGUI';
	}


	function initCommands() {
		return array(
			array(
				'permission' => 'read',
				'cmd' => 'showContent',
				'default' => true
			),
			array(
				'permission' => 'write',
				'cmd' => 'editProperties',
				'txt' => $this->txt('edit'),
				'default' => false
			),
		);
	}


	/**
	 * @return array
	 */
	function getProperties() {
		$props = array();
		$this->plugin->includeClass('class.ilObjPhotoGalleryAccess.php');
		if (! ilObjPhotoGalleryAccess::checkOnline($this->obj_id)) {
			$props[] = array(
				'alert' => true,
				'property' => $this->txt('status'),
				'value' => $this->txt('offline')
			);
		}

		return $props;
	}
}

?>
