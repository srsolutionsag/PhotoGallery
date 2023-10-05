<?php

/**
 * srObjAlbum
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Zeynep Karahan <zk@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 */
class srObjAlbum extends ActiveRecord
{
    public const TABLE_NAME = 'sr_obj_pg_album';

    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }

    public const SORT_TYPE_CREATE_DATE = 'create_date';
    public const SORT_TYPE_TITLE = 'title';
    public const SORT_TYPE_DIRECTION_ASC = 'asc';
    public const SORT_TYPE_DIRECTION_DESC = 'desc';
    /**
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected string $title = '';
    /**
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     * @db_is_primary       true
     * @con_sequence        true
     */
    protected ?int $id = null;
    /**
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           4000
     */
    protected string $description = '';
    /**
     * @db_has_field        true
     * @db_fieldtype        date
     */
    protected ?string $create_date = null;
    /**
     * @var int
     * @db_has_field  true
     * @db_fieldtype  integer
     * @db_length     4
     * @db_is_notnull true
     */
    protected $user_id = 0;
    /**
     * @var int
     * @db_has_field  true
     * @db_fieldtype  integer
     * @db_length     4
     * @db_is_notnull true
     */
    protected $object_id = 0;
    /**
     * @var int
     * @db_has_field  true
     * @db_fieldtype  integer
     * @db_length     4
     * @db_is_notnull true
     */
    protected $preview_id = 0;
    /**
     * @var string
     * @db_has_field  true
     * @db_fieldtype  text
     * @db_length     16
     * @db_is_notnull true
     */
    protected $sort_type = self::SORT_TYPE_CREATE_DATE;
    /**
     * @var string
     * @db_has_field  true
     * @db_fieldtype  text
     * @db_length     16
     * @db_is_notnull true
     */
    protected $sort_direction = self::SORT_TYPE_DIRECTION_ASC;
    /**
     * @var array
     */
    public static $sort_types = [self::SORT_TYPE_CREATE_DATE, self::SORT_TYPE_TITLE];
    //
    // Setter & Getter
    //
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setCreateDate(string $create_date): void
    {
        $this->create_date = $create_date;
    }

    public function getCreateDate(): string
    {
        return $this->create_date;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getObjectId(): int
    {
        return $this->object_id;
    }

    public function setObjectId(int $object_id): void
    {
        $this->object_id = $object_id;
    }

    public function getPreviewId(): int
    {
        return $this->preview_id;
    }

    public function setPreviewId(int $preview_id): void
    {
        $this->preview_id = $preview_id;
    }

    public function getMosaicWebSrc(): string
    {
        $pl = ilPhotoGalleryPlugin::getInstance();
        if ($this->getPreviewId() > 0) {
            /**
             * @var srObjPicture $srObjPicture
             */
            $srObjPicture = srObjPicture::find($this->getPreviewId());

            return $srObjPicture->getMosaicWebSrc();
        } else {
            return $pl->getDirectory() . '/templates/images/nopreview.jpg';
        }
    }

    /**
     * @return srObjPicture[]
     */
    public function getPictureObjects(): array
    {
        return srObjPicture::where(['album_id' => $this->getId()])->orderBy(
            $this->getSortType(),
            $this->getSortDirection()
        )->get();
    }

    public function getPictureCount(): int
    {
        return srObjPicture::where(['album_id' => $this->getId()])->count();
    }

    public function getAlbumPath(): string
    {
        return CLIENT_WEB_DIR . '/xpho/album_' . $this->getId();
    }

    public function getSortType(): string
    {
        return $this->sort_type;
    }

    public function setSortType(string $sort_type): void
    {
        $this->sort_type = $sort_type;
    }

    public function getSortDirection(): string
    {
        return $this->sort_direction;
    }

    public function setSortDirection(string $sort_direction): void
    {
        $this->sort_direction = $sort_direction;
    }

    public function delete(): void
    {
        parent::delete();
        ilFileUtils::delDir($this->getAlbumPath());
    }
}
