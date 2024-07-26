<#1>
<?php
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Picture/class.srObjPhotoData.php';
if (!$ilDB->tableExists(srObjPhotoData::TABLE_NAME)) {
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'is_online' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false
        ),
    );

    $ilDB->createTable(srObjPhotoData::TABLE_NAME, $fields);
    $ilDB->addPrimaryKey(srObjPhotoData::TABLE_NAME, array("id"));
}
?>

<#2>
<?php
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Album/class.srObjAlbum.php";
srObjAlbum::updateDB();
?>

<#3>
<?php
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Picture/class.srObjPicture.php";
srObjPicture::updateDB();
?>
<#4>
<?php
//Adding a new Permission rep_robj_xpho_download_images ("Download Images")
require_once("./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php");

$xpho_type_id = ilDBUpdateNewObjectType::addNewType(ilPhotoGalleryPlugin::PLUGIN_ID, 'Plugin Photogallery');

$offering_admin = ilDBUpdateNewObjectType::addCustomRBACOperation( //$a_id, $a_title, $a_class, $a_pos
    'rep_robj_xpho_download_images',
    'download images',
    'object',
    280
);
if ($offering_admin) {
    ilDBUpdateNewObjectType::addRBACOperation($xpho_type_id, $offering_admin);
}
?>
<#5>
<?php
// Introduction of sorting settings for pictures on album level
global $DIC;
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Album/class.srObjAlbum.php";
srObjAlbum::updateDB();
$DIC->database()->manipulate(
    "UPDATE " . srObjAlbum::TABLE_NAME . " SET sort_type = " . $ilDB->quote(
        srObjAlbum::SORT_TYPE_CREATE_DATE,
        'text'
    ) . ", sort_direction = " . $ilDB->quote(srObjAlbum::SORT_TYPE_DIRECTION_ASC, 'text')
);
?>
<#6>
<?php
// Add a new column which will store the album's collection resource id after the irss migration
global $DIC;
if (!$DIC->database()->tableColumnExists('sr_obj_pg_album', 'album_collection_rid')) {
    $DIC->database()->addTableColumn(
        'sr_obj_pg_album',
        'album_collection_rid',
        [
            'type' => 'text',
            'notnull' => false,
            'length' => 64,
            'default' => ''
        ]
    );
}
?>
<#7>
<?php
// Add a new column which will store the resource id of the album's of preview picture after the irss migration
global $DIC;
if (!$DIC->database()->tableColumnExists('sr_obj_pg_album', 'preview_picture_rid')) {
    $DIC->database()->addTableColumn(
        'sr_obj_pg_album',
        'preview_picture_rid',
        [
            'type' => 'text',
            'notnull' => false,
            'length' => 64,
            'default' => ''
        ]
    );
}
?>
<#8>
<?php
// Add a new column which will store the picture's resource id after the irss migration
global $DIC;
if (!$DIC->database()->tableColumnExists('sr_obj_pg_pic', 'picture_rid')) {
    $DIC->database()->addTableColumn(
        'sr_obj_pg_pic',
        'picture_rid',
        [
            'type' => 'text',
            'notnull' => false,
            'length' => 64,
            'default' => ''
        ]
    );
}
?>

