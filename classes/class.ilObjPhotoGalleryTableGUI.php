<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/Album/class.srObjAlbum.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.atTableGUI.php');

/**
 * TableGUI srModelObjectTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Zeynep Karahan <zk@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 *
 * @version $Id:
 */
class ilObjPhotoGalleryTableGUI extends atTableGUI {

	/**
	 * @return bool
	 */
	protected function initTableFilter() {
		return false;
	}


	/**
	 * @return bool
	 * @description returns false or set the following
	 * @description e.g. override table id oder title: $this->table_id = 'myid', $this->table_title = 'My Title'
	 */
	protected function initTableProperties() {
		require_once('./Services/Object/classes/class.ilObject2.php');
		$this->table_title = ilObject2::_lookupTitle(ilObject2::_lookupObjId($_GET['ref_id']));
	}


	/**
	 * @return bool
	 * @description return false or implements own form action and
	 */
	//TODO GET ersetzen
	protected function initFormActionsAndCmdButtons() {
		$this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
		if (($this->access->checkAccess("write", "", $_GET['ref_id']))) {
			//	$this->addHeaderCommand($this->ctrl->getLinkTargetByClass('srObjAlbumGUI', 'add'), $this->pl->txt('create_album'));
		}
	}


	/**
	 * @description implement your fillRow
	 *
	 * @param $a_set
	 *
	 * @return bool
	 */
	protected function fillTableRow($a_set) {
		/**
		 * @var $srObjAlbum srObjAlbum
		 */
		$srObjAlbum = srObjAlbum::find($a_set['id']);

		$this->tpl->setVariable('TITLE', $a_set['title']);

		if ($srObjAlbum->getPreviewId() != NULL) {
			$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_id', $srObjAlbum->getPreviewId());
			$this->ctrl->setParameterByClass('srObjPictureGUI', 'picture_type', srObjPicture::TITLE_PREVIEW);
			$src_preview = $this->ctrl->getLinkTargetByClass("srObjPictureGUI", "sendFile");

			$this->tpl->setVariable("IMAGE", ilUtil::img($src_preview));
		}
		$this->ctrl->setParameterByClass('srObjAlbumGUI', 'album_id', $a_set['id']);
		$this->tpl->setVariable('LINK_TITLE', $this->ctrl->getLinkTargetByClass('srObjAlbumGUI', 'managePictures'));
		$this->tpl->setVariable('DESCRIPTION', $a_set['description']);
		$this->tpl->setVariable('DATE', date('d.m.Y', strtotime($a_set['create_date'])));
		$sortings = array(
			$this->pl->txt('sort_type_' . $srObjAlbum->getSortType()),
			$this->pl->txt('sort_direction_' . $srObjAlbum->getSortDirection()),
		);
		$this->tpl->setVariable('SORTING', implode(', ', $sortings));
		if ($this->parent_cmd == 'manage') {
			$this->tpl->setCurrentBlock("edit_checkbox");
			$this->tpl->setVariable("ID", $a_set["id"]);
			$this->tpl->parseCurrentBlock();
			//checkbox, select all option
			$this->addMultiCommand('downloadAlbum', $this->pl->txt('download'));
			$this->addMultiCommand('confirmDelete', $this->pl->txt('delete'));
			$this->setSelectAllCheckbox('album_ids[]'); //add to checkbox in tpl
		}

		//TODO GET ersetzen
		// show Action "Download"
		if (ilObjPhotoGalleryAccess::checkManageTabAccess($_GET['ref_id'])) {

			//action list
			$alist = new ilAdvancedSelectionListGUI();
			$alist->setId($a_set['id']);
			$alist->setListTitle($this->pl->txt("actions"));

			if (($this->access->checkAccess("write", "", $_GET['ref_id']))) {
				$alist->addItem($this->pl->txt('edit'), 'edit', $this->ctrl->getLinkTargetByClass("srObjAlbumGUI", 'edit'));
				$alist->addItem($this->pl->txt('delete'), 'delete', $this->ctrl->getLinkTargetByClass("srObjAlbumGUI", 'confirmDelete'));
			}

			$alist->addItem($this->pl->txt('download'), 'download', $this->ctrl->getLinkTargetByClass("srObjAlbumGUI", 'downloadAlbum'));
			$this->tpl->setVariable("ACTION", $alist->getHTML());
		}
	}


	/**
	 * @return void
	 * @description $this->setData(Your Array of Data)
	 */
	protected function initTableData() {
		$this->setData(srObjAlbum::where(array('object_id' => ilObject::_lookupObjectId($_GET['ref_id'])), '=')->getArray());
	}


	/**
	 * @return bool
	 * @description returns false, if automatic columns are needed, otherwise implement your columnss
	 */
	protected function initTableColumns() {
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
	protected function initTableHeader() {
	}


	/**
	 * @return bool
	 * @description returns false, if dynamic template is needed, otherwise implement your own template by $this->setRowTemplate($a_template, $a_template_dir = "")
	 */
	protected function initTableRowTemplate() {
		$this->setRowTemplate('tpl.gallery_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery');
	}


	/**
	 * @return bool
	 * @description returns false, if global language is needed; implement your own language by setting $this->pl
	 */
	protected function initLanguage() {
		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/PhotoGallery/classes/class.ilPhotoGalleryPlugin.php');
		$this->pl = ilPhotoGalleryPlugin::getInstance();

		return false;
	}
}

?>