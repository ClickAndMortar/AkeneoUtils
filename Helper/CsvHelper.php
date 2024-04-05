<?php

namespace ClickAndMortar\AkeneoUtilsBundle\Helper;

class CsvHelper
{
    const SEPARATOR = ';';

    const HEADER_NAME_SEPARATOR = '/';

    /**
     * Generate CSV file from $jsonData
     *
     * @param string $filePath
     * @param string $jsonData
     *
     * @return bool
     */
    public function generateCsvFromJson($filePath, $jsonData)
    {
        $data = json_decode($jsonData, true);
        if (empty($data)) {
            return false;
        }
        $file = fopen($filePath, 'w');
        if (!$file) {
            return false;
        }

        $headers = $this->getHeadersFromArray($data[0]);
        fputcsv($file, $headers, self::SEPARATOR);
        foreach ($this->getRowsFromArray($data, $headers) as $row) {
            fputcsv($file, $row, self::SEPARATOR);
        }
        fclose($file);

        return true;
    }

    /**
     * @param array $array
     * @param string $prefix
     *
     * @return array
     */
    protected function getHeadersFromArray($array, $prefix = '')
    {
        $headers = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $headers = array_merge($headers, $this->getHeadersFromArray($value, $prefix . $key . self::HEADER_NAME_SEPARATOR));
            } else {
                $headers[] = $prefix . $key;
            }
        }

        return $headers;
    }

    /**
     * @param array $array
     * @param array $headers
     *
     * @return array
     */
    protected function getRowsFromArray($array, $headers)
    {
        $rows = [];
        foreach ($array as $item) {
            $row = [];
            foreach ($headers as $header) {
                $keys  = explode(self::HEADER_NAME_SEPARATOR, $header);
                $value = $item;
                foreach ($keys as $key) {
                    if (isset($value[$key])) {
                        $value = $value[$key];
                    } else {
                        $value = '';
                        break;
                    }
                }
                $row[] = $value;
            }
            $rows[] = $row;
        }

        return $rows;
    }
}