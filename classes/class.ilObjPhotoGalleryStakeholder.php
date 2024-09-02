<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;

/**
 * @author Lukas Zehnder <lukas@sr.solutions>
 */
class ilObjPhotoGalleryStakeholder extends AbstractResourceStakeholder
{
    protected int $owner = 6;
    private int $current_user;
    protected ?ilDBInterface $database = null;

    /**
     * @inheritDoc
     */
    public function __construct(int $owner = 6)
    {
        global $DIC;
        $this->current_user = (int) ($DIC->isDependencyAvailable('user')
            ? $DIC->user()->getId()
            : (defined('ANONYMOUS_USER_ID') ? ANONYMOUS_USER_ID : 6));
        $this->owner = $owner;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return 'obj_photo_gallery';
    }

    public function getOwnerOfNewResources(): int
    {
        return $this->owner;
    }

    public function canBeAccessedByCurrentUser(ResourceIdentification $identification): bool
    {
        global $DIC;

        $gallery_object_id = $this->resolveGalleryObjectId($identification);
        if ($gallery_object_id === null) {
            return true;
        }

        $ref_ids = ilObject2::_getAllReferences($gallery_object_id);
        foreach ($ref_ids as $ref_id) {
            // one must have read permissions on the photo gallery object to see the picture files
            if ($DIC->access()->checkAccessOfUser($this->current_user, 'read', '', $ref_id)) {
                return true;
            }
        }

        return false;
    }

    public function resourceHasBeenDeleted(ResourceIdentification $identification): bool
    {
        // at this place we could handle de deletion of a resource. currently not needed for photo gallery.
        return true;
    }

    public function getLocationURIForResourceUsage(ResourceIdentification $identification): ?string
    {
        $this->initDB();
        $gallery_object_id = $this->resolveGalleryObjectId($identification);
        if ($gallery_object_id !== null) {
            $gallery_references = ilObject::_getAllReferences($gallery_object_id);
            $gallery_ref_id = array_shift($gallery_references);

            // we currently deliver the goto-url of the photo gallery object in which the resource is used. in the future we might change this to deliver a more specific url leading directly to the album.
            return ilLink::_getLink($gallery_ref_id, 'xpho');
        }
        return null;
    }

    private function resolveGalleryObjectId(ResourceIdentification $identification): ?int
    {
        $this->initDB();
        $r = $this->database->queryF(
            "SELECT album.object_id, rca.rcid FROM il_resource_rca AS rca JOIN sr_obj_pg_album AS album ON album.album_collection_rid = rca.rcid WHERE rca.rid = %s;",
            ['text'],
            [$identification->serialize()]
        );
        $d = $this->database->fetchObject($r);

        return (isset($d->object_id) ? (int) $d->object_id : null);
    }

    private function initDB(): void
    {
        global $DIC;
        if ($this->database === null) {
            $this->database = $DIC->database();
        }
    }
}
