<?php
namespace Filerepo\Test\TestCase\Model\Behavior;

use Cake\TestSuite\TestCase;
use Filerepo\Model\Behavior\AttachmentBehavior;

/**
 * Filerepo\Model\Behavior\AttachmentBehavior Test Case
 */
class AttachmentBehaviorTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Filerepo\Model\Behavior\AttachmentBehavior
     */
    public $Attachment;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Attachment = new AttachmentBehavior();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Attachment);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
