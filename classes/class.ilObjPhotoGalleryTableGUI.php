<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\Range as DataRange;
use ILIAS\Data\Order as DataOrder;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder as RowBuilder;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\HTTP\Services as HttpServices;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\Renderer;
use ILIAS\Data\URI;
use ILIAS\Refinery\Factory;

/**
 * Class ilObjPhotoGalleryTableGUI
 *
 * @author  Lukas Zehnder <lukas@sr.solutions
 * @author  Fabian Schmid <fabian@sr.solutions>
 * @author  Zeynep Karahan <zk@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 */
class ilObjPhotoGalleryTableGUI implements DataRetrieval
{
    public const CMD_EDIT = 'edit';
    public const CMD_CONFIRM_DELETE = 'confirmDelete';
    public const CMD_DOWNLOAD_ALBUM = 'downloadAlbum';

    private DataFactory $data_factory;
    private HttpServices $http;
    private ilLanguage $lng;
    private ilPhotoGalleryPlugin $pl;
    private UIFactory $ui_factory;
    private Renderer $ui_renderer;
    private ilCtrlInterface $ctrl;
    private Factory $refinery;

    public function __construct()
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->data_factory = new DataFactory();
        $this->http = $DIC->http();
        $this->lng = $DIC->language();
        $this->pl = ilPhotoGalleryPlugin::getInstance();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->refinery = $DIC->refinery();
    }

    public function getTableForRepresentation(): string
    {
        //define actions for the table
        $here_uri = $this->data_factory->uri($this->http->request()->getUri()->__toString());
        $url_builder = new URLBuilder($here_uri);
        [$url_builder, $id_token] = $url_builder->acquireParameters(
            ["gallery"],
            "album_ids"
        );
        $actions = [
            'edit' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('edit'),
                $url_builder->withURI($this->buildURI(srObjAlbumGUI::class, self::CMD_EDIT)),
                $id_token
            ),
            'download' => $this->ui_factory->table()->action()->standard(
                $this->lng->txt('download'),
                $url_builder->withURI($this->buildURI(srObjAlbumGUI::class, self::CMD_DOWNLOAD_ALBUM)),
                $id_token
            ),
            'delete' => $this->ui_factory->table()->action()->standard(
                $this->lng->txt('delete'),
                $url_builder->withURI($this->buildURI(srObjAlbumGUI::class, self::CMD_CONFIRM_DELETE)),
                $id_token
            )->withAsync(),
        ];
        $gallery_ref_id = $this->http->wrapper()->query()->has('ref_id') ? $this->http->request()->getQueryParams()['ref_id'] : 0;
        $gallery = ilObjectFactory::getInstanceByRefId($gallery_ref_id);
        $gallery_title = $this->lng->txt('unknown');
        if($gallery !== null){
            $gallery_title = $gallery->getTitle();
        }
        $table = $this->ui_factory->table()->data(
            sprintf($this->pl->txt('manage_albums'), $gallery_title),
            $this->getColumsForRepresentation(),
            $this
        )->withActions($actions);
        return $this->ui_renderer->render([$table->withRequest($this->http->request())]);
    }

    /**
     * @throws Exception
     */
    public function getRows(
        RowBuilder $row_builder,
        array $visible_column_ids,
        DataRange $range,
        DataOrder $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): \Generator {
        $records = $this->getRecords($range, $order);
        foreach ($records as $record) {
            $row_id = (string) $record['id'];
            $record['create_date'] = new DateTimeImmutable($record['create_date']);
            yield $row_builder->buildDataRow($row_id, $record);
        }
    }

    public function getTotalRowCount(
        ?array $filter_data,
        ?array $additional_parameters
    ): ?int {
        return count($this->getRecords());
    }

    protected function getColumsForRepresentation(): array
    {
        return [
            'title' => $this->ui_factory->table()->column()->text($this->lng->txt('title'))->withHighlight(true),
            'description' => $this->ui_factory->table()->column()->text($this->lng->txt('description')),
            'create_date' => $this->ui_factory->table()->column()->date(
                $this->lng->txt('date'),
                $this->data_factory->dateFormat()->germanLong()
            ),
            'sort_type' => $this->ui_factory->table()->column()->text($this->pl->txt("sort_type")),
            'sort_direction' => $this->ui_factory->table()->column()->text($this->pl->txt("sort_direction")),
        ];
    }

    private function getRecords(DataRange $range = null, DataOrder $order = null): array
    {
        $records = srObjAlbum::where(['object_id' => ilObject::_lookupObjectId($_GET['ref_id'])], '=')->getArray();

        if ($order !== null) {
            [$order_field, $order_direction] = $order->join([], fn($ret, $key, $value): array => [$key, $value]);
            usort($records, fn($a, $b): int => $a[$order_field] <=> $b[$order_field]);
            if ($order_direction === 'DESC') {
                $records = array_reverse($records);
            }
        }
        if ($range !== null) {
            return array_slice($records, $range->getStart(), $range->getLength());
        }

        return $records;
    }

    /**
     * @throws ilCtrlException
     */
    private function buildURI(string $class, string $command): URI
    {
        return new URI(ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTargetByClass($class, $command));
    }
}
