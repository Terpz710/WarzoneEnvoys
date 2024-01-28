<?php

declare(strict_types=1);

namespace Terpz710\WarzoneEnvoys;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\ClosureTask;
use pocketmine\item\StringToItemParser;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\EnchantmentInstance;

use Terpz710\WarzoneEnvoys\Task\EnvoyTask;
use Terpz710\WarzoneEnvoys\API\FloatingTextAPI;

class Loader extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->saveResource("items.yml");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $targetTimeSeconds = $this->getConfig()->get("target_time", 60);
        $targetTimeTicks = $targetTimeSeconds * 20;
        $this->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $targetTimeTicks);
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($block->getTypeId() === VanillaBlocks::CHEST()->getTypeId()) {
            $event->cancel();
            $this->dropItemsFromChest($block->getPosition());
            $this->removeChest($block->getPosition());
            $this->removeFloatingText($block->getPosition());
            $player->sendMessage(TextFormat::GREEN . "Envoy claimed!");
        }
    }

    private function removeFloatingText(Position $position): void {
        $tag = "envoy_" . $position->getX() . "_" . $position->getY() . "_" . $position->getZ();
        FloatingTextAPI::remove($tag);
    }

    private function dropItemsFromChest(Position $position): void {
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

    private function removeChest(Position $position): void {
        $world = $position->getWorld();
        if ($world !== null) {
            $world->setBlock($position, VanillaBlocks::AIR());
        }
    }

    public function createChest() {
        $config = $this->getConfig();
        $chestLocations = $config->get("chest_locations", []);

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            if ($player instanceof Player) {
                $this->sendCountdownMessage($player, "Envoys are spawning soon, Get ready! Envoys are spawning in§f");
            }
        }

        foreach ($chestLocations as $chestLocation) {
            $worldName = $chestLocation["world"];
            $world = $this->getServer()->getWorldManager()->getWorldByName($worldName);

            if ($world !== null) {
                $chest = VanillaBlocks::CHEST();
                $position = new Position($chestLocation["x"], $chestLocation["y"], $chestLocation["z"], $world);
                $world->setBlock($position, $chest);
                $text = "§l§bEnvoy\nTap me!";
                FloatingTextAPI::create($position, $text, "envoy_" . $position->getX() . "_" . $position->getY() . "_" . $position->getZ());

                $this->addItemsToChest($world, $position);
            } else {
                $this->getLogger()->error("World not found: " . $worldName);
            }
        }

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            if ($player instanceof Player) {
                $this->sendBroadcastMessage($player, "Envoys has spawned!");
            }
        }
    }

    private function sendCountdownMessage(Player $player, string $message): void {
        $targetTimeSeconds = $this->getConfig()->get("target_time", 60);
        $countdown = [15, 10, 5, 4, 3, 2, 1];

        foreach ($countdown as $seconds) {
            if ($seconds <= $targetTimeSeconds) {
                $countdownTask = new ClosureTask(function () use ($player, $message, $seconds) {
                    if ($player->isOnline()) {
                        $player->sendMessage(TextFormat::YELLOW . "$message $seconds" . " seconds§e!");
                    }
                });
                $taskHandler = $this->getScheduler()->scheduleDelayedTask($countdownTask, ($targetTimeSeconds - $seconds) * 20);

                if ($taskHandler->isCancelled()) {
                    break;
                }
            }
        }
    }

    private function sendBroadcastMessage(Player $player, string $message): void {
        $player->sendMessage(TextFormat::GREEN . $message);
    }

    private function addItemsToChest(World $world, Position $position): void {
        $tile = $world->getTile($position);

        if ($tile !== null && $tile instanceof TileChest) {
            $inventory = $tile->getInventory();
            $chestSize = $inventory->getSize();
            $itemsConfig = new Config($this->getDataFolder() . "items.yml", Config::YAML);
            $itemsData = $itemsConfig->get("items", []);
            $availableSlots = range(0, $chestSize - 1);
            shuffle($availableSlots);

            foreach ($itemsData as $itemString) {
                $itemComponents = explode(":", $itemString);
                $itemName = $itemComponents[0];
                $quantity = $itemComponents[1] ?? 1;
                $customName = $itemComponents[2] ?? null;
                $enchantments = $itemComponents[3] ?? null;
                $item = StringToItemParser::getInstance()->parse($itemName);
                $item->setCount((int)$quantity);

                if ($customName !== null) {
                    $item->setCustomName($customName);
                }

                if ($enchantments !== null) {
                    $enchantmentData = explode(",", $enchantments);

                    foreach ($enchantmentData as $enchantmentString) {
                        $enchantmentComponents = explode("=", $enchantmentString);
                        $enchantmentName = $enchantmentComponents[0];
                        $enchantmentLevel = $enchantmentComponents[1] ?? 1;
                        $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentName);
                        $enchantmentInstance = new EnchantmentInstance($enchantment, (int)$enchantmentLevel);
                        $item->addEnchantment($enchantmentInstance);
                    }
                }

                $slotIndex = array_pop($availableSlots);

                if ($slotIndex !== null) {
                    $inventory->setItem($slotIndex, $item);
                } else {
                    break;
                }
            }
        }
    }
}
