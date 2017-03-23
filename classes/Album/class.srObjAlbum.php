<?php
require_once('./Services/ActiveRecord/class.ActiveRecord.php');

/**
 * srObjAlbum
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Zeynep Karahan <zk@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 *
 * @version 1
 */
class srObjAlbum extends ActiveRecord {

	/**
	 * @return string
	 */
	public static function returnDbTableName() {
		return 'sr_obj_pg_album';
	}

	const SORT_TYPE_CREATE_DATE = 'create_date';
	const SORT_TYPE_TITLE = 'title';
	const SORT_TYPE_DIRECTION_ASC = 'asc';
	const SORT_TYPE_DIRECTION_DESC = 'desc';

	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $title = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 * @db_is_primary       true
	 * @con_sequence        true
	 */
	protected $id;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           4000
	 */
	protected $description = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        date
	 */
	protected $create_date;
	/**
	 * @var int
	 *
	 * @db_has_field  true
	 * @db_fieldtype  integer
	 * @db_length     4
	 * @db_is_notnull true
	 */
	protected $user_id = 0;
	/**
	 * @var int
	 *
	 * @db_has_field  true
	 * @db_fieldtype  integer
	 * @db_length     4
	 * @db_is_notnull true
	 */
	protected $object_id = 0;
	/**
	 * @var int
	 *
	 * @db_has_field  true
	 * @db_fieldtype  integer
	 * @db_length     4
	 * @db_is_notnull true
	 */
	protected $preview_id = 0;

	/**
	 * @var string
	 *
	 * @db_has_field  true
	 * @db_fieldtype  text
	 * @db_length     16
	 * @db_is_notnull true
	 */
	protected $sort_type = self::SORT_TYPE_CREATE_DATE;

	/**
	 * @var string
	 *
	 * @db_has_field  true
	 * @db_fieldtype  text
	 * @db_length     16
	 * @db_is_notnull true
	 */
	protected $sort_direction = self::SORT_TYPE_DIRECTION_ASC;

	/**
	 * @var array
	 */
	public static $sort_types = array(
		self::SORT_TYPE_CREATE_DATE,
		self::SORT_TYPE_TITLE,
	);

	//
	// Setter & Getter
	//
	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param string $create_date
	 */
	public function setCreateDate($create_date) {
		$this->create_date = $create_date;
	}


	/**
	 * @return string
	 */
	public function getCreateDate() {
		return $this->create_date;
	}


	/**
	 * @return int
	 */
	public function getUserId() {
		return $this->user_id;
	}


	/**
	 * @param int $user_id
	 */
	public function setUserId($user_id) {
		$this->user_id = $user_id;
	}


	/**
	 * @return int
	 */
	public function getObjectId() {
		return $this->object_id;
	}


	/**
	 * @param int $object_id
	 */
	public function setObjectId($object_id) {
		$this->object_id = $object_id;
	}


	/**
	 * @return int
	 */
	public function getPreviewId() {
		return (int)$this->preview_id;
	}


	/**
	 * @param int $preview_id
	 */
	public function setPreviewId($preview_id) {
		$this->preview_id = $preview_id;
	}


	/**
	 * @return string
	 */
	public function getPreviewWebSrc() {
		if ($this->getPreviewId() > 0) {
			$obj_picture = srObjPicture::find($this->getPreviewId());

			return $obj_picture->getPreviewWebSrc();
		} else {
			return './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/images/nopreview.jpg';
		}
	}


	/**
	 * @return string
	 */
	public function getMosaicWebSrc() {
		if ($this->getPreviewId() > 0) {
			/**
			 * @var srObjPicture $srObjPicture
			 */
			$srObjPicture = srObjPicture::find($this->getPreviewId());

			return $srObjPicture->getMosaicWebSrc();
		} else {
			return './Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/templates/images/nopreview.jpg';
		}
	}


	/**
	 * @return srObjPicture[]
	 */
	public function getPictureObjects() {
		return srObjPicture::where(array( 'album_id' => $this->getId() ))->orderBy($this->getSortType(), $this->getSortDirection())->get();
	}


	/**
	 * @return int
	 */
	public function getPictureCount() {
		return srObjPicture::where(array( 'album_id' => $this->getId() ))->count();
	}


	/**
	 * @return string
	 */
	public function getAlbumPath() {
		return CLIENT_WEB_DIR . '/xpho/album_' . $this->getId();
	}

	/**
	 * @return string
	 */
	public function getSortType() {
		return $this->sort_type;
	}

	/**
	 * @param string $sort_type
	 */
	public function setSortType($sort_type) {
		$this->sort_type = $sort_type;
	}

	/**
	 * @return string
	 */
	public function getSortDirection() {
		return $this->sort_direction;
	}

	/**
	 * @param string $sort_direction
	 */
	public function setSortDirection($sort_direction) {
		$this->sort_direction = $sort_direction;
	}


	public function delete() {
		parent::delete();
		ilUtil::delDir($this->getAlbumPath());
	}
}