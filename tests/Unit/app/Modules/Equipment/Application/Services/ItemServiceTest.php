<?php

namespace Tests\Unit\App\Modules\Equipment\Application\Services;

use App\Modules\Character\Application\Contracts\CharacterRepositoryInterface;
use App\Modules\Character\Domain\Character;
use App\Modules\Character\Domain\CharacterId;
use App\Modules\Equipment\Application\Commands\CreateItemCommand;
use App\Modules\Equipment\Application\Contracts\ItemPrototypeRepositoryInterface;
use App\Modules\Equipment\Application\Contracts\ItemRepositoryInterface;
use App\Modules\Equipment\Domain\Item;
use App\Modules\Equipment\Domain\ItemId;
use App\Modules\Equipment\Domain\ItemPrice;
use App\Modules\Equipment\Domain\ItemPrototype;
use App\Modules\Equipment\Application\Factories\ItemFactory;
use App\Modules\Equipment\Application\Services\ItemService;
use App\Modules\Equipment\Domain\ItemEffect;
use App\Modules\Equipment\Domain\ItemPrototypeId;
use App\Modules\Equipment\Domain\ItemType;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class ItemServiceTest extends TestCase
{
    /** @var ItemService */
    private $sut;

    /** @var MockInterface| CharacterRepositoryInterface */
    private $characterRepository;

    /** @var MockInterface| ItemRepositoryInterface */
    private $itemRepository;

    /** @var MockInterface| ItemPrototypeRepositoryInterface */
    private $itemPrototypeRepository;

    /** @var MockInterface| Character */
    private $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterRepository = Mockery::mock(CharacterRepositoryInterface::class);
        $this->itemRepository = Mockery::mock(ItemRepositoryInterface::class);
        $this->itemPrototypeRepository = Mockery::mock(ItemPrototypeRepositoryInterface::class);
        $this->character = Mockery::mock(Character::class);

        $this->sut = new ItemService(
            $this->characterRepository,
            $this->itemRepository,
            $this->itemPrototypeRepository,
            new ItemFactory()
        );
    }

    public function testCreate(): void
    {
        // Arrange
        $itemId = ItemId::fromString('598d1570-e0e3-40d1-979b-64e48626f777');
        $itemPrototypeId = ItemPrototypeId::fromString('598d1570-e0e3-40d1-979b-64e48626f6f6');
        $name = 'Wooden club';
        $description = 'Club made from wood';
        $type = ItemType::mainHand();
        $price = ItemPrice::ofAmount(30);
        $effects = Collection::make([
            ItemEffect::damage(5)
        ]);
        $creatorCharacterId = CharacterId::fromString('65976e46-d2eb-4373-ba69-b7c9ea81b56f');
        $imageFilePath = 'images\equipment\main_hand\1club.png';

        $itemPrototype = new ItemPrototype(
            $itemPrototypeId,
            $name,
            $description,
            $imageFilePath,
            $type,
            $effects,
            $price
        );

        $createCommand = new CreateItemCommand($itemPrototypeId, $creatorCharacterId);

        $this->characterRepository->shouldReceive('getOne')->once()->andReturn($this->character);
        $this->itemPrototypeRepository->shouldReceive('getOne')->once()->andReturn($itemPrototype);
        $this->itemRepository->shouldReceive('nextIdentity')->once()->andReturn($itemId);
        $this->character->shouldReceive('getId')->once()->andReturn($creatorCharacterId);
        $this->character->shouldReceive('addItemToInventory')->once();
        $this->itemRepository->shouldReceive('add')->once()
            ->with(\Mockery::on(static function (Item $item)
            use ($itemId, $name, $description, $imageFilePath, $type, $effects, $itemPrototypeId, $creatorCharacterId, $price) {

                // Assert
                Assert::assertEquals($itemId, $item->getId());
                Assert::assertEquals($name, $item->getName());
                Assert::assertEquals($description, $item->getDescription());
                Assert::assertEquals($imageFilePath, $item->getImageFilePath());
                Assert::assertEquals($type, $item->getType());
                Assert::assertEquals($effects, $item->getEffects());
                Assert::assertEquals($itemPrototypeId, $item->getPrototypeId());
                Assert::assertEquals($creatorCharacterId, $item->getCreatorCharacterId());
                Assert::assertEquals($price, $item->getPrice());

                return true;
            }));
        $this->characterRepository->shouldReceive('update')->once();

        // Act
        $this->sut->create($createCommand);
    }
}
