<?php

declare(strict_types=1);

namespace Blacksmith;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\entity\human\Human;
use pocketmine\world\Position;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase implements Listener {

    /** @var array<string, array> Stores NPCs */
    private array $blacksmiths = [];
    private Config $cfg;

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->cfg = $this->getConfig();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if(!$sender instanceof Player) return false;

        if(!$sender->hasPermission("blacksmith.use")) {
            $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command.");
            return true;
        }

        $this->spawnBlacksmith($sender);

        return true;
    }

    private function spawnBlacksmith(Player $player): void {
        $world = $player->getWorld();
        $pos = $player->getPosition();

        $npc = new Human($world, $pos);
        $npc->setNameTag(TextFormat::colorize($this->cfg->getNested("messages.npc-name", "&bBlacksmith")));
        $npc->setNameTagAlwaysVisible(true);
        $npc->setSkin($player->getSkin());
        $npc->spawnToAll();

        $this->blacksmiths[$player->getName()] = [
            "position" => $pos
        ];

        $player->sendMessage(TextFormat::GREEN . "Blacksmith NPC spawned! Click it to open the shop.");
    }

    public function onInteractNPC(PlayerInteractEntityEvent $event): void {
        $player = $event->getPlayer();
        $entity = $event->getEntity();

        if($entity instanceof Human){
            $npcName = TextFormat::clean($entity->getNameTag());
            if($npcName === TextFormat::clean($this->cfg->getNested("messages.npc-name", "Blacksmith"))){
                $this->openShop($player);
            }
        }
    }

    public function openShop(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data){
            if($data === null) return;

            switch($data){
                case 0: $this->repairItem($player); break;
                case 1: $this->renameItem($player); break;
            }
        });

        $form->setTitle(TextFormat::colorize($this->cfg->getNested("messages.shop-title", "&6Blacksmith Shop")));
        $form->addButton("Repair Item");
        $form->addButton("Rename Item");
        $player->sendForm($form);
    }

    private function repairItem(Player $player): void {
        $xpCost = (int)$this->cfg->get("xp-cost", 2);
        $item = $player->getInventory()->getItemInHand();

        if($item->isNull()){
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.no-item")));
            return;
        }

        if($player->getXpLevel() < $xpCost){
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.no-xp")));
            return;
        }

        $item->setDamage(0);
        $player->getInventory()->setItemInHand($item);
        $player->subtractXpLevels($xpCost);
        $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.repair-success")));
    }

    private function renameItem(Player $player): void {
        $xpCost = (int)$this->cfg->get("xp-cost", 2);
        $item = $player->getInventory()->getItemInHand();

        if($item->isNull()){
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.no-item")));
            return;
        }

        if($player->getXpLevel() < $xpCost){
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.no-xp")));
            return;
        }

        $form = new CustomForm(function(Player $player, ?array $data){
            if($data === null) return;

            $name = $data[0] ?? null;
            if($name === null || $name === ""){
                $player->sendMessage(TextFormat::RED . "You must enter a valid name.");
                return;
            }

            $item = $player->getInventory()->getItemInHand();
            $item->setCustomName(TextFormat::colorize($name));
            $player->getInventory()->setItemInHand($item);
            $player->subtractXpLevels((int)$this->cfg->get("xp-cost", 2));
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.rename-success")));
        });

        $form->setTitle(TextFormat::colorize($this->cfg->getNested("messages.shop-title")));
        $form->addInput("Enter new name for your item:");
        $player->sendForm($form);
    }
}
