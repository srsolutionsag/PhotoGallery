<?php

/**
 * Class atTableGUI
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 */
abstract class atTableGUI extends ilTable2GUI
{
    public const CMD_ADD = 'add';
    public const CMD_CANCEL = 'cancel';
    public const CMD_CONFIRM_DELETE = 'confirmDelete';
    public const CMD_CREATE = 'create';
    public const CMD_DELETE = 'delete';
    public const CMD_EDIT = 'edit';
    public const CMD_DOWNLOAD = 'download';
    public const CMD_DOWNLOAD_ALBUM = 'downloadAlbum';
    public const CMD_SAVE = 'save';
    public const CMD_UPDATE = 'update';

    protected string $table_id = 'sr';
    protected string $table_title = 'Table (override protected $table_title)';
    protected string $table_prefix = 'srx';
    protected array $filter_array = [];
    public static int $num = 0;

    protected ilObjUser $usr;
    protected ilAccessHandler $access;
    protected ilPhotoGalleryPlugin $pl;
    protected \ILIAS\HTTP\Services $http;
    protected \ILIAS\Refinery\Factory $refinery;

    public function __construct(?object $a_parent_obj, string $a_parent_cmd)
    {
        global $DIC;

        $this->usr = $DIC->user();
        $this->access = $DIC->access();
        $this->ctrl = $DIC->ctrl();
        if (!$this->initLanguage()) {
            $this->lng = $DIC->language();
        }
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();

        $this->initTableProperties();
        $this->setId($this->table_id);
        $this->setTitle($this->table_title);
        parent::__construct($a_parent_obj, $a_parent_cmd);
        if (!$this->initFormActionsAndCmdButtons()) {
            $this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
        }
        $this->initTableFilter();
        $this->initTableData();
        if (!$this->initTableColumns()) {
            $this->initStandardTableColumns();
        }
        if (!$this->initTableRowTemplate()) {
            $this->setRowTemplate('tpl.std_row_template.html', strstr(__DIR__, 'Customizing'));
        }
    }

    /**
     * @description  returns false, if no filter is needed, otherwise implement filters
     * @description  set custom method for filtering and resetting ($this->setResetCommand('resetFilter'); and $this->setFilterCommand('applyFilter');)
     */
    abstract protected function initTableFilter(): bool;

    /**
     * @description $this->setData(Your Array of Data)
     */
    abstract protected function initTableData(): void;

    /**
     * @description returns false if automatic columns are needed, otherwise implement your columns
     */
    abstract protected function initTableColumns(): bool;

    /**
     * @description returns false or set the following
     * @description e.g. ovverride table id oder title: $this->table_id = 'myid', $this->table_title = 'My Title'
     */
    abstract protected function initTableProperties(): bool;

    /**
     * @description return false or implements own form action and
     */
    abstract protected function initFormActionsAndCmdButtons(): bool;

    /**
     * @description returns false if dynamic template is needed, otherwise implement your own template by $this->setRowTemplate($a_template, $a_template_dir = "")
     */
    abstract protected function initTableRowTemplate(): bool;

    /**
     * @description returns false, if global language is needed; implement your own language by setting $this->lng
     */
    abstract protected function initLanguage(): bool;

    /**
     * @description implement your woen fillRow or return false
     */
    abstract protected function fillTableRow(array $a_set): bool;

    final public function addFilterItemToForm(ilFormPropertyGUI $item): void
    {
        /**
         * @var $item ilTextInputGUI
         */
        $this->addFilterItem($item);
        $item->readFromSession();
        $this->filter_array[$item->getPostVar()] = $item->getValue();
    }

    final public function initStandardTableColumns(): bool
    {
        $data = $this->getData();
        if ($data === []) {
            return false;
        }
        foreach (array_keys(array_shift($data)) as $key) {
            $this->addColumn($this->lng->txt($key), $key);
        }
        $this->addColumn($this->lng->txt('actions'), 'actions');

        return true;
    }

    /**
     * @internal    param array $_set
     * @description override, when using own columns
     */
    final protected function fillRow(array $a_set): void
    {
        if (!$this->fillTableRow($a_set)) {
            self::$num++;
            foreach ($a_set as $value) {
                $this->addCell($value);
            }
            $this->ctrl->setParameter($this->parent_obj, 'object_id', $a_set['id']);
            $actions = new ilAdvancedSelectionListGUI();
            $actions->setId('actions_' . self::$num);
            $actions->setListTitle($this->lng->txt('actions'));
            $actions->addItem(
                $this->lng->txt('edit'),
                'edit',
                $this->ctrl->getLinkTarget($this->parent_obj, self::CMD_EDIT)
            );
            $actions->addItem(
                $this->lng->txt('delete'),
                'delete',
                $this->ctrl->getLinkTarget($this->parent_obj, self::CMD_CONFIRM_DELETE)
            );
            $this->tpl->setCurrentBlock('cell');
            $this->tpl->setVariable('VALUE', $actions->getHTML());
            $this->tpl->parseCurrentBlock();
        }
    }

    /**
     * @param mixed $value
     */
    public function addCell($value): void
    {
        $this->tpl->setCurrentBlock('cell');
        $this->tpl->setVariable('VALUE', $value ?: '&nbsp;');
        $this->tpl->parseCurrentBlock();
    }

    /**
     * @return mixed
     */
    public function getNavStart()
    {
        return $this->getNavigationParameter('from');
    }

    /**
     * @return mixed
     */
    public function getNavStop()
    {
        return $this->getNavigationParameter('to');
    }

    /**
     * @return mixed
     */
    public function getNavSortField()
    {
        return $this->getNavigationParameter('sort_field');
    }

    /**
     * @return mixed
     */
    public function getNavorder()
    {
        return $this->getNavigationParameter('order');
    }

    /**
     * @return array{from: string|int, to: mixed, sort_field: string|true, order: string}
     */
    public function getNavigationParametersAsArray(): array
    {
        $hits = $this->usr->getPref('hits_per_page');
        $parameters = explode(':', $_GET[$this->getNavParameter()]);

        return [
            'from' => $parameters[2] ?: 0,
            'to' => $parameters[2] !== '' && $parameters[2] !== '0' ? $parameters[2] + $hits - 1 : $hits - 1,
            'sort_field' => $parameters[0] ?: false,
            'order' => $parameters[1] !== '' && $parameters[1] !== '0' ? strtoupper($parameters[1]) : 'ASC'
        ];
    }

    /**
     * @return mixed
     */
    public function getNavigationParameter(string $param)
    {
        $array = $this->getNavigationParametersAsArray();

        return $array[$param];
    }
}
