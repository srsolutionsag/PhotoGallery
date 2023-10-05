<?php

/**
 * TableGUI srModelObjectTableGUI
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Zeynep Karahan <zk@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 */
class srObjAlbumTableGUI extends atTableGUI
{
    /**
     * @description returns false, if no filter is needed, otherwise implement filters
     */
    protected function initTableFilter(): bool
    {
        //the first version is without any filter
        return false;
        /*
        $this->setTitle($this->pl->txt('pics'));


        $this->setFilterCommand('applyFilter');
        $this->setResetCommand('resetFilter');
        $item = new ilTextInputGUI($this->pl->txt('pic_title'), 'title');
        $item->setSubmitFormOnEnter(true);
        $this->addFilterItem($item);
        $item->readFromSession();
        $this->filter['title'] = $item->getValue();*/
    }

    /**
     * @description return false or implements own form action and
     */
    protected function initFormActionsAndCmdButtons(): bool
    {
        $this->setFormAction($this->ctrl->getFormActionByClass(srObjPictureGUI::class));
        if (($this->access->checkAccess('write', '', $_GET['ref_id']))) {
            //$this->addHeaderCommand($this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, self::CMD_ADD), $this->pl->txt('add_new_pic'));
        }

        $this->setSelectAllCheckbox('picture_ids[]'); //add to checkbox in tpl
        $this->addMultiCommand(self::CMD_DOWNLOAD, $this->pl->txt('download'));

        $ref_id = $this->http->wrapper()->query()->retrieve('ref_id', $this->refinery->kindlyTo()->int());
        if (($this->access->checkAccess('write', '', $ref_id))) {
            $this->addMultiCommand(self::CMD_CONFIRM_DELETE, $this->pl->txt('delete'));
        }
        return true;
    }

    /**
     * @description returns false or set the following
     * @description e.g. override table id oder title: $this->table_id = 'myid', $this->table_title = 'My Title'
     */
    protected function initTableProperties(): bool
    {
        /**
         * @var $srObjAlbum srObjAlbum
         */
        $srObjAlbum = srObjAlbum::find($_GET['album_id']);
        $this->table_title = $srObjAlbum->getTitle();
        $this->table_id = 'alb' . $srObjAlbum->getId();
        return true;
    }

    /**
     * @description implement your fillRow
     */
    protected function fillTableRow(array $a_set): bool
    {
        $srObjPicture = srObjPicture::find($a_set['id']);
        $this->ctrl->setParameterByClass(srObjExifGUI::class, 'picture_id', ($a_set['id']));
        //$this->ctrl->setParameterByClass(srObjSliderGUI::class, 'album_id', ($_GET['album_id']));
        //$this->ctrl->setParameterByClass(srObjSliderGUI::class, 'picture_id', ($a_set['id']));
        $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', ($a_set['id']));
        $this->tpl->setVariable('TITLE', $a_set['title']);
        $this->tpl->setVariable('DESCRIPTION', $a_set['description']);
        $this->tpl->setVariable('DATE', date('d.m.Y', strtotime($a_set['create_date'])));

        $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_id', $srObjPicture->getId());
        $this->ctrl->setParameterByClass(srObjPictureGUI::class, 'picture_type', srObjPicture::TITLE_PREVIEW);
        $src_preview = $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, srObjPictureGUI::CMD_SEND_FILE);

        $this->tpl->setVariable('IMAGE', ilUtil::img($src_preview));

        $this->tpl->setCurrentBlock('edit_checkbox');
        $this->tpl->setVariable('ID', $a_set['id']);
        $this->tpl->parseCurrentBlock();

        $ref_id = $this->http->wrapper()->query()->retrieve('ref_id', $this->refinery->kindlyTo()->int());
        if (ilObjPhotoGalleryAccess::checkManageTabAccess($ref_id)) {
            $alist = new ilAdvancedSelectionListGUI();
            if (($this->access->checkAccess('write', '', $ref_id))) {
                $alist->setId($a_set['id']);
                $alist->setListTitle($this->pl->txt('actions'));
                $alist->addItem(
                    $this->pl->txt('edit'),
                    'edit',
                    $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, self::CMD_EDIT)
                );
                $alist->addItem(
                    $this->pl->txt('delete'),
                    'delete',
                    $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, self::CMD_CONFIRM_DELETE)
                );
            }
            $alist->addItem(
                $this->pl->txt('download'),
                'download',
                $this->ctrl->getLinkTargetByClass(srObjPictureGUI::class, self::CMD_DOWNLOAD)
            );
            $this->tpl->setVariable('ACTION', $alist->getHTML());
        }
        return true;
    }

    protected function initTableData(): void
    {
        /** @var srObjAlbum $album */
        $album = srObjAlbum::find((int) $_GET['album_id']);
        $this->setData(
            srObjPicture::where(['album_id' => (int) $_GET['album_id']], '=')->orderBy(
                $album->getSortType(),
                $album->getSortDirection()
            )->getArray()
        );
    }

    /**
     * @description returns false, if automatic columns are needed, otherwise implement your columns
     */
    protected function initTableColumns(): bool
    {
        $this->addColumn('', '', '1', true);
        $this->addColumn('', '', '100px');
        $this->addColumn($this->pl->txt('title'));
        $this->addColumn($this->pl->txt('description'));
        $this->addColumn($this->pl->txt('date'));
        $this->addColumn($this->pl->txt('actions'), '', '1');

        return true;
    }

    /**
     * @description returns false if standard-table-header is needes, otherwise implement your header
     */
    protected function initTableHeader(): bool
    {
        return false;
    }

    /**
     * @description returns false, if dynamic template is needed, otherwise implement your own template by $this->setRowTemplate($a_template, $a_template_dir = '')
     */
    protected function initTableRowTemplate(): bool
    {
        $this->setRowTemplate('tpl.album_row.html', $this->pl->getDirectory());
        return true;
    }

    /**
     * @description returns false, if global language is needed; implement your own language by setting $this->pl
     */
    protected function initLanguage(): bool
    {
        $this->pl = ilPhotoGalleryPlugin::getInstance();
        return true;
    }
}
