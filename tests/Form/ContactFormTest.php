<?php

namespace App\Tests\Form;

use App\Form\Contact\ContactCompanyType;
use App\Form\Contact\ContactType;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContactFormTest extends KernelTestCase
{
    use MatchesSnapshots;

    private readonly ContactType $contactForm;
    private readonly ContactCompanyType $companyForm;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->contactForm = $container->get(ContactType::class);
        $this->companyForm = $container->get(ContactCompanyType::class);

        parent::setUp();
    }

    public function testFormFields() {
        $formFields = $this->contactForm->getFormFields();

        $this->assertIsArray($formFields);

        $this->assertMatchesJsonSnapshot($formFields);
    }

    public function testCompanyFormFields() {
        $formFields = $this->companyForm->getFormFields();

        $this->assertIsArray($formFields);

        $this->assertMatchesJsonSnapshot($formFields);
    }

    public function testFormSections() {
        $formSections = $this->contactForm->getFormSections();

        $this->assertIsArray($formSections);

        $this->assertMatchesJsonSnapshot($formSections);
    }

    public function testIndexHeaders() {
        $indexHeaders = $this->contactForm->getIndexHeaders();

        $this->assertIsArray($indexHeaders);

        $this->assertMatchesJsonSnapshot($indexHeaders);
    }
}