<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->layer('Core', [
        'src/DuplicateRouteDetector.php',
        'src/Route.php',
        'src/RouteCollector.php',
        'src/RouteCollectorFactory.php',
        'src/RouteCollectorInterface.php',
        'src/RouteResult.php',
        'src/RouterInterface.php',
    ])
    ->layer('Config', 'src/ConfigProvider.php')
    ->layer('Exception', 'src/Exception')
    ->layer('Middleware', 'src/Middleware')
    ->layer('Test', 'src/Test')
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->ruleset([
        'Config'     => ['+Middleware'],
        'Exception'  => [],
        'Core'       => ['Exception'],
        'Middleware' => ['+Core'],
        'Test'       => ['+Middleware'],
    ]);
