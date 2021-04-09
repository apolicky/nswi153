<?php
/**
 * Num API
 * @version 1.0.0
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = new Slim\App();


/**
 * GET getBinaryOperation
 * Summary: 
 * Notes: Apply operation on two numbers
 * Output-Formats: [application/json]
 */
$app->GET('/binary/{op}', function($request, $response, $args) {
            
            $queryParams = $request->getQueryParams();
            $left = $queryParams['left'];    $right = $queryParams['right'];    
            
            $op = $args['op'];

            if($op === 'add') {
                $res = $left + $right;
            }
            else if($op === 'sub') {
                $res = $left - $right;
            }
            else {
                return $response->withStatus(500);
            }
            
            return $response->withJson(['result' => $res]);
            });


/**
 * GET getUnaryNegation
 * Summary: 
 * Notes: Oposite
 * Output-Formats: [application/json]
 */
$app->GET('/unary/negation', function($request, $response, $args) {
            
            $queryParams = $request->getQueryParams();
            $input = $queryParams['input'];    
            $res = -$input;
            return $response->withJson(['result' => $res]);
            });



$app->run();