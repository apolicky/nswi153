<?php

/*
 * This file holds internal testing functions. Do not use these function in your code.
 * In fact, it would be better if you ignore them altogether since they do not always represent best practices.
 */

function recodex_extract_zip($zipFile, $targetDir)
{
    $zip = new ZipArchive();
    if (!$zipFile || !file_exists($zipFile) || $zip->open($zipFile) !== true) {
        throw new Exception("Unable to open $zipFile.");
    }
    if (!$zip->extractTo($targetDir)) {
        throw new Exception("Unable to extract $zipFile.");
    }
    $zip->close();
}

function recodex_pdo_exec($pdo, $rawQuery)
{
    $query = trim($rawQuery);
    if (!$query) {
        return;
    }

    if ($pdo->exec($query) === false) {
        $error = join(', ', $pdo->errorInfo());
        throw new Exception("query failed on $error");
    }
}

function recodex_fill_table($norm, $csvFile)
{
    
    // echo "recodex_fill_table norm:, csvFile: $csvFile\n";

    $table = basename($csvFile, '.csv');
    $fp = fopen($csvFile, "r");
    if (!$fp) {
        throw new Exception("unable to open '$table' data file");
    }

    $colNames = fgetcsv($fp, 0, ';');
    if (!$colNames) {
        throw new Exception("'$table' data file does not have header");
    }

    while (($row = fgetcsv($fp, 0, ';'))) { // yes, there is an assignment in condition
        if(0 && $table === "user") {
            echo "colnames:\n";
            print_r($colNames);
            echo "row:\n";
            print_r($row);
            echo "______________________\n";
        }
        if (count($colNames) != count($row)) {
            throw new Exception("data corrupted in data file '$table'");
        }

        $record = [];
        foreach ($colNames as $idx => $name) {
            $record[$name] = $row[$idx];
        }
        $norm->$table()->insert($record);
    }

    fclose($fp);
}

function recodex_initialize_norm($dataDir)
{
    try {
        $pdo = new PDO('sqlite::memory:');

        // create DB schema
        $schemaFile = "$dataDir/schema.sql";
        if (!file_exists($schemaFile)) {
            throw new Exception("schema file not found");
        }

        $schemaInitQueries = explode(';', file_get_contents($schemaFile));
        foreach ($schemaInitQueries as $query) {
            recodex_pdo_exec($pdo, $query);
        }

        $norm = new NotORM($pdo);

        // fill in the data
        foreach (glob("$dataDir/*.csv") as $csvFile) {
            recodex_fill_table($norm, $csvFile);
        }

        return new NotORM($pdo); // create a fresh norm object
    } catch (Exception $e) {
        echo "DB init error: ", $e->getMessage(), "\n";
        exit(1);
    }
}
