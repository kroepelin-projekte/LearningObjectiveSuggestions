<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config;

use Generator;
use ilCtrlInterface;
use ilLanguage;
use ilLearningObjectiveSuggestionsConfigGUI;
use ilLearningObjectiveSuggestionsPlugin;
use ilStr;
use ilUtil;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ILIAS\HTTP\Services as HttpServices;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Table\Action\Action;
use ILIAS\UI\Component\Table\Column\Column;
use ILIAS\UI\Component\Table\Data;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;

class LearningObjectiveCourseTableGUI implements DataRetrieval
{
    private const string TABLE_ID = 'alo_courses';

    private const array PARAMETER_NAMESPACE = ['alo', 'courses'];

    private const string ACTION_CONFIGURE = 'configure';

    private const string ACTION_DELETE = 'delete';

    private const string ACTION_ACTIVATE = 'activate';

    private const string ACTION_DEACTIVATE = 'deactivate';

    /**
     * Maps table actions to the existing ilCtrl commands of the parent GUI
     */
    private const array ACTION_COMMANDS = [
        self::ACTION_CONFIGURE => ilLearningObjectiveSuggestionsConfigGUI::CMD_CONFIGURE_COURSE,
        self::ACTION_DELETE => ilLearningObjectiveSuggestionsConfigGUI::CMD_CONFIRM_DELETE_COURSE_CONFIG,
        self::ACTION_ACTIVATE => ilLearningObjectiveSuggestionsConfigGUI::CMD_ACTIVATE_CALCULATION,
        self::ACTION_DEACTIVATE => ilLearningObjectiveSuggestionsConfigGUI::CMD_DEACTIVATE_CALCULATION,
    ];

    protected object $parent_obj;

    protected ilCtrlInterface $ctrl;

    protected ilLanguage $lng;

    protected HttpServices $http;

    protected Refinery $refinery;

    protected UIFactory $ui_factory;

    protected UIRenderer $ui_renderer;

    protected ilLearningObjectiveSuggestionsPlugin $pl;

    protected URLBuilder $url_builder;

    protected URLBuilderToken $action_token;

    protected URLBuilderToken $row_id_token;

    /**
     * @var array<int, array{ref_id: int, title: string, is_calculation_active: bool}>
     */
    protected array $data = [];

    /**
     * @param object $a_parent_obj
     * @throws \ilCtrlException
     */
    public function __construct(object $a_parent_obj)
    {
        global $DIC;

        $this->parent_obj = $a_parent_obj;
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->pl = ilLearningObjectiveSuggestionsPlugin::getInstance();

        // All row actions point to the CSRF protected handleTableAction command (the link carries the token),
        // which dispatches them via handleTableAction()
        $target = (new DataFactory())->uri(
            ilUtil::_getHttpPath() . '/'
            . $this->ctrl->getLinkTarget($this->parent_obj, ilLearningObjectiveSuggestionsConfigGUI::CMD_HANDLE_TABLE_ACTION)
        );
        [$this->url_builder, $this->action_token, $this->row_id_token] = (new URLBuilder($target))->acquireParameters(
            self::PARAMETER_NAMESPACE,
            'table_action',
            'course_ref_id'
        );
    }

    /**
     * @param array $courses
     * @return void
     */
    public function setCourses(array $courses): void
    {
        $data = [];
        foreach ($courses as $course) {
            /**
             * @var LearningObjectiveCourse $course
             */

            $data[] = [
                'ref_id' => $course->getRefId(),
                'title' => $course->getTitle(),
                'is_calculation_active' => !$course->getIsCalculationInactive()
            ];
        }
        $this->data = $data;
    }

    /**
     * Redirects to the ilCtrl command of a clicked row action, passing the course as plain 'course_ref_id'
     * parameter as expected by the command methods. Does nothing if no row action was triggered.
     *
     * @return void
     * @throws \ilCtrlException
     */
    public function handleTableAction(): void
    {
        $query = $this->http->wrapper()->query();
        if (!$query->has($this->action_token->getName()) || !$query->has($this->row_id_token->getName())) {
            return;
        }

        $action = $query->retrieve($this->action_token->getName(), $this->refinery->kindlyTo()->string());
        $ref_ids = $query->retrieve(
            $this->row_id_token->getName(),
            $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int())
        );

        if (!isset(self::ACTION_COMMANDS[$action]) || count($ref_ids) !== 1) {
            return;
        }

        $this->ctrl->setParameter($this->parent_obj, 'course_ref_id', current($ref_ids));
        $this->ctrl->redirect($this->parent_obj, self::ACTION_COMMANDS[$action]);
    }

    /**
     * @return string
     */
    public function getHTML(): string
    {
        return $this->ui_renderer->render($this->getTable());
    }

    /**
     * @return array<string, Column>
     */
    public function getSelectableColumns(): array
    {
        $column = $this->ui_factory->table()->column();

        return [
            'ref_id' => $column->number($this->pl->txt('ref_id')),
            'title' => $column->text($this->pl->txt('title')),
            'is_calculation_active' => $column->boolean(
                $this->pl->txt('calculation'),
                $this->lng->txt('active'),
                $this->lng->txt('inactive')
            )->withIsOptional(true)
        ];
    }

    /**
     * @param DataRowBuilder $row_builder
     * @param array          $visible_column_ids
     * @param Range          $range
     * @param Order          $order
     * @param mixed          $additional_viewcontrol_data
     * @param mixed          $filter_data
     * @param mixed          $additional_parameters
     * @return Generator
     */
    public function getRows(
        DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        mixed $additional_viewcontrol_data,
        mixed $filter_data,
        mixed $additional_parameters
    ): Generator {
        foreach ($this->getRecords($range, $order) as $record) {
            yield $row_builder->buildDataRow((string) $record['ref_id'], $record)
                ->withDisabledAction(self::ACTION_ACTIVATE, $record['is_calculation_active'])
                ->withDisabledAction(self::ACTION_DEACTIVATE, !$record['is_calculation_active']);
        }
    }

    /**
     * @param mixed $additional_viewcontrol_data
     * @param mixed $filter_data
     * @param mixed $additional_parameters
     * @return int|null
     */
    public function getTotalRowCount(
        mixed $additional_viewcontrol_data,
        mixed $filter_data,
        mixed $additional_parameters
    ): ?int {
        return count($this->data);
    }

    /**
     * @return Data
     */
    protected function getTable(): Data
    {
        return $this->ui_factory->table()
            ->data($this, $this->pl->txt('courses'), $this->getSelectableColumns())
            ->withId(self::TABLE_ID)
            ->withActions($this->getActions())
            ->withOrder(new Order('title', Order::ASC))
            ->withRange(new Range(0, 25))
            ->withRequest($this->http->request());
    }

    /**
     * @return array<string, Action>
     */
    protected function getActions(): array
    {
        $labels = [
            self::ACTION_CONFIGURE => $this->pl->txt('configurate'),
            self::ACTION_DELETE => $this->pl->txt('delete_learning_objective_course'),
            self::ACTION_ACTIVATE => $this->pl->txt('activate_calculation'),
            self::ACTION_DEACTIVATE => $this->pl->txt('deactivate_calculation'),
        ];

        $actions = [];
        foreach ($labels as $action => $label) {
            $actions[$action] = $this->ui_factory->table()->action()->single(
                $label,
                $this->url_builder->withParameter($this->action_token, $action),
                $this->row_id_token
            );
        }

        return $actions;
    }

    /**
     * @param Range $range
     * @param Order $order
     * @return array
     */
    protected function getRecords(Range $range, Order $order): array
    {
        $records = $this->data;

        [$order_field, $order_direction] = $order->join([], static fn($ret, $key, $value) => [$key, $value]);

        usort($records, static function (array $left, array $right) use ($order_field): int {
            if ($order_field === 'title') {
                return ilStr::strCmp($left['title'], $right['title']);
            }

            return $left[$order_field] <=> $right[$order_field];
        });

        if ($order_direction === Order::DESC) {
            $records = array_reverse($records);
        }

        return array_slice($records, $range->getStart(), $range->getLength());
    }
}
