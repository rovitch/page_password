<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\Hooks;

use TYPO3\CMS\Core\DataHandling\DataHandler;

class PagePasswordUpdateHook
{
    /**
     * @param array<int|string> $incomingFieldArray
     * @param string $table
     * @param int|string $id
     * @param DataHandler $dataHandler
     */
    public function processDatamap_preProcessFieldArray(
        array &$incomingFieldArray,
        string $table,
        int|string $id,
        DataHandler $dataHandler,
    ): void {
        if ($table !== 'pages') {
            return;
        }

        if (!empty($incomingFieldArray['tx_pagepassword_password'])) {
            $incomingFieldArray['tx_pagepassword_password_changed_at'] = time();
        }
    }
}
