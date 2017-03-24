<#1>
<?php
    if (!$ilDB->tableExists('rep_robj_xpho_data')) {
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

    $ilDB->createTable("rep_robj_xpho_data", $fields);
    $ilDB->addPrimaryKey("rep_robj_xpho_data", array("id"));
    }
?>

<#2>
<?php
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Album/class.srObjAlbum.php";
srObjAlbum::installDB();
?>

<#3>
<?php
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Picture/class.srObjPicture.php";
srObjPicture::installDB();
?>
<#4>
<?php
//Adding a new Permission rep_robj_xpho_download_images ("Download Images")
require_once("./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php");

$xpho_type_id = ilDBUpdateNewObjectType::addNewType('xpho', 'Plugin Photogallery');

$offering_admin = ilDBUpdateNewObjectType::addCustomRBACOperation( //$a_id, $a_title, $a_class, $a_pos
	'rep_robj_xpho_download_images', 'download images', 'object', 280);
if($offering_admin)
{
	ilDBUpdateNewObjectType::addRBACOperation($xpho_type_id, $offering_admin);
}
?>
<#5>
<?php
// Introduction of sorting settings for pictures on album level
global $ilDB;
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Album/class.srObjAlbum.php";
srObjAlbum::updateDB();
$ilDB->manipulate("UPDATE sr_obj_pg_album SET sort_type = " . $ilDB->quote(srObjAlbum::SORT_TYPE_CREATE_DATE, 'text') . ", sort_direction = " . $ilDB->quote(srObjAlbum::SORT_TYPE_DIRECTION_ASC, 'text'));
?>