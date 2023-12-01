<?php declare(strict_types=1);

namespace Drupal\wire\Drush\Generators;

use DrupalCodeGenerator\Asset\AssetCollection as Assets;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\GeneratorType;
use DrupalCodeGenerator\Utils;

#[Generator(
  name: 'wire:component:create',
  description: 'Generates Wire component',
  aliases: ['wire'],
  templatePath: __DIR__ . '/templates',
  type: GeneratorType::MODULE_COMPONENT,
)]
final class WireComponentCreateGenerator extends BaseGenerator {

  protected function generate(array &$vars, Assets $assets): void {
    $ir = $this->createInterviewer($vars);

    $vars['machine_name'] = $ir->askMachineName();

    $vars['wire_label'] = $ir->askPluginLabel('Wire label');
    $vars['wire_id'] = $ir->askPluginId('Wire ID', '{wire_label|h2m}');
    $vars['class'] = $ir->askPluginClass('Wire class', Utils::camelize($vars['wire_id']));
    $vars['inline'] = $ir->confirm('Inline template', FALSE);
    $vars['services'] = $ir->askServices(FALSE);

    $assets->addFile('src/Plugin/WireComponent/{class}.php', 'wire_component.class.twig');
    if (!$vars['inline']) {
      $assets->addFile('templates/wire/{wire_id}.html.twig', 'wire_component.tpl.twig');
    }
  }

}
