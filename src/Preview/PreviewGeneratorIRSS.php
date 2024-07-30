<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

declare(strict_types=1);

namespace srag\Plugins\PhotoGallery\Preview;

use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use srag\Plugins\PhotoGallery\URL\URLBuilder;
use ILIAS\Filesystem\Stream\Streams;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class PreviewGeneratorIRSS implements PreviewGenerator
{
    private URLBuilder $url_builder;
    private \ilDBInterface $db;
    private \ILIAS\ResourceStorage\Services $irss;

    public function __construct(URLBuilder $url_builder)
    {
        global $DIC;
        $this->url_builder = $url_builder;
        $this->db = $DIC->database();
        $this->irss = $DIC->resourceStorage();
    }

    private function exists(ResourceIdentification $rid, int $max_size): ?ResourceIdentification
    {
        // check if we have a preview for this resource
        $result = $this->db->queryF(
            'SELECT flavour_rid FROM sr_obj_pg_flavour WHERE rid = %s AND max_size = %s',
            ['text', 'integer'],
            [$rid->serialize(), $max_size]
        );
        $flavour_rid_string = $this->db->fetchAssoc($result)['flavour_rid'] ?? null;
        if ($flavour_rid_string === null) {
            return null;
        }
        return $this->irss->manage()->find($flavour_rid_string);
    }

    public function getURL(ResourceIdentification $rid, int $max_size): string
    {
        if ($flavour_rid = $this->exists($rid, $max_size)) {
            return $this->url_builder->getForRid($flavour_rid);
        }
        // otherwise we generate the preview image, store it to the IRSS and deliver that
        $new_rid = $this->irss->manage()->find($this->generate($rid, $max_size));

        return $this->url_builder->getForRid($new_rid ?? $rid);
    }

    public function generate(ResourceIdentification $rid, int $max_size): string
    {
        if ($existing = $this->exists($rid, $max_size)) {
            return $existing->serialize();
        }

        $original_file_path = $this->irss->consume()->stream($rid)->getStream()->getMetadata()['uri'] ?? '';
        if (!file_exists($original_file_path)) {
            return '';
        }

        // build temp path for file
        // $temp_path = rtrim(ILIAS_DATA_DIR, '/') . '/temp/' . uniqid('xpho', true);
        $temp_path = uniqid('xpho', true);

        $crop = "-resize " . $max_size . "x" . $max_size . "^ -gravity Center -crop " . $max_size . "x" . $max_size . "+0+0 +repage ";
        $convert_cmd = \ilShellUtil::escapeShellArg($original_file_path) . " " . $crop . \ilShellUtil::escapeShellArg(
                $temp_path
            );
        \ilShellUtil::execConvert($convert_cmd);

        $flavour_rid = $this->irss->manage()->stream(
            Streams::ofResource(fopen($temp_path, 'rb')),
            new \ilObjPhotoGalleryStakeholder()
        );

        // store to db
        $this->db->insert(
            'sr_obj_pg_flavour',
            [
                'rid' => ['text', $rid->serialize()],
                'max_size' => ['integer', $max_size],
                'flavour_rid' => ['text', $flavour_rid->serialize()]
            ]
        );

        return $flavour_rid->serialize();
    }

}
