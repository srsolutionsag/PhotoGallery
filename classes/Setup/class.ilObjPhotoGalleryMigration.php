<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */

declare(strict_types=1);

use ILIAS\Setup\Migration;
use ILIAS\Setup\Environment;
use ILIAS\ResourceStorage\Collection\ResourceCollection;

/**
 * @author Lukas Zehnder <lukas@sr.solutions>
 */
class ilObjPhotoGalleryMigration implements Migration
{
    protected \ilResourceStorageMigrationHelper $helper;


    public function getLabel(): string
    {
        return "Migration of photo gallery albums and pictures to the resource storage service.";
    }


    public function getDefaultAmountOfStepsPerRun(): int
    {
        return 1000;
    }


    public function getPreconditions(Environment $environment): array
    {
        return \ilResourceStorageMigrationHelper::getPreconditions();
    }


    public function prepare(Environment $environment): void
    {
        $this->helper = new \ilResourceStorageMigrationHelper(
            new \ilObjPhotoGalleryStakeholder(),
            $environment
        );
    }


    public function step(Environment $environment): void //TODO: bei alben prüfen ob alle gallery alben migriert, wenn nicht keine alben in content anzeigen, stattdessen info dass migriert werden muss.
        //TODO: Readme um migrations-Vorgehen ergänzen. Ev. in After-Update (in Plugin-Klasse) von plugin prüfen, ob migration gemacht. Wenn nicht migrations-info ausgeben. Zudem prüfen ob in Web oder cli kontext via (PHP_SAPI !== 'cli') damit nur msg angezeigt in Webkontext.
    {
        //TODO: change migration to have multiple queries. first to get an album without an rid (albums shall get a collection whether they have pictures or not)
        //TODO: afterwards get the album's pictures if it has any, copy them to the irss and add their rids to the collection
        //TODO: also change the remaining steps query to check whether there are any albums without collection rid (and maybe pictures without rid)
        //first check if the needed columns exist (unfortunately checking for them in the pre-conditions didn't work as the database was not yet available)
        $collection_column_exists = $this->helper->getDatabase()->tableColumnExists('sr_obj_pg_album', 'album_collection_rid');
        $preview_column_exists = $this->helper->getDatabase()->tableColumnExists('sr_obj_pg_album', 'preview_picture_rid');
        if (!$collection_column_exists) {
            throw new ilException("The needed column album_collection_rid does not exist in the sr_obj_pg_album table.");
        }
        if (!$preview_column_exists) {
            throw new ilException("The needed column preview_picture_rid does not exist in the sr_obj_pg_album table.");
        }

        $irss_manager = $this->helper->getManager();
        $query = $this->helper->getDatabase()->query(
            "SELECT album.id AS album_id, album.preview_id, album.user_id AS album_owner_id, picture.id AS picture_id, picture.title AS picture_title, picture.user_id AS picture_owner_id FROM sr_obj_pg_album AS album"
            . " JOIN sr_obj_pg_pic AS picture ON picture.album_id = album.id"
            . " JOIN (SELECT MIN(a1.id) AS min_id FROM sr_obj_pg_album AS a1 JOIN sr_obj_pg_pic AS p ON p.album_id = a1.id"
            . " JOIN (SELECT a.id FROM sr_obj_pg_album AS a WHERE album_collection_rid IS NULL OR album_collection_rid = '') AS a2 ON a1.id = a2.id) AS calc"
            . " ON calc.min_id = album.id"
        );
        $dataset = $this->helper->getDatabase()->fetchAll($query);

        // build empty collection for album which will be filled later
        $album_id = (int)$dataset[0]['album_id'];
        $album_collection = $this->helper->getCollectionBuilder()->new(ResourceCollection::NO_SPECIFIC_OWNER);

        // only move original picture files to irss (other files - mosaic.png, presentation.png, preview.png - are not needed as the irss can now handle that)
        $picture_rids = [];
        $preview_picture_rid = null;
        foreach ($dataset as $entry) {
            $picture_owner_id = (int)$entry['picture_owner_id'];
            $picture_id = (int)$entry['picture_id'];
            $file_path = $this->buildAbsolutePathToOriginalPicture($album_id, $picture_id );
            // copy original picture file to irss but leave the directory and files there in case something goes wrong
            // TODO: remove old files and directories in a future version (once this migration has proven itself)
            $resource_identification = $this->helper->movePathToStorage(
                $file_path,
                $picture_owner_id,
                null,
                null,
                true
            );
            if ($resource_identification !== null) {
                // change the title of the newly created revision from 'original' to the actual title of the picture
                $current_revision = $irss_manager->getCurrentRevision($resource_identification);
                $current_revision->setTitle($entry['picture_title']);
                $irss_manager->updateRevision($current_revision);
                $album_collection->add($resource_identification);
                $picture_rid = $resource_identification->serialize();
                $picture_rids[] = $picture_rid;
                //check if the current picture is the preview picture of the album, if so remember this for the db update later on
                if ((int)$entry['preview_id'] === $picture_id) {
                    $preview_picture_rid = $picture_rid;
                }
            } else {
                throw new ilException("Could not move file with picture id " . $picture_id . " to storage");
            }
        }
        // store the collection of the album (must be here so added resources are stored with it)
        if ($this->helper->getCollectionBuilder()->store($album_collection)) {
            $album_collection_rid = $album_collection->getIdentification()->serialize();
        } else {
            throw new ilException("Could not build collection of album with id " . $album_id);
        }

        // update the album's db table with the new collection resource id
        $this->helper->getDatabase()->update(
            'sr_obj_pg_album',
            [
                'album_collection_rid' => ['text', $album_collection_rid]
            ],
            [
                'id' => ['integer', $album_id]
            ]
        );

        // update the album's db table with the new preview picture resource id if there is one
        if ($preview_picture_rid !== null) {
            $this->helper->getDatabase()->update(
                'sr_obj_pg_album',
                [
                    'preview_picture_rid' => ['text', $preview_picture_rid]
                ],
                [
                    'id' => ['integer', $album_id]
                ]
            );
        }

        // update the picture's db table with the new resource ids
        foreach ($picture_rids as $picture_rid) {
            $this->helper->getDatabase()->update(
                'sr_obj_pg_pic',
                [
                    'picture_rid' => ['text', $picture_rid]
                ],
                [
                    'id' => ['integer', $picture_id]
                ]
            );
        }
    }


    public function getRemainingAmountOfSteps(): int
    {
        $r = $this->helper->getDatabase()->query(
            "SELECT COUNT(DISTINCT(a.id)) AS amount FROM sr_obj_pg_album AS a"
            . " JOIN sr_obj_pg_pic AS p ON p.album_id = a.id"
            . " WHERE a.album_collection_rid IS NULL OR a.album_collection_rid = '';"
        );
        $d = $this->helper->getDatabase()->fetchObject($r);

        return (int)$d->amount;
    }


    protected function buildAbsolutePathToOriginalPicture(int $album_id, int $picture_id): string
    {
        return CLIENT_DATA_DIR . '/xpho/album_' . $album_id . '/picture_' . $picture_id . "/original.png";
    }

}
