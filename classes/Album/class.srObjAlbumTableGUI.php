<?php
//require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.srModelObjectTableGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/class.atTableGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Picture/class.srObjPicture.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');

/**
 * TableGUI srModelObjectTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Zeynep Karahan <zk@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 *
 * @version $Id:
 *
 */
class srObjAlbumTableGUI extends atTableGUI {

	/**
	 * @return bool
	 * @description returns false, if no filter is needed, otherwise implement filters
	 *
	 */
	protected function initTableFilter() {
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
	 * @return bool
	 * @description return false or implements own form action and
	 */
	//TODO GET ersetzen
	protected function initFormActionsAndCmdButtons() {
		$this->setFormAction($this->ctrl->getFormActionByClass('srobjpicturegui'));
		if (($this->access->checkAccess('write', '', $_GET['ref_id']))) {
			//$this->addHeaderCommand($this->ctrl->getLinkTargetByClass('srObjPictureGUI', 'add'), $this->pl->txt('add_new_pic'));
		}

		$this->setSelectAllCheckbox('picture_ids[]'); //add to checkbox in tpl
		$this->addMultiCommand('download', $this->pl->txt('download'));

		if (($this->access->checkAccess('write', '', $_GET['ref_id']))) {
			$this->addMultiCommand('confirmDelete', $this->pl->txt('delete'));
		}
	}


	/**
	 * @return bool
	 * @description returns false or set the following
	 * @description e.g. override table id oder title: $this->table_id = 'myid', $this->table_title = 'My Title'
	 */
	protected function initTableProperties() {
		/**
		 * @var $srObjAlbum srObjAlbum
		 */
		$srObjAlbum = srObjAlbum::find($_GET['album_id']);
		$this->table_title = $srObjAlbum->getTitle();
		$this->table_id = 'alb' . $srObjAlbum->getId();
	}


	/**
	 * @description implement your fillRow
	 *
	 * @param $a_set
	 *
	 * @return bool
	 */
	protected function fillTableRow($a_set) {
		$srObjPicture = srObjPicture::find($a_set['id']);
		$this->ctrl->setParameterByClass('srObjExifGUI', 'picture_id', ($a_set['id']));
		//$this->ctrl->setParameterByClass('srObjSliderGUI', 'album_id', ($_GET['album_id']));
		//$this->ctrl->setParameterByClass('srObjSliderGUI', 'picture_id', ($a_set['id']));
		$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_id', ($a_set['id']));
		$this->tpl->setVariable('TITLE', $a_set['title']);
		$this->tpl->setVariable('DESCRIPTION', $a_set['description']);
		$this->tpl->setVariable('DATE', date('d.m.Y', strtotime($a_set['create_date'])));

		$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_id', $srObjPicture->getId());
		$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_type', srObjPicture::TITLE_PREVIEW);
		$src_preview = $this->ctrl->getLinkTargetByClass("srObjPictureGUI", "sendFile");

		$this->tpl->setVariable('IMAGE', ilUtil::img($src_preview));

		$this->tpl->setCurrentBlock('edit_checkbox');
		$this->tpl->setVariable('ID', $a_set['id']);
		$this->tpl->parseCurrentBlock();

		//TODO GET ersetzen
		if (ilObjPhotoGalleryAccess::checkManageTabAccess($_GET['ref_id'])) {
			$alist = new ilAdvancedSelectionListGUI();
			if (($this->access->checkAccess('write', '', $_GET['ref_id']))) {
				$alist->setId($a_set['id']);
				$alist->setListTitle($this->pl->txt('actions'));
				$alist->addItem($this->pl->txt('edit'), 'edit', $this->ctrl->getLinkTargetByClass('srObjPictureGUI', 'edit'));
				$alist->addItem($this->pl->txt('delete'), 'delete', $this->ctrl->getLinkTargetByClass('srObjPictureGUI', 'confirmDelete'));
			}
			$alist->addItem($this->pl->txt('download'), 'download', $this->ctrl->getLinkTargetByClass('srObjPictureGUI', 'download'));
			$this->tpl->setVariable('ACTION', $alist->getHTML());
		}
	}


	protected function initTableData() {
		/** @var srObjAlbum $album */
		$album = srObjAlbum::find((int)$_GET['album_id']);
		$this->setData(srObjPicture::where(array(
			'album_id' => (int)$_GET['album_id']), '='
		)
			->orderBy($album->getSortType(), $album->getSortDirection())
			->getArray());
	}


	/**
	 * @return bool
	 * @description returns false, if automatic columns are needed, otherwise implement your columns
	 */
	protected function initTableColumns() {
		$this->addColumn('', '', '1', true);
		$this->addColumn('', '', '100px');
		$this->addColumn($this->pl->txt('title'));
		$this->addColumn($this->pl->txt('description'));
		$this->addColumn($this->pl->txt('date'));
		$this->addColumn($this->pl->txt('actions'), '', '1');
	}


	/**
	 * @return bool
	 * @description returns false if standard-table-header is needes, otherwise implement your header
	 */
	protected function initTableHeader() {
		return false;
	}


	/**
	 * @return bool
	 * @description returns false, if dynamic template is needed, otherwise implement your own template by $this->setRowTemplate($a_template, $a_template_dir = '')
	 */
	protected function initTableRowTemplate() {
		$this->setRowTemplate('tpl.album_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery');
	}


	/**
	 * @return bool
	 * @description returns false, if global language is needed; implement your own language by setting $this->pl
	 */
	protected function initLanguage() {
		$this->pl = new ilPhotoGalleryPlugin();
	}
}

?>