<?php

require_once __DIR__ . '/vendor/NotORM.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/Model.php';

try {
    // prepare database and NotORM instance for testing
    $norm = recodex_initialize_norm(__DIR__ . '/data');

    // create model and perform tests on it
    $model = new Model($norm);

    // Testing...
    echo "All users:\n";
    print_r($model->getUsers());
    echo "--------------------------------------------------------------------------------\n";

    echo "All lectures:\n";
    print_r($model->getAllLectures());
    echo "--------------------------------------------------------------------------------\n";

    echo "Teachers:\n";
    print_r($model->getLecturesTeachers());
    echo "--------------------------------------------------------------------------------\n";

    echo "NPRG042 students:\n";
    print_r($model->getEnrolledStudents('NPRG042'));
    echo "Enrolling student #13 for NPRG042:\n";
    $model->enrollStudent(13, 'NPRG042');
    $model->enrollStudent(13, 'NPRG042');
    $model->enrollStudent(13, 'NPRG042');
    $model->enrollStudent(13, 'NPRG042');
    $model->enrollStudent(13, 'NPRG042');
    print_r($model->getEnrolledStudents('NPRG042'));
    echo "--------------------------------------------------------------------------------\n";

    echo "Renaming NPRG042:\n";
    $model->updateLectureName('NPRG042', 'Parapgranie');
    print_r($model->getAllLectures());
} catch (Exception $e) {
    echo "Uncauth exception: ", $e->getMessage(), "\n";
    exit(1);
}
