<?php

declare(strict_types=1);
namespace B13\Container\Tests\Functional\Datahandler\ContentDefender;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Tests\Functional\Datahandler\DatahandlerTest;
use TYPO3\CMS\Core\Utility\StringUtility;

class MaxItemsTest extends DatahandlerTest
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/container',
        'typo3conf/ext/container_example',
        'typo3conf/ext/content_defender',
    ];

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     * @group content_defender
     */
    public function canMoveElementIntoContainerIfMaxitemsIsNotReached(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/can_move_element_into_container_if_maxitems_is_not_reached.xml');
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'move' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '1-202',
                            'sys_language_uid' => 0,

                        ],
                    ],
                ],
            ],
        ];

        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(1, (int)$row['tx_container_parent'], 'element is not in container');
        self::assertSame(202, (int)$row['colPos'], 'element has wrong colPos');
        self::assertSame([], $this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function cannotMoveElementIntoContainerIfMaxitemsIsReached(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/cannot_move_element_into_container_if_maxitems_is_reached.xml');
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'move' => [
                        'action' => 'paste',
                        'target' => -3,
                        'update' => [
                            'colPos' => '1-202',
                            'sys_language_uid' => 0,

                        ],
                    ],
                ],
            ],
        ];

        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(0, (int)$row['tx_container_parent'], 'element is moved into container');
        self::assertSame(0, (int)$row['colPos'], 'element is moved into container colPos');
        self::assertNotEmpty($this->dataHandler->errorLog, 'dataHander error log is empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function cannotCopyElementIntoContainerIfMaxitemsIsReachedAfterIntoContainer(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/cannot_copy_element_into_container_if_maxitems_is_reached.xml');

        $cmdmap = [
            'tt_content' => [
                2 => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '1-202',
                            'sys_language_uid' => 0,

                        ],
                    ],
                ],
            ],
        ];
        // into container

        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $queryBuilder = $this->getQueryBuilder();
        $row = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2)
                )
            )
            ->execute()
            ->fetch();
        self::assertFalse($row);
        self::assertNotEmpty($this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function cannotCopyElementIntoContainerIfMaxitemsIsReachedAfterElement(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/cannot_copy_element_into_container_if_maxitems_is_reached.xml');
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'copy' => -2,
                ],
            ],
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $queryBuilder = $this->getQueryBuilder();
        $row = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    't3_origuid',
                    $queryBuilder->createNamedParameter(2)
                )
            )
            ->execute()
            ->fetch();
        self::assertFalse($row);
        self::assertNotEmpty($this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function canCreateElementInContainerIfMaxitemsIsNotReached(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/can_create_element_in_container_if_maxitems_is_not_reached.xml');
        $newId = StringUtility::getUniqueId('NEW');
        $datamap = [
            'tt_content' => [
                $newId => [
                    'colPos' => 202,
                    'tx_container_parent' => 1,
                    'pid' => 1,
                    'sys_language_uid' => 0,
                    'header' => $newId,
                ],
            ],
        ];
        $this->dataHandler->start($datamap, [], $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $queryBuilder = $this->getQueryBuilder();
        $row = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'header',
                    $queryBuilder->createNamedParameter($newId)
                )
            )
            ->execute()
            ->fetch();
        self::assertIsArray($row);
        self::assertSame([], $this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function cannotCreateElementInContainerIfMaxitemsIsReached(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/cannot_create_element_in_container_if_maxitems_is_reached.xml');
        $newId = StringUtility::getUniqueId('NEW');
        $datamap = [
            'tt_content' => [
                $newId => [
                    'colPos' => 202,
                    'tx_container_parent' => 1,
                    'pid' => 1,
                    'sys_language_uid' => 0,
                    'header' => $newId,
                ],
            ],
        ];
        $this->dataHandler->start($datamap, [], $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $queryBuilder = $this->getQueryBuilder();
        $row = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'header',
                    $queryBuilder->createNamedParameter($newId)
                )
            )
            ->execute()
            ->fetch();
        self::assertFalse($row);
        self::assertNotEmpty($this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function canEditElementInContainerWhenMaxitemIsReached(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/can_edit_element_in_container_if_maxitems_is_reached.xml');
        $datamap = [
            'tt_content' => [
                3 => [
                    'colPos' => 202,
                    'tx_container_parent' => 1,
                    'pid' => 1,
                    'sys_language_uid' => 0,
                    'header' => 'bar',
                ],
            ],
        ];
        $this->dataHandler->start($datamap, [], $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $row = $this->fetchOneRecord('uid', 3);
        self::assertSame('bar', $row['header'], 'header is not updated');
        self::assertSame([], $this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function canMoveContainerWithMaxitemsReachedColumnToOtherPage(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/can_move_container_with_maxitems_reached_column_to_other_page.xml');
        $cmdmap = [
            'tt_content' => [
                1 => [
                    'move' => [
                        'action' => 'paste',
                        'target' => 2,
                        'update' => [
                            'colPos' => 0,
                            'sys_language_uid' => 0,
                        ],
                    ],
                ],
            ],
        ];

        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $row = $this->fetchOneRecord('uid', 1);
        self::assertSame(2, (int)$row['pid'], 'element is not moved to other page');
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(2, (int)$row['pid'], 'child is not moved to other page');
        self::assertSame([], $this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function canCopyContainerWithMaxitemsReachedColumnToOtherPage(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/can_copy_container_with_maxitems_reached_column_to_other_page.xml');
        $cmdmap = [
            'tt_content' => [
                1 => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 2,
                        'update' => [
                            'colPos' => 0,
                            'sys_language_uid' => 0,
                        ],
                    ],
                ],
            ],
        ];

        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $row = $this->fetchOneRecord('t3_origuid', 1);
        self::assertSame(2, (int)$row['pid'], 'element is not moved to other page');
        $child = $this->fetchOneRecord('t3_origuid', 2);
        self::assertSame(2, (int)$child['pid'], 'child is not moved to other page');
        self::assertSame([], $this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function canCopyElementFromContainerMaxitemsReachedColumnToOtherColumn(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/can_copy_element_from_container_maxitems_reached_column_to_other_column.xml');
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '1-201',
                            'sys_language_uid' => 0,
                        ],
                    ],
                ],
            ],
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $row = $this->fetchOneRecord('t3_origuid', 2);
        self::assertSame(201, (int)$row['colPos'], 'element is not copied to other column');
        self::assertSame([], $this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function canCopyElementFromContainerMaxitemsReachedColumnToOtherContainer(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/can_copy_element_from_container_maxitems_reached_column_to_other_container.xml');
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '3-201',
                            'sys_language_uid' => 0,
                        ],
                    ],
                ],
            ],
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $row = $this->fetchOneRecord('t3_origuid', 2);
        self::assertSame(3, (int)$row['tx_container_parent'], 'element is not copied to other container');
        self::assertSame([], $this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function canMoveElementFromContainerMaxitemsReachedColumnToOtherContainer(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/can_copy_element_from_container_maxitems_reached_column_to_other_container.xml');
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'move' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'colPos' => '3-201',
                            'sys_language_uid' => 0,
                        ],
                    ],
                ],
            ],
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $row = $this->fetchOneRecord('uid', 2);
        self::assertSame(3, (int)$row['tx_container_parent'], 'element is not copied to other container');
        self::assertSame([], $this->dataHandler->errorLog, 'dataHander error log is not empty');
    }

    /**
     * @test
     * @group content_defender
     */
    public function cannotMoveElementInsideContainerColumnIfMaxitemsIsReached(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/container/Tests/Functional/Datahandler/ContentDefender/Fixtures/Maxitems/cannot_move_element_inside_container_column_if_maxitems_is_reached.xml');
        $cmdmap = [
            'tt_content' => [
                2 => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => -2,
                        'update' => [
                            'colPos' => '1-202',
                            'sys_language_uid' => 0,
                        ],
                    ],
                ],
            ],
        ];
        $this->dataHandler->start([], $cmdmap, $this->backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        self::assertNotEmpty($this->dataHandler->errorLog, 'dataHander error log is not empty');
    }
}
