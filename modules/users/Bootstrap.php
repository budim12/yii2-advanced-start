<?php
/**
 * Created by PhpStorm.
 * User: Alexey Shevchenko <ivanovosity@gmail.com>
 * Date: 17.10.16
 * Time: 13:36
 */

namespace modules\users;

use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        // i18n
        $app->i18n->translations['modules/users/*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@modules/users/messages',
            'fileMap' => [
                'modules/users/backend' => 'backend.php',
                'modules/users/frontend' => 'frontend.php',
                'modules/users/mail' => 'mail.php',
            ],
        ];

        // Rules
        $app->getUrlManager()->addRules(
            [
                // объявление правил здесь
                '<_a:(login|logout|signup|email-confirm|request-password-reset|password-reset)>' => 'users/default/<_a>',

                'users' => 'users/default/index',
                'users/create' => 'users/default/create',
                'users/<id:\d+>/<_a:[\w\-]+>' => 'users/default/<_a>',
                'user/update' => 'users/default/update',
                'user/update-profile' => 'users/default/update-profile',
                'user/update-avatar' => 'users/default/update-avatar',
                'user/update-password' => 'users/default/update-password',
                'user/delete' => 'users/default/delete',
            ]
        );
    }
}