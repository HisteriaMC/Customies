<?php
declare(strict_types=1);

namespace customiesdevs\customies\item;

use InvalidArgumentException;
use minicore\CustomPlayer;
use pocketmine\block\Block;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\inventory\CreativeCategory;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use ReflectionClass;
use function array_values;

final class CustomiesItemFactory {
	use SingletonTrait;

	/** @var ItemTypeEntry[] */
	private array $itemTableEntries = [];

    /**
     * Identifier in first key of the array and item object in the second
     * @var array<array{id: int, id_string: string, item: Item}> $itemRegisteredList
     */
    private array $itemRegisteredList = [];

	/**
	 * Get a custom item from its identifier. An exception will be thrown if the item is not registered.
	 */
	public function get(string $identifier, int $amount = 1): Item {
		$item = StringToItemParser::getInstance()->parse($identifier);

		if($item === null) {
			throw new InvalidArgumentException("Custom item " . $identifier . " is not registered");
		}
		return $item->setCount($amount);
	}

	/**
	 * Returns the item properties CompoundTag which maps out all custom item properties.
	 * @return ItemTypeEntry[]
	 */
	public function getItemComponentEntries(?CustomPlayer $player = null): array {
		return is_null($player) ? $this->itemTableEntries : $this->regenerateItemComponents($player);
	}

	/**
	 * Returns custom item entries for the StartGamePacket itemTable property.
	 * @return ItemTypeEntry[]
	 */
	public function getItemTableEntries(): array {
		return array_values($this->itemTableEntries);
	}

    /**
     * Registers the item to the item factory and assigns it an ID. It also updates the required mappings and stores the
     * item components if present.
     * @phpstan-param class-string $className
     */
    public function registerItem(Item $item, string $identifier): void
    {
        /*if ($className !== Item::class)
            Utils::testValidInstance($className, Item::class);

        $itemId = Cache::getInstance()->getNextAvailableItemID($identifier);
        $item = new $className(new ItemIdentifier($itemId), $name);*/
        $itemId = $item->getTypeId();
		$this->registerCustomItemMapping($identifier, $itemId);

		GlobalItemDataHandlers::getDeserializer()->map($identifier, fn() => clone $item);
		GlobalItemDataHandlers::getSerializer()->map($item, fn() => new SavedItemData($identifier));

		StringToItemParser::getInstance()->register($identifier, fn() => clone $item);

//        $componentBased = $item instanceof ItemComponents;

        $this->itemRegisteredList[] = [
            "id" => $itemId,
            "id_string" => $identifier,
            "item" => $item
        ];

		$this->itemTableEntries[$identifier] = new ItemTypeEntry($identifier, $itemId, true, 1,
            new CacheableNbt($item->getComponents()
                ->setInt("id", $itemId) //TODO: is it useful to set the id here ?
                ->setString("name", $identifier)
            )
        );

        if (method_exists($item, 'getCreativeInfo')) {
            $creativeCategory = match ($item->getCreativeInfo()->getCategory()) {
                CreativeInventoryInfo::CATEGORY_CONSTRUCTION => CreativeCategory::CONSTRUCTION,
                CreativeInventoryInfo::CATEGORY_NATURE => CreativeCategory::NATURE,
                CreativeInventoryInfo::CATEGORY_EQUIPMENT => CreativeCategory::EQUIPMENT,
                default => CreativeCategory::ITEMS
            };
        } else $creativeCategory = CreativeCategory::ITEMS;


		CreativeInventory::getInstance()->add($item, $creativeCategory); //TODO groupId 1.21.60
	}

    public function regenerateItemComponents(CustomPlayer $player): array {
        $itemComponentEntries = [];
        foreach ($this->itemRegisteredList as $item) {
            $itemObject = $item["item"];
            $itemId = $item["id"];
            $identifier = $item["id_string"];

            if($itemObject instanceof ItemComponents) {
                $itemComponentEntries[$identifier] = new ItemTypeEntry($identifier, $itemId, true, 1,
                    new CacheableNbt($itemObject->getComponents($player)
                        ->setInt("id", $itemId) //TODO: is it useful to set the id here ?
                        ->setString("name", $identifier)
                    )
                );
            }
        }
        return $itemComponentEntries;
    }

	/**
	 * Registers a custom item ID to the required mappings in the global ItemTypeDictionary instance.
	 */
	private function registerCustomItemMapping(string $identifier, int $itemId): void {
		$dictionary = TypeConverter::getInstance()->getItemTypeDictionary();
		$reflection = new ReflectionClass($dictionary);

		$intToString = $reflection->getProperty("intToStringIdMap");
		/** @var int[] $value */
		$value = $intToString->getValue($dictionary);
		$intToString->setValue($dictionary, $value + [$itemId => $identifier]);

		$stringToInt = $reflection->getProperty("stringToIntMap");
		/** @var int[] $value */
		$value = $stringToInt->getValue($dictionary);
		$stringToInt->setValue($dictionary, $value + [$identifier => $itemId]);
	}

	/**
	 * Registers the required mappings for the block to become an item that can be placed etc. It is assigned an ID that
	 * correlates to its block ID.
	 */
	public function registerBlockItem(string $identifier, Block $block): void {
		$itemId = $block->getIdInfo()->getBlockTypeId();
		$this->registerCustomItemMapping($identifier, $itemId);
		StringToItemParser::getInstance()->registerBlock($identifier, fn() => clone $block);
		$this->itemTableEntries[] = new ItemTypeEntry($identifier, $itemId, false, 2, new CacheableNbt(new CompoundTag()));

		$blockItemIdMap = BlockItemIdMap::getInstance();
		$reflection = new ReflectionClass($blockItemIdMap);

		$itemToBlockId = $reflection->getProperty("itemToBlockId");
		/** @var string[] $value */
		$value = $itemToBlockId->getValue($blockItemIdMap);
		$itemToBlockId->setValue($blockItemIdMap, $value + [$identifier => $identifier]);
	}
}
