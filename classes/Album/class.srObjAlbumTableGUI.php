<?php

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
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\ResourceStorage\Services as ResourceStorage;
use ILIAS\ResourceStorage\Flavour\Definition\CropToSquare;

/**
 * Class ilObjPhotoGalleryTableGUI
 *
 * @author  Lukas Zehnder <lukas@sr.solutions
 * @author  Fabian Schmid <fabian@sr.solutions>
 * @author  Zeynep Karahan <zk@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 */
class srObjAlbumTableGUI implements DataRetrieval
{
    public const CMD_EDIT = 'edit';
    public const CMD_CONFIRM_DELETE = 'confirmDelete';
    public const CMD_DOWNLOAD_PICTURE = 'download';

    private DataFactory $data_factory;
    private HttpServices $http;
    private ilLanguage $lng;
    private ilPhotoGalleryPlugin $pl;
    private UIFactory $ui_factory;
    private Renderer $ui_renderer;
    private ilCtrlInterface $ctrl;
    private Refinery $refinery;
    private ResourceStorage $irss;

    public function __construct()
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->data_factory = new DataFactory();
        $this->http = $DIC->http();
        $this->irss = $DIC->resourceStorage();
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
            ["album"],
            "picture_ids"
        );
        $actions = [
            'edit' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('edit'),
                $url_builder->withURI($this->buildURI(srObjPictureGUI::class, self::CMD_EDIT)),
                $id_token
            ),
            'download' => $this->ui_factory->table()->action()->standard(
                $this->lng->txt('download'),
                $url_builder->withURI($this->buildURI(srObjPictureGUI::class, self::CMD_DOWNLOAD_PICTURE)),
                $id_token
            ),
            'delete' => $this->ui_factory->table()->action()->standard(
                $this->lng->txt('delete'),
                $url_builder->withURI($this->buildURI(srObjPictureGUI::class, self::CMD_CONFIRM_DELETE)),
                $id_token
            )->withAsync(),
        ];
        $table = $this->ui_factory->table()->data(
            $this->pl->txt('manage_picture'),
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
            $picture_id = (string) $record['id'];
            $record['create_date'] = new DateTimeImmutable($record['create_date']);
            /**
             * @var srObjPicture $picture
             */
            $picture = srObjPicture::find($picture_id);
            $picture_rid = $picture->getPictureRID();
            $picture_identifier = $this->irss->manage()->find($picture_rid);
            if ($picture_identifier !== null) {
                $picture_flavour = new ilObjPhotoGalleryCropToSquare( 96, 75);
                $flavour = $this->irss->flavours()->get($picture_identifier, $picture_flavour);
                $flavour_urls = $this->irss->consume()->flavourUrls($flavour)->getURLsAsArray();
                $src_preview = $flavour_urls[0];
            }
            $record['image'] = $this->ui_factory->symbol()->icon()->custom($src_preview, $record['title'], "large");
            yield $row_builder->buildDataRow($picture_id, $record);
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
            'image' => $this->ui_factory->table()->column()->statusIcon("image")->withIsSortable(false),
            'title' => $this->ui_factory->table()->column()->text("title")->withHighlight(true),
            'description' => $this->ui_factory->table()->column()->text("description"),
            'create_date' => $this->ui_factory->table()->column()->date(
                "date", $this->data_factory->dateFormat()->germanLong()
            )
        ];
    }

    private function getRecords(DataRange $range = null, DataOrder $order = null): array
    {
        if (!$this->http->wrapper()->query()->has('album_id')) {
            return [];
        }
        $album_id = $this->http->wrapper()->query()->retrieve('album_id', $this->refinery->kindlyTo()->int());
        $records = srObjPicture::where(['album_id' => $album_id], '=')->getArray();

        if ($order) {
            list($order_field, $order_direction) = $order->join([], fn($ret, $key, $value) => [$key, $value]);
            usort($records, fn($a, $b) => $a[$order_field] <=> $b[$order_field]);
            if ($order_direction === 'DESC') {
                $records = array_reverse($records);
            }
        }
        if ($range) {
            $records = array_slice($records, $range->getStart(), $range->getLength());
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
