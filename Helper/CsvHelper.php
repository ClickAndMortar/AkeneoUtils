<?php

namespace ClickAndMortar\AkeneoUtilsBundle\Helper;

class CsvHelper
{
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
        fputcsv($file, $headers);
        foreach ($this->getRowsFromArray($data, $headers) as $row) {
            fputcsv($file, $row);
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
                $headers = array_merge($headers, $this->getHeadersFromArray($value, $prefix . $key . '_'));
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
                $keys  = explode('_', $header);
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