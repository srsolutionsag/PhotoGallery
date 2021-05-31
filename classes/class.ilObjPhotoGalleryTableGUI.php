<?php

/**
 * TableGUI srModelObjectTableGUI
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Zeynep Karahan <zk@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 */
class ilObjPhotoGalleryTableGUI extends atTableGUI
{

    /**
     * @return bool
     */
    protected function initTableFilter()
    {
        return false;
    }

    /**
     * @return bool
     * @description returns false or set the following
     * @description e.g. override table id oder title: $this->table_id = 'myid', $this->table_title = 'My Title'
     */
    protected function initTableProperties()
    {

        $this->table_title = ilObject2::_lookupTitle(ilObject2::_lookupObjId($_GET['ref_id']));
    }


    /**
     * @return bool
     * @description return false or implements own form action and
     */
    //TODO GET ersetzen
    protected function initFormActionsAndCmdButtons()
    {
        $this->setFormAction($this->ctrl->getFormActionByClass(srObjAlbumGUI::class));
        if (($this->access->checkAccess("write", "", $_GET['ref_id']))) {
            //	$this->addHeaderCommand($this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, self::CMD_ADD), $this->pl->txt('create_album'));
        }

        $this->setSelectAllCheckbox('album_ids[]'); //add to checkbox in tpl
        $this->addMultiCommand(self::CMD_DOWNLOAD, $this->pl->txt('download'));

        if (($this->access->checkAccess('write', '', $_GET['ref_id']))) {
            $this->addMultiCommand(self::CMD_CONFIRM_DELETE, $this->pl->txt('delete'));
        }
    }

    /**
     * @description implement your fillRow
     * @param $a_set
     * @return bool
     */
    protected function fillTableRow($a_set)
    {
        /**
         * @var $srObjAlbum srObjAlbum
         */
        $srObjAlbum = srObjAlbum::find($a_set['id']);

        $this->tpl->setVariable('TITLE', $a_set['title']);

        if ($srObjAlbum->getPreviewId() != null) {
            $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $srObjAlbum->getPreviewId());
            $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_type', srObjPicture::TITLE_PREVIEW);
            $src_preview = $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, srObjPictureGUI::CMD_SEND_FILE);

            $this->tpl->setVariable("IMAGE", ilUtil::img($src_preview));
        }
        $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $a_set['id']);
        $this->tpl->setVariable('LINK_TITLE', $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, srObjAlbumGUI::CMD_MANAGE_PICTURES));
        $this->tpl->setVariable('DESCRIPTION', $a_set['description']);
        $this->tpl->setVariable('DATE', date('d.m.Y', strtotime($a_set['create_date'])));
        $sortings = array(
            $this->pl->txt('sort_type_' . $srObjAlbum->getSortType()),
            $this->pl->txt('sort_direction_' . $srObjAlbum->getSortDirection()),
        );
        $this->tpl->setVariable('SORTING', implode(', ', $sortings));

        $this->tpl->setCurrentBlock("edit_checkbox");
        $this->tpl->setVariable("ID", $a_set["id"]);
        $this->tpl->parseCurrentBlock();

        //TODO GET ersetzen
        // show Action "Download"
        if (ilObjPhotoGalleryAccess::checkManageTabAccess($_GET['ref_id'])) {

            //action list
            $alist = new ilAdvancedSelectionListGUI();
            $alist->setId($a_set['id']);
            $alist->setListTitle($this->pl->txt("actions"));

            if (($this->access->checkAccess("write", "", $_GET['ref_id']))) {
                $alist->addItem($this->pl->txt('edit'), 'edit', $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, self::CMD_EDIT));
                $alist->addItem($this->pl->txt('delete'), 'delete', $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, self::CMD_CONFIRM_DELETE));
            }

            $alist->addItem($this->pl->txt('download'), 'download', $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, self::CMD_DOWNLOAD_ALBUM));
            $this->tpl->setVariable("ACTION", $alist->getHTML());
        }
    }

    /**
     * @return void
     * @description $this->setData(Your Array of Data)
     */
    protected function initTableData()
    {
        $this->setData(srObjAlbum::where(array('object_id' => ilObject::_lookupObjectId($_GET['ref_id'])), '=')->getArray());
    }

    /**
     * @return bool
     * @description returns false, if automatic columns are needed, otherwise implement your columnss
     */
    protected function initTableColumns()
    {
        $this->addColumn('', '', '1', true);
        $this->addColumn('', '', '100px');
        $this->addColumn($this->pl->txt('title'));
        $this->addColumn($this->pl->txt('description'));
        $this->addColumn($this->pl->txt('date'));
        $this->addColumn($this->pl->txt('sorting'));
        $this->addColumn($this->pl->txt('actions'), '', '1');
    }

    /**
     * @return bool
     * @description returns false if standard-table-header is needes, otherwise implement your header
     */
    protected function initTableHeader()
    {
    }

    /**
     * @return bool
     * @description returns false, if dynamic template is needed, otherwise implement your own template by $this->setRowTemplate($a_template, $a_template_dir = "")
     */
    protected function initTableRowTemplate()
    {
        $this->setRowTemplate('tpl.gallery_row.html', $this->pl->getDirectory());
    }

    /**
     * @return bool
     * @description returns false, if global language is needed; implement your own language by setting $this->pl
     */
    protected function initLanguage()
    {

        $this->pl = ilPhotoGalleryPlugin::getInstance();

        return false;
    }
}


