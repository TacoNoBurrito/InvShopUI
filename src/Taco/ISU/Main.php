<?php namespace Taco\ISU;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\{item\Item, item\ItemIds, Player, plugin\Plugin, plugin\PluginBase, utils\Config};
use Taco\ISU\commands\ShopCommand;

/**
 * Class Main
 * @package Taco\ISU
 */
class Main extends PluginBase {

	/**
	 * @var Config $config
	 */
	private $config;

	/**
	 * @var Plugin $economy
	 */
	private $economy;

	public function onEnable() : void {
		if (!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
		$this->saveResource("config.yml");
		$this->config = $this->getConfig()->getAll();
		$this->getServer()->getCommandMap()->register("InvShopMenu", new ShopCommand($this));
		$this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
	}

	/**
	 * @param Player $player
	 */
	public function openMenu1(Player $player) : void {
		$menu = InvMenu::create(InvMenu::TYPE_CHEST);
		$inventory = $menu->getInventory();
		$menu->setName("Shop Categories");
		foreach($this->config["categories"] as $cat => $i) {
			$inventory->addItem(Item::get(ItemIds::FIRE_CHARGE)->setCustomName($cat));
		}
		$menu->send($player);
		$menu->setListener(function(InvMenuTransaction $transaction) use($player, $menu, $inventory) : InvMenuTransactionResult {
			$this->openShopMenu($player, $transaction->getItemClicked()->getCustomName());
			$transaction->getPlayer()->removeWindow($transaction->getAction()->getInventory());
			return $transaction->discard();
		});
	}

	/**
	 * @param Player $player
	 * @param string $type
	 */
	public function openShopMenu(Player $player, string $type) : void {
		$menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
		$inventory = $menu->getInventory();
		$menu->setName($type);
		foreach($this->config["categories"][$type]["contents"] as $id => $price) {
			$ex = explode(":", $id);
			$inventory->addItem(Item::get($ex[0], $ex[1], 64)->setLore(["§aPrice: $".$price]));
		}
		$menu->send($player);
		$menu->setListener(function(InvMenuTransaction $transaction) use($player, $menu, $inventory) : InvMenuTransactionResult {
			$item = $transaction->getItemClicked();
			$price = str_replace("§aPrice: $", "", $item->getLore()[0]);
			$this->openBuyItemMenu($player, $item, (int)$price);
			$transaction->getPlayer()->removeWindow($transaction->getAction()->getInventory());
			return $transaction->discard();
		});
	}

	/**
	 * @param Player $player
	 * @param Item $item
	 * @param int $price
	 */
	public function openBuyItemMenu(Player $player, Item $item, int $price) {
		$menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
		$inventory = $menu->getInventory();
		$menu->setName("Buy Menu");
		$inventory->setItem(39, Item::get(20, 13)->setCustomName("§aPurchase Item"));
		$inventory->setItem(41, Item::get(20, 14)->setCustomName("§cClose Menu"));
		$inventory->setItem(18, Item::get(160, 14)->setCustomName("§cSet Stack Size To One"));
		$inventory->setItem(19, Item::get(160, 14, 10)->setCustomName("§cRemove 10 From Stack"));
		$inventory->setItem(20, Item::get(160, 14)->setCustomName("§cRemove 1 From Stack"));
		$inventory->setItem(22, $item);
		$menu->send($player);
		$menu->setListener(function(InvMenuTransaction $transaction) use($player, $menu, $inventory, $item, $price) : InvMenuTransactionResult {
			$size = 64;
			$clicked = $transaction->getItemClicked();
			switch($clicked->getCustomName()) {
				case "§cSet Stack Size To One":
					$size = 1;
					$item = Item::get($item->getId(), $item->getDamage(), 1);
					$inventory->setItem(22, $item);
					break;
				case "§cRemove 10 From Stack":
					if ($size - 10 <= 0) {

					} else {
						$size = $size - 10;
						$item = Item::get($item->getId(), $item->getDamage(), $size);
						$inventory->setItem(22, $item);
					}
					break;
				case "§cRemove 1 From Stack":
					if ($size - 1 <= 0) {

					} else {
						$size = $size - 1;
						$item = Item::get($item->getId(), $item->getDamage(), $size);
						$inventory->setItem(22, $item);
					}
					break;
				case "§cClose Menu":
					$transaction->getPlayer()->removeWindow($transaction->getAction()->getInventory());
					break;
				case "§aPurchase Item":
					$money = $this->economy->myMoney($player);
					if ($money >= $price) {
						$this->economy->reduceMoney($player, $price);
						$player->sendMessage("§aSuccessfully Purchased For ${$price}!");
					} else $player->sendMessage("§cYou Do Not Have Enough Money To Purchase This Item!");
					$transaction->getPlayer()->removeWindow($transaction->getAction()->getInventory());
					break;
			}
			return $transaction->discard();
		});
	}

}