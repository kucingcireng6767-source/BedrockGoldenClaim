<?php

namespace BedrockClaim;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {

    private array $pos1 = [];
    private Config $claims;

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->claims = new Config($this->getDataFolder() . "claims.yml", Config::YAML);
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlock();

        if ($item->getTypeId() === ItemTypeIds::GOLDEN_SHOVEL) {
            $event->cancel();
            $name = strtolower($player->getName());
            $pos = $block->getPosition();

            if (!isset($this->pos1[$name])) {
                $this->pos1[$name] = [$pos->getX(), $pos->getZ(), $pos->getWorld()->getFolderName()];
                $player->sendMessage("§e[Claim] Sudut 1 ditandai! Klik sudut diagonal (Sudut 2).");
            } else {
                $p1 = $this->pos1[$name];
                unset($this->pos1[$name]);

                if ($p1[2] !== $pos->getWorld()->getFolderName()) {
                    $player->sendMessage("§c[Claim] Gagal! Kedua sudut harus di dunia yang sama.");
                    return;
                }

                $minX = min($p1[0], $pos->getX());
                $maxX = max($p1[0], $pos->getX());
                $minZ = min($p1[1], $pos->getZ());
                $maxZ = max($p1[1], $pos->getZ());

                $claimData = [
                    "owner" => $player->getName(),
                    "world" => $pos->getWorld()->getFolderName(),
                    "minX" => $minX, "maxX" => $maxX,
                    "minZ" => $minZ, "maxZ" => $maxZ
                ];

                $allClaims = $this->claims->get("data", []);
                $allClaims[] = $claimData;
                $this->claims->set("data", $allClaims);
                $this->claims->save();

                $player->sendMessage("§a[Claim] Berhasil! Lahan ini sekarang milik: §b" . $player->getName());
            }
        }
    }

    public function onBreak(BlockBreakEvent $event): void {
        $this->checkProtection($event, $event->getPlayer(), $event->getBlock()->getPosition());
    }

    public function onPlace(BlockPlaceEvent $event): void {
        $this->checkProtection($event, $event->getPlayer(), $event->getBlock()->getPosition());
    }

    private function checkProtection($event, Player $player, Position $pos): void {
        $allClaims = $this->claims->get("data", []);
        $x = $pos->getX();
        $z = $pos->getZ();
        $world = $pos->getWorld()->getFolderName();

        foreach ($allClaims as $claim) {
            if ($claim["world"] === $world && $x >= $claim["minX"] && $x <= $claim["maxX"] && $z >= $claim["minZ"] && $z <= $claim["maxZ"]) {
                if (strtolower($claim["owner"]) !== strtolower($player->getName())) {
                    $event->cancel();
                    $player->sendMessage("§c[Dilarang] Ini adalah lahan milik §e" . $claim["owner"] . "§c!");
                    break;
                }
            }
        }
    }
}
