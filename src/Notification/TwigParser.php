<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification;

use ilMailBodyPurifier;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;

class TwigParser implements Parser
{
    /**
     * Templates are entered by admins, so they are rendered in the Twig sandbox: only these tags, filters
     * and the placeholder getters (see Placeholders) are allowed. Everything else, e.g. callables in
     * map/filter/sort or functions like constant() and include(), raises a SecurityError.
     */
    private const array ALLOWED_TAGS = ['if', 'for', 'set'];

    private const array ALLOWED_FILTERS = [
        'escape',
        'e',
        'upper',
        'lower',
        'capitalize',
        'title',
        'trim',
        'default',
        'length',
        'join',
        'first',
        'last',
        'date',
    ];

    private const array ALLOWED_METHODS = [
        User::class => ['getLogin', 'getFirstname', 'getLastname', 'getEmail'],
        LearningObjectiveCourse::class => ['getTitle', 'getLink'],
    ];

    /**
     * @param string $template
     * @param array $placeholders
     * @return string
     */
    public function parse(string $template, array $placeholders): string
    {
        $twig = $this->getTwig();
        $tpl = $twig->createTemplate($template);

        // Mails are plain text, so instead of HTML-escaping (which would show entities like &amp;) the result
        // is sanitized the same way the core mail form does it, which neutralizes HTML from placeholder values
        return (new ilMailBodyPurifier())->purify($tpl->render($placeholders));
    }

    /**
     * @param string $template
     * @param array  $placeholders
     * @return bool
     */
    public function isValid(string $template, array $placeholders): bool
    {
        try {
            $this->parse($template, $placeholders);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return Environment|null
     */
    protected function getTwig(): ?Environment
    {
        static $instance = null;
        if ($instance !== null) {
            return $instance;
        }

        $loader = new ArrayLoader([]);
        $twig = new Environment($loader, ['autoescape' => false]);
        $twig->addExtension(new SandboxExtension(
            new SecurityPolicy(self::ALLOWED_TAGS, self::ALLOWED_FILTERS, self::ALLOWED_METHODS, [], []),
            true
        ));
        $instance = $twig;

        return $twig;
    }
}
