<?php

declare(strict_types=1);

namespace Terpz710\WarzoneEnvoys\Task;

use pocketmine\scheduler\Task;
use pocketmine\scheduler\ClosureTask;

use Terpz710\WarzoneEnvoys\Loader;

class EnvoyTask extends Task {

    private $plugin;

    public function __construct(Loader $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(): void {
        $this->plugin->createChest();

        $chestDespawnTime = $this->plugin->getConfig()->get("chest_despawn_time", 30);
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () {
            $this->plugin->despawnChests();
        }), $chestDespawnTime * 20);
    }
}
