<?php
/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

use ILIAS\HTTP\Services;
use srag\Plugins\PhotoGallery\DIC;

/**
 * @author            Fabian Schmid <fabian@sr.solutions>
 *
 * @ilCtrl_isCalledBy ilPhotoGalleryDeliveryGUI: ilUIPluginRouterGUI
 */
class ilPhotoGalleryDeliveryGUI
{
    public const RID = "rid";
    private ilAccessHandler $access;
    private ilCtrlInterface $ctrl;
    private ilDBInterface $db;
    private Services $http;
    private \ILIAS\ResourceStorage\Services $irss;
    private ilObjUser $user;

    public function __construct()
    {
        global $xphoDIC;
        /** @var DIC $xphoDIC */
        $this->access = $xphoDIC->ilias()->access();
        $this->ctrl = $xphoDIC->ilias()->ctrl();
        $this->db = $xphoDIC->ilias()->database();
        $this->http = $xphoDIC->ilias()->http();
        $this->irss = $xphoDIC->ilias()->resourceStorage();
        $this->user = $xphoDIC->ilias()->user();
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd('deliver');
        if ($cmd === 'deliver') {
            $this->deliver();
        }
        // otherwise
        $this->notFound();
    }

    private function deliver(): void
    {
        $rid_string = $this->http->request()->getQueryParams()[self::RID] ?? null;
        if ($rid_string === null) {
            $this->notFound();
            return;
        }
        $rid = $this->irss->manage()->find($rid_string);
        if ($rid === null) {
            $this->notFound();
            return;
        }

        // check whether rid is from flavour, if so get picture rid as this is the one needed for further queries
        $flavour_query = $this->db->queryF(
            "SELECT rid FROM ilias.sr_obj_pg_flavour WHERE flavour_rid = %s;",
            ["text"],
            [$rid_string]
        );
        $flavour_result = $this->db->fetchObject($flavour_query);
        if($flavour_result !== null) {
            $rid_string = $flavour_result->rid;
        }

        // check stakeholder validity
        $xpho_stakeholder_id = "obj_photo_gallery";
        $stkh_query = $this->db->queryF(
            "SELECT COUNT(DISTINCT(rid)) AS matches FROM ilias.il_resource_stkh_u WHERE rid = %s AND stakeholder_id = %s;",
            ["text", "text"],
            [$rid_string, $xpho_stakeholder_id]
        );
        $stkh_result = $this->db->fetchObject($stkh_query);
        $has_valid_stakeholder = ($stkh_result !== null && $stkh_result->matches > 0);

        // check whether rid is part of a photo gallery album
        $album_query = $this->db->queryF(
            "SELECT COUNT(DISTINCT(album.id)) AS matches FROM sr_obj_pg_pic AS picture"
            ." JOIN sr_obj_pg_album AS album ON picture.album_id = album.id"
            ." WHERE picture.picture_rid = %s;",
            ["text"],
            [$rid_string]
        );
        $album_result = $this->db->fetchObject($album_query);
        $is_part_of_xpho_album = ($album_result !== null && $album_result->matches > 0);

        // obtain ref id of corresponding gallery and do rbac access check
        $gallery_query = $this->db->queryF(
            "SELECT obj_ref.ref_id AS gallery_ref_id FROM (SELECT album.object_id AS gallery_obj_id FROM sr_obj_pg_pic AS picture"
            ." JOIN sr_obj_pg_album AS album ON picture.album_id = album.id"
            ." WHERE picture.picture_rid = %s) AS result"
            ." JOIN object_reference As obj_ref ON result.gallery_obj_id = obj_ref.obj_id;",
            ["text"],
            [$rid_string]
        );
        $gallery_result = $this->db->fetchObject($gallery_query);
        $has_access_to_gallery = false;
        if($gallery_result !== null) {
            $has_access_to_gallery = $this->access->checkAccessOfUser($this->user->getId(), "read", "", $gallery_result->gallery_ref_id);
        }

        if (!$has_valid_stakeholder || !$is_part_of_xpho_album || !$has_access_to_gallery) {
            $this->notFound();
            return;
        }

        $this->irss->consume()->inline($rid)->run();
    }

    protected function notFound(): void
    {
        $this->http->saveResponse(
            $this->http->response()->withStatus(404)
        );
        $this->http->sendResponse();
        $this->http->close();
    }
}
