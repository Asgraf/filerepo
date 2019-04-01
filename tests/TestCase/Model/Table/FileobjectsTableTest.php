<?php
namespace Filerepo\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Filerepo\Model\Table\FileobjectsTable;

/**
 * Filerepo\Model\Table\FileobjectsTable Test Case
 */
class FileobjectsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Filerepo\Model\Table\FileobjectsTable
     */
    public $Fileobjects;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Filerepo.Fileobjects'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Fileobjects') ? [] : ['className' => FileobjectsTable::class];
        $this->Fileobjects = TableRegistry::getTableLocator()->get('Fileobjects', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Fileobjects);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test clearThumbs method
     *
     * @return void
     */
    public function testClearThumbs()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test clearAllThumbs method
     *
     * @return void
     */
    public function testClearAllThumbs()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test cleanupUnusedFiles method
     *
     * @return void
     */
    public function testCleanupUnusedFiles()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test getThumbnail method
     *
     * @return void
     */
    public function testGetThumbnail()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
