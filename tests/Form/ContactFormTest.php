<?php

namespace App\Tests\Form;

use App\Form\Contact\ContactType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContactFormTest extends KernelTestCase
{
    private readonly ContactType $contactForm;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->contactForm = $container->get(ContactType::class);

        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testFormFields() {
        $formFields = $this->contactForm->getFormFields();

        $this->assertIsArray($formFields);
    }

    public function testFormSections() {
        $formSections = $this->contactForm->getFormSections();

        $this->assertIsArray($formSections);
    }

    public function testIndexHeaders() {
        $indexHeaders = $this->contactForm->getIndexHeaders();

        $this->assertIsArray($indexHeaders);
    }
}