<?php
/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

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
        if ($ilAccess->checkAccess('rep_robj_xpho_download_images', '', $ref_id)) {
            return true;
        }
        if ($ilAccess->checkAccess('write', '', $ref_id)) {
            return true;
        }
        return (bool) $ilAccess->checkAccess('delete', '', $ref_id);
    }
}
