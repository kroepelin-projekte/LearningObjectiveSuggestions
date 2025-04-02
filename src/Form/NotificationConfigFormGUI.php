<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Form;

use ilFormPropertyGUI;
use ilFormSectionHeaderGUI;
use ilLanguage;
use ilLearningObjectiveSuggestionsConfigGUI;
use ilLearningObjectiveSuggestionsPlugin;
use ilObjUser;
use ilPropertyFormGUI;
use ilTextAreaInputGUI;
use ilTextInputGUI;
use ilUtil;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Parser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Placeholders;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class NotificationConfigFormGUI extends ilPropertyFormGUI
{
    protected CourseConfigProvider $config;
    protected Parser $parser;
    protected ?ilObjUser $user;
    protected ilLanguage $lng;
    protected ilLearningObjectiveSuggestionsPlugin $pl;
    protected \ilTemplate $tpl;


    /**
     * @param CourseConfigProvider $config
     * @param Parser               $parser
     */
    public function __construct(CourseConfigProvider $config, Parser $parser)
    {
        parent::__construct();
        global $DIC;
        $this->user = $DIC->user();
        $this->lng = $DIC->language();
        $this->config = $config;
        $this->parser = $parser;
        $this->pl = ilLearningObjectiveSuggestionsPlugin::getInstance();
        $this->init();
    }
    /**
     * Additionally check if the strings can be parsed
     */
    public function checkInput(): bool
    {
        $result = parent::checkInput();
        $placeholders = new Placeholders();
        $ph = $placeholders->getPlaceholders($this->config->getCourse(), new User($this->user), array());
        if (!$this->parser->isValid($this->getInput('email_subject'), $ph)) {
            /** @var ilFormPropertyGUI $subject */
            $subject = $this->getItemByPostVar('email_subject');
            $subject->setAlert($this->pl->txt("invalid_placeholders"));
            $result = false;
        }
        if (!$this->parser->isValid($this->getInput('email_body'), $ph)) {
            /** @var ilFormPropertyGUI $body */
            $body = $this->getItemByPostVar('email_body');
            $body->setAlert($this->pl->txt("invalid_placeholders"));
            $result = false;
        }
        if (!$result) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("form_input_not_valid"), true);
        }

        return $result;
    }
    protected function init(): void
    {
        $this->setTitle($this->pl->txt("configuration"));

        $item = new ilTextInputGUI($this->pl->txt("sender_user_id"), 'notification_sender_user_id');
        $item->setInfo($this->pl->txt("sender_user_id_info"));
        $item->setRequired(true);
        $item->setValue($this->config->get($item->getPostVar()));
        $item->setDataSource($this->ctrl->getLinkTargetByClass(ilLearningObjectiveSuggestionsConfigGUI::class, ilLearningObjectiveSuggestionsConfigGUI::CMD_CONFIGURE_NOTIFICATIONS_USERS_AUTOCOMPLETE, "", true));
        $this->addItem($item);

        $item = new ilTextInputGUI($this->pl->txt("cc_role_id"), 'notification_cc_role_id');
        $item->setInfo($this->pl->txt("cc_role_id_info"));
        $item->setValue($this->config->get($item->getPostVar()));
        $item->setDataSource($this->ctrl->getLinkTargetByClass(ilLearningObjectiveSuggestionsConfigGUI::class, ilLearningObjectiveSuggestionsConfigGUI::CMD_CONFIGURE_NOTIFICATIONS_ROLES_AUTOCOMPLETE, "", true));
        $this->addItem($item);

        $item = new ilFormSectionHeaderGUI();
        $item->setTitle($this->pl->txt("templates"));
        $this->addItem($item);

        $item = new ilTextInputGUI($this->pl->txt("subject"), 'email_subject');
        $item->setRequired(true);
        $item->setValue($this->config->get($item->getPostVar()));
        $this->addItem($item);

        $item = new ilTextAreaInputGUI($this->pl->txt("body"), 'email_body');
        $item->setRequired(true);
        $item->setInfo($this->pl->txt("body_info"));
        $item->setRows(10);
        if (!is_null($this->config->get($item->getPostVar()))) {
            $item->setValue($this->config->get($item->getPostVar()));
        }
        $this->addItem($item);

        $info = "<br>" . $this->pl->txt("placeholders_info") . "<br><br>";
        $ph = array();
        $placeholders = new Placeholders();
        foreach ($placeholders->getAvailablePlaceholders() as $key => $value) {
            $ph[] = "&lbrace;&lbrace; {$key} &rbrace;&rbrace; : {$this->pl->txt($value)}";
        }
        $info .= implode('<br>', $ph);
        $item->setInfo($info);

        $this->addCommandButton(ilLearningObjectiveSuggestionsConfigGUI::CMD_SAVE_NOTIFICATIONS, $this->pl->txt("save"));
        $this->addCommandButton(ilLearningObjectiveSuggestionsConfigGUI::CMD_CANCEL, $this->pl->txt("cancel"));
    }
}
