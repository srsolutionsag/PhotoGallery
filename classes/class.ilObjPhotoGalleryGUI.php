<?php
/*
    +-----------------------------------------------------------------------------+
    | ILIAS open source                                                           |
    +-----------------------------------------------------------------------------+
    | Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
    |                                                                             |
    | This program is free software; you can redistribute it and/or               |
    | modify it under the terms of the GNU General Public License                 |
    | as published by the Free Software Foundation; either version 2              |
    | of the License, or (at your option) any later version.                      |
    |                                                                             |
    | This program is distributed in the hope that it will be useful,             |
    | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
    | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
    | GNU General Public License for more details.                                |
    |                                                                             |
    | You should have received a copy of the GNU General Public License           |
    | along with this program; if not, write to the Free Software                 |
    | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
    +-----------------------------------------------------------------------------+
*/

use ILIAS\DI\UIServices;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\HTTP\Services as HttpService;
use ILIAS\ResourceStorage\Services as ResourceStorage;
use ILIAS\ResourceStorage\Flavour\Definition\CropToSquare;

/**
 * User Interface class for example repository object.
 * @author            Lukas Zehnder <lukas@sr.solutions>
 * @author            Fabian Schmid <fabian@sr.solutions>
 * @author            Zeynep Karahan <zk@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @author            Gabriel Comte <gc@studer-raimann.ch>
 * $Id$
 * @ilCtrl_isCalledBy ilObjPhotoGalleryGUI: ilRepositoryGUI, ilObjPluginDispatchGUI, ilAdministrationGUI
 * @ilCtrl_Calls      ilObjPhotoGalleryGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 * @ilCtrl_Calls      ilObjPhotoGalleryGUI: srObjAlbumGUI, srObjPictureGUI, srObjExifGUI
 */
class ilObjPhotoGalleryGUI extends ilObjectPluginGUI
{
    public $parent;
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
    /**
     * @var ilPhotoGalleryPlugin
     */
    protected $pl;
    /**
     * @var ilPropertyFormGUI
     */
    protected $form;
    /**
     * @var ilNavigationHistory
     */
    protected $history;
    /**
     * @var ilAppEventHandler
     */
    protected $event;
    public UIServices $ui;
    private HttpService $http;
    private ResourceStorage $irss;

    protected function afterConstructor(): void
    {
        global $DIC;

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
            case 'srobjalbumgui':
                $this->setTabs();
                $this->tabs_gui->activateTab(self::TAB_CONTENT);
                $album_gui = new srObjAlbumGUI($this);
                $this->ctrl->forwardCommand($album_gui);
                $this->tpl->printToStdout();
                break;
            case 'srobjpicturegui':
                $picture_gui = new srObjPictureGUI($this);
                $this->ctrl->forwardCommand($picture_gui);
                $this->tpl->printToStdout();
                break;
            case 'ilcommonactiondispatchergui':
                include_once(__DIR__ . "/Services/Object/classes/class.ilCommonActionDispatcherGUI.php");
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
        $this->tpl->setContent($this->ui->renderer()->render($this->getEditForm()));
    }

    public function editObject(): void
    {
        $this->tabs_gui->activateTab(self::TAB_SETTINGS);
        $this->tpl->setContent($this->ui->renderer()->render($this->getEditForm()));
    }

    protected function getEditForm(): StandardForm
    {
        // create input fields
        $title_input = $this->ui->factory()->input()->field()->text(
            $this->pl->txt('gallery_title')
        )->withMaxLength(
            128
        )->withValue(
            $this->object->getTitle()
        )->withRequired(true);
        $description_input = $this->ui->factory()->input()->field()->textarea(
            $this->pl->txt('description')
        )->withValue($this->object->getDescription());
        $tile_image_input = $this->object->getObjectProperties()->getPropertyTileImage()->toForm(
            $this->lng,
            $this->ui->factory()->input()->field(),
            $this->refinery
        );

        // create named section and add input fields
        $section = $this->ui->factory()->input()->field()->section(
            [
                'title' => $title_input,
                'description' => $description_input,
                'tile_image' => $tile_image_input
            ],
            $this->pl->txt('edit')
        );

        // create form and add section
        return $this->ui->factory()->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, atTableGUI::CMD_UPDATE),
            ['gallery' => $section]
        );
    }

    public function update(): void
    {
        $form = $this->getEditForm();
        $form = $form->withRequest($this->http->request());
        $data = $form->getData();

        if ($data === null) {
            $this->setTabs();
            $this->tpl->setContent($this->ui->renderer()->render([$form]));
            return;
        }

        // store title and description
        $this->object->setTitle($data['gallery']['title']);
        $this->object->setDescription($data['gallery']['description']);
        $this->object->update();
        // store tile image
        if (($data['gallery']['tile_image'] ?? null) !== null) {
            $this->object->getObjectProperties()->storePropertyTileImage($data['gallery']['tile_image']);
        }

        $this->ui->mainTemplate()->setOnScreenMessage("success", $this->pl->txt('success_edit'), true);
        $this->setTabs();
        $this->editObject();
    }

    public function saveObject(): void
    {
        if (!$this->access_handler->checkAccess('write', '', $this->object->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent, '');
        } else {
            $this->object->update();
        }
        $this->ctrl->redirect($this->parent, '');
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
        if (!$this->access_handler->checkAccess('write', '', $this->object->getRefId())) {
            $this->ui->mainTemplate()->setOnScreenMessage("failure", $this->pl->txt('permission_denied'), true);
            $this->ctrl->redirect($this->parent, '');
        }
        // create add album button and add it to toolbar
        $add_album_button = $this->ui->factory()->button()->primary(
            $this->pl->txt('add_album'),
            $this->ctrl->getLinkTargetByClass(srObjAlbumGUI::class, atTableGUI::CMD_ADD)
        );
        $this->toolbar->addComponent($add_album_button);

        // album cards
        $cards = [];
        /**
         * @var $srObjAlbum srObjAlbum
         */
        foreach ($this->object->getAlbumObjects() as $srObjAlbum) {
            $content = [];
            $content[] = $this->ui->factory()->listing()->property()->withItems([
                ["0", $srObjAlbum->getDescription(), false]
            ]);
            $content[] = $this->ui->factory()->listing()->property()->withItems([
                ["1", date('d.m.Y', strtotime($srObjAlbum->getCreateDate())), false]
            ]);
            $content[] = $this->ui->factory()->listing()->property()->withItems([
                ["2", $srObjAlbum->getPictureCount() . ' ' . $this->pl->txt('pics'), false]
            ]);
            // image for the card
            $src_mosaic = $this->pl->getDirectory() . '/templates/images/nopreview.svg';
            if ($srObjAlbum->getPreviewId() > 0) {
                $preview_rid = $srObjAlbum->getPreviewPictureRid();
                $preview_identifier = $this->irss->manage()->find($preview_rid);
                if ($preview_identifier !== null) {
                    $preview_flavour = new CropToSquare(false, 512, 75);
                    $flavour = $this->irss->flavours()->get($preview_identifier, $preview_flavour);
                    $flavour_urls = $this->irss->consume()->flavourUrls($flavour)->getURLsAsArray();
                    $src_mosaic = $flavour_urls[0];
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
            $picture_identifiers[] = $picture_identifier;
        }
        $irss->consume()->downloadResources($picture_identifiers, 'pictures.zip')->run();
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
