<?php

class EW_UntranslatedStrings_Model_ExportToZip
{
    protected function getCsvByLocaleCode($localeCode)
    {
        /** @var EW_UntranslatedStrings_Model_Resource_String_Collection $collection */
        $collection = Mage::getResourceModel('ew_untranslatedstrings/string_collection');
        $collection->joinStoreCode();
        $collection->addFieldToFilter('locale', $localeCode);

        /** @var EW_UntranslatedStrings_Block_Adminhtml_Report_Grid $grid */
        $grid = Mage::getBlockSingleton('ew_untranslatedstrings/adminhtml_report_grid');
        $grid->setCollection($collection);

        // filtering for a the given locale
        $this->resetFilters();
        $grid->setDefaultFilter(['locale' => $localeCode]);
        $file = $grid->getCsvFile();

        return [
            'locale' => $localeCode,
            'path'   => $file['value'],
        ];
    }

    /**
     * Archive input files into a ZIP file and return the path of the output file
     */
    protected function archive(array $files)
    {
        $zipFilePath = $this->getZipFilePath();
        $zip = $this->createZipArchive($zipFilePath);

        foreach ($files as $file) {
            $localname = sprintf('untranslated_strings_report_%s.csv', $file['locale']);
            $zip->addFile($file['path'], $localname);
        }

        $zip->close();
        $this->removeCsvFiles($files);

        return $zipFilePath;
    }

    /**
     * @param $zipFilePath
     *
     * @return ZipArchive
     * @throws Mage_Core_Exception
     */
    protected function createZipArchive($zipFilePath)
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE)!==TRUE) {
            Mage::throwException("cannot open <$zipFilePath>\n");
        }

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        return $zip;
    }

    public function getZip()
    {
        $untranslatedLocaleCodes = Mage::getModel('ew_untranslatedstrings/string')->getUntranslatedLocaleCodes();
        $csvFilesByLocale = [];
        foreach ($untranslatedLocaleCodes as $localeCode) {
            $csvFilesByLocale[] = $this->getCsvByLocaleCode($localeCode);
        }

        return $this->archive($csvFilesByLocale);
    }

    /**
     * @return string
     */
    protected function getZipFilePath()
    {
        $zipFilePath = Mage::getBaseDir('var').DS.'export'.DS.'untranslated_strings_report.zip';

        return $zipFilePath;
    }

    protected function removeCsvFiles($files)
    {
        foreach ($files as $file) {
            unlink($file['path']);
        }
    }

    /**
     * Reset filters on the grid, so the ZIP will contain all the log entries
     */
    protected function resetFilters()
    {
        $session = Mage::getSingleton('adminhtml/session');
        $session->unsetData('ew_untranslatedstrings_adminhtml_report_gridfilter');
        $session->unsetData('ew_untranslatedstrings_adminhtml_report_griddir');
        $session->unsetData('ew_untranslatedstrings_adminhtml_report_gridsort');
        $session->unsetData('ew_untranslatedstrings_adminhtml_report_gridpage');
        $session->unsetData('ew_untranslatedstrings_adminhtml_report_gridlimit');
    }
}
