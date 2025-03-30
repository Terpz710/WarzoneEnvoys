<?php

declare(strict_types=1);

namespace terpz710\warzoneenvoys\manager;

use pocketmine\Server;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\Chest as TileChest;

use pocketmine\math\Vector3;

use pocketmine\player\Player;

use pocketmine\world\World;
use pocketmine\world\Position;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\utils\SingletonTrait;

use pocketmine\scheduler\ClosureTask;

use pocketmine\item\StringToItemParser;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\EnchantmentInstance;

use terpz710\warzoneenvoys\Loader;

use terpz710\warzoneenvoys\api\EnvoyAPI;

final class EnvoyManager {
    use SingletonTrait;

    private Config $messagesConfig;

    public function __construct() {
        $this->messagesConfig = new Config($this->getDataFolder() . "messages.yml");
    }

    public function removeFloatingText(Position $position) : void{
        $tag = "envoy_" . $position->getX() . "_" . $position->getY() . "_" . $position->getZ();
        EnvoyAPI::remove($tag);
    }

    public function dropItemsFromChest(Position $position) : void{
        $world = $position->getWorld();

        if ($world !== null) {
            $tile = $world->getTile($position);

            if ($tile !== null && $tile instanceof TileChest) {
                $inventory = $tile->getInventory();

                foreach ($inventory->getContents() as $slot => $item) {
                    $world->dropItem($position->add(0.5, 1, 0.5), $item);
                    $inventory->clear($slot);
                }
            }
        }
    }

    public function removeChest(Position $position) : void{
        $world = $position->getWorld();
        if ($world !== null) {
            $world->setBlock($position, VanillaBlocks::AIR());
            EnvoyAPI::explode($position);
        }
    }

    public function createChest() : void{
        $config = Loader::getInstance()->getConfig();
        $chestLocations = $config->get("chest_locations", []);

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($player instanceof Player) {
                $this->sendCountdownMessage($player, $this->messagesConfig->get("envoy_spawn_countdown"));
            }
        }

        foreach ($chestLocations as $chestLocation) {
            $worldName = $chestLocation["world"];
            $world = $this->getServer()->getWorldManager()->getWorldByName($worldName);

            if ($world !== null) {
                $chest = VanillaBlocks::CHEST();
                $position = new Position($chestLocation["x"], $chestLocation["y"], $chestLocation["z"], $world);
                $world->setBlock($position, $chest);
                $text = $this->messagesConfig->get("floating_text");
                EnvoyAPI::create($position, $text, "envoy_" . $position->getX() . "_" . $position->getY() . "_" . $position->getZ());

                EnvoyItems::getInstance()->addItemsToChest($world, $position);
            } else {
                Loader::getInstance()->getLogger()->error("World not found: " . $worldName);
            }
        }

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($player instanceof Player) {
                $this->sendBroadcastMessage($player, $this->messagesConfig->get("envoy_spawned"));
            }
        }
    }

    public function sendCountdownMessage(Player $player, string $message) : void{
        $targetTimeSeconds = Loader::getInstance()->getConfig()->get("target_time", 60);
        $countdown = [15, 10, 5, 4, 3, 2, 1];

        foreach ($countdown as $seconds) {
            if ($seconds <= $targetTimeSeconds) {
                $countdownTask = new ClosureTask(function () use ($player, $message, $seconds) {
                    if ($player->isOnline()) {
                        $formattedMessage = str_replace(["{seconds}"], [$seconds], $message);
                        $player->sendMessage($formattedMessage);
                    }
                });
                $taskHandler = Loader::getInstance()->getScheduler()->scheduleDelayedTask($countdownTask, ($targetTimeSeconds - $seconds) * 20);

                if ($taskHandler->isCancelled()) {
                    break;
                }
            }
        }
    }

    public function sendBroadcastMessage(Player $player, string $message) : void{
        $formattedMessage = $message;

        $player->sendMessage($formattedMessage);
    }
}