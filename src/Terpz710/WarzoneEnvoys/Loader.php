<?php

declare(strict_types=1);

namespace Terpz710\WarzoneEnvoys;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

use Terpz710\WarzoneEnvoys\Task\EnvoyTask;

class Loader extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), 1200);
    }

    public function createChest() {
        $config = $this->getConfig();
        $targetTime = $config->get("target_time", 6000);
        $chestLocation = $config->get("chest_location", []);

        $worldManager = $this->getServer()->getWorldManager();
        $world = $worldManager->getWorldByName($chestLocation["world"]);

        if ($world !== null) {
            $level = $world->getWorld();
            $chest = VanillaBlocks::CHEST();
            $chest->setComponents($chestLocation["x"], $chestLocation["y"], $chestLocation["z"]);
            $level->setBlock($chest, $chest);
        } else {
            $this->getLogger()->error("World not found: " . $chestLocation["world"]);
        }
    }
}