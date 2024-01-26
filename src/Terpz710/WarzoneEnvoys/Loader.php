<?php

declare(strict_types=1);

namespace Terpz710\WarzoneEnvoys;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

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
        $chestLocations = $config->get("chest_locations", []);

        foreach ($chestLocations as $chestLocation) {
            $worldName = $chestLocation["world"];
            $worldManager = $this->getServer()->getWorldManager();
            $world = $worldManager->getWorldByName($worldName);

            if ($world !== null) {
                $level = $worldManager->getWorldByName($worldName);
                $chest = VanillaBlocks::CHEST();

                $position = new Vector3($chestLocation["x"], $chestLocation["y"], $chestLocation["z"]);

                $level->setBlock($position, $chest);

                foreach ($this->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player) {
                        $player->sendMessage("A chest has spawned at $worldName, X: {$position->getX()}, Y: {$position->getY()}, Z: {$position->getZ()}!");
                    }
                }
            } else {
                $this->getLogger()->error("World not found: " . $worldName);
            }
        }
    }
}
