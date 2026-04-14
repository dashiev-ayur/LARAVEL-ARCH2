<?php

use App\Application\Some\UseCases\LoggUseCase;

test('execute echoes hello world', function () {
    $logger = new LoggUseCase;
    $logger->setMessage('Hello, world!');
    expect($logger->message2)->toBe('[Hello, world!]');
    echo $logger->message2.PHP_EOL;

    ob_start();
    $logger->execute();
    $output = ob_get_clean();
    expect($output)->toBe('Hello, world!');
});
