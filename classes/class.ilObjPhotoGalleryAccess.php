<?php
/*
    +-----------------------------------------------------------------------------+
    | ILIAS open source                                                           |
    +-----------------------------------------------------------------------------+
    | Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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

/**
 * Access/Condition checking for PhotoGallery object
 * Please do not create instances of large application classes (like ilObjPhotoGallery)
 * Write small methods within this class to determin the status.
 * @author        Fabian Schmid <fs@studer-raimann.ch>
 * @author        Martin Studer <ms@studer-raimann.ch>
 */
class ilObjPhotoGalleryAccess extends ilObjectPluginAccess
{
    /**
     * @param string $a_cmd
     * @param string $a_permission
     * @param int    $a_ref_id
     * @param int    $a_obj_id
     * @param string $a_user_id
     */
    public function _checkAccess(string $cmd, string $permission, int $ref_id, int $obj_id, ?int $user_id = null): bool
    {
        global $DIC;
        if ($user_id === null) {
            $user_id = $DIC->user()->getId();
        }
        return $DIC->access()->checkAccessOfUser($user_id, $permission, '', $ref_id);
    }

    /**
     * @param $a_id
     */
    public static function checkOnline($a_id): bool
    {
        return true;
    }

    // The manage tab is only displayed for user with at least one of theese rights: rep_robj_xpho_download_images, write, delete
    public static function checkManageTabAccess(int $ref_id): bool
    {
        global $DIC;
        $ilAccess = $DIC->access();

        return $ilAccess->checkAccess('rep_robj_xpho_download_images', '', $ref_id)
            || $ilAccess->checkAccess('write', '', $ref_id)
            || $ilAccess->checkAccess('delete', '', $ref_id);
    }
}
