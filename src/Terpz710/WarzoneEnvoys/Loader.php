<?php

declare(strict_types=1);

namespace Terpz710\WarzoneEnvoys;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;
use pocketmine\utils\Config;
use pocketmine\item\StringToItemParser;
use pocketmine\scheduler\ClosureTask;

use Terpz710\WarzoneEnvoys\Task\EnvoyTask;

class Loader extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->saveResource("items.yml");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $targetTimeSeconds = $this->getConfig()->get("target_time", 60);
        $targetTimeTicks = $targetTimeSeconds * 20;

        $this->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $targetTimeTicks);
    }

    public function createChest() {
        $config = $this->getConfig();
        $chestLocations = $config->get("chest_locations", []);

        $itemsConfig = new Config($this->getDataFolder() . "items.yml", Config::YAML);
        $itemsData = $itemsConfig->get("items", []);

        foreach ($chestLocations as $chestLocation) {
            $worldName = $chestLocation["world"];
            $world = $this->getServer()->getWorldManager()->getWorldByName($worldName);

            if ($world !== null) {
                $chest = VanillaBlocks::CHEST();

                $position = new Vector3($chestLocation["x"], $chestLocation["y"], $chestLocation["z"]);

                $world->setBlock($position, $chest);

                foreach ($this->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof Player) {
                        $player->sendMessage("A chest has spawned at {$worldName}, X: {$position->getX()}, Y: {$position->getY()}, Z: {$position->getZ()}!");
                    }
                }

                $this->addItemsToChest($world, $position, $itemsData);
            } else {
                $this->getLogger()->error("World not found: " . $worldName);
            }
        }
    }

    public function despawnChests(): void {
        $config = $this->getConfig();
        $chestLocations = $config->get("chest_locations", []);

        foreach ($chestLocations as $chestLocation) {
            $worldName = $chestLocation["world"];
            $world = $this->getServer()->getWorldManager()->getWorldByName($worldName);

            if ($world !== null) {
                $position = new Vector3($chestLocation["x"], $chestLocation["y"], $chestLocation["z"]);
                $block = $world->getBlock($position);

                if ($block instanceof TileChest) {
                    $world->setBlock($position, VanillaBlocks::AIR());

                    foreach ($this->getServer()->getOnlinePlayers() as $player) {
                        if ($player instanceof Player) {
                            $player->sendMessage("The chest at X: {$position->getX()}, Y: {$position->getY()}, Z: {$position->getZ()} has despawned!");
                        }
                    }
                }
            }
        }
    }

    private function addItemsToChest(World $world, Vector3 $position, array $itemsData) {
        $tile = $world->getTile($position);

        if ($tile !== null && $tile instanceof TileChest) {
            $inventory = $tile->getInventory();

            foreach ($itemsData as $itemString) {
                $item = StringToItemParser::getInstance()->parse($itemString);
                $inventory->addItem($item);
            }
        }
    }
}
