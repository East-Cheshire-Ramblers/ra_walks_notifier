<?php

declare(strict_types=1);

namespace WalksManagerWatch;

final class BookingCsvParser
{
    /**
     * Parse a Walks Manager booking CSV file using the v4.2 import rules.
     *
     * @return array{
     *     records_read:int,
     *     rows:array<int,array{line:int,group_code:string,name:string,email:string,partner:string}>,
     *     errors:array<int,array{line:int,column:int,message:string,email?:string}>,
     *     ignored:array<int,array{line:int,reason:string}>
     * }
     */
    public function parseFile(string $filename): array
    {
        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open ' . $filename);
        }

        try {
            return $this->parseHandle($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     *
     * @return array{
     *     records_read:int,
     *     rows:array<int,array{line:int,group_code:string,name:string,email:string,partner:string}>,
     *     errors:array<int,array{line:int,column:int,message:string,email?:string}>,
     *     ignored:array<int,array{line:int,reason:string}>
     * }
     */
    public function parseHandle($handle): array
    {
        $recordsRead = 0;
        $rows = [];
        $errors = [];
        $ignored = [];

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $recordsRead++;

            if ($recordsRead === 1) {
                $ignored[] = ['line' => $recordsRead, 'reason' => 'header'];
                continue;
            }

            $firstColumn = $this->field($data, 0);

            if (str_starts_with($firstColumn, '#')) {
                $ignored[] = ['line' => $recordsRead, 'reason' => 'comment'];
                continue;
            }

            $parsed = $this->parseLine($data, $recordsRead);

            if ($parsed['valid']) {
                $rows[] = $parsed['row'];
            } else {
                foreach ($parsed['errors'] as $error) {
                    $errors[] = $error;
                }
            }
        }

        return [
            'records_read' => $recordsRead,
            'rows' => $rows,
            'errors' => $errors,
            'ignored' => $ignored,
        ];
    }

    /**
     * @param array<int,string|null> $data
     *
     * @return array{
     *     valid:bool,
     *     row?:array{line:int,group_code:string,name:string,email:string,partner:string},
     *     errors:array<int,array{line:int,column:int,message:string,email?:string}>
     * }
     */
    public function parseLine(array $data, int $lineNumber): array
    {
        $groupCode = $this->field($data, 0);
        $name = $this->field($data, 1);
        $email = $this->field($data, 2);
        $partner = $this->field($data, 3);
        $errors = [];

        if ($groupCode === '') {
            $errors[] = [
                'line' => $lineNumber,
                'column' => 1,
                'message' => 'First column (Group code) is blank',
            ];
        }

        if ($name === '') {
            $error = [
                'line' => $lineNumber,
                'column' => 2,
                'message' => 'Second column (name) is blank',
            ];

            if ($email !== '') {
                $error['email'] = $email;
            }

            $errors[] = $error;
        }

        if ($email === '') {
            $errors[] = [
                'line' => $lineNumber,
                'column' => 3,
                'message' => 'Third column (email) is blank',
            ];
        }

        if ($errors !== []) {
            return ['valid' => false, 'errors' => $errors];
        }

        return [
            'valid' => true,
            'row' => [
                'line' => $lineNumber,
                'group_code' => $groupCode,
                'name' => $name,
                'email' => $email,
                'partner' => $partner,
            ],
            'errors' => [],
        ];
    }

    /**
     * @param array<int,string|null> $data
     */
    private function field(array $data, int $index): string
    {
        return trim((string) ($data[$index] ?? ''));
    }
}
