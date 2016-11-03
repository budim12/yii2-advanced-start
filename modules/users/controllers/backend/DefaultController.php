<?php

namespace modules\users\controllers\backend;

use Yii;
use yii\helpers\Url;
use modules\users\models\LoginForm;
use modules\users\models\backend\User;
use modules\users\models\backend\UserSearch;
use modules\users\models\UploadForm;
use yii\web\UploadedFile;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use modules\rbac\models\Rbac as BackendRbac;
use modules\users\Module;

/**
 * UserController implements the CRUD actions for User model.
 */
class DefaultController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'logout' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login'],
                        'allow' => true,
                        'roles' => ['?']
                    ],
                    [
                        'actions' => ['logout', 'index', 'view', 'update', 'update-profile', 'update-password', 'update-avatar'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    /*[
                        'actions' => ['update', 'upload'],
                        'allow' => true,
                        'roles' => [BackendRbac::PERMISSION_BACKEND_USER_MANAGER, BackendRbac::RULE_UPDATE_OWN_POST],
                    ],*/
                    [
                        'actions' => ['create', 'delete'],
                        'allow' => true,
                        'roles' => [BackendRbac::PERMISSION_BACKEND_USER_MANAGER],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();
        $model->scenario = $model::SCENARIO_ADMIN_CREATE;

        $uploadModel = new UploadForm();

        $model->role = $model::RBAC_DEFAULT_ROLE;
        $model->status = $model::STATUS_WAIT;
        $model->registration_type = Yii::$app->user->identity->getId();

        if ($model->load(Yii::$app->request->post())) {
            $uploadModel->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->save()) {
                $authManager = Yii::$app->getAuthManager();
                $role = $authManager->getRole($model->role);
                $authManager->assign($role, $model->id);

                $uploadModel->upload($model->id);
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = $model::SCENARIO_ADMIN_UPDATE;

        $user_role = $model->getUserRoleValue();
        $model->role = $user_role ? $user_role : $model::RBAC_DEFAULT_ROLE;

        if (!Yii::$app->user->can(BackendRbac::PERMISSION_BACKEND_USER_UPDATE, ['model' => $model])) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You are not allowed to edit the profile.'));
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdateProfile($id)
    {
        $model = $this->findModel($id);
        $model->scenario = $model::SCENARIO_ADMIN_UPDATE;

        if (!Yii::$app->user->can(BackendRbac::PERMISSION_BACKEND_USER_UPDATE, ['model' => $model])) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You are not allowed to edit the profile.'));
            return $this->redirect(['index']);
        }

        $user_role = $model->getUserRoleValue();
        $model->role = $user_role ? $user_role : $model::RBAC_DEFAULT_ROLE;
        $_role = $model->role;

        if ($model->load(Yii::$app->request->post())) {
            // Если изменена роль
            if ($_role != $model->role) {
                $authManager = Yii::$app->getAuthManager();
                // Отвязываем старую роль если она существует
                if ($role = $authManager->getRole($_role))
                    $authManager->revoke($role, $model->id);
                // Привязываем новую
                $role = $authManager->getRole($model->role);
                $authManager->assign($role, $model->id);
            }
            if ($model->save())
                Yii::$app->session->setFlash('success', Module::t('backend', 'MSG_PROFILE_SAVE_SUCCESS'));
        }
        return $this->redirect(['update', 'id' => $model->id, 'tab' => 'profile']);
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdatePassword($id)
    {
        $model = $this->findModel($id);
        $model->scenario = $model::SCENARIO_PASSWORD_UPDATE;

        if (!Yii::$app->user->can(BackendRbac::PERMISSION_BACKEND_USER_UPDATE, ['model' => $model])) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You are not allowed to edit the profile.'));
            return $this->redirect(['index']);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Module::t('backend', 'MSG_PASSWORD_UPDATE_SUCCESS'));
        }
        return $this->redirect(['update', 'id' => $model->id, 'tab' => 'password']);
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdateAvatar($id)
    {
        $model = $this->findModel($id);
        $model->scenario = $model::SCENARIO_AVATAR_UPDATE;

        if (!Yii::$app->user->can(BackendRbac::PERMISSION_BACKEND_USER_UPDATE, ['model' => $model])) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You are not allowed to edit the profile.'));
            return $this->redirect(['index']);
        }

        $avatar = $model->avatar;
        if ($model->load(Yii::$app->request->post()) && ($model->scenario === $model::SCENARIO_AVATAR_UPDATE)) {
            if ($model->isDel) {
                if ($avatar) {
                    $upload = Yii::$app->getModule('users')->uploads;
                    $path = str_replace('\\', '/', Url::to('@upload') . DIRECTORY_SEPARATOR . $upload . DIRECTORY_SEPARATOR . $model->id);
                    $avatar = $path . '/' . $avatar;
                    if (file_exists($avatar))
                        unlink($avatar);
                    $model->avatar = null;
                    $model->save();
                }
            }
            $uploadModel = new UploadForm();
            if ($uploadModel->imageFile = UploadedFile::getInstance($model, 'imageFile'))
                $uploadModel->upload($model->id);
        }
        return $this->redirect(['update', 'id' => $model->id, 'tab' => 'avatar']);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        // Нельзя удалить профиль администратора
        if (($model->getUserRoleValue($model->id) == BackendRbac::ROLE_ADMINISTRATOR)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You can not remove the Administrator profile.'));
            return $this->redirect(['index']);
        }
        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $this->layout = '//login';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // Если запрещен доступ к Backend сбрасываем авторизацию записываем сообщение в сессию
            // и перебрасываем на страницу входа
            if (!Yii::$app->user->can(BackendRbac::PERMISSION_BACKEND)) {
                Yii::$app->user->logout();
                Yii::$app->session->setFlash('error', Module::t('backend', 'MSG_YOU_NOT_ALLOWED'));
                return $this->goHome();
            }
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}