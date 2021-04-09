<?php
/**
 * Num API
 * @version 1.0.0
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = new Slim\App();


/**
 * GET getUnaryNegation
 * Summary: 
 * Notes: Oposite
 * Output-Formats: [application/json]
 */
$app->GET('//unary/negation', function($request, $response, $args) {
            
            $queryParams = $request->getQueryParams();
            $input = $queryParams['input'];    
            
            
            $response->write('How about implementing getUnaryNegation as a GET method ?');
            return $response;
            });



$app->run();
