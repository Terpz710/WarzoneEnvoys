<?php

declare(strict_types=1);

namespace terpz710\warzoneenvoys\utils;

use pocketmine\player\Player;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;

use pocketmine\utils\SingletonTrait;

class EnvoyUtils {
    use SingletonTrait;

    public function playSound(Player $player, string $sound) : void{
        $pos = $player->getPosition();
        $packet = PlaySoundPacket::create($sound, $pos->getX(), $pos->getY(), $pos->getZ(), 150, 1);

        $player->getNetworkSession()->sendDataPacket($packet);
    }
}