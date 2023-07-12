<?php

namespace App\Tests\Form;

use App\Form\Job\JobType;
use PHPUnit\Framework\Attributes\CoversClass;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(JobType::class)]
class JobFormTest extends KernelTestCase
{
    use MatchesSnapshots;

    private readonly JobType $jobForm;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->jobForm = $container->get(JobType::class);

        parent::setUp();
    }

    public function testFormFields() {
        $formFields = $this->jobForm->getFormFields();

        $this->assertIsArray($formFields);

        $this->assertMatchesJsonSnapshot($formFields);
    }

    public function testFormSections() {
        $formSections = $this->jobForm->getFormSections();

        $this->assertIsArray($formSections);

        $this->assertMatchesJsonSnapshot($formSections);
    }

    public function testIndexHeaders() {
        $indexHeaders = $this->jobForm->getIndexHeaders();

        $this->assertIsArray($indexHeaders);

        $this->assertMatchesJsonSnapshot($indexHeaders);
    }
}