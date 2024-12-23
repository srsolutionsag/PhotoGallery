<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

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


    public function step(Environment $environment): void
    {
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
        // get one album which has not been migrated yet (the one amongst the unmigrated albums who has the lowest id)
        $album_query = $this->helper->getDatabase()->query(
            "SELECT album.id AS album_id, album.preview_id, album.user_id AS album_owner_id FROM sr_obj_pg_album AS album"
            . " JOIN (SELECT MIN(a.id) AS min_id FROM sr_obj_pg_album AS a WHERE a.album_collection_rid IS NULL OR a.album_collection_rid = '') AS calc ON calc.min_id = album.id;"
        );
        $album_entry = $this->helper->getDatabase()->fetchAssoc($album_query);

        // build empty collection for album which will be filled later (if the album has any pictures)
        $album_id = (int) $album_entry['album_id'];
        $album_collection = $this->helper->getCollectionBuilder()->new(ResourceCollection::NO_SPECIFIC_OWNER);

        // get the albums pictures - if there are any
        $pictures_query = $this->helper->getDatabase()->queryF(
            "SELECT picture.id AS picture_id, picture.title AS picture_title, picture.user_id AS picture_owner_id FROM sr_obj_pg_pic AS picture"
            . " JOIN sr_obj_pg_album AS album ON picture.album_id = album.id WHERE album.id = %s;",
            ['integer'],
            [$album_id]
        );
        $picture_dataset = $this->helper->getDatabase()->fetchAll($pictures_query);

        if (!empty($picture_dataset)) {
            $migrated_pictures = [];
            $missing_files = [];
            $preview_picture_rid = null;
            // iterate through the albums pictures and migrate them to the irss
            $i = 0;
            foreach ($picture_dataset as $picture_entry) {
                $picture_owner_id = (int)$picture_entry['picture_owner_id'];
                $picture_id = (int)$picture_entry['picture_id'];
                // copy original picture file to irss but leave the directory and files there in case something goes wrong
                // TODO: remove old files and directories in a future version (once this migration has proven itself)
                $path_to_file_dir = $this->buildPathToPictureDir($album_id, $picture_id);
                $absolute_path_to_original_file = glob($path_to_file_dir . '/original.*');
                if (!$absolute_path_to_original_file || !is_file($absolute_path_to_original_file[0])) {
                    $missing_files[$i]['picture_id'] = $picture_id;
                    $i++;
                    continue;
                }
                $resource_identification = $this->helper->movePathToStorage(
                    $absolute_path_to_original_file[0],
                    $picture_owner_id,
                    null,
                    null,
                    true
                );
                if ($resource_identification !== null) {
                    // change the title of the newly created revision from 'original' to the actual title of the picture
                    $current_revision = $irss_manager->getCurrentRevision($resource_identification);
                    $current_revision->setTitle($picture_entry['picture_title']);
                    $irss_manager->updateRevision($current_revision);
                    $album_collection->add($resource_identification);
                    $picture_rid = $resource_identification->serialize();
                    $migrated_pictures[$i]['picture_rid'] = $picture_rid;
                    $migrated_pictures[$i]['picture_id'] = $picture_id;
                    //check if the current picture is the preview picture of the album, if so remember this for the db update later on
                    if ((int) $album_entry['preview_id'] === $picture_id) {
                        $preview_picture_rid = $picture_rid;
                    }
                } else {
                    throw new ilException("Could not move file with picture id " . $picture_id . " to storage");
                }
                $i++;
            }
        }

        // store the collection of the album (must be done here so any added resources are stored with it)
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
        // update the picture's db table with the new resource ids - if there are any
        if (!empty($migrated_pictures)) {
            foreach ($migrated_pictures as $migrated_picture) {
                $this->helper->getDatabase()->update(
                    'sr_obj_pg_pic',
                    [
                        'picture_rid' => ['text', $migrated_picture['picture_rid']]
                    ],
                    [
                        'id' => ['integer', $migrated_picture['picture_id']]
                    ]
                );
            }
        }
        // update the picture's db table with a "failed" rid value for any missing files
        if (!empty($missing_files)) {
            foreach ($missing_files as $missing_file) {
                $this->helper->getDatabase()->update(
                    'sr_obj_pg_pic',
                    [
                        'picture_rid' => ['text', "failed"]
                    ],
                    [
                        'id' => ['integer', $missing_file['picture_id']]
                    ]
                );
            }
        }
    }


    public function getRemainingAmountOfSteps(): int
    {
        if (!$this->helper->getDatabase()->tableExists('sr_obj_pg_album')) {
            return 0;
        }

        $r = $this->helper->getDatabase()->query(
            "SELECT COUNT(DISTINCT(a.id)) AS amount FROM sr_obj_pg_album AS a"
            . " WHERE a.album_collection_rid IS NULL OR a.album_collection_rid = '';"
        );
        $d = $this->helper->getDatabase()->fetchObject($r);

        return (int) $d->amount;
    }

    protected function buildPathToPictureDir(int $album_id, int $picture_id): string
    {
        return CLIENT_DATA_DIR . '/xpho/album_' . $album_id . '/picture_' . $picture_id;
    }
}
