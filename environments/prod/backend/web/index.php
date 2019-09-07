<?php

use yii\helpers\ArrayHelper;
use yii\web\Application;
use yii\base\InvalidConfigException;

defined('YII_DEBUG') || define('YII_DEBUG', false);
defined('YII_ENV') || define('YII_ENV', 'prod');
defined('YII_APP_BASE_PATH') || define('YII_APP_BASE_PATH', __DIR__ . '/../../');

require YII_APP_BASE_PATH . '/vendor/autoload.php';
require YII_APP_BASE_PATH . '/vendor/yiisoft/yii2/Yii.php';
require YII_APP_BASE_PATH . '/common/config/bootstrap.php';
require YII_APP_BASE_PATH . '/backend/config/bootstrap.php';

$config = ArrayHelper::merge(
    require YII_APP_BASE_PATH . '/common/config/main.php',
    require YII_APP_BASE_PATH . '/common/config/main-local.php',
    require YII_APP_BASE_PATH . '/backend/config/main.php',
    require YII_APP_BASE_PATH . '/backend/config/main-local.php'
);

try {
    $application = new Application($config);
} catch (InvalidConfigException $e) {
}

$application->run();
