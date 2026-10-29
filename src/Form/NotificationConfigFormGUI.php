<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Form;

use ilLanguage;
use ilLearningObjectiveSuggestionsConfigGUI;
use ilLearningObjectiveSuggestionsPlugin;
use ilObjUser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Parser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Placeholders;
use ILIAS\UI\Factory;
use ilCtrlInterface;

class NotificationConfigFormGUI
{
    protected CourseConfigProvider $config;

    protected Parser $parser;

    protected ?ilObjUser $user;

    protected ilLanguage $lng;

    protected ilLearningObjectiveSuggestionsPlugin $pl;

    protected \ilTemplate $tpl;

    private Factory $ui;

    private ilCtrlInterface $ctrl;

    /**
     * @param CourseConfigProvider $config
     * @param Parser               $parser
     */
    public function __construct(
        CourseConfigProvider $config, Parser $parser
    ) {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->ui = $DIC->ui()->factory();
        $this->user = $DIC->user();
        $this->lng = $DIC->language();
        $this->config = $config;
        $this->parser = $parser;
        $this->pl = ilLearningObjectiveSuggestionsPlugin::getInstance();
    }


    /**
     * @throws \ilCtrlException
     */
    public function buidForm()
    {
        $field = $this->ui->input()->field();

        $sender = $field->text($this->pl->txt('sender_user_id'), $this->pl->txt('sender_user_id_info'))
                        ->withRequired(true)
                        ->withDedicatedName('notification_sender_user_id')
                        ->withValue((string) ($this->config->get('notification_sender_user_id') ?? ''));

        $cc_role = $field->text($this->pl->txt('cc_role_id'), $this->pl->txt('cc_role_id_info'))
                         ->withDedicatedName('notification_cc_role_id')
                         ->withValue((string) ($this->config->get('notification_cc_role_id') ?? ''));

        $configuration = $field->section(
            [
                'notification_sender_user_id' => $sender,
                'notification_cc_role_id' => $cc_role,
            ],
            $this->pl->txt('configuration')
        );

        $subject = $field->text($this->pl->txt('subject'))
                         ->withRequired(true)
                         ->withDedicatedName('email_subject')
                         ->withValue((string) ($this->config->get('email_subject') ?? ''));

        $body = $field->textarea($this->pl->txt('body'), $this->pl->txt('body_info'))
                      ->withRequired(true)
                      ->withDedicatedName('email_body');

        $body_value = $this->config->get('email_body');
        if ($body_value !== null) {
            $body = $body->withValue((string) $body_value);
        }

        $placeholders = new Placeholders();
        $available_placeholders = $placeholders->getAvailablePlaceholders();

        if (!empty($available_placeholders)) {
            $placeholderList = $this->ui->listing()->unordered($available_placeholders);

            $placeholders_array = [];
            foreach($placeholderList->getItems() as $key =>$list) {
                $placeholders_array[] = "&lbrace;&lbrace; {$key} &rbrace;&rbrace; : {$this->pl->txt($list)}";
            }

            $placeholderListHtml = '<br>' . $this->pl->txt("placeholders_info") . '<br><br>';
            foreach($placeholders_array as $val) {
                $placeholderListHtml .= $val . '<br>';
            }

            $body = $body->withByline(
                $placeholderListHtml
            );
        }

        $templates = $field->section(
            [
                'email_subject' => $subject,
                'email_body' => $body
            ],
            $this->pl->txt('templates')
        );

        return $this->ui->input()->container()->form()->standard(
            $this->ctrl->getFormActionByClass(
                ilLearningObjectiveSuggestionsConfigGUI::class,
                ilLearningObjectiveSuggestionsConfigGUI::CMD_SAVE_NOTIFICATIONS
            ),
            [
                'configuration' => $configuration,
                'templates' => $templates,
            ]
        )->withSubmitLabel($this->pl->txt('save'));
    }

    /**
     * @param CourseConfigProvider $config
     * @return void
     * @throws \ilCtrlException
     */
    public function save(CourseConfigProvider $config)
    {
        global $DIC;

        $request = $DIC->http()->request();

        $form = $this->buidForm();

        if (
            $request->getMethod() == 'POST'
            && array_key_exists('ref_id', $request->getQueryParams())
            && array_key_exists('course_ref_id', $request->getQueryParams())
        ) {
            $form = $form->withRequest($request);
            $data = $form->getData();

            foreach ($data['configuration'] as $key => $value) {
                $config->set($key, $value);
            }

            foreach ($data['templates'] as $key => $value) {
                $config->set($key, $value);
            }

            $tpl = $DIC->ui()->mainTemplate();
            $tpl->setOnScreenMessage('success', $this->pl->txt('saved'), true);
        }
    }
}
