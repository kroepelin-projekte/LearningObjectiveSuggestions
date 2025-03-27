<?php
namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigParser implements Parser {
	public function parse(string $template, array $placeholders): string
    {
		$twig = $this->getTwig();
		$tpl = $twig->createTemplate($template);

		return $tpl->render($placeholders);
	}
	public function isValid(string $template, array $placeholders): bool
    {
		try {
			$this->parse($template, $placeholders);

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
	protected function getTwig(): ?Environment
    {
		static $instance = NULL;
		if ($instance !== NULL) {
			return $instance;
		}
		/*$loader = new \Twig_Loader_Array([]);
		$twig = new \Twig_Environment($loader, array( 'autoescape' => false ));*/
        $loader = new ArrayLoader([]);
        $twig = new Environment($loader, ['autoescape' => false]);
		$instance = $twig;

		return $twig;
	}
}