<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use modules\rbac\Module;
use yii\helpers\VarDumper;

/* @var $this yii\web\View */
/* @var $model modules\rbac\models\Assignment */

$this->title = Module::t('module', 'View');
$this->params['breadcrumbs'][] = ['label' => Module::t('module', 'RBAC'), 'url' => ['default/index']];
$this->params['breadcrumbs'][] = ['label' => Module::t('module', 'Assign'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Html::encode($model->username);
?>
<div class="rbac-backend-assign-view">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><?= Html::encode($this->title) ?>
                <small><?= Html::encode($model->username) ?></small>
            </h3>
        </div>
        <div class="box-body">
            <div class="pull-left"></div>
            <div class="pull-right"></div>

            <div class="row">
                <div class="col-md-6">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            [
                                'attribute' => 'username',
                                'label' => Module::t('module', 'User'),
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'role',
                                'format' => 'raw',
                                'value' => $model->getRoleName($model->id)
                            ]
                        ],
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?php
                    $role = $model->getRoleUser();
                    $auth = Yii::$app->authManager;
                    if ($permissionsRole = $auth->getPermissionsByRole($role)) : ?>
                        <strong><?= Module::t('module', 'Permissions by role') ?></strong>
                        <ul>
                            <?php foreach ($permissionsRole as $value) {
                                echo Html::tag('li', $value->name . ' (' . $value->description . ')') . PHP_EOL;
                            } ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <p>
                <?= Html::a('<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> ' . Module::t('module', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<span class="glyphicon glyphicon-remove" aria-hidden="true"></span> ' . Module::t('module', 'Revoke'), ['revoke', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Module::t('module', 'Do you really want to untie the role?'),
                        'method' => 'post',
                    ],
                ]) ?>
            </p>
        </div>
    </div>
</div>
