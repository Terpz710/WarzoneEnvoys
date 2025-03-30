<?php

declare(strict_types=1);

namespace terpz710\warzoneenvoys\manager;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\utils\SingletonTrait;

use pocketmine\world\World;
use pocketmine\world\Position;

use pocketmine\block\tile\Chest as TileChest;

use pocketmine\item\StringToItemParser;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\EnchantmentInstance;

use terpz710\warzoneenvoys\Loader;

class EnvoyItems {
    use SingletonTrait;

    public function addItemsToChest(World $world, Position $position) : void{
        $tile = $world->getTile($position);

        if ($tile !== null && $tile instanceof TileChest) {
            $inventory = $tile->getInventory();
            $allSlotsEmpty = true;

            foreach ($inventory->getContents() as $content) {
                if (!$content->isNull()) {
                    $allSlotsEmpty = false;
                    break;
                }
            }

            if ($allSlotsEmpty) {
                $itemsPerChestConfig = Loader::getInstance()->getConfig()->get("items_per_chest", []);
                $minItemsPerChest = (int) $itemsPerChestConfig["min"] ?? 1;
                $maxItemsPerChest = (int) $itemsPerChestConfig["max"] ?? 5;
                $minItemsPerChest = min($minItemsPerChest, $maxItemsPerChest);
                $numItemsToAdd = mt_rand($minItemsPerChest, $maxItemsPerChest);
                $chestSize = $inventory->getSize();
                $itemsConfig = new Config($this->getDataFolder() . "items.yml");
                $itemsData = $itemsConfig->get("items", []);
                $availableSlots = range(0, $chestSize - 1);
                shuffle($availableSlots);

                for ($i = 0; $i < $numItemsToAdd; $i++) {
                    $itemString = array_shift($itemsData);

                    if ($itemString !== null) {
                        $itemComponents = explode(":", $itemString);
                        $itemName = $itemComponents[0];
                        $quantity = $itemComponents[1] ?? 1;
                        $customName = ($itemComponents[2] ?? "") === "DEFAULT" ? null : $itemComponents[2] ?? null;
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

                        $slotIndex = array_shift($availableSlots);

                        if ($slotIndex !== null) {
                            $inventory->setItem($slotIndex, $item);
                        } else {
                            break;
                        }
                    }
                }
            }
        }
    }
}