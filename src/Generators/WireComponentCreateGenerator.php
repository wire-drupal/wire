<?php declare(strict_types=1);

namespace Drupal\wire\Generators;

use DrupalCodeGenerator\Command\DrupalGenerator;
use DrupalCodeGenerator\Command\Plugin\PluginGenerator;
use DrupalCodeGenerator\Utils;

/**
 * Implements "generate wire:component:create" command.
 */
class WireComponentCreateGenerator extends PluginGenerator {

  protected string $name = 'wire:component:create';

  protected string $description = 'Generates Wire component.';

  protected string $alias = 'wire';

  protected string $templatePath = __DIR__ . '/templates';

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars): void {
    $this->collectDefault($vars);
    $this->collectServices($vars, FALSE);

    $this->addFile('src/Plugin/WireComponent/{class}.php', 'wire_component.class');
    if ($vars['inline'] === 'No') {
      $this->addFile('templates/wire/{wire_id}.html.twig', 'wire_component.tpl');
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function collectDefault(array &$vars): void {
    DrupalGenerator::collectDefault($vars);
    $vars['wire_label'] = $this->askWireLabelQuestion();
    $vars['wire_id'] = $this->askWireIdQuestion();
    $vars['class'] = $this->askWireClassQuestion($vars);
    $vars['inline'] = $this->askWireInlineQuestion($vars);
  }

  protected function askWireLabelQuestion(): ?string {
    return $this->ask('Wire label', 'Example', '::validateRequired');
  }

  protected function askWireIdQuestion(): ?string {
    return $this->ask('Wire ID', '{wire_label|h2m}', '::validateRequiredMachineName');
  }

  protected function askWireClassQuestion(array $vars): string {
    return $this->ask('Wire class', Utils::camelize($vars['wire_id']));
  }

  protected function askWireInlineQuestion(): ?string {
    return $this->ask('Inline template', 'No', '::validateRequired');
  }

}
