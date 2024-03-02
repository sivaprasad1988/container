<?php

declare(strict_types=1);

namespace B13\Container\Tests\Functional\Integrity;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Domain\Factory\ContainerFactory;
use B13\Container\Domain\Service\ContainerService;
use B13\Container\Integrity\Database;
use B13\Container\Integrity\Sorting;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SortingWithContentDefenderTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/container',
        'typo3conf/ext/container_example',
        'typo3conf/ext/content_defender',
    ];

    /**
     * @var Sorting
     */
    protected $sorting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
        $context = GeneralUtility::makeInstance(Context::class);
        $containerRegistry = GeneralUtility::makeInstance(Registry::class);
        $sortingDatabase = GeneralUtility::makeInstance(Database::class);
        $factoryDatabase = GeneralUtility::makeInstance(\B13\Container\Domain\Factory\Database::class, $context);
        $containerFactory = GeneralUtility::makeInstance(ContainerFactory::class, $factoryDatabase, $containerRegistry, $context);
        $containerService = GeneralUtility::makeInstance(ContainerService::class, $containerRegistry, $containerFactory);
        $this->sorting = GeneralUtility::makeInstance(Sorting::class, $sortingDatabase, $containerRegistry, $containerFactory, $containerService);
    }

    /**
     * @test
     * @group content_defender
     */
    public function childBeforeContainerIsSortedAfterContainerEvenIfCTypeDisallowedByContentDefender(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SortingWithContentDefender/disallowed_child_is_before_container.csv');
        $errors = $this->sorting->run(false);
        self::assertTrue(count($errors) === 1, 'should get one error');
        $rows = $this->getContentsByUid();
        self::assertTrue($rows[3]['sorting'] < $rows[2]['sorting'], 'child should be sorted after container');
    }

    protected function getContentsByUid(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $res = $queryBuilder->select('uid', 'sorting', 'colPos')
            ->from('tt_content')
            ->execute()
            ->fetchAllAssociative();
        $rows = [];
        foreach ($res as $row) {
            $rows[$row['uid']] = $row;
        }
        return $rows;
    }
}
