<?php

declare(strict_types=1);

namespace Terpz710\WarzoneEnvoys;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;

use Terpz710\WarzoneEnvoys\Task\EnvoyTask;

class Loader extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $targetTimeSeconds = $this->getConfig()->get("target_time", 30);
        $targetTimeTicks = $targetTimeSeconds * 20;

        $this->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $targetTimeTicks);
    }

    public function createChest() {
        $config = $this->getConfig();
        $chestLocation = $config->get("chest_location", []);

        $worldManager = $this->getServer()->getWorldManager();
        $world = $worldManager->getWorldByName($chestLocation["world"]);

        if ($world !== null) {
            $level = $worldManager->getWorldByName($chestLocation["world"]);
            $chest = VanillaBlocks::CHEST();
            
            $position = new Vector3($chestLocation["x"], $chestLocation["y"], $chestLocation["z"]);
            
            $level->setBlock($position, $chest);

            $world->broadcastMessage("A chest has spawned!");

        } else {
            $this->getLogger()->error("World not found: " . $chestLocation["world"]);
        }
    }
}
