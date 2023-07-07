<?php

namespace App\Tests\Form;

use App\Form\Item\ItemType;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ItemFormTest extends KernelTestCase
{
    use MatchesSnapshots;

    private readonly ItemType $itemForm;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->itemForm = $container->get(ItemType::class);

        parent::setUp();
    }

    public function testFormFields() {
        $formFields = $this->itemForm->getFormFields();

        $this->assertIsArray($formFields);

        $this->assertMatchesJsonSnapshot($formFields);
    }

    public function testFormSections() {
        $formSections = $this->itemForm->getFormSections();

        $this->assertIsArray($formSections);

        $this->assertMatchesJsonSnapshot($formSections);
    }

    public function testIndexHeaders() {
        $indexHeaders = $this->itemForm->getIndexHeaders();

        $this->assertIsArray($indexHeaders);

        $this->assertMatchesJsonSnapshot($indexHeaders);
    }
}