<?php
/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

use ILIAS\DI\UIServices;
use ILIAS\HTTP\Services as HttpService;
use ILIAS\ResourceStorage\Services as ResourceStorage;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use srag\Plugins\PhotoGallery\Preview\PreviewGenerator;

/**
 * @author            Lukas Zehnder <lukas@sr.solutions>
 * @author            Fabian Schmid <fabian@sr.solutions>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @author            Gabriel Comte <gc@studer-raimann.ch>
 *
 * @ilCtrl_isCalledBy ilObjPhotoGalleryGUI: ilRepositoryGUI, ilObjPluginDispatchGUI, ilAdministrationGUI
 * @ilCtrl_Calls      ilObjPhotoGalleryGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 * @ilCtrl_Calls      ilObjPhotoGalleryGUI: srObjAlbumGUI, srObjPictureGUI
 */
class ilObjPhotoGalleryGUI extends ilObjectPluginGUI
{
    protected object $parent; // TODO this is currently unknown and never set, problably remove it
    public const CMD_INFO_SCREEN = 'infoScreen';
    public const CMD_EDIT_PROPERTIES = 'editProperties';
    public const CMD_LIST_ALBUMS = 'list_albums';
    public const CMD_MANAGE_ALBUMS = 'manageAlbums';
    public const CMD_PERM = 'perm';
    public const CMD_SHOW_CONTENT = 'showContent';
    public const CMD_SHOW_SUMMARY = 'showSummary';
    public const TAB_CONTENT = 'content';
    public const TAB_INFO = 'info';
    public const TAB_LIST_ALBUMS = 'list_albums';
    public const TAB_MANAGE_ALBUMS = 'manage_albums';
    public const TAB_PERMISSIONS = 'permissions';
    public const TAB_SETTINGS = 'settings';
    protected ilPhotoGalleryPlugin $pl;
    protected ?ilPropertyFormGUI $form = null;
    protected ilNavigationHistory $history;
    protected ilAppEventHandler $event;
    protected UIServices $ui;
    protected HttpService $http;
    protected ResourceStorage $irss;
    protected PreviewGenerator $previews;

    protected function afterConstructor(): void
    {
        global $DIC, $xphoDIC;

        $this->tpl = $DIC->ui()->mainTemplate();
        $this->history = $DIC["ilNavigationHistory"];
        $this->access = $DIC->access();
        $this->ctrl = $DIC->ctrl();
        $this->tabs_gui = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->pl = ilPhotoGalleryPlugin::getInstance();
        $this->event = $DIC->event();
        $this->ui = $DIC->ui();
        $this->http = $DIC->http();
        $this->irss = $DIC->resourceStorage();
        $this->previews = $xphoDIC[PreviewGenerator::class];

        // add a link pointing to this object in footer [The "Permanent Link" in the footer]
        if ($this->object instanceof \ilObject) {
            $this->tpl->setPermanentLink($this->pl->getId(), $this->object->getRefId());
        }
    }

    public function getType(): string
    {
        return ilPhotoGalleryPlugin::PLUGIN_ID;
    }

    public function executeCommand(): void
    {
        if ($this->access->checkAccess('read', '', $this->ref_id)) {
            $this->history->addItem(
                $this->ref_id,
                $this->ctrl->getLinkTarget($this, $this->getStandardCmd()),
                $this->getType(),
                ''
            );
        }
        $cmd = $this->ctrl->getCmd();
        $next_class = $this->ctrl->getNextClass($this);
        $this->setTitleAndDescription();
        $this->setLocator();

        switch ($next_class) {
            case 'ilpermissiongui':
                $this->setTabs();
                $this->tabs_gui->activateTab(self::TAB_PERMISSIONS);
                $perm_gui = new ilPermissionGUI($this);
                $this->ctrl->forwardCommand($perm_gui);
                $this->tpl->printToStdout();
                break;
            case 'ilinfoscreengui':
                $this->setTabs();
                $this->tabs_gui->activateTab(self::TAB_INFO);
                $info_gui = new ilInfoScreenGUI($this);
                $this->ctrl->forwardCommand($info_gui);
                $this->tpl->printToStdout();
                break;
            case strtolower(srObjAlbumGUI::class):
                $this->setTabs();
                $this->tabs_gui->activateTab(self::TAB_CONTENT);
                $album_gui = new srObjAlbumGUI($this);
                $this->ctrl->forwardCommand($album_gui);
                $this->tpl->printToStdout();
                break;
            case strtolower(srObjPictureGUI::class):
                $picture_gui = new srObjPictureGUI($this);
                $this->ctrl->forwardCommand($picture_gui);
                $this->tpl->printToStdout();
                break;
            case 'ilcommonactiondispatchergui':
                $gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
                $this->ctrl->forwardCommand($gui);
                break;
            case 'srobjphotogallerygui':
            case '':
                switch ($cmd) {
                    case atTableGUI::CMD_CREATE:
                        $this->tpl->setTitle($this->pl->txt('obj_title_create_new'));
                        $this->create();
                        break;
                    case atTableGUI::CMD_SAVE:
                        $this->save();
                        $this->tpl->printToStdout();
                        break;
                    case atTableGUI::CMD_CANCEL:
                        $this->cancel();
                        break;
                    case atTableGUI::CMD_EDIT:
                    case self::CMD_EDIT_PROPERTIES:
                        $this->setTabs();
                        $this->edit();
                        $this->tpl->printToStdout();
                        break;
                    case atTableGUI::CMD_UPDATE:
                        $this->update();
                        $this->tpl->printToStdout();
                        break;
                    case self::CMD_MANAGE_ALBUMS:
                        $this->setTabs();
                        $this->tabs_gui->activateTab(self::TAB_CONTENT);
                        $this->setSubTabsContent();
                        $this->tabs_gui->activateSubTab(self::TAB_MANAGE_ALBUMS);
                        $this->manageAlbums();
                        $this->tpl->printToStdout();
                        break;
                    case self::CMD_INFO_SCREEN:
                        $this->setTabs();

                        $this->ctrl->setCmd(self::CMD_SHOW_SUMMARY);
                        $this->ctrl->setCmdClass(ilInfoScreenGUI::class);
                        $this->infoScreen();

                        $this->tabs_gui->activateTab(self::TAB_INFO);

                        $this->tpl->printToStdout();
                        break;
                    case self::CMD_SHOW_CONTENT:
                    case self::CMD_LIST_ALBUMS:
                    case '':
                        $this->setTabs();
                        $this->tabs_gui->activateTab(self::TAB_CONTENT);
                        $this->setSubTabsContent();
                        $this->tabs_gui->activateSubTab(self::TAB_LIST_ALBUMS);
                        $this->listAlbums();
                        $this->tpl->printToStdout();
                        break;
                }
                break;
        }
    }

    public function edit(): void
    {
        $this->tabs_gui->activateTab(self::TAB_SETTINGS);
//        $this->tpl->setContent($this->ui->renderer()->render($this->getEditForm()));
        $this->tpl->setContent($this->initEditForm()->getHTML());
    }

    public function editObject(): void
    {
        $this->tabs_gui->activateTab(self::TAB_SETTINGS);
//        $this->tpl->setContent($this->ui->renderer()->render($this->getEditForm()));
        $this->tpl->setContent($this->initEditForm()->getHTML());
    }

    protected function initEditForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->pl->txt('edit'));
        // title
        $ti = new ilTextInputGUI($this->pl->txt('gallery_title'), 'title');
        $ti->setMaxLength(128);
        $ti->setSize(40);
        $ti->setRequired(true);
        $form->addItem($ti);
        // description
        $ta = new ilTextAreaInputGUI($this->pl->txt('description'), 'desc');
        $ta->setRows(2);
        $form->addItem($ta);
        $ta->setValue($this->object->getDescription());
        $ti->setValue($this->object->getTitle());

        // tile image
        $obj_service = $this->getObjectService();
        $form = $obj_service->commonSettings()->legacyForm($form, $this->object)->addTileImage();

        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton(atTableGUI::CMD_UPDATE, $this->pl->txt('save'));
        $form->addCommandButton(self::CMD_SHOW_CONTENT, $this->pl->txt('cancel'));

        return $form;
    }

    public function update(): void
    {
        $form = $this->initEditForm();

        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->ui->mainTemplate()->setOnScreenMessage(
                "failure",
                $GLOBALS['DIC']->language()->txt('err_check_input')
            );
            $this->editObject();
        }

        // tile image
        $obj_service = $this->getObjectService();
        $obj_service->commonSettings()->legacyForm($form, $this->object)->saveTileImage();
        // title and description
        parent::update();
    }

    public function saveObject(): void
    {
        if (!$this->access_handler->checkAccess('write', '', $this->object->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirectByClass(ilRepositoryGUI::class, "view");
        } else {
            $this->object->update();
        }
        $this->ctrl->redirectByClass(ilRepositoryGUI::class, "view");
    }

    public function getAfterCreationCmd(): string
    {
        return self::CMD_LIST_ALBUMS;
    }

    public function getStandardCmd(): string
    {
        return self::CMD_LIST_ALBUMS;
    }

    protected function setTabs(): void
    {
        $this->tabs_gui->addTab(
            self::TAB_CONTENT,
            $this->pl->txt('content'),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOW_CONTENT)
        );
        $this->tabs_gui->addTab(
            self::TAB_INFO,
            $this->pl->txt('info'),
            $this->ctrl->getLinkTargetByClass(ilInfoScreenGUI::class, self::CMD_SHOW_SUMMARY)
        );
        if ($this->access_handler->checkAccess('write', '', $this->object->getRefId())) {
            $this->tabs_gui->addTab(
                self::TAB_SETTINGS,
                $this->pl->txt('settings'),
                $this->ctrl->getLinkTarget($this, atTableGUI::CMD_EDIT)
            );
        }
        if ($this->access->checkAccess('edit_permission', '', $this->object->getRefId())) {
            $this->tabs_gui->addTab(
                self::TAB_PERMISSIONS,
                $this->pl->txt('permissions'),
                $this->ctrl->getLinkTargetByClass(ilPermissionGUI::class, self::CMD_PERM)
            );
        }
    }

    protected function setSubTabsContent(): void
    {
        $this->tabs_gui->addSubTab(
            self::TAB_LIST_ALBUMS,
            $this->pl->txt('view'),
            $this->ctrl->getLinkTarget($this, self::CMD_LIST_ALBUMS)
        );

        // show tab "manage" on level overview
        if (ilObjPhotoGalleryAccess::checkManageTabAccess($this->object->getRefId())) {
            $this->tabs_gui->addSubTab(
                self::TAB_MANAGE_ALBUMS,
                $this->pl->txt('manage'),
                $this->ctrl->getLinkTarget($this, self::CMD_MANAGE_ALBUMS)
            );
        }
    }

    public function listAlbums(): void
    {
        if (!$this->access_handler->checkAccess('read', '', $this->object->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirectByClass(ilRepositoryGUI::class, "view");
        }

        // album cards
        $cards = [];
        /**
         * @var $srObjAlbum srObjAlbum
         */
        foreach ($this->object->getAlbumObjects() as $srObjAlbum) {
            $content = [];
            $content[] = $this->ui->factory()->listing()->descriptive([
                "" => $srObjAlbum->getDescription(),
                " " => date('d.m.Y', strtotime($srObjAlbum->getCreateDate())),
                "  " => $srObjAlbum->getPictureCount() . ' ' . $this->pl->txt('pics')
            ]);
            // image for the card
            $src_mosaic = $this->pl->getDirectory() . '/templates/images/nopreview.svg';
            if ($srObjAlbum->getPreviewId() > 0) {
                $preview_rid = $srObjAlbum->getPreviewPictureRid() ?? "";
                $preview_identifier = $this->irss->manage()->find($preview_rid);
                if ($preview_identifier !== null) {
                    $src_mosaic = $this->previews->getURL($preview_identifier, 512);
                }
            }
            $image = $this->ui->factory()->image()->responsive(
                $src_mosaic,
                $srObjAlbum->getTitle()
            );
            $this->ctrl->setParameterByClass(srObjAlbumGUI::class, 'album_id', $srObjAlbum->getId());
            $open_album_action = $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class);
            $card = $this->ui->factory()->card()->standard(
                $srObjAlbum->getTitle(),
                $image->withAction($open_album_action)
            )->withTitleAction(
                $open_album_action
            )->withSections($content);
            $cards[] = $card;
        }

        if($this->access_handler->checkAccess('write', '', $this->object->getRefId())) {
            $add_new_album_image = $this->ui->factory()->image()->responsive(
                $this->pl->getDirectory() . '/templates/images/addnew.svg',
                $this->pl->txt('add_album')
            );
            $add_new_album_action = $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, atTableGUI::CMD_ADD);
            $add_new_album_card = $this->ui->factory()->card()->standard(
                "",
                $add_new_album_image->withAction($add_new_album_action)
            );
            $cards[] = $add_new_album_card;
            // create add album button and add it to toolbar
            $add_album_button = $this->ui->factory()->button()->primary(
                $this->pl->txt('add_album'),
                $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, atTableGUI::CMD_ADD)
            );
            $this->toolbar->addComponent($add_album_button);
        }
        $deck = $this->ui->factory()->deck($cards);
        $this->tpl->setContent($this->ui->renderer()->render($deck));
    }

    public function manageAlbums(): void
    {
        if (!ilObjPhotoGalleryAccess::checkManageTabAccess($this->object->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this, '');
        } else {
            $table_gui = new ilObjPhotoGalleryTableGUI();
            $this->tpl->setContent($table_gui->getTableForRepresentation());
        }
    }

    public static function executeDownload(array $picture_ids): void
    {
        global $DIC;
        $irss = $DIC->resourceStorage();

        $picture_identifiers = [];
        foreach ($picture_ids as $picture_id) {
            /**
             * @var $picture srObjPicture
             */
            $picture = srObjPicture::find($picture_id);
            if ($picture === null) {
                continue;
            }
            $picture_rid = $picture->getPictureRid();
            $picture_identifier = $irss->manage()->find($picture_rid);
            if ($picture_identifier === null) {
                continue;
            }
            $picture_resource = $irss->manage()->getResource($picture_identifier);
            $picture_revision = $picture_resource->getCurrentRevision();
            $picture_revision_infos = $picture_revision->getInformation();
            $picture_filename = $picture_revision_infos->getTitle();
            // This special case is needed as the first version of the irss migration did name all files "duplicate_for_irss."
            if (str_contains($picture_filename, "duplicate_for_irss")) {
                $picture_suffix = $picture_revision->getInformation()->getSuffix();
                if(empty($picture_suffix)) {
                    $picture_suffix = $picture->getSuffix();
                }
                $new_filename = $picture->getTitle() . '.' . $picture_suffix;
                $new_revision_infos = new ILIAS\ResourceStorage\Information\FileInformation();
                $new_revision_infos->setTitle($new_filename);
                $new_revision_infos->setSuffix($picture_revision_infos->getSuffix());
                $new_revision_infos->setMimeType($picture_revision_infos->getMimeType());
                $new_revision_infos->setSize($picture_revision_infos->getSize());
                $new_revision_infos->setCreationDate($picture_revision_infos->getCreationDate());
                $picture_revision->setInformation($new_revision_infos);
                $picture_resource->replaceRevision($picture_revision);
            }
            $picture_identifiers[] = $picture_identifier;
        }
        if (!empty($picture_identifiers)) {
            $irss->consume()->downloadResources($picture_identifiers, 'pictures.zip')->run();
        } else {
            $DIC->ui()->mainTemplate()->setOnScreenMessage("failure", $DIC->language()->txt('msg_obj_no_download'), true);
        }
    }

    protected function afterSave(ilObject $new_object): void
    {
        $this->event->raise(
            'Services/Object',
            'afterSave',
            ['object' => $new_object, 'obj_id' => $new_object->getId(), 'obj_type' => $new_object->getType()]
        );

        parent::afterSave($new_object);
    }

    public function performCommand(string $cmd): void
    {
        // TODO: Implement performCommand() method.
    }
}
