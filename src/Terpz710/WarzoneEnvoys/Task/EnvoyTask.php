<?php

declare(strict_types=1);

namespace Terpz710\WarzoneEnvoys\Task;

use pocketmine\scheduler\Task

use Terpz710\WarzoneEnvoys\Loader;

class EnvoyTask extends Task {

    private $plugin;

    public function __construct(Loader $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(): void {
        $this->plugin->createChest();
    }
}
