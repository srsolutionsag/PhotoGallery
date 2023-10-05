<?php

/**
 * srObjExif
 * @author  Zeynep Karahan <zk@studer-raimann.ch>
 * delete this file ? is not used
 */
class srObjExif extends ActiveRecord
{
    public const TABLE_NAME = 'sr_obj_pg_exif_data';

    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @var int
     * @db_has_field  true
     * @db_fieldtype  integer
     * @db_length     1
     * @db_is_notnull true
     */
    protected $picture_id = 0;
    /**
     * @var string
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     * @db_is_primary       true
     */
    protected $id;
    /**
     * @var string
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $exif_title = '';
    /**
     * @var string
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $exif_entry = '';

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getPictureId()
    {
        return $this->picture_id;
    }

    /**
     * @param int $foto_id
     */
    public function setPictureId($picture_id): void
    {
        $this->picture_id = $picture_id;
    }

    /**
     * @return string
     */
    public function getExifTitle()
    {
        return $this->exif_title;
    }

    /**
     * @param string $exif_title
     */
    public function setExifTitle($exif_title): void
    {
        $this->exif_title = $exif_title;
    }

    /**
     * @return string
     */
    public function getExifEntry()
    {
        return $this->exif_entry;
    }

    /**
     * @param string $exif_entry
     */
    public function setExifEntry($exif_entry): void
    {
        $this->exif_entry = $exif_entry;
    }
}
