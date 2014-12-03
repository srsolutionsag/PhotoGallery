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
