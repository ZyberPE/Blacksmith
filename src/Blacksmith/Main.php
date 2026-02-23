<?php

namespace Blacksmith;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\form\Form;

class Main extends PluginBase {

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command->getName() === "blacksmith" && $sender instanceof Player){
            $this->openShop($sender);
            return true;
        }
        return false;
    }

    private function openShop(Player $player): void {
        $form = new class($this, $player) implements Form {
            public function __construct(private Main $plugin, private Player $player) {}
            
            public function handleResponse(Player $player, $data): void {
                if($data === null) return;
                switch($data){
                    case 0:
                        $this->plugin->repairItem($player);
                        break;
                    case 1:
                        $this->plugin->renameItemForm($player);
                        break;
                }
            }

            public function jsonSerialize(): array {
                return [
                    "type" => "form",
                    "title" => "Blacksmith",
                    "content" => "Choose an option:",
                    "buttons" => [
                        ["text" => "Repair Item"],
                        ["text" => "Rename Item"]
                    ]
                ];
            }
        };

        $player->sendForm($form);
    }

    public function repairItem(Player $player): void {
        $item = $player->getInventory()->getItemInHand();
        if($item->isNull()){
            $player->sendMessage($this->getConfig()->get("messages")["no_item"]);
            return;
        }

        $xpCost = $this->getConfig()->getInt("repair_xp_cost");
        $xpManager = $player->getXpManager();

        if($xpManager->getCurrentXp() < $xpCost){
            $player->sendMessage($this->getConfig()->get("messages")["not_enough_xp"]);
            return;
        }

        $item->setDamage(0);
        $player->getInventory()->setItemInHand($item);

        $xpManager->addXp(-$xpCost);
        $player->sendMessage($this->getConfig()->get("messages")["repair_success"]);
    }

    public function renameItemForm(Player $player): void {
        $item = $player->getInventory()->getItemInHand();
        if($item->isNull()){
            $player->sendMessage($this->getConfig()->get("messages")["no_item"]);
            return;
        }

        $xpCost = $this->getConfig()->getInt("rename_xp_cost");
        $xpManager = $player->getXpManager();

        if($xpManager->getCurrentXp() < $xpCost){
            $player->sendMessage($this->getConfig()->get("messages")["not_enough_xp"]);
            return;
        }

        $form = new class($this, $player) implements Form {
            public function __construct(private Main $plugin, private Player $player) {}
            
            public function handleResponse(Player $player, $data): void {
                if($data === null || !isset($data["name"])) return;

                $item = $player->getInventory()->getItemInHand();
                if($item->isNull()) return;

                $xpCost = $this->plugin->getConfig()->getInt("rename_xp_cost");
                $xpManager = $player->getXpManager();
                if($xpManager->getCurrentXp() < $xpCost){
                    $player->sendMessage($this->plugin->getConfig()->get("messages")["not_enough_xp"]);
                    return;
                }

                $item->setCustomName($data["name"]);
                $player->getInventory()->setItemInHand($item);
                $xpManager->addXp(-$xpCost);
                $player->sendMessage($this->plugin->getConfig()->get("messages")["rename_success"]);
            }

            public function jsonSerialize(): array {
                return [
                    "type" => "custom_form",
                    "title" => "Rename Item",
                    "content" => [
                        [
                            "type" => "input",
                            "text" => $this->plugin->getConfig()->get("messages")["enter_name"],
                            "placeholder" => $this->plugin->getConfig()->get("rename_placeholder")
                        ]
                    ]
                ];
            }
        };

        $player->sendForm($form);
    }
}
