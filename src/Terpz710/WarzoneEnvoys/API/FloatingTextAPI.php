<?php

namespace Terpz710\WarzoneEnvoys\API;

use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;

class FloatingTextAPI
{
    public static array $floatingText = [];

    public static function create(Position $position, string $text, string $tag): void
    {
        $floatingText = new FloatingTextParticle($text);
        if (array_key_exists($tag, self::$floatingText)) {
            self::remove($tag);
        }
        self::$floatingText[$tag] = [$position, $floatingText];
        $position->getWorld()->addParticle($position, $floatingText, $position->getWorld()->getPlayers());
    }

    public static function remove(string $tag): void
    {
        if (!array_key_exists($tag, self::$floatingText)) {
            return;
        }
        $floatingText = self::$floatingText[$tag][1];
        $floatingText->setInvisible();
        self::$floatingText[$tag][1] = $floatingText;
        self::$floatingText[$tag][0]->getWorld()->addParticle(self::$floatingText[$tag][0], $floatingText, self::$floatingText[$tag][0]->getWorld()->getPlayers());
        unset(self::$floatingText[$tag]);
    }

    public static function update(string $tag, string $text): void
    {
        if (!array_key_exists($tag, self::$floatingText)) {
            return;
        }
        $floatingText = self::$floatingText[$tag][1];
        $floatingText->setText($text);
        self::$floatingText[$tag][1] = $floatingText;
        self::$floatingText[$tag][0]->getWorld()->addParticle(self::$floatingText[$tag][0], $floatingText, self::$floatingText[$tag][0]->getWorld()->getPlayers());
    }
}