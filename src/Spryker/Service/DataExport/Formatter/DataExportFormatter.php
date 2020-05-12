<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Service\DataExport\Formatter;

use Generated\Shared\Transfer\DataExportConfigurationTransfer;
use Generated\Shared\Transfer\DataExportFormatResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;

class DataExportFormatter implements DataExportFormatterInterface
{
    protected const MESSAGE_FORMATTER_PLUGIN_NOT_FOUND = 'Formatter plugin not found for format "%s"';
    protected const FORMAT_CSV = 'csv';

    /**
     * @var \Spryker\Service\DataExportExtension\Dependency\Plugin\DataExportFormatterPluginInterface[]
     */
    protected $dataExportFormatterPlugins;

    /**
     * @var \Spryker\Service\DataExport\Formatter\DataExportFormatterInterface
     */
    protected $dataExportCsvFormatter;

    /**
     * @param \Spryker\Service\DataExportExtension\Dependency\Plugin\DataExportFormatterPluginInterface[] $dataExportFormatterPlugins
     * @param \Spryker\Service\DataExport\Formatter\DataExportFormatterInterface $dataExportCsvFormatter
     */
    public function __construct(array $dataExportFormatterPlugins, DataExportFormatterInterface $dataExportCsvFormatter)
    {
        $this->dataExportFormatterPlugins = $dataExportFormatterPlugins;
        $this->dataExportCsvFormatter = $dataExportCsvFormatter;
    }

    /**
     * @param array $data
     * @param \Generated\Shared\Transfer\DataExportConfigurationTransfer $dataExportConfigurationTransfer
     *
     * @return \Generated\Shared\Transfer\DataExportFormatResponseTransfer
     */
    public function formatBatch(array $data, DataExportConfigurationTransfer $dataExportConfigurationTransfer): DataExportFormatResponseTransfer
    {
        $dataExportConfigurationTransfer->requireFormat();

        foreach ($this->dataExportFormatterPlugins as $dataExportFormatterPlugin) {
            if (!$dataExportFormatterPlugin->isApplicable($dataExportConfigurationTransfer)) {
                continue;
            }

            return $dataExportFormatterPlugin->format($data, $dataExportConfigurationTransfer);
        }

        if ($dataExportConfigurationTransfer->getFormat()->getType() === static::FORMAT_CSV) {
            return $this->dataExportCsvFormatter->formatBatch($data, $dataExportConfigurationTransfer);
        }

        return $this->createFormatterNotFoundResponse($dataExportConfigurationTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\DataExportConfigurationTransfer $dataExportConfigurationTransfer
     *
     * @return string|null
     */
    public function getFormatExtension(DataExportConfigurationTransfer $dataExportConfigurationTransfer): ?string
    {
        foreach ($this->dataExportFormatterPlugins as $dataExportFormatterPlugin) {
            if (!$dataExportFormatterPlugin->isApplicable($dataExportConfigurationTransfer)) {
                continue;
            }

            return $dataExportFormatterPlugin->getExtension($dataExportConfigurationTransfer);
        }

        if ($dataExportConfigurationTransfer->getFormat()->getType() === static::FORMAT_CSV) {
            return $this->dataExportCsvFormatter->getFormatExtension($dataExportConfigurationTransfer);
        }

        return null;
    }

    /**
     * @param \Generated\Shared\Transfer\DataExportConfigurationTransfer $dataExportConfigurationTransfer
     *
     * @return \Generated\Shared\Transfer\DataExportFormatResponseTransfer
     */
    protected function createFormatterNotFoundResponse(DataExportConfigurationTransfer $dataExportConfigurationTransfer): DataExportFormatResponseTransfer
    {
        $messageTransfer = (new MessageTransfer())->setValue(
            sprintf(static::MESSAGE_FORMATTER_PLUGIN_NOT_FOUND, $dataExportConfigurationTransfer->getFormat()->getType())
        );

        return (new DataExportFormatResponseTransfer())
            ->setIsSuccessful(false)
            ->addMessage($messageTransfer);
    }
}
