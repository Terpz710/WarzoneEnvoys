<?php

declare(strict_types=1);

namespace Terpz710\WarzoneEnvoys\Task;

use pocketmine\scheduler\Task;

class EnvoyTask extends Task {

    private $plugin;

    public function __construct(Loader $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(): void {
        $currentTime = $this->plugin->getServer()->getTick() % 24000;

        $config = $this->plugin->getConfig();
        $targetTime = $config->get("target_time", 6000);

        if ($currentTime === $targetTime) {
            $this->plugin->createChest();
        }
    }
}