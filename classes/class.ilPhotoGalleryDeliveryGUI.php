<?php
/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

use ILIAS\HTTP\Services;
use srag\Plugins\PhotoGallery\DIC;

/**
 * @author            Fabian Schmid <fabian@sr.solutions>
 *
 * @ilCtrl_isCalledBy ilPhotoGalleryDeliveryGUI: ilUIPluginRouterGUI
 */
class ilPhotoGalleryDeliveryGUI
{
    public const RID = "rid";
    private ilCtrlInterface $ctrl;
    private Services $http;
    private \ILIAS\ResourceStorage\Services $irss;

    public function __construct()
    {
        global $xphoDIC;
        /** @var DIC $xphoDIC */
        $this->ctrl = $xphoDIC->ilias()->ctrl();
        $this->http = $xphoDIC->ilias()->http();
        $this->irss = $xphoDIC->ilias()->resourceStorage();
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd('deliver');

        if ($cmd === 'deliver') {
            $this->deliver();
        }
        // otherwise
        $this->notFound();
    }

    private function deliver(): void
    {
        $rid_string = $this->http->request()->getQueryParams()[self::RID] ?? null;
        if ($rid_string === null) {
            $this->notFound();
            return;
        }
        $rid = $this->irss->manage()->find($rid_string);
        if ($rid === null) {
            $this->notFound();
            return;
        }
        $this->irss->consume()->inline($rid)->run();
    }

    protected function notFound(): void
    {
        $this->http->saveResponse(
            $this->http->response()->withStatus(404)
        );
        $this->http->sendResponse();
        $this->http->close();
    }
}
