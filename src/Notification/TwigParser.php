<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification;

use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigParser implements Parser
{
    /**
     * @param string $template
     * @param array $placeholders
     * @return string
     */
    public function parse(string $template, array $placeholders): string
    {
        $twig = $this->getTwig();
        $tpl = $twig->createTemplate($template);

        return $tpl->render($placeholders);
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
        $instance = $twig;

        return $twig;
    }
}
